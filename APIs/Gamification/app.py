from fastapi import FastAPI, HTTPException, Depends, Header
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel
from typing import Optional, List
import mysql.connector
import jwt
from datetime import datetime, date, timedelta

# ── Config ─────────────────────────────────────────────────────────────
SECRET_KEY = "bright-steps-jwt-secret-change-in-production"
ALGORITHM = "HS256"

app = FastAPI(
    title="Bright Steps Gamification API",
    description="Badges, points, streaks, and leaderboard for child engagement",
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
        host="localhost",
        user="root",
        password="",
        database="grad",
        charset="utf8mb4"
    )

# ── Auth helper ────────────────────────────────────────────────────────
def get_current_user(authorization: Optional[str] = Header(None)):
    if not authorization or not authorization.startswith("Bearer "):
        raise HTTPException(status_code=401, detail="Missing or invalid Authorization header")
    token = authorization.split(" ")[1]
    try:
        payload = jwt.decode(token, SECRET_KEY, algorithms=[ALGORITHM])
        if payload.get("type") != "access":
            raise HTTPException(status_code=401, detail="Invalid token type")
        return payload
    except jwt.ExpiredSignatureError:
        raise HTTPException(status_code=401, detail="Token has expired")
    except jwt.InvalidTokenError:
        raise HTTPException(status_code=401, detail="Invalid token")


# ── Models ─────────────────────────────────────────────────────────────

class AwardBadgeRequest(BaseModel):
    child_id: int
    badge_id: int

class AwardPointsRequest(BaseModel):
    child_id: int
    refrence_id: int  # references points_refrence table

class CreateBadgeRequest(BaseModel):
    name: str
    description: str
    icon: Optional[str] = "🏅"

class UpdateStreakRequest(BaseModel):
    child_id: int
    streak_type: str  # growth_tracking, milestone_logging, daily_login


# ── Routes ─────────────────────────────────────────────────────────────

@app.get("/")
def root():
    return {
        "message": "Bright Steps Gamification API is running!",
        "endpoints": {
            "GET  /badges":                    "List all badges",
            "POST /badges":                    "Create a new badge",
            "GET  /badges/child/{child_id}":   "Child's earned badges",
            "POST /badges/award":              "Award a badge",
            "GET  /points/{child_id}":         "Point balance",
            "GET  /points/{child_id}/history": "Transaction history",
            "POST /points/award":              "Award points",
            "GET  /leaderboard":               "Top children by points",
            "GET  /streaks/{child_id}":        "Get tracking streaks",
            "POST /streaks/update":            "Update a streak",
            "GET  /dashboard/{child_id}":      "Full gamification dashboard",
        }
    }


# ── Badges ─────────────────────────────────────────────────────────────

@app.get("/badges")
def list_badges():
    """List all available badges."""
    db = get_db()
    cursor = db.cursor(dictionary=True)

    try:
        cursor.execute("SELECT * FROM badge ORDER BY badge_id")
        badges = cursor.fetchall()
        return {"count": len(badges), "badges": badges}
    finally:
        cursor.close()
        db.close()


@app.post("/badges")
def create_badge(req: CreateBadgeRequest, user: dict = Depends(get_current_user)):
    """Create a new badge (admin)."""
    db = get_db()
    cursor = db.cursor()

    try:
        cursor.execute(
            "INSERT INTO badge (name, description, icon) VALUES (%s, %s, %s)",
            (req.name, req.description, req.icon)
        )
        db.commit()
        return {
            "success": True,
            "badge_id": cursor.lastrowid,
            "message": f"Badge '{req.name}' created",
        }
    finally:
        cursor.close()
        db.close()


