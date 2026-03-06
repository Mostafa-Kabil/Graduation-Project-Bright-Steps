from fastapi import FastAPI, HTTPException, Depends, Header
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel
from typing import Optional, List
import mysql.connector
import jwt
import uuid
import os
from datetime import datetime, timedelta
from dotenv import load_dotenv

load_dotenv(os.path.join(os.path.dirname(__file__), '..', '..', '.env'))
import uuid
from datetime import datetime, timedelta

# ── Config ─────────────────────────────────────────────────────────────
SECRET_KEY = os.getenv("JWT_SECRET", "bright-steps-jwt-secret-change-in-production")
ALGORITHM = os.getenv("JWT_ALGORITHM", "HS256")
SESSION_EXPIRE_DAYS = 30

app = FastAPI(
    title="Bright Steps Session Management API",
    description="Active session tracking, token revocation, and device management",
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


def parse_user_agent(ua: str) -> str:
    """Extract a friendly device name from User-Agent string."""
    if not ua:
        return "Unknown Device"
    ua_lower = ua.lower()
    if "mobile" in ua_lower or "android" in ua_lower:
        return "Mobile Device"
    elif "iphone" in ua_lower or "ipad" in ua_lower:
        return "Apple Device"
    elif "windows" in ua_lower:
        return "Windows PC"
    elif "mac" in ua_lower:
        return "Mac"
    elif "linux" in ua_lower:
        return "Linux PC"
    return "Unknown Device"


# ── Models ─────────────────────────────────────────────────────────────

class CreateSessionRequest(BaseModel):
    user_id: int
    token_jti: Optional[str] = None
    ip_address: Optional[str] = None
    user_agent: Optional[str] = None
    device_name: Optional[str] = None

class BlacklistTokenRequest(BaseModel):
    token_jti: str
    user_id: int
    expires_at: str  # ISO datetime


# ── Routes ─────────────────────────────────────────────────────────────

@app.get("/")
def root():
    return {
        "message": "Bright Steps Session Management API is running!",
        "endpoints": {
            "POST /sessions":                      "Create a new session",
            "GET  /sessions/{user_id}":             "List active sessions",
            "DELETE /sessions/{session_id}":        "Revoke a session",
            "DELETE /sessions/user/{user_id}/all":  "Revoke all sessions",
            "POST /blacklist":                      "Blacklist a JWT token",
            "GET  /blacklist/check/{jti}":          "Check if token is blacklisted",
            "PUT  /sessions/{session_id}/heartbeat":"Update last active time",
            "GET  /sessions/{user_id}/count":       "Get active session count",
        }
    }


@app.post("/sessions")
def create_session(req: CreateSessionRequest):
    """Create a new session record (called after successful login)."""
    session_id = str(uuid.uuid4())
    device_name = req.device_name or parse_user_agent(req.user_agent or "")
    expires_at = datetime.utcnow() + timedelta(days=SESSION_EXPIRE_DAYS)

    db = get_db()
    cursor = db.cursor()

    try:
        cursor.execute(
            """INSERT INTO user_sessions
               (session_id, user_id, token_jti, ip_address, user_agent, device_name, is_active, expires_at)
               VALUES (%s, %s, %s, %s, %s, %s, 1, %s)""",
            (session_id, req.user_id, req.token_jti, req.ip_address,
             req.user_agent, device_name, expires_at)
        )
        db.commit()

        return {
            "success": True,
            "session_id": session_id,
            "device_name": device_name,
            "expires_at": expires_at.isoformat(),
        }
    finally:
        cursor.close()
        db.close()


@app.get("/sessions/{user_id}")
def list_sessions(user_id: int, user: dict = Depends(get_current_user)):
    """List all active sessions for a user."""
    db = get_db()
    cursor = db.cursor(dictionary=True)

    try:
        cursor.execute(
            """SELECT session_id, ip_address, user_agent, device_name,
                      is_active, created_at, last_active_at, expires_at
               FROM user_sessions
               WHERE user_id = %s AND is_active = 1 AND expires_at > NOW()
               ORDER BY last_active_at DESC""",
            (user_id,)
        )
        sessions = cursor.fetchall()

        for s in sessions:
            for key in ("created_at", "last_active_at", "expires_at"):
                if isinstance(s.get(key), datetime):
                    s[key] = s[key].isoformat()

        return {
            "user_id": user_id,
            "active_sessions": len(sessions),
            "sessions": sessions,
        }
    finally:
        cursor.close()
        db.close()


@app.delete("/sessions/{session_id}")
def revoke_session(session_id: str, user: dict = Depends(get_current_user)):
    """Revoke (deactivate) a specific session."""
    db = get_db()
    cursor = db.cursor(dictionary=True)

    try:
        # Get session info for token blacklisting
        cursor.execute(
            "SELECT token_jti, user_id FROM user_sessions WHERE session_id = %s",
            (session_id,)
        )
        session = cursor.fetchone()

        if not session:
            raise HTTPException(status_code=404, detail="Session not found")

        # Deactivate session
        cursor.execute(
            "UPDATE user_sessions SET is_active = 0 WHERE session_id = %s",
            (session_id,)
        )

        # Blacklist the associated token if it has a JTI
        if session.get("token_jti"):
            cursor.execute(
                """INSERT IGNORE INTO token_blacklist (token_jti, user_id, expires_at)
                   VALUES (%s, %s, DATE_ADD(NOW(), INTERVAL 1 DAY))""",
                (session["token_jti"], session["user_id"])
            )

        db.commit()
        return {"success": True, "message": "Session revoked"}
    finally:
        cursor.close()
        db.close()


@app.delete("/sessions/user/{user_id}/all")
def revoke_all_sessions(user_id: int, user: dict = Depends(get_current_user)):
    """Revoke all sessions for a user (logout everywhere)."""
    db = get_db()
    cursor = db.cursor(dictionary=True)

    try:
        # Get all active token JTIs
        cursor.execute(
            "SELECT token_jti FROM user_sessions WHERE user_id = %s AND is_active = 1 AND token_jti IS NOT NULL",
            (user_id,)
        )
        tokens = cursor.fetchall()

        # Deactivate all sessions
        cursor.execute(
            "UPDATE user_sessions SET is_active = 0 WHERE user_id = %s",
            (user_id,)
        )
        revoked_count = cursor.rowcount

        # Blacklist all tokens
        for token in tokens:
            cursor.execute(
                """INSERT IGNORE INTO token_blacklist (token_jti, user_id, expires_at)
                   VALUES (%s, %s, DATE_ADD(NOW(), INTERVAL 1 DAY))""",
                (token["token_jti"], user_id)
            )

        db.commit()

        return {
            "success": True,
            "revoked_sessions": revoked_count,
            "blacklisted_tokens": len(tokens),
            "message": "All sessions revoked",
        }
    finally:
        cursor.close()
        db.close()


@app.post("/blacklist")
def blacklist_token(req: BlacklistTokenRequest):
    """Add a JWT token to the blacklist."""
    db = get_db()
    cursor = db.cursor()

    try:
        cursor.execute(
            """INSERT IGNORE INTO token_blacklist (token_jti, user_id, expires_at)
               VALUES (%s, %s, %s)""",
            (req.token_jti, req.user_id, req.expires_at)
        )
        db.commit()

        return {"success": True, "message": "Token blacklisted"}
    finally:
        cursor.close()
        db.close()


@app.get("/blacklist/check/{jti}")
def check_blacklist(jti: str):
    """Check if a JWT token is blacklisted."""
    db = get_db()
    cursor = db.cursor(dictionary=True)

    try:
        cursor.execute(
            "SELECT id FROM token_blacklist WHERE token_jti = %s AND expires_at > NOW()",
            (jti,)
        )
        is_blacklisted = cursor.fetchone() is not None
        return {"jti": jti, "blacklisted": is_blacklisted}
    finally:
        cursor.close()
        db.close()


@app.put("/sessions/{session_id}/heartbeat")
def heartbeat(session_id: str):
    """Update the last_active_at timestamp for a session."""
    db = get_db()
    cursor = db.cursor()

    try:
        cursor.execute(
            "UPDATE user_sessions SET last_active_at = NOW() WHERE session_id = %s AND is_active = 1",
            (session_id,)
        )
        db.commit()

        if cursor.rowcount == 0:
            raise HTTPException(status_code=404, detail="Active session not found")

        return {"success": True, "message": "Session heartbeat updated"}
    finally:
        cursor.close()
        db.close()


@app.get("/sessions/{user_id}/count")
def session_count(user_id: int):
    """Get the number of active sessions for a user."""
    db = get_db()
    cursor = db.cursor(dictionary=True)

    try:
        cursor.execute(
            """SELECT COUNT(*) as count FROM user_sessions
               WHERE user_id = %s AND is_active = 1 AND expires_at > NOW()""",
            (user_id,)
        )
        count = cursor.fetchone()["count"]
        return {"user_id": user_id, "active_sessions": count}
    finally:
        cursor.close()
        db.close()
