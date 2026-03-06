from fastapi import FastAPI, HTTPException, Depends, Header
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel
from typing import Optional, List
import mysql.connector
import jwt
import os
from datetime import datetime
from dotenv import load_dotenv

load_dotenv(os.path.join(os.path.dirname(__file__), '..', '..', '.env'))
import jwt
from datetime import datetime

# ── Config ─────────────────────────────────────────────────────────────
SECRET_KEY = os.getenv("JWT_SECRET", "bright-steps-jwt-secret-change-in-production")
ALGORITHM = os.getenv("JWT_ALGORITHM", "HS256")

CATEGORIES = ["motor_skills", "language", "cognitive", "social_emotional", "self_care"]

app = FastAPI(
    title="Bright Steps Milestone Tracking API",
    description="Developmental milestone tracking with age-appropriate checklists",
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


# ── Models ─────────────────────────────────────────────────────────────

class AchieveMilestoneRequest(BaseModel):
    achieved_at: Optional[str] = None  # ISO date (YYYY-MM-DD), defaults to today
    notes: Optional[str] = None


# ── Routes ─────────────────────────────────────────────────────────────

@app.get("/")
def root():
    return {
        "message": "Bright Steps Milestone Tracking API is running!",
        "endpoints": {
            "GET  /milestones":                                  "List all milestones",
            "GET  /milestones/category/{category}":              "Milestones by category",
            "GET  /milestones/child/{child_id}":                 "Child's milestone progress",
            "POST /milestones/child/{child_id}/{milestone_id}":  "Mark milestone achieved",
            "DELETE /milestones/child/{child_id}/{milestone_id}":"Remove achievement",
            "GET  /milestones/child/{child_id}/summary":         "Progress summary",
            "GET  /milestones/suggestions/{age_months}":         "Suggested milestones for age",
            "GET  /categories":                                  "List milestone categories",
        }
    }


@app.get("/milestones")
def list_milestones(
    category: Optional[str] = None,
    min_age: Optional[int] = None,
    max_age: Optional[int] = None,
):
    """List all milestones with optional filters."""
    db = get_db()
    cursor = db.cursor(dictionary=True)

    try:
        query = "SELECT * FROM milestones WHERE 1=1"
        params = []

        if category:
            if category not in CATEGORIES:
                raise HTTPException(status_code=400, detail=f"Invalid category. Must be one of: {CATEGORIES}")
            query += " AND category = %s"
            params.append(category)
        if min_age is not None:
            query += " AND max_age_months >= %s"
            params.append(min_age)
        if max_age is not None:
            query += " AND min_age_months <= %s"
            params.append(max_age)

        query += " ORDER BY category, min_age_months, title"
        cursor.execute(query, params)
        milestones = cursor.fetchall()

        return {"count": len(milestones), "milestones": milestones}
    finally:
        cursor.close()
        db.close()


@app.get("/milestones/category/{category}")
def get_by_category(category: str):
    """Get all milestones for a specific category."""
    if category not in CATEGORIES:
        raise HTTPException(status_code=400, detail=f"Invalid category. Must be one of: {CATEGORIES}")

    db = get_db()
    cursor = db.cursor(dictionary=True)

    try:
        cursor.execute(
            "SELECT * FROM milestones WHERE category = %s ORDER BY min_age_months, title",
            (category,)
        )
        milestones = cursor.fetchall()

        return {"category": category, "count": len(milestones), "milestones": milestones}
    finally:
        cursor.close()
        db.close()


@app.get("/milestones/child/{child_id}")
def get_child_milestones(child_id: int, category: Optional[str] = None, user: dict = Depends(get_current_user)):
    """Get milestone progress for a child, showing achieved and remaining milestones."""
    db = get_db()
    cursor = db.cursor(dictionary=True)

    try:
        # Get child's age
        cursor.execute(
            "SELECT birth_day, birth_month, birth_year FROM child WHERE child_id = %s",
            (child_id,)
        )
        child = cursor.fetchone()
        if not child:
            raise HTTPException(status_code=404, detail="Child not found")

        now = datetime.now()
        try:
            birth = datetime(child["birth_year"], child["birth_month"], child["birth_day"])
            age_months = max(0, (now.year - birth.year) * 12 + (now.month - birth.month))
        except (ValueError, TypeError):
            age_months = None

        # Get all relevant milestones
        query = """
            SELECT m.*, cm.achieved_at, cm.notes, cm.recorded_at,
                   CASE WHEN cm.child_id IS NOT NULL THEN 1 ELSE 0 END as achieved
            FROM milestones m
            LEFT JOIN child_milestones cm ON m.milestone_id = cm.milestone_id AND cm.child_id = %s
            WHERE 1=1
        """
        params = [child_id]

        if category:
            query += " AND m.category = %s"
            params.append(category)

        query += " ORDER BY m.category, m.min_age_months, m.title"
        cursor.execute(query, params)
        milestones = cursor.fetchall()

        for m in milestones:
            if isinstance(m.get("achieved_at"), datetime):
                m["achieved_at"] = m["achieved_at"].strftime("%Y-%m-%d")
            if isinstance(m.get("recorded_at"), datetime):
                m["recorded_at"] = m["recorded_at"].isoformat()
            m["achieved"] = bool(m["achieved"])

        achieved_count = sum(1 for m in milestones if m["achieved"])

        return {
            "child_id": child_id,
            "age_months": age_months,
            "total_milestones": len(milestones),
            "achieved_count": achieved_count,
            "remaining_count": len(milestones) - achieved_count,
            "completion_percentage": round(achieved_count / len(milestones) * 100, 1) if milestones else 0,
            "milestones": milestones,
        }
    finally:
        cursor.close()
        db.close()


@app.post("/milestones/child/{child_id}/{milestone_id}")
def achieve_milestone(
    child_id: int,
    milestone_id: int,
    req: AchieveMilestoneRequest,
    user: dict = Depends(get_current_user)
):
    """Mark a milestone as achieved for a child."""
    db = get_db()
    cursor = db.cursor(dictionary=True)

    try:
        # Verify child exists
        cursor.execute("SELECT child_id FROM child WHERE child_id = %s", (child_id,))
        if not cursor.fetchone():
            raise HTTPException(status_code=404, detail="Child not found")

        # Verify milestone exists
        cursor.execute("SELECT title, category FROM milestones WHERE milestone_id = %s", (milestone_id,))
        milestone = cursor.fetchone()
        if not milestone:
            raise HTTPException(status_code=404, detail="Milestone not found")

        achieved_at = req.achieved_at or datetime.now().strftime("%Y-%m-%d")

        cursor.execute(
            """INSERT INTO child_milestones (child_id, milestone_id, achieved_at, notes)
               VALUES (%s, %s, %s, %s)
               ON DUPLICATE KEY UPDATE achieved_at = VALUES(achieved_at), notes = VALUES(notes)""",
            (child_id, milestone_id, achieved_at, req.notes)
        )
        db.commit()

        return {
            "success": True,
            "message": f"Milestone '{milestone['title']}' achieved! 🎉",
            "milestone": milestone["title"],
            "category": milestone["category"],
            "achieved_at": achieved_at,
        }
    finally:
        cursor.close()
        db.close()


@app.delete("/milestones/child/{child_id}/{milestone_id}")
def remove_milestone(child_id: int, milestone_id: int, user: dict = Depends(get_current_user)):
    """Remove a milestone achievement."""
    db = get_db()
    cursor = db.cursor()

    try:
        cursor.execute(
            "DELETE FROM child_milestones WHERE child_id = %s AND milestone_id = %s",
            (child_id, milestone_id)
        )
        db.commit()

        if cursor.rowcount == 0:
            raise HTTPException(status_code=404, detail="Milestone achievement not found")

        return {"success": True, "message": "Milestone achievement removed"}
    finally:
        cursor.close()
        db.close()


@app.get("/milestones/child/{child_id}/summary")
def get_summary(child_id: int, user: dict = Depends(get_current_user)):
    """Get a progress summary grouped by category."""
    db = get_db()
    cursor = db.cursor(dictionary=True)

    try:
        # Get child's age
        cursor.execute(
            "SELECT birth_day, birth_month, birth_year FROM child WHERE child_id = %s",
            (child_id,)
        )
        child = cursor.fetchone()
        if not child:
            raise HTTPException(status_code=404, detail="Child not found")

        now = datetime.now()
        try:
            birth = datetime(child["birth_year"], child["birth_month"], child["birth_day"])
            age_months = max(0, (now.year - birth.year) * 12 + (now.month - birth.month))
        except (ValueError, TypeError):
            age_months = None

        summary = {}
        for cat in CATEGORIES:
            # Total milestones in this category up to child's age
            cursor.execute(
                """SELECT COUNT(*) as total FROM milestones
                   WHERE category = %s AND min_age_months <= %s""",
                (cat, age_months or 72)
            )
            total = cursor.fetchone()["total"]

            # Achieved milestones
            cursor.execute(
                """SELECT COUNT(*) as achieved FROM child_milestones cm
                   INNER JOIN milestones m ON cm.milestone_id = m.milestone_id
                   WHERE cm.child_id = %s AND m.category = %s""",
                (child_id, cat)
            )
            achieved = cursor.fetchone()["achieved"]

            display_name = cat.replace("_", " ").title()
            summary[cat] = {
                "display_name": display_name,
                "total": total,
                "achieved": achieved,
                "remaining": total - achieved,
                "percentage": round(achieved / total * 100, 1) if total > 0 else 0,
            }

        # Overall
        total_all = sum(s["total"] for s in summary.values())
        achieved_all = sum(s["achieved"] for s in summary.values())

        return {
            "child_id": child_id,
            "age_months": age_months,
            "overall": {
                "total": total_all,
                "achieved": achieved_all,
                "percentage": round(achieved_all / total_all * 100, 1) if total_all > 0 else 0,
            },
            "by_category": summary,
        }
    finally:
        cursor.close()
        db.close()


@app.get("/milestones/suggestions/{age_months}")
def get_suggestions(age_months: int):
    """Get suggested milestones for a given age."""
    db = get_db()
    cursor = db.cursor(dictionary=True)

    try:
        cursor.execute(
            """SELECT * FROM milestones
               WHERE min_age_months <= %s AND max_age_months >= %s
               ORDER BY category, min_age_months""",
            (age_months, age_months)
        )
        milestones = cursor.fetchall()

        # Group by category
        grouped = {}
        for m in milestones:
            cat = m["category"]
            if cat not in grouped:
                grouped[cat] = {
                    "display_name": cat.replace("_", " ").title(),
                    "milestones": [],
                }
            grouped[cat]["milestones"].append(m)

        return {
            "age_months": age_months,
            "total_suggestions": len(milestones),
            "by_category": grouped,
        }
    finally:
        cursor.close()
        db.close()


@app.get("/categories")
def list_categories():
    """List all milestone categories."""
    return {
        "categories": [
            {"key": "motor_skills", "display": "Motor Skills", "icon": "🏃", "description": "Physical movement and coordination"},
            {"key": "language", "display": "Language", "icon": "🗣️", "description": "Speech, communication, and vocabulary"},
            {"key": "cognitive", "display": "Cognitive", "icon": "🧠", "description": "Thinking, problem-solving, and learning"},
            {"key": "social_emotional", "display": "Social-Emotional", "icon": "❤️", "description": "Feelings, relationships, and social skills"},
            {"key": "self_care", "display": "Self-Care", "icon": "🪥", "description": "Daily living skills and independence"},
        ]
    }