@app.get("/badges/child/{child_id}")
def get_child_badges(child_id: int, user: dict = Depends(get_current_user)):
    """Get all badges earned by a child."""
    db = get_db()
    cursor = db.cursor(dictionary=True)

    try:
        cursor.execute(
            """SELECT b.badge_id, b.name, b.description, b.icon, cb.redeemed_at
               FROM child_badge cb
               INNER JOIN badge b ON cb.badge_id = b.badge_id
               WHERE cb.child_id = %s
               ORDER BY cb.redeemed_at DESC""",
            (child_id,)
        )
        badges = cursor.fetchall()

        for b in badges:
            if isinstance(b.get("redeemed_at"), datetime):
                b["redeemed_at"] = b["redeemed_at"].isoformat()

        # Get total available badges
        cursor.execute("SELECT COUNT(*) as total FROM badge")
        total = cursor.fetchone()["total"]

        return {
            "child_id": child_id,
            "earned_count": len(badges),
            "total_available": total,
            "completion": round(len(badges) / total * 100, 1) if total > 0 else 0,
            "badges": badges,
        }
    finally:
        cursor.close()
        db.close()


@app.post("/badges/award")
def award_badge(req: AwardBadgeRequest, user: dict = Depends(get_current_user)):
    """Award a badge to a child."""
    db = get_db()
    cursor = db.cursor(dictionary=True)

    try:
        # Verify child exists
        cursor.execute("SELECT child_id FROM child WHERE child_id = %s", (req.child_id,))
        if not cursor.fetchone():
            raise HTTPException(status_code=404, detail="Child not found")

        # Verify badge exists
        cursor.execute("SELECT name, icon FROM badge WHERE badge_id = %s", (req.badge_id,))
        badge = cursor.fetchone()
        if not badge:
            raise HTTPException(status_code=404, detail="Badge not found")

        # Check if already awarded
        cursor.execute(
            "SELECT child_id FROM child_badge WHERE child_id = %s AND badge_id = %s",
            (req.child_id, req.badge_id)
        )
        if cursor.fetchone():
            return {"success": False, "message": "Badge already earned"}

        cursor.execute(
            "INSERT INTO child_badge (child_id, badge_id) VALUES (%s, %s)",
            (req.child_id, req.badge_id)
        )
        db.commit()

        return {
            "success": True,
            "message": f"Badge '{badge['name']}' {badge['icon']} awarded! 🎉",
            "badge_name": badge["name"],
        }
    finally:
        cursor.close()
        db.close()


# ── Points ─────────────────────────────────────────────────────────────

@app.get("/points/{child_id}")
def get_points(child_id: int, user: dict = Depends(get_current_user)):
    """Get point balance for a child."""
    db = get_db()
    cursor = db.cursor(dictionary=True)

    try:
        cursor.execute(
            "SELECT wallet_id, total_points FROM points_wallet WHERE child_id = %s",
            (child_id,)
        )
        wallet = cursor.fetchone()
        if not wallet:
            raise HTTPException(status_code=404, detail="Points wallet not found")

        return {
            "child_id": child_id,
            "wallet_id": wallet["wallet_id"],
            "total_points": wallet["total_points"],
        }
    finally:
        cursor.close()
        db.close()


@app.get("/points/{child_id}/history")
def get_point_history(child_id: int, limit: int = 50, user: dict = Depends(get_current_user)):
    """Get point transaction history."""
    db = get_db()
    cursor = db.cursor(dictionary=True)

    try:
        # Get wallet id
        cursor.execute("SELECT wallet_id FROM points_wallet WHERE child_id = %s", (child_id,))
        wallet = cursor.fetchone()
        if not wallet:
            raise HTTPException(status_code=404, detail="Points wallet not found")

        cursor.execute(
            """SELECT pt.transaction_id, pt.points_change, pt.transaction_type, pt.created_at,
                      pr.action_name, pr.points_value
               FROM points_transaction pt
               INNER JOIN points_refrence pr ON pt.refrence_id = pr.refrence_id
               WHERE pt.wallet_id = %s
               ORDER BY pt.created_at DESC LIMIT %s""",
            (wallet["wallet_id"], limit)
        )
        transactions = cursor.fetchall()

        for t in transactions:
            if isinstance(t.get("created_at"), datetime):
                t["created_at"] = t["created_at"].isoformat()

        return {
            "child_id": child_id,
            "count": len(transactions),
            "transactions": transactions,
        }
    finally:
        cursor.close()
        db.close()


