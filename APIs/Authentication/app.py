from fastapi import FastAPI, HTTPException, Depends, Header
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel, EmailStr
from typing import Optional
import mysql.connector
import bcrypt
import jwt
import uuid
import os
from datetime import datetime, timedelta
from dotenv import load_dotenv

load_dotenv(os.path.join(os.path.dirname(__file__), '..', '..', '.env'))

# ── Config ─────────────────────────────────────────────────────────────
SECRET_KEY = os.getenv("JWT_SECRET", "bright-steps-jwt-secret-change-in-production")
ALGORITHM = os.getenv("JWT_ALGORITHM", "HS256")
ACCESS_TOKEN_EXPIRE_MINUTES = 60
REFRESH_TOKEN_EXPIRE_DAYS = 30
REFRESH_TOKEN_EXPIRE_DAYS = 30

app = FastAPI(
    title="Bright Steps Authentication API",
    description="JWT-based authentication API for the Bright Steps platform",
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

# ── JWT helpers ────────────────────────────────────────────────────────

def create_access_token(user_id: int, email: str, role: str) -> str:
    payload = {
        "sub": str(user_id),
        "email": email,
        "role": role,
        "type": "access",
        "exp": datetime.utcnow() + timedelta(minutes=ACCESS_TOKEN_EXPIRE_MINUTES),
        "iat": datetime.utcnow(),
    }
    return jwt.encode(payload, SECRET_KEY, algorithm=ALGORITHM)


def create_refresh_token(user_id: int) -> str:
    payload = {
        "sub": str(user_id),
        "type": "refresh",
        "jti": str(uuid.uuid4()),
        "exp": datetime.utcnow() + timedelta(days=REFRESH_TOKEN_EXPIRE_DAYS),
        "iat": datetime.utcnow(),
    }
    return jwt.encode(payload, SECRET_KEY, algorithm=ALGORITHM)


def decode_token(token: str) -> dict:
    try:
        return jwt.decode(token, SECRET_KEY, algorithms=[ALGORITHM])
    except jwt.ExpiredSignatureError:
        raise HTTPException(status_code=401, detail="Token has expired")
    except jwt.InvalidTokenError:
        raise HTTPException(status_code=401, detail="Invalid token")


def get_current_user(authorization: Optional[str] = Header(None)):
    if not authorization or not authorization.startswith("Bearer "):
        raise HTTPException(status_code=401, detail="Missing or invalid Authorization header")
    token = authorization.split(" ")[1]
    payload = decode_token(token)
    if payload.get("type") != "access":
        raise HTTPException(status_code=401, detail="Invalid token type")
    return payload


# ── Models ─────────────────────────────────────────────────────────────

class RegisterRequest(BaseModel):
    first_name: str
    last_name: str
    email: str
    password: str
    role: Optional[str] = "parent"

class LoginRequest(BaseModel):
    email: str
    password: str

class RefreshRequest(BaseModel):
    refresh_token: str

class ChangePasswordRequest(BaseModel):
    current_password: str
    new_password: str

class ForgotPasswordRequest(BaseModel):
    email: str

class ResetPasswordRequest(BaseModel):
    token: str
    new_password: str


# ── Routes ─────────────────────────────────────────────────────────────

@app.get("/")
def root():
    return {
        "message": "Bright Steps Authentication API is running!",
        "endpoints": {
            "POST /register": "Register a new user",
            "POST /login": "Login and get JWT tokens",
            "POST /refresh": "Refresh access token",
            "GET  /me": "Get current user profile",
            "POST /change-password": "Change password",
            "POST /forgot-password": "Request password reset",
            "POST /reset-password": "Reset password with token",
        }
    }


@app.post("/register")
def register(req: RegisterRequest):
    """Register a new user account."""
    if len(req.first_name) < 2:
        raise HTTPException(status_code=400, detail="First name must be at least 2 characters")
    if len(req.last_name) < 2:
        raise HTTPException(status_code=400, detail="Last name must be at least 2 characters")
    if len(req.password) < 8:
        raise HTTPException(status_code=400, detail="Password must be at least 8 characters")

    db = get_db()
    cursor = db.cursor(dictionary=True)

    try:
        # Check if email exists
        cursor.execute("SELECT user_id FROM users WHERE email = %s", (req.email,))
        if cursor.fetchone():
            raise HTTPException(status_code=409, detail="Email already registered")

        # Hash password
        hashed = bcrypt.hashpw(req.password.encode('utf-8'), bcrypt.gensalt()).decode('utf-8')

        # Insert user
        cursor.execute(
            "INSERT INTO users (first_name, last_name, email, password, role) VALUES (%s, %s, %s, %s, %s)",
            (req.first_name, req.last_name, req.email, hashed, req.role)
        )
        user_id = cursor.lastrowid

        # If parent role, create parent record
        if req.role == "parent":
            cursor.execute(
                "INSERT INTO parent (parent_id, number_of_children) VALUES (%s, 0)",
                (user_id,)
            )

        db.commit()

        # Generate tokens
        access_token = create_access_token(user_id, req.email, req.role)
        refresh_token = create_refresh_token(user_id)

        return {
            "success": True,
            "message": "Account created successfully",
            "user": {
                "user_id": user_id,
                "first_name": req.first_name,
                "last_name": req.last_name,
                "email": req.email,
                "role": req.role,
            },
            "access_token": access_token,
            "refresh_token": refresh_token,
            "token_type": "bearer",
        }
    finally:
        cursor.close()
        db.close()


@app.post("/login")
def login(req: LoginRequest):
    """Authenticate user and return JWT tokens."""
    db = get_db()
    cursor = db.cursor(dictionary=True)

    try:
        cursor.execute("SELECT * FROM users WHERE email = %s LIMIT 1", (req.email,))
        user = cursor.fetchone()

        if not user:
            raise HTTPException(status_code=401, detail="Invalid email or password")

        # Verify password (support both bcrypt and PHP password_hash)
        stored_hash = user["password"].encode('utf-8')
        if not bcrypt.checkpw(req.password.encode('utf-8'), stored_hash):
            raise HTTPException(status_code=401, detail="Invalid email or password")

        access_token = create_access_token(user["user_id"], user["email"], user["role"])
        refresh_token = create_refresh_token(user["user_id"])

        return {
            "success": True,
            "user": {
                "user_id": user["user_id"],
                "first_name": user["first_name"],
                "last_name": user["last_name"],
                "email": user["email"],
                "role": user["role"],
            },
            "access_token": access_token,
            "refresh_token": refresh_token,
            "token_type": "bearer",
        }
    finally:
        cursor.close()
        db.close()


@app.post("/refresh")
def refresh_token(req: RefreshRequest):
    """Refresh an expired access token."""
    payload = decode_token(req.refresh_token)

    if payload.get("type") != "refresh":
        raise HTTPException(status_code=400, detail="Invalid token type – expected refresh token")

    user_id = int(payload["sub"])

    db = get_db()
    cursor = db.cursor(dictionary=True)

    try:
        cursor.execute("SELECT * FROM users WHERE user_id = %s LIMIT 1", (user_id,))
        user = cursor.fetchone()

        if not user:
            raise HTTPException(status_code=404, detail="User not found")

        new_access = create_access_token(user["user_id"], user["email"], user["role"])
        new_refresh = create_refresh_token(user["user_id"])

        return {
            "access_token": new_access,
            "refresh_token": new_refresh,
            "token_type": "bearer",
        }
    finally:
        cursor.close()
        db.close()


@app.get("/me")
def get_me(user: dict = Depends(get_current_user)):
    """Get current user profile from JWT token."""
    user_id = int(user["sub"])

    db = get_db()
    cursor = db.cursor(dictionary=True)

    try:
        cursor.execute(
            "SELECT user_id, first_name, last_name, email, role FROM users WHERE user_id = %s",
            (user_id,)
        )
        profile = cursor.fetchone()

        if not profile:
            raise HTTPException(status_code=404, detail="User not found")

        # If parent, get additional info
        if profile["role"] == "parent":
            cursor.execute(
                "SELECT number_of_children, created_at FROM parent WHERE parent_id = %s",
                (user_id,)
            )
            parent_info = cursor.fetchone()
            if parent_info:
                profile["number_of_children"] = parent_info["number_of_children"]
                profile["member_since"] = parent_info["created_at"].isoformat() if parent_info["created_at"] else None

        return profile
    finally:
        cursor.close()
        db.close()


@app.post("/change-password")
def change_password(req: ChangePasswordRequest, user: dict = Depends(get_current_user)):
    """Change password for authenticated user."""
    if len(req.new_password) < 8:
        raise HTTPException(status_code=400, detail="New password must be at least 8 characters")

    user_id = int(user["sub"])
    db = get_db()
    cursor = db.cursor(dictionary=True)

    try:
        cursor.execute("SELECT password FROM users WHERE user_id = %s", (user_id,))
        record = cursor.fetchone()

        if not record:
            raise HTTPException(status_code=404, detail="User not found")

        if not bcrypt.checkpw(req.current_password.encode('utf-8'), record["password"].encode('utf-8')):
            raise HTTPException(status_code=401, detail="Current password is incorrect")

        new_hash = bcrypt.hashpw(req.new_password.encode('utf-8'), bcrypt.gensalt()).decode('utf-8')
        cursor.execute("UPDATE users SET password = %s WHERE user_id = %s", (new_hash, user_id))
        db.commit()

        return {"success": True, "message": "Password changed successfully"}
    finally:
        cursor.close()
        db.close()


@app.post("/forgot-password")
def forgot_password(req: ForgotPasswordRequest):
    """Generate a password reset token."""
    db = get_db()
    cursor = db.cursor(dictionary=True)

    try:
        cursor.execute("SELECT user_id, email FROM users WHERE email = %s", (req.email,))
        user = cursor.fetchone()

        # Always return success to prevent email enumeration
        if not user:
            return {"success": True, "message": "If an account with that email exists, a reset link has been sent."}

        # Generate reset token
        reset_token = str(uuid.uuid4())
        expires_at = datetime.utcnow() + timedelta(hours=1)

        cursor.execute(
            """INSERT INTO password_reset_tokens (user_id, token, expires_at)
               VALUES (%s, %s, %s)""",
            (user["user_id"], reset_token, expires_at)
        )
        db.commit()

        # In production, send email via the Email API
        # For now, return the token (in production, never expose this)
        return {
            "success": True,
            "message": "If an account with that email exists, a reset link has been sent.",
            "debug_token": reset_token,  # Remove in production
        }
    finally:
        cursor.close()
        db.close()


@app.post("/reset-password")
def reset_password(req: ResetPasswordRequest):
    """Reset password using a reset token."""
    if len(req.new_password) < 8:
        raise HTTPException(status_code=400, detail="Password must be at least 8 characters")

    db = get_db()
    cursor = db.cursor(dictionary=True)

    try:
        cursor.execute(
            """SELECT * FROM password_reset_tokens
               WHERE token = %s AND used = 0 AND expires_at > NOW()
               LIMIT 1""",
            (req.token,)
        )
        record = cursor.fetchone()

        if not record:
            raise HTTPException(status_code=400, detail="Invalid or expired reset token")

        new_hash = bcrypt.hashpw(req.new_password.encode('utf-8'), bcrypt.gensalt()).decode('utf-8')
        cursor.execute("UPDATE users SET password = %s WHERE user_id = %s", (new_hash, record["user_id"]))
        cursor.execute("UPDATE password_reset_tokens SET used = 1 WHERE token_id = %s", (record["token_id"],))
        db.commit()

        return {"success": True, "message": "Password has been reset successfully"}
    finally:
        cursor.close()
        db.close()
