from fastapi import FastAPI, HTTPException
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel
from typing import Optional
import json
import os
import math

app = FastAPI(
    title="Bright Steps WHO Growth Comparison API",
    description="Compare child growth measurements against WHO Child Growth Standards",
    version="1.0.0"
)

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

# ── Load WHO data ─────────────────────────────────────────────────────
DATA_PATH = os.path.join(os.path.dirname(__file__), "who_data.json")
with open(DATA_PATH, "r") as f:
    WHO_DATA = json.load(f)


# ── Helpers ────────────────────────────────────────────────────────────

def find_nearest_age(age_months: int, available_ages: list) -> str:
    """Find the nearest available age in WHO data."""
    ages = [int(a) for a in available_ages]
    nearest = min(ages, key=lambda x: abs(x - age_months))
    return str(nearest)


def calculate_z_score(value: float, median: float, sd_neg1: float, sd_pos1: float) -> float:
    """Calculate approximate z-score using WHO SD values."""
    if value >= median:
        sd = sd_pos1 - median
    else:
        sd = median - sd_neg1

    if sd == 0:
        return 0.0

    return round((value - median) / sd, 2)


def z_score_to_percentile(z: float) -> float:
    """Convert z-score to percentile using the cumulative distribution function approximation."""
    return round(0.5 * (1 + math.erf(z / math.sqrt(2))) * 100, 1)


def get_status(z_score: float) -> dict:
    """Get traffic-light status and label from z-score."""
    if z_score < -3:
        return {"color": "red", "label": "Severely below normal", "concern": "high"}
    elif z_score < -2:
        return {"color": "red", "label": "Below normal", "concern": "high"}
    elif z_score < -1:
        return {"color": "yellow", "label": "Slightly below average", "concern": "moderate"}
    elif z_score <= 1:
        return {"color": "green", "label": "Normal range", "concern": "low"}
    elif z_score <= 2:
        return {"color": "yellow", "label": "Slightly above average", "concern": "moderate"}
    elif z_score <= 3:
        return {"color": "yellow", "label": "Above normal", "concern": "moderate"}
    else:
        return {"color": "red", "label": "Significantly above normal", "concern": "high"}


def compare_measurement(value: float, gender: str, age_months: int, metric: str) -> dict:
    """Compare a single measurement against WHO standards."""
    if metric not in WHO_DATA:
        return None

    gender_key = "boys" if gender.lower() in ["male", "boy", "boys", "m"] else "girls"
    age_data = WHO_DATA[metric].get(gender_key, {})

    if not age_data:
        return None

    nearest_age = find_nearest_age(age_months, age_data.keys())
    standards = age_data[nearest_age]

    z = calculate_z_score(value, standards["median"], standards["sd_neg1"], standards["sd_pos1"])
    percentile = z_score_to_percentile(z)
    status = get_status(z)

    return {
        "value": value,
        "metric": metric.replace("_", " ").title(),
        "age_months_used": int(nearest_age),
        "who_median": standards["median"],
        "who_range": {
            "low_normal": standards["sd_neg2"],
            "high_normal": standards["sd_pos2"],
        },
        "z_score": z,
        "percentile": percentile,
        "status": status,
    }


# ── Models ─────────────────────────────────────────────────────────────

class CompareRequest(BaseModel):
    gender: str  # male/female or boy/girl
    age_months: int
    weight: Optional[float] = None  # kg
    height: Optional[float] = None  # cm
    head_circumference: Optional[float] = None  # cm

class PercentileRequest(BaseModel):
    gender: str
    age_months: int
    metric: str  # weight_for_age, height_for_age, head_circumference_for_age
    value: float


# ── Routes ─────────────────────────────────────────────────────────────

@app.get("/")
def root():
    return {
        "message": "Bright Steps WHO Growth Comparison API is running!",
        "endpoints": {
            "POST /compare": "Compare child measurements against WHO standards",
            "GET  /standards/{gender}/{age_months}": "Get WHO standards for age/gender",
            "POST /percentile": "Calculate percentile for a measurement",
            "POST /growth-assessment": "Full growth assessment with recommendations",
        }
    }


