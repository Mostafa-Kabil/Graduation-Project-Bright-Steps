from fastapi import FastAPI, HTTPException, Request
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel
from typing import Optional
import mysql.connector
import os
from datetime import datetime
from dotenv import load_dotenv

load_dotenv(os.path.join(os.path.dirname(__file__), '..', '..', '.env'))

# ── Stripe (test mode – set keys in .env) ──────────────────
import stripe
stripe.api_key = os.getenv("STRIPE_SECRET_KEY", "sk_test_REPLACE_WITH_YOUR_SECRET_KEY")
STRIPE_PUBLISHABLE_KEY = os.getenv("STRIPE_PUBLISHABLE_KEY", "pk_test_REPLACE_WITH_YOUR_PUBLISHABLE_KEY")

app = FastAPI(
    title="Bright Steps Payment API",
    description="Payment processing API with Stripe integration for Bright Steps",
    version="1.0.0"
)

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

# ── DB helper ──────────────────────────────────────────────────────────
def get_db():
    return mysql.connector.connect(
        host=os.getenv("DB_HOST", "localhost"),
        user=os.getenv("DB_USER", "root"),
        password=os.getenv("DB_PASSWORD", ""),
        database=os.getenv("DB_NAME", "grad"),
        charset="utf8mb4"
    )

# ── Models ─────────────────────────────────────────────────────────────

class CheckoutRequest(BaseModel):
    subscription_id: int
    parent_id: int

class ConfirmPaymentRequest(BaseModel):
    payment_intent_id: str
    parent_id: int
    subscription_id: int
    child_name: Optional[str] = ""

# ── Routes ─────────────────────────────────────────────────────────────

@app.get("/")
def root():
    return {
        "message": "Bright Steps Payment API is running!",
        "endpoints": {
            "GET  /config": "Get Stripe publishable key",
            "POST /create-checkout": "Create a Stripe PaymentIntent",
            "POST /confirm-payment": "Record confirmed payment in DB",
            "GET  /payment-history/{parent_id}": "Get payment history",
        }
    }


@app.get("/config")
def get_config():
    """Return the Stripe publishable key for frontend use."""
    return {"publishable_key": STRIPE_PUBLISHABLE_KEY}


@app.post("/create-checkout")
def create_checkout(req: CheckoutRequest):
    """Create a Stripe PaymentIntent for the given subscription."""
    db = get_db()
    cursor = db.cursor(dictionary=True)

    try:
        # Look up subscription price
        cursor.execute(
            "SELECT plan_name, price, plan_period FROM subscription WHERE subscription_id = %s",
            (req.subscription_id,)
        )
        subscription = cursor.fetchone()
        if not subscription:
            raise HTTPException(status_code=404, detail="Subscription plan not found")

        amount_cents = int(float(subscription["price"]) * 100)
        if amount_cents <= 0:
            raise HTTPException(status_code=400, detail="Free plans do not require payment")

        intent = stripe.PaymentIntent.create(
            amount=amount_cents,
            currency="usd",
            metadata={
                "parent_id": str(req.parent_id),
                "subscription_id": str(req.subscription_id),
                "plan_name": subscription["plan_name"],
            }
        )

        return {
            "client_secret": intent.client_secret,
            "payment_intent_id": intent.id,
            "amount": float(subscription["price"]),
            "plan_name": subscription["plan_name"],
            "plan_period": subscription["plan_period"],
        }

    finally:
        cursor.close()
        db.close()


@app.post("/confirm-payment")
def confirm_payment(req: ConfirmPaymentRequest):
    """Record a confirmed payment in the database."""
    db = get_db()
    cursor = db.cursor(dictionary=True)

    try:
        # Verify payment with Stripe
        intent = stripe.PaymentIntent.retrieve(req.payment_intent_id)
        if intent.status != "succeeded":
            raise HTTPException(status_code=400, detail=f"Payment not succeeded. Status: {intent.status}")

        # Get subscription info
        cursor.execute(
            "SELECT plan_name, price FROM subscription WHERE subscription_id = %s",
            (req.subscription_id,)
        )
        sub = cursor.fetchone()
        if not sub:
            raise HTTPException(status_code=404, detail="Subscription not found")

        amount = float(sub["price"])

        # Insert into payment table
        cursor.execute(
            """INSERT INTO payment (subscription_id, amount_pre_discount, discount_rate, method, status)
               VALUES (%s, %s, %s, %s, %s)""",
            (req.subscription_id, amount, 0.00, "stripe", "completed")
        )
        payment_id = cursor.lastrowid

        # Insert/update parent_subscription
        child_name = req.child_name or "All Children"
        cursor.execute(
            """INSERT INTO parent_subscription (parent_id, subscription_id, child_name)
               VALUES (%s, %s, %s)
               ON DUPLICATE KEY UPDATE subscription_id = VALUES(subscription_id)""",
            (req.parent_id, req.subscription_id, child_name)
        )

        db.commit()

        return {
            "success": True,
            "payment_id": payment_id,
            "message": f"Payment recorded. You are now on the {sub['plan_name']} plan!",
        }

    except stripe.error.StripeError as e:
        raise HTTPException(status_code=400, detail=str(e))
    finally:
        cursor.close()
        db.close()


@app.get("/payment-history/{parent_id}")
def payment_history(parent_id: int):
    """Return payment history for a parent."""
    db = get_db()
    cursor = db.cursor(dictionary=True)

    try:
        cursor.execute(
            """SELECT p.payment_id, p.amount_pre_discount, p.amount_post_discount,
                      p.discount_rate, p.method, p.status, p.paid_at,
                      s.plan_name, s.plan_period
               FROM payment p
               INNER JOIN subscription s ON p.subscription_id = s.subscription_id
               INNER JOIN parent_subscription ps ON p.subscription_id = ps.subscription_id
               WHERE ps.parent_id = %s
               ORDER BY p.paid_at DESC""",
            (parent_id,)
        )
        payments = cursor.fetchall()

        # Convert datetime/decimal to serializable types
        for p in payments:
            if isinstance(p.get("paid_at"), datetime):
                p["paid_at"] = p["paid_at"].isoformat()
            for key in ("amount_pre_discount", "amount_post_discount", "discount_rate"):
                if p.get(key) is not None:
                    p[key] = float(p[key])

        return {"parent_id": parent_id, "payments": payments}
    finally:
        cursor.close()
        db.close()


@app.post("/webhook")
async def stripe_webhook(request: Request):
    """Handle Stripe webhook events (optional for production)."""
    payload = await request.body()
    event = None

    try:
        event = stripe.Event.construct_from(
            stripe.util.json.loads(payload), stripe.api_key
        )
    except ValueError:
        raise HTTPException(status_code=400, detail="Invalid payload")

    if event.type == "payment_intent.succeeded":
        intent = event.data.object
        return {"status": "success", "payment_intent": intent.id}

    return {"status": "received", "type": event.type}
