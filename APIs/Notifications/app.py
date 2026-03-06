from fastapi import FastAPI, HTTPException
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel
from typing import Optional, List
import mysql.connector
import os
from datetime import datetime
from dotenv import load_dotenv

load_dotenv(os.path.join(os.path.dirname(__file__), '..', '..', '.env'))

app = FastAPI(
    title="Bright Steps Notifications API",
    description="In-app notification system for the Bright Steps platform",
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

class CreateNotificationRequest(BaseModel):
    user_id: int
    type: Optional[str] = "system"  # appointment_reminder, payment_success, growth_alert, milestone, system
    title: str
    message: str

class BulkNotificationRequest(BaseModel):
    user_ids: List[int]
    type: Optional[str] = "system"
    title: str
    message: str

# ── Routes ─────────────────────────────────────────────────────────────

@app.get("/")
def root():
    return {
        "message": "Bright Steps Notifications API is running!",
        "endpoints": {
            "POST /notifications": "Create a notification",
            "POST /notifications/bulk": "Send to multiple users",
            "GET  /notifications/{user_id}": "Get user notifications",
            "PUT  /notifications/{id}/read": "Mark as read",
            "PUT  /notifications/{user_id}/read-all": "Mark all as read",
            "DELETE /notifications/{id}": "Delete notification",
        }
    }


@app.post("/notifications")
def create_notification(req: CreateNotificationRequest):
    """Create a single notification for a user."""
    valid_types = ["appointment_reminder", "payment_success", "growth_alert", "milestone", "system"]
    if req.type not in valid_types:
        raise HTTPException(status_code=400, detail=f"Invalid type. Must be one of: {valid_types}")

    db = get_db()
    cursor = db.cursor()

    try:
        cursor.execute(
            """INSERT INTO notifications (user_id, type, title, message)
               VALUES (%s, %s, %s, %s)""",
            (req.user_id, req.type, req.title, req.message)
        )
        db.commit()
        return {
            "success": True,
            "notification_id": cursor.lastrowid,
            "message": "Notification created",
        }
    finally:
        cursor.close()
        db.close()


@app.post("/notifications/bulk")
def create_bulk_notifications(req: BulkNotificationRequest):
    """Send the same notification to multiple users."""
    db = get_db()
    cursor = db.cursor()

    try:
        data = [(uid, req.type, req.title, req.message) for uid in req.user_ids]
        cursor.executemany(
            """INSERT INTO notifications (user_id, type, title, message)
               VALUES (%s, %s, %s, %s)""",
            data
        )
        db.commit()
        return {
            "success": True,
            "count": len(req.user_ids),
            "message": f"Sent {len(req.user_ids)} notifications",
        }
    finally:
        cursor.close()
        db.close()


@app.get("/notifications/{user_id}")
def get_notifications(user_id: int, unread_only: bool = False, limit: int = 50):
    """Get all notifications for a user."""
    db = get_db()
    cursor = db.cursor(dictionary=True)

    try:
        query = "SELECT * FROM notifications WHERE user_id = %s"
        params = [user_id]

        if unread_only:
            query += " AND is_read = 0"

        query += " ORDER BY created_at DESC LIMIT %s"
        params.append(limit)

        cursor.execute(query, params)
        notifications = cursor.fetchall()

        # Convert datetime objects
        for n in notifications:
            if isinstance(n.get("created_at"), datetime):
                n["created_at"] = n["created_at"].isoformat()

        # Count unread
        cursor.execute(
            "SELECT COUNT(*) as count FROM notifications WHERE user_id = %s AND is_read = 0",
            (user_id,)
        )
        unread_count = cursor.fetchone()["count"]

        return {
            "user_id": user_id,
            "unread_count": unread_count,
            "notifications": notifications,
        }
    finally:
        cursor.close()
        db.close()


@app.put("/notifications/{notification_id}/read")
def mark_as_read(notification_id: int):
    """Mark a single notification as read."""
    db = get_db()
    cursor = db.cursor()

    try:
        cursor.execute(
            "UPDATE notifications SET is_read = 1 WHERE notification_id = %s",
            (notification_id,)
        )
        db.commit()

        if cursor.rowcount == 0:
            raise HTTPException(status_code=404, detail="Notification not found")

        return {"success": True, "message": "Marked as read"}
    finally:
        cursor.close()
        db.close()


@app.put("/notifications/{user_id}/read-all")
def mark_all_as_read(user_id: int):
    """Mark all notifications as read for a user."""
    db = get_db()
    cursor = db.cursor()

    try:
        cursor.execute(
            "UPDATE notifications SET is_read = 1 WHERE user_id = %s AND is_read = 0",
            (user_id,)
        )
        db.commit()
        return {"success": True, "updated": cursor.rowcount, "message": "All notifications marked as read"}
    finally:
        cursor.close()
        db.close()


@app.delete("/notifications/{notification_id}")
def delete_notification(notification_id: int):
    """Delete a notification."""
    db = get_db()
    cursor = db.cursor()

    try:
        cursor.execute(
            "DELETE FROM notifications WHERE notification_id = %s",
            (notification_id,)
        )
        db.commit()

        if cursor.rowcount == 0:
            raise HTTPException(status_code=404, detail="Notification not found")

        return {"success": True, "message": "Notification deleted"}
    finally:
        cursor.close()
        db.close()