@app.post("/points/award")
def award_points(req: AwardPointsRequest, user: dict = Depends(get_current_user)):
    """Award points to a child based on a points reference action."""
    db = get_db()
    cursor = db.cursor(dictionary=True)

    try:
        # Get wallet
        cursor.execute("SELECT wallet_id FROM points_wallet WHERE child_id = %s", (req.child_id,))
        wallet = cursor.fetchone()
        if not wallet:
            raise HTTPException(status_code=404, detail="Points wallet not found")

        # Get reference
        cursor.execute(
            "SELECT action_name, points_value, adjust_sign FROM points_refrence WHERE refrence_id = %s",
            (req.refrence_id,)
        )
        ref = cursor.fetchone()
        if not ref:
            raise HTTPException(status_code=404, detail="Points reference not found")

        # Insert transaction (trigger handles points_change and wallet update)
        cursor.execute(
            "INSERT INTO points_transaction (refrence_id, wallet_id) VALUES (%s, %s)",
            (req.refrence_id, wallet["wallet_id"])
        )
        db.commit()

        sign = "+" if ref["adjust_sign"] == "+" else "-"
        return {
            "success": True,
            "message": f"{sign}{ref['points_value']} points for '{ref['action_name']}'",
            "action": ref["action_name"],
            "points_change": ref["points_value"] if ref["adjust_sign"] == "+" else -ref["points_value"],
        }
    finally:
        cursor.close()
        db.close()


# ── Leaderboard ────────────────────────────────────────────────────────

@app.get("/leaderboard")
def get_leaderboard(limit: int = 10):
    """Get the top children by points (anonymized first names only)."""
    db = get_db()
    cursor = db.cursor(dictionary=True)

    try:
        cursor.execute(
            """SELECT c.child_id, c.first_name, pw.total_points,
                      (SELECT COUNT(*) FROM child_badge cb WHERE cb.child_id = c.child_id) as badge_count
               FROM points_wallet pw
               INNER JOIN child c ON pw.child_id = c.child_id
               WHERE pw.total_points > 0
               ORDER BY pw.total_points DESC LIMIT %s""",
            (limit,)
        )
        leaderboard = cursor.fetchall()

        for i, entry in enumerate(leaderboard):
            entry["rank"] = i + 1
            # Anonymize: show first name + first letter of last initial
            entry["display_name"] = f"{entry['first_name']}"
            del entry["child_id"]  # Remove ID for privacy

        return {"count": len(leaderboard), "leaderboard": leaderboard}
    finally:
        cursor.close()
        db.close()


# ── Streaks ────────────────────────────────────────────────────────────

@app.get("/streaks/{child_id}")
def get_streaks(child_id: int, user: dict = Depends(get_current_user)):
    """Get all streaks for a child."""
    db = get_db()
    cursor = db.cursor(dictionary=True)

    try:
        cursor.execute(
            """SELECT streak_type, current_count, longest_count, last_activity_date
               FROM streaks WHERE child_id = %s""",
            (child_id,)
        )
        streaks = cursor.fetchall()

        for s in streaks:
            if isinstance(s.get("last_activity_date"), date):
                s["last_activity_date"] = s["last_activity_date"].isoformat()

            # Check if streak is still active (last activity was today or yesterday)
            if s.get("last_activity_date"):
                last = datetime.strptime(s["last_activity_date"], "%Y-%m-%d").date() if isinstance(s["last_activity_date"], str) else s["last_activity_date"]
                days_since = (date.today() - last).days
                s["is_active"] = days_since <= 1
                s["days_since_last"] = days_since
            else:
                s["is_active"] = False
                s["days_since_last"] = None

        # Add display info
        streak_display = {
            "growth_tracking": {"icon": "📊", "display": "Growth Tracking"},
            "milestone_logging": {"icon": "⭐", "display": "Milestone Logging"},
            "daily_login": {"icon": "🔥", "display": "Daily Login"},
        }

        for s in streaks:
            info = streak_display.get(s["streak_type"], {"icon": "📌", "display": s["streak_type"]})
            s["icon"] = info["icon"]
            s["display_name"] = info["display"]

        return {"child_id": child_id, "streaks": streaks}
    finally:
        cursor.close()
        db.close()


