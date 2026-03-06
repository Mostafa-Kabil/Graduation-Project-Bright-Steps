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

app = FastAPI(
    title="Bright Steps Child Profile API",
    description="CRUD API for child profiles and growth records",
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

class AddChildRequest(BaseModel):
    parent_id: int
    ssn: Optional[str] = ""
    first_name: str
    last_name: str
    birth_day: int
    birth_month: int
    birth_year: int
    gender: str  # male / female
    birth_certificate: Optional[str] = None

class UpdateChildRequest(BaseModel):
    first_name: Optional[str] = None
    last_name: Optional[str] = None
    birth_day: Optional[int] = None
    birth_month: Optional[int] = None
    birth_year: Optional[int] = None
    gender: Optional[str] = None
    birth_certificate: Optional[str] = None

class AddGrowthRequest(BaseModel):
    height: Optional[float] = None   # cm
    weight: Optional[float] = None   # kg
    head_circumference: Optional[float] = None  # cm


# ── Routes ─────────────────────────────────────────────────────────────

@app.get("/")
def root():
    return {
        "message": "Bright Steps Child Profile API is running!",
        "endpoints": {
            "POST /children":                       "Add a child profile",
            "GET  /children/{parent_id}":            "List children for a parent",
            "GET  /child/{child_id}":                "Get child details",
            "PUT  /child/{child_id}":                "Update child profile",
            "DELETE /child/{child_id}":              "Delete child",
            "POST /child/{child_id}/growth":         "Add growth record",
            "GET  /child/{child_id}/growth":         "Get growth history",
            "GET  /child/{child_id}/growth/latest":  "Get latest growth record",
            "GET  /child/{child_id}/age":            "Get child's age in months",
        }
    }


@app.post("/children")
def add_child(req: AddChildRequest, user: dict = Depends(get_current_user)):
    """Add a new child profile linked to a parent."""
    if len(req.first_name) < 2:
        raise HTTPException(status_code=400, detail="First name must be at least 2 characters")

    db = get_db()
    cursor = db.cursor(dictionary=True)

    try:
        # Verify parent exists
        cursor.execute("SELECT parent_id FROM parent WHERE parent_id = %s", (req.parent_id,))
        if not cursor.fetchone():
            raise HTTPException(status_code=404, detail="Parent not found")

        # Generate SSN if not provided
        ssn = req.ssn or f"BS-{req.parent_id}-{datetime.now().strftime('%Y%m%d%H%M%S')}"

        cursor.execute(
            """INSERT INTO child (ssn, parent_id, first_name, last_name, birth_day, birth_month, birth_year, gender, birth_certificate)
               VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s)""",
            (ssn, req.parent_id, req.first_name, req.last_name,
             req.birth_day, req.birth_month, req.birth_year,
             req.gender, req.birth_certificate)
        )
        child_id = cursor.lastrowid

        # Create points wallet for the child
        cursor.execute(
            "INSERT INTO points_wallet (child_id, total_points) VALUES (%s, 0)",
            (child_id,)
        )

        db.commit()

        return {
            "success": True,
            "child_id": child_id,
            "message": f"Child profile created for {req.first_name}",
        }
    finally:
        cursor.close()
        db.close()


@app.get("/children/{parent_id}")
def list_children(parent_id: int, user: dict = Depends(get_current_user)):
    """List all children for a parent."""
    db = get_db()
    cursor = db.cursor(dictionary=True)

    try:
        cursor.execute(
            """SELECT child_id, first_name, last_name, birth_day, birth_month, birth_year, gender
               FROM child WHERE parent_id = %s ORDER BY child_id""",
            (parent_id,)
        )
        children = cursor.fetchall()

        # Calculate age for each child
        now = datetime.now()
        for child in children:
            try:
                birth_date = datetime(child["birth_year"], child["birth_month"], child["birth_day"])
                age_months = (now.year - birth_date.year) * 12 + (now.month - birth_date.month)
                child["age_months"] = max(0, age_months)
            except (ValueError, TypeError):
                child["age_months"] = None

        return {"parent_id": parent_id, "count": len(children), "children": children}
    finally:
        cursor.close()
        db.close()


@app.get("/child/{child_id}")
def get_child(child_id: int, user: dict = Depends(get_current_user)):
    """Get detailed child profile."""
    db = get_db()
    cursor = db.cursor(dictionary=True)

    try:
        cursor.execute(
            """SELECT c.child_id, c.ssn, c.parent_id, c.first_name, c.last_name,
                      c.birth_day, c.birth_month, c.birth_year, c.gender, c.birth_certificate
               FROM child c WHERE c.child_id = %s""",
            (child_id,)
        )
        child = cursor.fetchone()
        if not child:
            raise HTTPException(status_code=404, detail="Child not found")

        # Calculate age
        now = datetime.now()
        try:
            birth_date = datetime(child["birth_year"], child["birth_month"], child["birth_day"])
            child["age_months"] = max(0, (now.year - birth_date.year) * 12 + (now.month - birth_date.month))
        except (ValueError, TypeError):
            child["age_months"] = None

        # Get latest growth record
        cursor.execute(
            """SELECT height, weight, head_circumference, recorded_at
               FROM growth_record WHERE child_id = %s ORDER BY recorded_at DESC LIMIT 1""",
            (child_id,)
        )
        latest_growth = cursor.fetchone()
        if latest_growth:
            if isinstance(latest_growth.get("recorded_at"), datetime):
                latest_growth["recorded_at"] = latest_growth["recorded_at"].isoformat()
            for key in ("height", "weight", "head_circumference"):
                if latest_growth.get(key) is not None:
                    latest_growth[key] = float(latest_growth[key])
        child["latest_growth"] = latest_growth

        # Get points
        cursor.execute(
            "SELECT total_points FROM points_wallet WHERE child_id = %s",
            (child_id,)
        )
        wallet = cursor.fetchone()
        child["total_points"] = wallet["total_points"] if wallet else 0

        # Get badge count
        cursor.execute(
            "SELECT COUNT(*) as count FROM child_badge WHERE child_id = %s",
            (child_id,)
        )
        child["badge_count"] = cursor.fetchone()["count"]

        return child
    finally:
        cursor.close()
        db.close()


@app.put("/child/{child_id}")
def update_child(child_id: int, req: UpdateChildRequest, user: dict = Depends(get_current_user)):
    """Update child profile fields."""
    db = get_db()
    cursor = db.cursor()

    try:
        updates = []
        values = []

        if req.first_name is not None:
            updates.append("first_name = %s")
            values.append(req.first_name)
        if req.last_name is not None:
            updates.append("last_name = %s")
            values.append(req.last_name)
        if req.birth_day is not None:
            updates.append("birth_day = %s")
            values.append(req.birth_day)
        if req.birth_month is not None:
            updates.append("birth_month = %s")
            values.append(req.birth_month)
        if req.birth_year is not None:
            updates.append("birth_year = %s")
            values.append(req.birth_year)
        if req.gender is not None:
            updates.append("gender = %s")
            values.append(req.gender)
        if req.birth_certificate is not None:
            updates.append("birth_certificate = %s")
            values.append(req.birth_certificate)

        if not updates:
            raise HTTPException(status_code=400, detail="No fields to update")

        values.append(child_id)
        cursor.execute(
            f"UPDATE child SET {', '.join(updates)} WHERE child_id = %s",
            values
        )
        db.commit()

        if cursor.rowcount == 0:
            raise HTTPException(status_code=404, detail="Child not found")

        return {"success": True, "message": "Child profile updated"}
    finally:
        cursor.close()
        db.close()


@app.delete("/child/{child_id}")
def delete_child(child_id: int, user: dict = Depends(get_current_user)):
    """Delete a child profile."""
    db = get_db()
    cursor = db.cursor()

    try:
        cursor.execute("DELETE FROM child WHERE child_id = %s", (child_id,))
        db.commit()

        if cursor.rowcount == 0:
            raise HTTPException(status_code=404, detail="Child not found")

        return {"success": True, "message": "Child profile deleted"}
    finally:
        cursor.close()
        db.close()


# ── Growth Records ────────────────────────────────────────────────────

@app.post("/child/{child_id}/growth")
def add_growth_record(child_id: int, req: AddGrowthRequest, user: dict = Depends(get_current_user)):
    """Add a growth measurement record."""
    if req.height is None and req.weight is None and req.head_circumference is None:
        raise HTTPException(status_code=400, detail="At least one measurement is required")

    db = get_db()
    cursor = db.cursor()

    try:
        # Verify child exists
        cursor.execute("SELECT child_id FROM child WHERE child_id = %s", (child_id,))
        if not cursor.fetchone():
            raise HTTPException(status_code=404, detail="Child not found")

        cursor.execute(
            """INSERT INTO growth_record (child_id, height, weight, head_circumference)
               VALUES (%s, %s, %s, %s)""",
            (child_id, req.height, req.weight, req.head_circumference)
        )
        db.commit()

        return {
            "success": True,
            "record_id": cursor.lastrowid,
            "message": "Growth record added",
        }
    finally:
        cursor.close()
        db.close()


@app.get("/child/{child_id}/growth")
def get_growth_history(child_id: int, limit: int = 50, user: dict = Depends(get_current_user)):
    """Get growth measurement history for a child."""
    db = get_db()
    cursor = db.cursor(dictionary=True)

    try:
        cursor.execute(
            """SELECT record_id, height, weight, head_circumference, recorded_at
               FROM growth_record WHERE child_id = %s
               ORDER BY recorded_at DESC LIMIT %s""",
            (child_id, limit)
        )
        records = cursor.fetchall()

        for r in records:
            if isinstance(r.get("recorded_at"), datetime):
                r["recorded_at"] = r["recorded_at"].isoformat()
            for key in ("height", "weight", "head_circumference"):
                if r.get(key) is not None:
                    r[key] = float(r[key])

        return {"child_id": child_id, "count": len(records), "records": records}
    finally:
        cursor.close()
        db.close()


@app.get("/child/{child_id}/growth/latest")
def get_latest_growth(child_id: int, user: dict = Depends(get_current_user)):
    """Get the most recent growth record."""
    db = get_db()
    cursor = db.cursor(dictionary=True)

    try:
        cursor.execute(
            """SELECT record_id, height, weight, head_circumference, recorded_at
               FROM growth_record WHERE child_id = %s
               ORDER BY recorded_at DESC LIMIT 1""",
            (child_id,)
        )
        record = cursor.fetchone()

        if not record:
            raise HTTPException(status_code=404, detail="No growth records found")

        if isinstance(record.get("recorded_at"), datetime):
            record["recorded_at"] = record["recorded_at"].isoformat()
        for key in ("height", "weight", "head_circumference"):
            if record.get(key) is not None:
                record[key] = float(record[key])

        return record
    finally:
        cursor.close()
        db.close()


@app.get("/child/{child_id}/age")
def get_child_age(child_id: int, user: dict = Depends(get_current_user)):
    """Calculate child's current age in months and years."""
    db = get_db()
    cursor = db.cursor(dictionary=True)

    try:
        cursor.execute(
            "SELECT birth_day, birth_month, birth_year FROM child WHERE child_id = %s",
            (child_id,)
        )
        child = cursor.fetchone()
        if not child:
            raise HTTPException(status_code=404, detail="Child not found")

        now = datetime.now()
        try:
            birth_date = datetime(child["birth_year"], child["birth_month"], child["birth_day"])
            total_months = (now.year - birth_date.year) * 12 + (now.month - birth_date.month)
            years = total_months // 12
            months = total_months % 12
        except (ValueError, TypeError):
            raise HTTPException(status_code=400, detail="Invalid birth date")

        return {
            "child_id": child_id,
            "age_months": max(0, total_months),
            "age_years": years,
            "age_remaining_months": months,
            "age_display": f"{years} years, {months} months" if years > 0 else f"{months} months",
        }
    finally:
        cursor.close()
        db.close()
