from fastapi import FastAPI, HTTPException, Request
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel
from typing import Optional, Dict
from collections import defaultdict
from datetime import datetime, timedelta
import mysql.connector
import time
import threading

# ── Config ─────────────────────────────────────────────────────────────
# Default rate limits: {endpoint_pattern: (max_requests, window_seconds)}
DEFAULT_LIMITS = {
    "/login":            (5,  60),     # 5 per minute
    "/register":         (3,  60),     # 3 per minute
    "/forgot-password":  (3,  300),    # 3 per 5 minutes
    "/reset-password":   (5,  300),    # 5 per 5 minutes
    "/send":             (10, 60),     # 10 emails per minute
    "__default__":       (100, 60),    # 100 per minute for everything else
}

app = FastAPI(
    title="Bright Steps Rate Limiting API",
    description="Centralized rate limiter & IP blocking for the Bright Steps platform",
    version="1.0.0"
)

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

# ── In-memory sliding window store ────────────────────────────────────
# Structure: { "ip:endpoint": [ timestamp1, timestamp2, ... ] }
request_log: Dict[str, list] = defaultdict(list)
blocked_ips_cache: set = set()
log_lock = threading.Lock()


# ── DB helper ──────────────────────────────────────────────────────────
def get_db():
    return mysql.connector.connect(
        host="localhost",
        user="root",
        password="",
        database="grad",
        charset="utf8mb4"
    )


def load_blocked_ips():
    """Load blocked IPs from database into memory cache."""
    global blocked_ips_cache
    try:
        db = get_db()
        cursor = db.cursor(dictionary=True)
        cursor.execute(
            "SELECT ip_address FROM blocked_ips WHERE expires_at IS NULL OR expires_at > NOW()"
        )
        blocked_ips_cache = {row["ip_address"] for row in cursor.fetchall()}
        cursor.close()
        db.close()
    except Exception:
        pass  # Table might not exist yet


# Load on startup
load_blocked_ips()


# ── Helpers ────────────────────────────────────────────────────────────

def get_limit_for_endpoint(endpoint: str) -> tuple:
    """Get the rate limit config for a given endpoint."""
    for pattern, limit in DEFAULT_LIMITS.items():
        if pattern != "__default__" and pattern in endpoint:
            return limit
    return DEFAULT_LIMITS["__default__"]


def clean_old_entries(key: str, window_seconds: int):
    """Remove entries outside the current time window."""
    cutoff = time.time() - window_seconds
    request_log[key] = [t for t in request_log[key] if t > cutoff]


def check_rate_limit(ip: str, endpoint: str) -> dict:
    """Check if a request should be allowed."""
    # Check blocked IPs first
    if ip in blocked_ips_cache:
        return {
            "allowed": False,
            "reason": "IP is blocked",
            "blocked": True,
        }

    max_requests, window_seconds = get_limit_for_endpoint(endpoint)
    key = f"{ip}:{endpoint}"

    with log_lock:
        clean_old_entries(key, window_seconds)
        current_count = len(request_log[key])

        if current_count >= max_requests:
            return {
                "allowed": False,
                "reason": "Rate limit exceeded",
                "limit": max_requests,
                "window_seconds": window_seconds,
                "current_count": current_count,
                "retry_after": int(window_seconds - (time.time() - request_log[key][0])) if request_log[key] else window_seconds,
            }

        # Allow and record
        request_log[key].append(time.time())
        return {
            "allowed": True,
            "remaining": max_requests - current_count - 1,
            "limit": max_requests,
            "window_seconds": window_seconds,
        }


# ── Models ─────────────────────────────────────────────────────────────

class CheckRequest(BaseModel):
    ip_address: str
    endpoint: str

class BlockRequest(BaseModel):
    ip_address: str
    reason: Optional[str] = "Manually blocked"
    blocked_by: Optional[int] = None
    duration_hours: Optional[int] = None  # None = permanent

class UpdateLimitRequest(BaseModel):
    endpoint: str
    max_requests: int
    window_seconds: int


# ── Routes ─────────────────────────────────────────────────────────────

@app.get("/")
def root():
    return {
        "message": "Bright Steps Rate Limiting API is running!",
        "endpoints": {
            "POST /check":            "Check if a request should be allowed",
            "GET  /status/{ip}":      "Get rate limit status for an IP",
            "POST /block":            "Block an IP address",
            "DELETE /block/{ip}":     "Unblock an IP address",
            "GET  /blocked":          "List all blocked IPs",
            "GET  /limits":           "View current rate limit config",
            "PUT  /limits":           "Update a rate limit",
            "GET  /stats":            "Rate limiting statistics",
        }
    }


@app.post("/check")
def check(req: CheckRequest):
    """Check if a request from an IP to an endpoint should be allowed."""
    result = check_rate_limit(req.ip_address, req.endpoint)

    # Log blocked requests to DB
    if not result["allowed"]:
        try:
            db = get_db()
            cursor = db.cursor()
            cursor.execute(
                """INSERT INTO rate_limit_log (ip_address, endpoint, request_count, blocked)
                   VALUES (%s, %s, %s, 1)""",
                (req.ip_address, req.endpoint, result.get("current_count", 0))
            )
            db.commit()
            cursor.close()
            db.close()
        except Exception:
            pass

    return result