@app.post("/streaks/update")
def update_streak(req: UpdateStreakRequest, user: dict = Depends(get_current_user)):
    """Update a streak (call when the child performs the streak action)."""
    valid_types = ["growth_tracking", "milestone_logging", "daily_login"]
    if req.streak_type not in valid_types:
        raise HTTPException(status_code=400, detail=f"streak_type must be one of: {valid_types}")

    db = get_db()
    cursor = db.cursor(dictionary=True)

    try:
        today = date.today()

        # Check existing streak
        cursor.execute(
            "SELECT * FROM streaks WHERE child_id = %s AND streak_type = %s",
            (req.child_id, req.streak_type)
        )
        streak = cursor.fetchone()

        if streak:
            last_date = streak["last_activity_date"]
            if last_date == today:
                return {"success": True, "message": "Already logged today", "current_count": streak["current_count"]}

            if last_date and (today - last_date).days == 1:
                # Continue streak
                new_count = streak["current_count"] + 1
                new_longest = max(streak["longest_count"], new_count)
            else:
                # Streak broken, restart
                new_count = 1
                new_longest = streak["longest_count"]

            cursor.execute(
                """UPDATE streaks SET current_count = %s, longest_count = %s, last_activity_date = %s
                   WHERE child_id = %s AND streak_type = %s""",
                (new_count, new_longest, today, req.child_id, req.streak_type)
            )
        else:
            # First time
            new_count = 1
            cursor.execute(
                """INSERT INTO streaks (child_id, streak_type, current_count, longest_count, last_activity_date)
                   VALUES (%s, %s, 1, 1, %s)""",
                (req.child_id, req.streak_type, today)
            )

        db.commit()

        message = f"🔥 {new_count}-day streak!" if new_count > 1 else "Streak started! 🎯"

        return {
            "success": True,
            "current_count": new_count,
            "message": message,
        }
    finally:
        cursor.close()
        db.close()


# ── Dashboard ──────────────────────────────────────────────────────────

@app.get("/dashboard/{child_id}")
def get_dashboard(child_id: int, user: dict = Depends(get_current_user)):
    """Get complete gamification dashboard for a child."""
    db = get_db()
    cursor = db.cursor(dictionary=True)

    try:
        # Points
        cursor.execute("SELECT total_points FROM points_wallet WHERE child_id = %s", (child_id,))
        wallet = cursor.fetchone()
        total_points = wallet["total_points"] if wallet else 0

        # Badges
        cursor.execute(
            """SELECT b.name, b.icon FROM child_badge cb
               INNER JOIN badge b ON cb.badge_id = b.badge_id
               WHERE cb.child_id = %s ORDER BY cb.redeemed_at DESC LIMIT 5""",
            (child_id,)
        )
        recent_badges = cursor.fetchall()

        cursor.execute("SELECT COUNT(*) as count FROM child_badge WHERE child_id = %s", (child_id,))
        badge_count = cursor.fetchone()["count"]

        cursor.execute("SELECT COUNT(*) as total FROM badge")
        total_badges = cursor.fetchone()["total"]

        # Streaks
        cursor.execute("SELECT streak_type, current_count, longest_count FROM streaks WHERE child_id = %s", (child_id,))
        streaks = cursor.fetchall()

        # Recent transactions
        cursor.execute(
            """SELECT pt.points_change, pt.created_at, pr.action_name
               FROM points_transaction pt
               INNER JOIN points_refrence pr ON pt.refrence_id = pr.refrence_id
               INNER JOIN points_wallet pw ON pt.wallet_id = pw.wallet_id
               WHERE pw.child_id = %s
               ORDER BY pt.created_at DESC LIMIT 5""",
            (child_id,)
        )
        recent_activity = cursor.fetchall()
        for a in recent_activity:
            if isinstance(a.get("created_at"), datetime):
                a["created_at"] = a["created_at"].isoformat()

        return {
            "child_id": child_id,
            "points": total_points,
            "badges": {
                "earned": badge_count,
                "total": total_badges,
                "recent": recent_badges,
            },
            "streaks": streaks,
            "recent_activity": recent_activity,
        }
    finally:
        cursor.close()
        db.close()