@app.post("/compare")
def compare(req: CompareRequest):
    """Compare child's measurements against WHO standards."""
    if req.age_months < 0 or req.age_months > 60:
        raise HTTPException(status_code=400, detail="Age must be between 0 and 60 months")

    results = {}

    if req.weight is not None:
        r = compare_measurement(req.weight, req.gender, req.age_months, "weight_for_age")
        if r:
            results["weight"] = r

    if req.height is not None:
        r = compare_measurement(req.height, req.gender, req.age_months, "height_for_age")
        if r:
            results["height"] = r

    if req.head_circumference is not None:
        r = compare_measurement(req.head_circumference, req.gender, req.age_months, "head_circumference_for_age")
        if r:
            results["head_circumference"] = r

    if not results:
        raise HTTPException(status_code=400, detail="At least one measurement (weight/height/head_circumference) required")

    # Overall status
    statuses = [v["status"]["concern"] for v in results.values()]
    if "high" in statuses:
        overall = {"color": "red", "label": "Needs attention", "recommendation": "Please consult a pediatrician for a professional assessment."}
    elif "moderate" in statuses:
        overall = {"color": "yellow", "label": "Monitor closely", "recommendation": "Keep tracking and consider discussing with your child's doctor at the next visit."}
    else:
        overall = {"color": "green", "label": "On track", "recommendation": "Your child's growth is within the normal range. Keep up the great work!"}

    return {
        "gender": req.gender,
        "age_months": req.age_months,
        "measurements": results,
        "overall_status": overall,
    }


@app.get("/standards/{gender}/{age_months}")
def get_standards(gender: str, age_months: int):
    """Get WHO standard values for a specific age and gender."""
    if age_months < 0 or age_months > 60:
        raise HTTPException(status_code=400, detail="Age must be between 0 and 60 months")

    gender_key = "boys" if gender.lower() in ["male", "boy", "boys", "m"] else "girls"
    result = {}

    for metric in ["weight_for_age", "height_for_age", "head_circumference_for_age"]:
        age_data = WHO_DATA[metric].get(gender_key, {})
        if age_data:
            nearest = find_nearest_age(age_months, age_data.keys())
            result[metric] = {
                "age_months_used": int(nearest),
                "standards": age_data[nearest],
            }

    return {"gender": gender, "age_months": age_months, "standards": result}


@app.post("/percentile")
def get_percentile(req: PercentileRequest):
    """Calculate the percentile rank for a given measurement."""
    valid_metrics = ["weight_for_age", "height_for_age", "head_circumference_for_age"]
    if req.metric not in valid_metrics:
        raise HTTPException(status_code=400, detail=f"Metric must be one of: {valid_metrics}")

    result = compare_measurement(req.value, req.gender, req.age_months, req.metric)
    if not result:
        raise HTTPException(status_code=404, detail="No data available for this combination")

    return {
        "metric": req.metric,
        "value": req.value,
        "percentile": result["percentile"],
        "z_score": result["z_score"],
        "who_median": result["who_median"],
        "status": result["status"],
    }


@app.post("/growth-assessment")
def growth_assessment(req: CompareRequest):
    """Full growth assessment with detailed recommendations."""
    if req.age_months < 0 or req.age_months > 60:
        raise HTTPException(status_code=400, detail="Age must be between 0 and 60 months")

    results = {}
    recommendations = []

    if req.weight is not None:
        r = compare_measurement(req.weight, req.gender, req.age_months, "weight_for_age")
        if r:
            results["weight"] = r
            if r["z_score"] < -2:
                recommendations.append({
                    "area": "Weight",
                    "priority": "high",
                    "message": f"Weight ({req.weight} kg) is below the normal range. Consider consulting a pediatrician about nutrition and feeding."
                })
            elif r["z_score"] > 2:
                recommendations.append({
                    "area": "Weight",
                    "priority": "moderate",
                    "message": f"Weight ({req.weight} kg) is above the expected range. Discuss healthy eating habits with your child's doctor."
                })

    if req.height is not None:
        r = compare_measurement(req.height, req.gender, req.age_months, "height_for_age")
        if r:
            results["height"] = r
            if r["z_score"] < -2:
                recommendations.append({
                    "area": "Height",
                    "priority": "high",
                    "message": f"Height ({req.height} cm) is below the expected range. This could indicate stunting. Consult a pediatrician."
                })

    if req.head_circumference is not None:
        r = compare_measurement(req.head_circumference, req.gender, req.age_months, "head_circumference_for_age")
        if r:
            results["head_circumference"] = r
            if abs(r["z_score"]) > 2:
                recommendations.append({
                    "area": "Head Circumference",
                    "priority": "high",
                    "message": f"Head circumference ({req.head_circumference} cm) is outside the normal range. A medical evaluation is recommended."
                })

    if not recommendations:
        recommendations.append({
            "area": "General",
            "priority": "low",
            "message": "All measurements are within the normal range. Continue monitoring regularly."
        })

    return {
        "gender": req.gender,
        "age_months": req.age_months,
        "measurements": results,
        "recommendations": recommendations,
        "next_check": "Schedule the next growth check in 1-3 months.",
    }
