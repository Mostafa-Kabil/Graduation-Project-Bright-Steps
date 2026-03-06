from fastapi import FastAPI, HTTPException, Depends, Header
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel
from typing import Optional, List
import mysql.connector
import jwt
from datetime import datetime, timedelta

# ── Config ─────────────────────────────────────────────────────────────
SECRET_KEY = "bright-steps-jwt-secret-change-in-production"
ALGORITHM = "HS256"

app = FastAPI(
    title="Bright Steps Appointment Booking API",
    description="Appointment scheduling with specialists and clinics",
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

class BookAppointmentRequest(BaseModel):
    parent_id: int
    specialist_id: int
    payment_id: int
    type: str  # online / onsite
    scheduled_at: str  # ISO datetime
    comment: Optional[str] = None

class UpdateAppointmentRequest(BaseModel):
    scheduled_at: Optional[str] = None
    type: Optional[str] = None
    comment: Optional[str] = None

class CompleteAppointmentRequest(BaseModel):
    report: Optional[str] = None
    comment: Optional[str] = None

class FeedbackRequest(BaseModel):
    parent_id: int
    specialist_id: int
    content: str
    rating: int  # 1-5


# ── Routes ─────────────────────────────────────────────────────────────

@app.get("/")
def root():
    return {
        "message": "Bright Steps Appointment Booking API is running!",
        "endpoints": {
            "GET  /specialists":                            "List all specialists",
            "GET  /specialists/{id}":                       "Get specialist details",
            "GET  /specialists/{id}/slots":                 "Available time slots",
            "POST /appointments":                           "Book appointment",
            "GET  /appointments/parent/{parent_id}":        "Parent's appointments",
            "GET  /appointments/specialist/{specialist_id}":"Specialist's appointments",
            "GET  /appointments/{id}":                      "Get appointment details",
            "PUT  /appointments/{id}":                      "Reschedule appointment",
            "PUT  /appointments/{id}/cancel":               "Cancel appointment",
            "PUT  /appointments/{id}/complete":             "Mark as completed",
            "POST /appointments/{id}/feedback":             "Submit feedback",
        }
    }


# ── Specialists ────────────────────────────────────────────────────────

@app.get("/specialists")
def list_specialists(clinic_id: Optional[int] = None, specialization: Optional[str] = None):
    """List all specialists with clinic information."""
    db = get_db()
    cursor = db.cursor(dictionary=True)

    try:
        query = """
            SELECT s.specialist_id, u.first_name, u.last_name, s.specialization,
                   s.experience_years, s.certificate_of_experience,
                   c.clinic_id, c.clinic_name, c.location,
                   (SELECT AVG(f.rating) FROM feedback f WHERE f.specialist_id = s.specialist_id) as avg_rating,
                   (SELECT COUNT(f.feedback_id) FROM feedback f WHERE f.specialist_id = s.specialist_id) as review_count
            FROM specialist s
            INNER JOIN users u ON s.specialist_id = u.user_id
            INNER JOIN clinic c ON s.clinic_id = c.clinic_id
            WHERE 1=1
        """
        params = []

        if clinic_id:
            query += " AND s.clinic_id = %s"
            params.append(clinic_id)
        if specialization:
            query += " AND s.specialization LIKE %s"
            params.append(f"%{specialization}%")

        query += " ORDER BY avg_rating DESC"
        cursor.execute(query, params)
        specialists = cursor.fetchall()

        for s in specialists:
            if s.get("avg_rating") is not None:
                s["avg_rating"] = round(float(s["avg_rating"]), 1)

        return {"count": len(specialists), "specialists": specialists}
    finally:
        cursor.close()
        db.close()


@app.get("/specialists/{specialist_id}")
def get_specialist(specialist_id: int):
    """Get detailed specialist profile."""
    db = get_db()
    cursor = db.cursor(dictionary=True)

    try:
        cursor.execute(
            """SELECT s.specialist_id, u.first_name, u.last_name, u.email,
                      s.specialization, s.experience_years, s.certificate_of_experience,
                      c.clinic_id, c.clinic_name, c.location, c.logo as clinic_logo
               FROM specialist s
               INNER JOIN users u ON s.specialist_id = u.user_id
               INNER JOIN clinic c ON s.clinic_id = c.clinic_id
               WHERE s.specialist_id = %s""",
            (specialist_id,)
        )
        specialist = cursor.fetchone()

        if not specialist:
            raise HTTPException(status_code=404, detail="Specialist not found")

        # Get average rating
        cursor.execute(
            "SELECT AVG(rating) as avg_rating, COUNT(*) as count FROM feedback WHERE specialist_id = %s",
            (specialist_id,)
        )
        rating_info = cursor.fetchone()
        specialist["avg_rating"] = round(float(rating_info["avg_rating"]), 1) if rating_info["avg_rating"] else None
        specialist["review_count"] = rating_info["count"]

        # Get phone numbers
        cursor.execute(
            "SELECT phone FROM clinic_phone WHERE clinic_id = %s",
            (specialist["clinic_id"],)
        )
        specialist["clinic_phones"] = [row["phone"] for row in cursor.fetchall()]

        return specialist
    finally:
        cursor.close()
        db.close()


@app.get("/specialists/{specialist_id}/slots")
def get_available_slots(specialist_id: int, date: Optional[str] = None):
    """Get available time slots for a specialist."""
    db = get_db()
    cursor = db.cursor(dictionary=True)

    try:
        # Get booked slots for the date (or next 7 days)
        if date:
            query = """
                SELECT scheduled_at FROM appointment
                WHERE specialist_id = %s AND status NOT IN ('cancelled')
                AND DATE(scheduled_at) = %s
            """
            params = [specialist_id, date]
        else:
            query = """
                SELECT scheduled_at FROM appointment
                WHERE specialist_id = %s AND status NOT IN ('cancelled')
                AND scheduled_at >= NOW() AND scheduled_at <= DATE_ADD(NOW(), INTERVAL 7 DAY)
            """
            params = [specialist_id]

        cursor.execute(query, params)
        booked = cursor.fetchall()
        booked_times = set()
        for b in booked:
            if isinstance(b["scheduled_at"], datetime):
                booked_times.add(b["scheduled_at"].strftime("%Y-%m-%d %H:%M"))

        # Generate available slots (9 AM to 5 PM, 30-min intervals)
        target_date = datetime.strptime(date, "%Y-%m-%d") if date else datetime.now()
        if target_date.date() < datetime.now().date():
            return {"specialist_id": specialist_id, "date": date, "slots": []}

        slots = []
        for day_offset in range(0 if date else 0, 1 if date else 7):
            current_date = target_date + timedelta(days=day_offset)
            # Skip weekends
            if current_date.weekday() >= 5:
                continue

            day_slots = []
            for hour in range(9, 17):
                for minute in [0, 30]:
                    slot_time = current_date.replace(hour=hour, minute=minute, second=0, microsecond=0)
                    if slot_time < datetime.now():
                        continue
                    time_str = slot_time.strftime("%Y-%m-%d %H:%M")
                    day_slots.append({
                        "datetime": time_str,
                        "time": slot_time.strftime("%I:%M %p"),
                        "available": time_str not in booked_times,
                    })

            if day_slots:
                slots.append({
                    "date": current_date.strftime("%Y-%m-%d"),
                    "day": current_date.strftime("%A"),
                    "slots": day_slots,
                })

        return {"specialist_id": specialist_id, "schedule": slots}
    finally:
        cursor.close()
        db.close()


# ── Appointments ───────────────────────────────────────────────────────

@app.post("/appointments")
def book_appointment(req: BookAppointmentRequest, user: dict = Depends(get_current_user)):
    """Book a new appointment."""
    if req.type not in ["online", "onsite"]:
        raise HTTPException(status_code=400, detail="Type must be 'online' or 'onsite'")

    db = get_db()
    cursor = db.cursor(dictionary=True)

    try:
        # Verify specialist exists
        cursor.execute("SELECT specialist_id FROM specialist WHERE specialist_id = %s", (req.specialist_id,))
        if not cursor.fetchone():
            raise HTTPException(status_code=404, detail="Specialist not found")

        # Check for scheduling conflicts
        cursor.execute(
            """SELECT appointment_id FROM appointment
               WHERE specialist_id = %s AND scheduled_at = %s AND status NOT IN ('cancelled')""",
            (req.specialist_id, req.scheduled_at)
        )
        if cursor.fetchone():
            raise HTTPException(status_code=409, detail="This time slot is already booked")

        # Book appointment
        cursor.execute(
            """INSERT INTO appointment (parent_id, payment_id, specialist_id, status, type, comment, scheduled_at)
               VALUES (%s, %s, %s, %s, %s, %s, %s)""",
            (req.parent_id, req.payment_id, req.specialist_id,
             "scheduled", req.type, req.comment, req.scheduled_at)
        )
        appointment_id = cursor.lastrowid
        db.commit()

        return {
            "success": True,
            "appointment_id": appointment_id,
            "status": "scheduled",
            "message": "Appointment booked successfully",
        }
    finally:
        cursor.close()
        db.close()


@app.get("/appointments/parent/{parent_id}")
def get_parent_appointments(
    parent_id: int,
    status: Optional[str] = None,
    upcoming_only: bool = False,
    user: dict = Depends(get_current_user)
):
    """Get all appointments for a parent."""
    db = get_db()
    cursor = db.cursor(dictionary=True)

    try:
        query = """
            SELECT a.appointment_id, a.status, a.type, a.scheduled_at, a.comment, a.report,
                   u.first_name as doctor_first_name, u.last_name as doctor_last_name,
                   s.specialization, c.clinic_name, c.location
            FROM appointment a
            INNER JOIN specialist s ON a.specialist_id = s.specialist_id
            INNER JOIN users u ON s.specialist_id = u.user_id
            INNER JOIN clinic c ON s.clinic_id = c.clinic_id
            WHERE a.parent_id = %s
        """
        params = [parent_id]

        if status:
            query += " AND a.status = %s"
            params.append(status)
        if upcoming_only:
            query += " AND a.scheduled_at >= NOW() AND a.status = 'scheduled'"

        query += " ORDER BY a.scheduled_at DESC"
        cursor.execute(query, params)
        appointments = cursor.fetchall()

        for a in appointments:
            if isinstance(a.get("scheduled_at"), datetime):
                a["scheduled_at"] = a["scheduled_at"].isoformat()

        return {"parent_id": parent_id, "count": len(appointments), "appointments": appointments}
    finally:
        cursor.close()
        db.close()


@app.get("/appointments/specialist/{specialist_id}")
def get_specialist_appointments(
    specialist_id: int,
    status: Optional[str] = None,
    date: Optional[str] = None,
    user: dict = Depends(get_current_user)
):
    """Get all appointments for a specialist."""
    db = get_db()
    cursor = db.cursor(dictionary=True)

    try:
        query = """
            SELECT a.appointment_id, a.status, a.type, a.scheduled_at, a.comment,
                   pu.first_name as parent_first_name, pu.last_name as parent_last_name
            FROM appointment a
            INNER JOIN parent p ON a.parent_id = p.parent_id
            INNER JOIN users pu ON p.parent_id = pu.user_id
            WHERE a.specialist_id = %s
        """
        params = [specialist_id]

        if status:
            query += " AND a.status = %s"
            params.append(status)
        if date:
            query += " AND DATE(a.scheduled_at) = %s"
            params.append(date)

        query += " ORDER BY a.scheduled_at ASC"
        cursor.execute(query, params)
        appointments = cursor.fetchall()

        for a in appointments:
            if isinstance(a.get("scheduled_at"), datetime):
                a["scheduled_at"] = a["scheduled_at"].isoformat()

        return {"specialist_id": specialist_id, "count": len(appointments), "appointments": appointments}
    finally:
        cursor.close()
        db.close()


@app.get("/appointments/{appointment_id}")
def get_appointment(appointment_id: int, user: dict = Depends(get_current_user)):
    """Get detailed appointment information."""
    db = get_db()
    cursor = db.cursor(dictionary=True)

    try:
        cursor.execute(
            """SELECT a.*,
                      u.first_name as doctor_first_name, u.last_name as doctor_last_name,
                      s.specialization, s.experience_years,
                      c.clinic_name, c.location as clinic_location,
                      pu.first_name as parent_first_name, pu.last_name as parent_last_name
               FROM appointment a
               INNER JOIN specialist s ON a.specialist_id = s.specialist_id
               INNER JOIN users u ON s.specialist_id = u.user_id
               INNER JOIN clinic c ON s.clinic_id = c.clinic_id
               INNER JOIN parent p ON a.parent_id = p.parent_id
               INNER JOIN users pu ON p.parent_id = pu.user_id
               WHERE a.appointment_id = %s""",
            (appointment_id,)
        )
        appointment = cursor.fetchone()

        if not appointment:
            raise HTTPException(status_code=404, detail="Appointment not found")

        if isinstance(appointment.get("scheduled_at"), datetime):
            appointment["scheduled_at"] = appointment["scheduled_at"].isoformat()

        return appointment
    finally:
        cursor.close()
        db.close()


@app.put("/appointments/{appointment_id}")
def update_appointment(appointment_id: int, req: UpdateAppointmentRequest, user: dict = Depends(get_current_user)):
    """Reschedule or update an appointment."""
    db = get_db()
    cursor = db.cursor(dictionary=True)

    try:
        # Verify appointment exists and is not cancelled/completed
        cursor.execute(
            "SELECT status, specialist_id FROM appointment WHERE appointment_id = %s",
            (appointment_id,)
        )
        appt = cursor.fetchone()
        if not appt:
            raise HTTPException(status_code=404, detail="Appointment not found")
        if appt["status"] in ("cancelled", "completed"):
            raise HTTPException(status_code=400, detail=f"Cannot update a {appt['status']} appointment")

        updates = []
        values = []

        if req.scheduled_at:
            # Check for conflicts
            cursor.execute(
                """SELECT appointment_id FROM appointment
                   WHERE specialist_id = %s AND scheduled_at = %s
                   AND appointment_id != %s AND status NOT IN ('cancelled')""",
                (appt["specialist_id"], req.scheduled_at, appointment_id)
            )
            if cursor.fetchone():
                raise HTTPException(status_code=409, detail="New time slot is already booked")
            updates.append("scheduled_at = %s")
            values.append(req.scheduled_at)

        if req.type:
            if req.type not in ["online", "onsite"]:
                raise HTTPException(status_code=400, detail="Type must be 'online' or 'onsite'")
            updates.append("type = %s")
            values.append(req.type)

        if req.comment is not None:
            updates.append("comment = %s")
            values.append(req.comment)

        if not updates:
            raise HTTPException(status_code=400, detail="No fields to update")

        updates.append("status = 'rescheduled'")
        values.append(appointment_id)

        cursor.execute(
            f"UPDATE appointment SET {', '.join(updates)} WHERE appointment_id = %s",
            values
        )
        db.commit()

        return {"success": True, "message": "Appointment updated"}
    finally:
        cursor.close()
        db.close()


@app.put("/appointments/{appointment_id}/cancel")
def cancel_appointment(appointment_id: int, user: dict = Depends(get_current_user)):
    """Cancel an appointment."""
    db = get_db()
    cursor = db.cursor()

    try:
        cursor.execute(
            "UPDATE appointment SET status = 'cancelled' WHERE appointment_id = %s AND status NOT IN ('completed', 'cancelled')",
            (appointment_id,)
        )
        db.commit()

        if cursor.rowcount == 0:
            raise HTTPException(status_code=400, detail="Cannot cancel this appointment")

        return {"success": True, "message": "Appointment cancelled"}
    finally:
        cursor.close()
        db.close()


@app.put("/appointments/{appointment_id}/complete")
def complete_appointment(appointment_id: int, req: CompleteAppointmentRequest, user: dict = Depends(get_current_user)):
    """Mark an appointment as completed (specialist action)."""
    db = get_db()
    cursor = db.cursor()

    try:
        updates = ["status = 'completed'"]
        values = []

        if req.report:
            updates.append("report = %s")
            values.append(req.report)
        if req.comment:
            updates.append("comment = %s")
            values.append(req.comment)

        values.append(appointment_id)
        cursor.execute(
            f"UPDATE appointment SET {', '.join(updates)} WHERE appointment_id = %s AND status NOT IN ('cancelled')",
            values
        )
        db.commit()

        if cursor.rowcount == 0:
            raise HTTPException(status_code=400, detail="Cannot complete this appointment")

        return {"success": True, "message": "Appointment marked as completed"}
    finally:
        cursor.close()
        db.close()


@app.post("/appointments/{appointment_id}/feedback")
def submit_feedback(appointment_id: int, req: FeedbackRequest, user: dict = Depends(get_current_user)):
    """Submit feedback for a completed appointment."""
    if req.rating < 1 or req.rating > 5:
        raise HTTPException(status_code=400, detail="Rating must be between 1 and 5")

    db = get_db()
    cursor = db.cursor(dictionary=True)

    try:
        # Verify appointment is completed
        cursor.execute(
            "SELECT status FROM appointment WHERE appointment_id = %s",
            (appointment_id,)
        )
        appt = cursor.fetchone()
        if not appt:
            raise HTTPException(status_code=404, detail="Appointment not found")
        if appt["status"] != "completed":
            raise HTTPException(status_code=400, detail="Can only submit feedback for completed appointments")

        cursor.execute(
            """INSERT INTO feedback (parent_id, specialist_id, content, rating)
               VALUES (%s, %s, %s, %s)""",
            (req.parent_id, req.specialist_id, req.content, req.rating)
        )
        db.commit()

        return {
            "success": True,
            "feedback_id": cursor.lastrowid,
            "message": "Feedback submitted. Thank you!",
        }
    finally:
        cursor.close()
        db.close()