@app.get("/status/{ip_address}")
def get_status(ip_address: str):
    """Get current rate limit status for an IP across all endpoints."""
    is_blocked = ip_address in blocked_ips_cache
    active_windows = {}

    with log_lock:
        for key, timestamps in request_log.items():
            if key.startswith(f"{ip_address}:"):
                endpoint = key.split(":", 1)[1]
                max_req, window_sec = get_limit_for_endpoint(endpoint)
                cutoff = time.time() - window_sec
                recent = [t for t in timestamps if t > cutoff]
                if recent:
                    active_windows[endpoint] = {
                        "current_count": len(recent),
                        "limit": max_req,
                        "remaining": max(0, max_req - len(recent)),
                        "window_seconds": window_sec,
                    }

    return {
        "ip_address": ip_address,
        "is_blocked": is_blocked,
        "active_windows": active_windows,
    }


@app.post("/block")
def block_ip(req: BlockRequest):
    """Manually block an IP address."""
    expires_at = None
    if req.duration_hours:
        expires_at = datetime.utcnow() + timedelta(hours=req.duration_hours)

    db = get_db()
    cursor = db.cursor()

    try:
        cursor.execute(
            """INSERT INTO blocked_ips (ip_address, reason, blocked_by, expires_at)
               VALUES (%s, %s, %s, %s)
               ON DUPLICATE KEY UPDATE reason = VALUES(reason), expires_at = VALUES(expires_at)""",
            (req.ip_address, req.reason, req.blocked_by, expires_at)
        )
        db.commit()
        blocked_ips_cache.add(req.ip_address)

        return {
            "success": True,
            "message": f"IP {req.ip_address} blocked",
            "expires_at": expires_at.isoformat() if expires_at else "permanent",
        }
    finally:
        cursor.close()
        db.close()


@app.delete("/block/{ip_address}")
def unblock_ip(ip_address: str):
    """Unblock an IP address."""
    db = get_db()
    cursor = db.cursor()

    try:
        cursor.execute("DELETE FROM blocked_ips WHERE ip_address = %s", (ip_address,))
        db.commit()
        blocked_ips_cache.discard(ip_address)

        if cursor.rowcount == 0:
            raise HTTPException(status_code=404, detail="IP not found in blocked list")

        return {"success": True, "message": f"IP {ip_address} unblocked"}
    finally:
        cursor.close()
        db.close()


@app.get("/blocked")
def list_blocked():
    """List all currently blocked IPs."""
    db = get_db()
    cursor = db.cursor(dictionary=True)

    try:
        cursor.execute(
            """SELECT ip_address, reason, blocked_by, blocked_at, expires_at
               FROM blocked_ips
               WHERE expires_at IS NULL OR expires_at > NOW()
               ORDER BY blocked_at DESC"""
        )
        blocked = cursor.fetchall()

        for b in blocked:
            if b.get("blocked_at"):
                b["blocked_at"] = b["blocked_at"].isoformat()
            if b.get("expires_at"):
                b["expires_at"] = b["expires_at"].isoformat()

        return {"count": len(blocked), "blocked_ips": blocked}
    finally:
        cursor.close()
        db.close()


@app.get("/limits")
def get_limits():
    """View current rate limit configuration."""
    limits = {}
    for endpoint, (max_req, window_sec) in DEFAULT_LIMITS.items():
        limits[endpoint] = {
            "max_requests": max_req,
            "window_seconds": window_sec,
            "description": f"{max_req} requests per {window_sec} seconds",
        }
    return {"limits": limits}


@app.put("/limits")
def update_limit(req: UpdateLimitRequest):
    """Update rate limit for an endpoint (runtime only, not persisted)."""
    DEFAULT_LIMITS[req.endpoint] = (req.max_requests, req.window_seconds)
    return {
        "success": True,
        "message": f"Limit for '{req.endpoint}' updated to {req.max_requests} per {req.window_seconds}s",
    }


@app.get("/stats")
def get_stats():
    """Get rate limiting statistics."""
    db = get_db()
    cursor = db.cursor(dictionary=True)

    try:
        # Total blocked requests
        cursor.execute("SELECT COUNT(*) as total FROM rate_limit_log WHERE blocked = 1")
        total_blocked = cursor.fetchone()["total"]

        # Top blocked IPs
        cursor.execute(
            """SELECT ip_address, COUNT(*) as block_count
               FROM rate_limit_log WHERE blocked = 1
               GROUP BY ip_address ORDER BY block_count DESC LIMIT 10"""
        )
        top_blocked_ips = cursor.fetchall()

        # Top blocked endpoints
        cursor.execute(
            """SELECT endpoint, COUNT(*) as block_count
               FROM rate_limit_log WHERE blocked = 1
               GROUP BY endpoint ORDER BY block_count DESC LIMIT 10"""
        )
        top_blocked_endpoints = cursor.fetchall()

        # Blocks in the last hour
        cursor.execute(
            """SELECT COUNT(*) as count FROM rate_limit_log
               WHERE blocked = 1 AND window_start > DATE_SUB(NOW(), INTERVAL 1 HOUR)"""
        )
        recent_blocks = cursor.fetchone()["count"]

        # Active in-memory tracking
        with log_lock:
            active_keys = len(request_log)
            total_tracked = sum(len(v) for v in request_log.values())

        return {
            "total_blocked_requests": total_blocked,
            "blocks_last_hour": recent_blocks,
            "currently_blocked_ips": len(blocked_ips_cache),
            "active_tracking_keys": active_keys,
            "total_tracked_requests": total_tracked,
            "top_blocked_ips": top_blocked_ips,
            "top_blocked_endpoints": top_blocked_endpoints,
        }
    finally:
        cursor.close()
        db.close()
