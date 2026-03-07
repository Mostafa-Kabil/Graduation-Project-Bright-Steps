from fastapi import FastAPI, HTTPException
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel
from typing import Optional, List
import mysql.connector
import os
from datetime import datetime
import json
from dotenv import load_dotenv

load_dotenv(os.path.join(os.path.dirname(__file__), '..', '..', '.env'))

app = FastAPI(
    title="Bright Steps Audit Log API",
    description="Immutable action logging for compliance and security monitoring",
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

# Valid action types
VALID_ACTIONS = [
    "login", "logout", "register", "password_change", "password_reset",
    "profile_update", "child_add", "child_update", "child_delete",
    "growth_record_add", "payment", "appointment_book", "appointment_cancel",
    "data_export", "admin_action", "session_revoke", "ip_block", "ip_unblock",
    "milestone_achieve", "badge_award", "settings_change",
]

# ── Models ─────────────────────────────────────────────────────────────

class LogRequest(BaseModel):
    user_id: Optional[int] = None
    action: str
    resource: Optional[str] = None
    resource_id: Optional[str] = None
    ip_address: Optional[str] = None
    user_agent: Optional[str] = None
    details: Optional[dict] = None

class BulkLogRequest(BaseModel):
    logs: List[LogRequest]


# ── Routes ─────────────────────────────────────────────────────────────

@app.get("/")
def root():
    return {
        "message": "Bright Steps Audit Log API is running!",
        "endpoints": {
            "POST /log":                  "Record an action",
            "POST /log/bulk":             "Record multiple actions at once",
            "GET  /logs":                 "Query logs (filter by user, action, date)",
            "GET  /logs/user/{user_id}":  "All logs for a user",
            "GET  /logs/action/{action}": "All logs of a specific action",
            "GET  /stats":                "Audit statistics summary",
            "GET  /actions":              "List valid action types",
        }
    }


@app.post("/log")
def create_log(req: LogRequest):
    """Record a single audit log entry (immutable — insert only)."""
    if req.action not in VALID_ACTIONS:
        raise HTTPException(
            status_code=400,
            detail=f"Invalid action '{req.action}'. Valid actions: {VALID_ACTIONS}"
        )

    db = get_db()
    cursor = db.cursor()

    try:
        details_json = json.dumps(req.details) if req.details else None
        cursor.execute(
            """INSERT INTO audit_logs (user_id, action, resource, resource_id, ip_address, user_agent, details)
               VALUES (%s, %s, %s, %s, %s, %s, %s)""",
            (req.user_id, req.action, req.resource, req.resource_id,
             req.ip_address, req.user_agent, details_json)
        )
        db.commit()

        return {
            "success": True,
            "log_id": cursor.lastrowid,
            "message": "Audit log recorded",
        }
    finally:
        cursor.close()
        db.close()


@app.post("/log/bulk")
def create_bulk_logs(req: BulkLogRequest):
    """Record multiple audit log entries at once."""
    db = get_db()
    cursor = db.cursor()

    try:
        data = []
        for log in req.logs:
            if log.action not in VALID_ACTIONS:
                continue  # Skip invalid actions silently in bulk
            details_json = json.dumps(log.details) if log.details else None
            data.append((log.user_id, log.action, log.resource, log.resource_id,
                         log.ip_address, log.user_agent, details_json))

        cursor.executemany(
            """INSERT INTO audit_logs (user_id, action, resource, resource_id, ip_address, user_agent, details)
               VALUES (%s, %s, %s, %s, %s, %s, %s)""",
            data
        )
        db.commit()

        return {
            "success": True,
            "count": len(data),
            "message": f"Recorded {len(data)} audit logs",
        }
    finally:
        cursor.close()
        db.close()


@app.get("/logs")
def query_logs(
    user_id: Optional[int] = None,
    action: Optional[str] = None,
    resource: Optional[str] = None,
    date_from: Optional[str] = None,
    date_to: Optional[str] = None,
    limit: int = 100,
    offset: int = 0,
):
    """Query audit logs with flexible filters."""
    db = get_db()
    cursor = db.cursor(dictionary=True)

    try:
        query = "SELECT * FROM audit_logs WHERE 1=1"
        params = []

        if user_id is not None:
            query += " AND user_id = %s"
            params.append(user_id)
        if action:
            query += " AND action = %s"
            params.append(action)
        if resource:
            query += " AND resource = %s"
            params.append(resource)
        if date_from:
            query += " AND created_at >= %s"
            params.append(date_from)
        if date_to:
            query += " AND created_at <= %s"
            params.append(date_to)

        # Get total count for pagination
        count_query = query.replace("SELECT *", "SELECT COUNT(*) as total")
        cursor.execute(count_query, params)
        total = cursor.fetchone()["total"]

        query += " ORDER BY created_at DESC LIMIT %s OFFSET %s"
        params.extend([limit, offset])

        cursor.execute(query, params)
        logs = cursor.fetchall()

        for log in logs:
            if isinstance(log.get("created_at"), datetime):
                log["created_at"] = log["created_at"].isoformat()
            if log.get("details"):
                try:
                    log["details"] = json.loads(log["details"])
                except (json.JSONDecodeError, TypeError):
                    pass

        return {
            "total": total,
            "limit": limit,
            "offset": offset,
            "logs": logs,
        }
    finally:
        cursor.close()
        db.close()


@app.get("/logs/user/{user_id}")
def get_user_logs(user_id: int, limit: int = 50):
    """Get all audit logs for a specific user."""
    db = get_db()
    cursor = db.cursor(dictionary=True)

    try:
        cursor.execute(
            """SELECT * FROM audit_logs WHERE user_id = %s
               ORDER BY created_at DESC LIMIT %s""",
            (user_id, limit)
        )
        logs = cursor.fetchall()

        for log in logs:
            if isinstance(log.get("created_at"), datetime):
                log["created_at"] = log["created_at"].isoformat()
            if log.get("details"):
                try:
                    log["details"] = json.loads(log["details"])
                except (json.JSONDecodeError, TypeError):
                    pass

        return {"user_id": user_id, "count": len(logs), "logs": logs}
    finally:
        cursor.close()
        db.close()


@app.get("/logs/action/{action}")
def get_action_logs(action: str, limit: int = 50):
    """Get all logs of a specific action type."""
    if action not in VALID_ACTIONS:
        raise HTTPException(status_code=400, detail=f"Invalid action. Valid: {VALID_ACTIONS}")

    db = get_db()
    cursor = db.cursor(dictionary=True)

    try:
        cursor.execute(
            """SELECT * FROM audit_logs WHERE action = %s
               ORDER BY created_at DESC LIMIT %s""",
            (action, limit)
        )
        logs = cursor.fetchall()

        for log in logs:
            if isinstance(log.get("created_at"), datetime):
                log["created_at"] = log["created_at"].isoformat()
            if log.get("details"):
                try:
                    log["details"] = json.loads(log["details"])
                except (json.JSONDecodeError, TypeError):
                    pass

        return {"action": action, "count": len(logs), "logs": logs}
    finally:
        cursor.close()
        db.close()


@app.get("/stats")
def get_stats():
    """Get audit log statistics."""
    db = get_db()
    cursor = db.cursor(dictionary=True)

    try:
        # Total logs
        cursor.execute("SELECT COUNT(*) as total FROM audit_logs")
        total = cursor.fetchone()["total"]

        # Logs today
        cursor.execute(
            "SELECT COUNT(*) as count FROM audit_logs WHERE DATE(created_at) = CURDATE()"
        )
        today = cursor.fetchone()["count"]

        # Actions breakdown
        cursor.execute(
            """SELECT action, COUNT(*) as count FROM audit_logs
               GROUP BY action ORDER BY count DESC"""
        )
        action_breakdown = cursor.fetchall()

        # Most active users (top 10)
        cursor.execute(
            """SELECT al.user_id, u.first_name, u.last_name, COUNT(*) as action_count
               FROM audit_logs al
               LEFT JOIN users u ON al.user_id = u.user_id
               WHERE al.user_id IS NOT NULL
               GROUP BY al.user_id ORDER BY action_count DESC LIMIT 10"""
        )
        top_users = cursor.fetchall()

        # Recent activity (last 24 hours, grouped by hour)
        cursor.execute(
            """SELECT HOUR(created_at) as hour, COUNT(*) as count
               FROM audit_logs
               WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
               GROUP BY HOUR(created_at) ORDER BY hour"""
        )
        hourly_activity = cursor.fetchall()

        # Suspicious activity (multiple failed logins from same IP)
        cursor.execute(
            """SELECT ip_address, COUNT(*) as attempts
               FROM audit_logs
               WHERE action = 'login' AND details LIKE '%failed%'
               AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
               GROUP BY ip_address HAVING attempts > 3
               ORDER BY attempts DESC"""
        )
        suspicious = cursor.fetchall()

        return {
            "total_logs": total,
            "logs_today": today,
            "action_breakdown": action_breakdown,
            "top_users": top_users,
            "hourly_activity_24h": hourly_activity,
            "suspicious_activity": suspicious,
        }
    finally:
        cursor.close()
        db.close()


@app.get("/actions")
def list_actions():
    """List all valid action types."""
    return {"actions": VALID_ACTIONS}
