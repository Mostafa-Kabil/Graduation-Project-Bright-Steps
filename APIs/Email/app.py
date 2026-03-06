from fastapi import FastAPI, HTTPException
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel
from typing import Optional
import mysql.connector
import smtplib
import os
from email.mime.text import MIMEText
from email.mime.multipart import MIMEMultipart
from datetime import datetime

# ── SMTP Config (Gmail default – set via env vars) ─────────────────────
SMTP_HOST = os.getenv("SMTP_HOST", "smtp.gmail.com")
SMTP_PORT = int(os.getenv("SMTP_PORT", "587"))
SMTP_EMAIL = os.getenv("SMTP_EMAIL", "your-email@gmail.com")
SMTP_PASSWORD = os.getenv("SMTP_PASSWORD", "your-app-password")
FROM_NAME = "Bright Steps"

app = FastAPI(
    title="Bright Steps Email API",
    description="Email sending API with branded templates for Bright Steps",
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

def log_email(recipient: str, subject: str, template_type: str, status: str, error: str = None):
    """Log email to the database."""
    try:
        db = get_db()
        cursor = db.cursor()
        cursor.execute(
            """INSERT INTO email_logs (recipient_email, subject, template_type, status, error_message)
               VALUES (%s, %s, %s, %s, %s)""",
            (recipient, subject, template_type, status, error)
        )
        db.commit()
        cursor.close()
        db.close()
    except Exception:
        pass  # Don't fail if logging fails

# ── Email template base ───────────────────────────────────────────────

def wrap_template(content: str) -> str:
    """Wrap email content in the Bright Steps branded HTML template."""
    return f"""
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
    </head>
    <body style="margin:0; padding:0; background-color:#0a0a1a; font-family:'Segoe UI',Arial,sans-serif;">
        <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#0a0a1a; padding:40px 20px;">
            <tr>
                <td align="center">
                    <table width="600" cellpadding="0" cellspacing="0" style="background:linear-gradient(135deg,#111122,#1a1a2e); border-radius:16px; overflow:hidden; border:1px solid rgba(255,255,255,0.08);">
                        <!-- Header -->
                        <tr>
                            <td style="background:linear-gradient(135deg,#6366f1,#a855f7); padding:30px 40px; text-align:center;">
                                <h1 style="margin:0; color:#fff; font-size:24px; font-weight:700;">✨ Bright Steps</h1>
                                <p style="margin:5px 0 0; color:rgba(255,255,255,0.85); font-size:14px;">AI-Powered Child Development</p>
                            </td>
                        </tr>
                        <!-- Body -->
                        <tr>
                            <td style="padding:40px;">
                                {content}
                            </td>
                        </tr>
                        <!-- Footer -->
                        <tr>
                            <td style="padding:20px 40px; border-top:1px solid rgba(255,255,255,0.08); text-align:center;">
                                <p style="margin:0; color:#64748b; font-size:12px;">
                                    &copy; {datetime.now().year} Bright Steps. All rights reserved.<br>
                                    <a href="#" style="color:#6366f1; text-decoration:none;">Unsubscribe</a> &middot;
                                    <a href="#" style="color:#6366f1; text-decoration:none;">Privacy Policy</a>
                                </p>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </body>
    </html>
    """

def send_email(to: str, subject: str, html_body: str) -> bool:
    """Send an email via SMTP."""
    msg = MIMEMultipart("alternative")
    msg["From"] = f"{FROM_NAME} <{SMTP_EMAIL}>"
    msg["To"] = to
    msg["Subject"] = subject
    msg.attach(MIMEText(html_body, "html"))

    try:
        with smtplib.SMTP(SMTP_HOST, SMTP_PORT) as server:
            server.starttls()
            server.login(SMTP_EMAIL, SMTP_PASSWORD)
            server.send_message(msg)
        return True
    except Exception as e:
        raise e


# ── Models ─────────────────────────────────────────────────────────────

class GenericEmailRequest(BaseModel):
    to: str
    subject: str
    body_html: str

class WelcomeEmailRequest(BaseModel):
    to: str
    first_name: str

class PaymentConfirmationRequest(BaseModel):
    to: str
    first_name: str
    plan_name: str
    amount: float
    payment_id: Optional[int] = None

class AppointmentReminderRequest(BaseModel):
    to: str
    first_name: str
    doctor_name: str
    clinic_name: str
    appointment_date: str
    appointment_type: Optional[str] = "onsite"

class PasswordResetRequest(BaseModel):
    to: str
    first_name: str
    reset_link: str


# ── Routes ─────────────────────────────────────────────────────────────

@app.get("/")
def root():
    return {
        "message": "Bright Steps Email API is running!",
        "endpoints": {
            "POST /send": "Send generic email",
            "POST /send/welcome": "Welcome email",
            "POST /send/payment-confirmation": "Payment receipt",
            "POST /send/appointment-reminder": "Appointment reminder",
            "POST /send/password-reset": "Password reset link",
        }
    }


@app.post("/send")
def send_generic(req: GenericEmailRequest):
    """Send a generic email with custom HTML body."""
    try:
        html = wrap_template(req.body_html)
        send_email(req.to, req.subject, html)
        log_email(req.to, req.subject, "generic", "sent")
        return {"success": True, "message": f"Email sent to {req.to}"}
    except Exception as e:
        log_email(req.to, req.subject, "generic", "failed", str(e))
        raise HTTPException(status_code=500, detail=f"Failed to send email: {str(e)}")


@app.post("/send/welcome")
def send_welcome(req: WelcomeEmailRequest):
    """Send a branded welcome email to a new user."""
    subject = "Welcome to Bright Steps! 🎉"
    content = f"""
    <h2 style="color:#e2e8f0; margin:0 0 15px;">Welcome, {req.first_name}! 👋</h2>
    <p style="color:#94a3b8; font-size:15px; line-height:1.7;">
        We're thrilled to have you on board! Bright Steps is here to help you
        monitor and support your child's development journey with AI-powered tools.
    </p>
    <h3 style="color:#c4b5fd; margin:25px 0 10px;">Here's what you can do:</h3>
    <ul style="color:#94a3b8; font-size:14px; line-height:2; padding-left:20px;">
        <li>📊 Track growth with WHO-standard comparisons</li>
        <li>🎯 Monitor developmental milestones</li>
        <li>🗣️ Analyze speech &amp; language development</li>
        <li>🏆 Earn fun badges with the gamification system</li>
        <li>👨‍⚕️ Connect with specialized clinics</li>
    </ul>
    <div style="text-align:center; margin:30px 0;">
        <a href="http://localhost/Bright%20Steps%20Website/dashboard.php"
           style="display:inline-block; padding:14px 36px; background:linear-gradient(135deg,#6366f1,#a855f7);
                  color:#fff; text-decoration:none; border-radius:10px; font-weight:700; font-size:15px;">
            Go to Your Dashboard →
        </a>
    </div>
    <p style="color:#64748b; font-size:13px;">
        If you have any questions, visit our <a href="#" style="color:#6366f1;">Help Center</a>
        or reply to this email.
    </p>
    """

    try:
        html = wrap_template(content)
        send_email(req.to, subject, html)
        log_email(req.to, subject, "welcome", "sent")
        return {"success": True, "message": f"Welcome email sent to {req.to}"}
    except Exception as e:
        log_email(req.to, subject, "welcome", "failed", str(e))
        raise HTTPException(status_code=500, detail=f"Failed to send email: {str(e)}")


@app.post("/send/payment-confirmation")
def send_payment_confirmation(req: PaymentConfirmationRequest):
    """Send a payment receipt email."""
    subject = "Payment Confirmed – Bright Steps Premium 🎉"
    content = f"""
    <h2 style="color:#e2e8f0; margin:0 0 15px;">Payment Successful! ✅</h2>
    <p style="color:#94a3b8; font-size:15px; line-height:1.7;">
        Hi {req.first_name}, your payment has been processed successfully.
    </p>
    <table width="100%" cellpadding="0" cellspacing="0" style="margin:25px 0; border:1px solid rgba(255,255,255,0.1); border-radius:10px; overflow:hidden;">
        <tr style="background:rgba(99,102,241,0.1);">
            <td style="padding:15px 20px; color:#c4b5fd; font-weight:600;">Plan</td>
            <td style="padding:15px 20px; color:#e2e8f0; text-align:right;">{req.plan_name}</td>
        </tr>
        <tr>
            <td style="padding:15px 20px; color:#c4b5fd; font-weight:600; border-top:1px solid rgba(255,255,255,0.05);">Amount</td>
            <td style="padding:15px 20px; color:#22c55e; text-align:right; font-weight:700; font-size:18px; border-top:1px solid rgba(255,255,255,0.05);">${req.amount:.2f}</td>
        </tr>
        <tr>
            <td style="padding:15px 20px; color:#c4b5fd; font-weight:600; border-top:1px solid rgba(255,255,255,0.05);">Date</td>
            <td style="padding:15px 20px; color:#e2e8f0; text-align:right; border-top:1px solid rgba(255,255,255,0.05);">{datetime.now().strftime('%B %d, %Y')}</td>
        </tr>
        {f'<tr><td style="padding:15px 20px; color:#c4b5fd; font-weight:600; border-top:1px solid rgba(255,255,255,0.05);">Payment ID</td><td style="padding:15px 20px; color:#e2e8f0; text-align:right; border-top:1px solid rgba(255,255,255,0.05);">#{req.payment_id}</td></tr>' if req.payment_id else ''}
    </table>
    <p style="color:#94a3b8; font-size:14px;">
        You now have access to all Premium features. Enjoy! 🚀
    </p>
    """

    try:
        html = wrap_template(content)
        send_email(req.to, subject, html)
        log_email(req.to, subject, "payment_confirmation", "sent")
        return {"success": True, "message": f"Payment confirmation sent to {req.to}"}
    except Exception as e:
        log_email(req.to, subject, "payment_confirmation", "failed", str(e))
        raise HTTPException(status_code=500, detail=f"Failed to send email: {str(e)}")


@app.post("/send/appointment-reminder")
def send_appointment_reminder(req: AppointmentReminderRequest):
    """Send an appointment reminder email."""
    subject = f"Appointment Reminder – {req.appointment_date}"
    type_icon = "🏥" if req.appointment_type == "onsite" else "💻"
    type_label = "In-Person" if req.appointment_type == "onsite" else "Online"

    content = f"""
    <h2 style="color:#e2e8f0; margin:0 0 15px;">Upcoming Appointment 📅</h2>
    <p style="color:#94a3b8; font-size:15px; line-height:1.7;">
        Hi {req.first_name}, this is a reminder about your upcoming appointment.
    </p>
    <div style="background:rgba(99,102,241,0.08); border:1px solid rgba(99,102,241,0.15); border-radius:12px; padding:25px; margin:20px 0;">
        <p style="color:#e2e8f0; margin:0 0 10px; font-size:14px;">
            <strong style="color:#c4b5fd;">Doctor:</strong> Dr. {req.doctor_name}
        </p>
        <p style="color:#e2e8f0; margin:0 0 10px; font-size:14px;">
            <strong style="color:#c4b5fd;">Clinic:</strong> {req.clinic_name}
        </p>
        <p style="color:#e2e8f0; margin:0 0 10px; font-size:14px;">
            <strong style="color:#c4b5fd;">Date:</strong> {req.appointment_date}
        </p>
        <p style="color:#e2e8f0; margin:0; font-size:14px;">
            <strong style="color:#c4b5fd;">Type:</strong> {type_icon} {type_label}
        </p>
    </div>
    <p style="color:#64748b; font-size:13px;">
        Please arrive 10 minutes early for in-person appointments.
    </p>
    """

    try:
        html = wrap_template(content)
        send_email(req.to, subject, html)
        log_email(req.to, subject, "appointment_reminder", "sent")
        return {"success": True, "message": f"Reminder sent to {req.to}"}
    except Exception as e:
        log_email(req.to, subject, "appointment_reminder", "failed", str(e))
        raise HTTPException(status_code=500, detail=f"Failed to send email: {str(e)}")


@app.post("/send/password-reset")
def send_password_reset(req: PasswordResetRequest):
    """Send a password reset link email."""
    subject = "Reset Your Password – Bright Steps"
    content = f"""
    <h2 style="color:#e2e8f0; margin:0 0 15px;">Password Reset Request 🔐</h2>
    <p style="color:#94a3b8; font-size:15px; line-height:1.7;">
        Hi {req.first_name}, we received a request to reset your password.
        Click the button below to create a new password.
    </p>
    <div style="text-align:center; margin:30px 0;">
        <a href="{req.reset_link}"
           style="display:inline-block; padding:14px 36px; background:linear-gradient(135deg,#6366f1,#a855f7);
                  color:#fff; text-decoration:none; border-radius:10px; font-weight:700; font-size:15px;">
            Reset Password
        </a>
    </div>
    <p style="color:#64748b; font-size:13px;">
        This link expires in 1 hour. If you didn't request this, you can safely ignore this email.
    </p>
    """

    try:
        html = wrap_template(content)
        send_email(req.to, subject, html)
        log_email(req.to, subject, "password_reset", "sent")
        return {"success": True, "message": f"Reset email sent to {req.to}"}
    except Exception as e:
        log_email(req.to, subject, "password_reset", "failed", str(e))
        raise HTTPException(status_code=500, detail=f"Failed to send email: {str(e)}")
