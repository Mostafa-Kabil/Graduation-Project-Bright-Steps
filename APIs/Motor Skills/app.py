"""
Bright Steps – AI-Powered Motor Skills Behavior Generator
Generates personalized behavior checklists based on child's age, growth metrics, and speech analysis.
"""
from fastapi import FastAPI, HTTPException
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel
from typing import Optional, List, Dict
import mysql.connector
import os
from dotenv import load_dotenv

load_dotenv(os.path.join(os.path.dirname(__file__), '..', '..', '.env'))

app = FastAPI(
    title="Bright Steps Motor Skills AI API",
    description="AI-powered behavior checklist generation for child motor development",
    version="1.0.0"
)

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

# Database connection
def get_db():
    try:
        conn = mysql.connector.connect(
            host=os.getenv("DB_HOST", "localhost"),
            user=os.getenv("DB_USER", "root"),
            password=os.getenv("DB_PASSWORD", ""),
            database=os.getenv("DB_NAME", "grad"),
            charset="utf8mb4"
        )
        return conn
    except mysql.connector.Error as e:
        raise HTTPException(status_code=500, detail=f"Database connection failed: {str(e)}")


# Helper functions for database operations
def ensure_category_exists(conn, category_name: str, category_type: str, category_description: str) -> int:
    """Ensure category exists in database, return category_id."""
    cursor = conn.cursor(dictionary=True)
    cursor.execute("SELECT category_id FROM behavior_category WHERE category_name = %s", (category_name,))
    result = cursor.fetchone()

    if result:
        category_id = result['category_id']
    else:
        cursor.execute(
            "INSERT INTO behavior_category (category_name, category_type, category_description) VALUES (%s, %s, %s)",
            (category_name, category_type, category_description)
        )
        conn.commit()
        category_id = cursor.lastrowid

    cursor.close()
    return category_id


def ensure_behavior_exists(conn, category_id: int, behavior_details: str, behavior_type: str = 'milestone', indicator: str = 'AI-generated') -> int:
    """Ensure behavior exists in database, return behavior_id."""
    cursor = conn.cursor(dictionary=True)
    cursor.execute("SELECT behavior_id FROM behavior WHERE behavior_details = %s AND category_id = %s", (behavior_details, category_id))
    result = cursor.fetchone()

    if result:
        behavior_id = result['behavior_id']
    else:
        cursor.execute(
            "INSERT INTO behavior (category_id, behavior_type, behavior_details, indicator) VALUES (%s, %s, %s, %s)",
            (category_id, behavior_type, behavior_details, indicator)
        )
        conn.commit()
        behavior_id = cursor.lastrowid

    cursor.close()
    return behavior_id

# Request model
class ChildMetrics(BaseModel):
    child_id: int
    age_months: int
    weight: Optional[float] = None
    height: Optional[float] = None
    head_circumference: Optional[float] = None
    speech_vocabulary: Optional[int] = None
    speech_clarity: Optional[int] = None
    condition: Optional[str] = None  # e.g., "on_track", "speech_delay", "motor_delay"

# AI-generated behavior database by age range and category
# 4 Developmental Pillars: Motor Skills, Attention, Communication, Social Skills
BEHAVIOR_DATABASE = {
    "gross_motor": {
        "0-3": [
            {"behavior": "Lifts head when on tummy", "typical_age": "0-2 months"},
            {"behavior": "Pushes up on arms during tummy time", "typical_age": "2-3 months"},
            {"behavior": "Holds head steady without support", "typical_age": "3-4 months"},
            {"behavior": "Rolls from tummy to back", "typical_age": "3-4 months"},
        ],
        "4-7": [
            {"behavior": "Rolls over both ways", "typical_age": "4-6 months"},
            {"behavior": "Sits without support", "typical_age": "5-7 months"},
            {"behavior": "Bears weight on legs when held", "typical_age": "5-7 months"},
            {"behavior": "Begins to crawl", "typical_age": "6-8 months"},
        ],
        "8-11": [
            {"behavior": "Crawls on hands and knees", "typical_age": "7-9 months"},
            {"behavior": "Pulls to standing position", "typical_age": "8-10 months"},
            {"behavior": "Cruises along furniture", "typical_age": "9-11 months"},
            {"behavior": "Stands alone momentarily", "typical_age": "10-12 months"},
        ],
        "12-17": [
            {"behavior": "Walks independently", "typical_age": "12-15 months"},
            {"behavior": "Squats to pick up toys", "typical_age": "13-15 months"},
            {"behavior": "Begins to run stiffly", "typical_age": "15-18 months"},
            {"behavior": "Walks up stairs with help", "typical_age": "15-18 months"},
        ],
        "18-23": [
            {"behavior": "Runs with good coordination", "typical_age": "18-21 months"},
            {"behavior": "Kicks a ball forward", "typical_age": "18-22 months"},
            {"behavior": "Jumps with both feet", "typical_age": "20-24 months"},
            {"behavior": "Walks up stairs holding rail", "typical_age": "18-24 months"},
        ],
        "24-36": [
            {"behavior": "Hops on one foot briefly", "typical_age": "24-30 months"},
            {"behavior": "Climbs well on playground equipment", "typical_age": "24-30 months"},
            {"behavior": "Throws ball overhand", "typical_age": "24-30 months"},
            {"behavior": "Pedals tricycle", "typical_age": "24-36 months"},
            {"behavior": "Balances on one foot 2-3 seconds", "typical_age": "30-36 months"},
        ],
    },
    "fine_motor": {
        "0-3": [
            {"behavior": "Opens and shuts hands", "typical_age": "0-2 months"},
            {"behavior": "Brings hands to mouth", "typical_age": "1-3 months"},
            {"behavior": "Grasps objects placed in hand", "typical_age": "2-4 months"},
            {"behavior": "Reaches for toys", "typical_age": "3-4 months"},
        ],
        "4-7": [
            {"behavior": "Transfers objects between hands", "typical_age": "4-6 months"},
            {"behavior": "Rakes at small objects", "typical_age": "4-5 months"},
            {"behavior": "Bangs objects together", "typical_age": "6-7 months"},
            {"behavior": "Uses raking grasp", "typical_age": "5-6 months"},
        ],
        "8-11": [
            {"behavior": "Uses pincer grasp thumb and finger", "typical_age": "8-10 months"},
            {"behavior": "Points with index finger", "typical_age": "9-11 months"},
            {"behavior": "Drops objects intentionally", "typical_age": "8-10 months"},
            {"behavior": "Claps hands together", "typical_age": "9-10 months"},
        ],
        "12-17": [
            {"behavior": "Stacks 2-3 blocks", "typical_age": "12-15 months"},
            {"behavior": "Turns pages of board book", "typical_age": "12-15 months"},
            {"behavior": "Scribbles with crayon", "typical_age": "13-15 months"},
            {"behavior": "Uses spoon with some spilling", "typical_age": "15-18 months"},
        ],
        "18-23": [
            {"behavior": "Stacks 4-6 blocks", "typical_age": "18-21 months"},
            {"behavior": "Turns doorknobs", "typical_age": "18-22 months"},
            {"behavior": "Strings large beads", "typical_age": "20-24 months"},
            {"behavior": "Uses spoon with minimal spilling", "typical_age": "21-24 months"},
        ],
        "24-36": [
            {"behavior": "Draws circles and lines", "typical_age": "24-30 months"},
            {"behavior": "Uses child-safe scissors", "typical_age": "24-30 months"},
            {"behavior": "Copies simple letters", "typical_age": "30-36 months"},
            {"behavior": "Builds tower of 6+ blocks", "typical_age": "24-30 months"},
            {"behavior": "Buttons large buttons", "typical_age": "30-36 months"},
        ],
    },
    "attention": {
        "0-3": [
            {"behavior": "Looks at faces when feeding", "typical_age": "0-2 months"},
            {"behavior": "Maintains eye contact briefly", "typical_age": "1-3 months"},
            {"behavior": "Watches moving objects", "typical_age": "2-3 months"},
            {"behavior": "Turns toward familiar voices", "typical_age": "2-4 months"},
        ],
        "4-7": [
            {"behavior": "Focuses on toy for 1-2 minutes", "typical_age": "4-6 months"},
            {"behavior": "Looks when name is called", "typical_age": "5-7 months"},
            {"behavior": "Pays attention to surroundings", "typical_age": "6-8 months"},
            {"behavior": "Shows preference for familiar people", "typical_age": "6-8 months"},
        ],
        "8-11": [
            {"behavior": "Attends to book for 2-3 minutes", "typical_age": "8-10 months"},
            {"behavior": "Responds to simple requests", "typical_age": "9-11 months"},
            {"behavior": "Watches caregiver during activities", "typical_age": "9-11 months"},
            {"behavior": "Shows joint attention (looks where you point)", "typical_age": "9-12 months"},
        ],
        "12-17": [
            {"behavior": "Listens to short stories", "typical_age": "12-15 months"},
            {"behavior": "Focuses on activity for 3-5 minutes", "typical_age": "13-15 months"},
            {"behavior": "Follows one-step directions", "typical_age": "13-15 months"},
            {"behavior": "Notices when environment changes", "typical_age": "15-18 months"},
        ],
        "18-23": [
            {"behavior": "Attends to preferred activity 5-8 minutes", "typical_age": "18-21 months"},
            {"behavior": "Listens to entire short book", "typical_age": "18-22 months"},
            {"behavior": "Waits briefly for turn", "typical_age": "20-24 months"},
            {"behavior": "Completes simple puzzles", "typical_age": "20-24 months"},
        ],
        "24-36": [
            {"behavior": "Focuses on activity 8-10 minutes", "typical_age": "24-30 months"},
            {"behavior": "Follows two-step directions", "typical_age": "24-30 months"},
            {"behavior": "Waits turn with prompting", "typical_age": "27-33 months"},
            {"behavior": "Completes 10+ piece puzzles", "typical_age": "30-36 months"},
            {"behavior": "Listens to songs all the way through", "typical_age": "24-30 months"},
        ],
    },
    "communication": {
        "0-3": [
            {"behavior": "Makes eye contact during feeding", "typical_age": "0-2 months"},
            {"behavior": "Coos and makes vowel sounds", "typical_age": "1-3 months"},
            {"behavior": "Smiles socially", "typical_age": "2-3 months"},
            {"behavior": "Responds to caregiver voice", "typical_age": "2-4 months"},
        ],
        "4-7": [
            {"behavior": "Babbles with consonants", "typical_age": "4-6 months"},
            {"behavior": "Responds to own name", "typical_age": "5-7 months"},
            {"behavior": "Uses sounds to get attention", "typical_age": "6-8 months"},
            {"behavior": "Imitates sounds", "typical_age": "6-8 months"},
        ],
        "8-11": [
            {"behavior": "Uses mama/dada specifically", "typical_age": "8-10 months"},
            {"behavior": "Points to communicate needs", "typical_age": "9-11 months"},
            {"behavior": "Imitates gestures (wave bye-bye)", "typical_age": "9-11 months"},
            {"behavior": "Understands no", "typical_age": "9-12 months"},
        ],
        "12-17": [
            {"behavior": "Uses 3-10 words meaningfully", "typical_age": "12-15 months"},
            {"behavior": "Follows simple directions", "typical_age": "13-15 months"},
            {"behavior": "Shakes head yes/no", "typical_age": "13-15 months"},
            {"behavior": "Points to show you something", "typical_age": "15-18 months"},
        ],
        "18-23": [
            {"behavior": "Uses 10-50 words", "typical_age": "18-21 months"},
            {"behavior": "Combines two words", "typical_age": "18-22 months"},
            {"behavior": "Names familiar objects", "typical_age": "18-22 months"},
            {"behavior": "Follows two-step related directions", "typical_age": "21-24 months"},
        ],
        "24-36": [
            {"behavior": "Uses 2-3 word sentences", "typical_age": "24-30 months"},
            {"behavior": "Asks what/where questions", "typical_age": "24-30 months"},
            {"behavior": "Uses pronouns (I, me, you)", "typical_age": "24-30 months"},
            {"behavior": "Speech is 50-75% understandable", "typical_age": "30-36 months"},
            {"behavior": "Knows 200+ words", "typical_age": "30-36 months"},
        ],
    },
    "social_skills": {
        "0-3": [
            {"behavior": "Recognizes primary caregiver", "typical_age": "0-2 months"},
            {"behavior": "Smiles in response to smiles", "typical_age": "2-3 months"},
            {"behavior": "Enjoys being held", "typical_age": "2-4 months"},
            {"behavior": "Calms when picked up", "typical_age": "3-4 months"},
        ],
        "4-7": [
            {"behavior": "Distinguishes familiar vs strangers", "typical_age": "4-6 months"},
            {"behavior": "Enjoys social games (peek-a-boo)", "typical_age": "5-7 months"},
            {"behavior": "Shows excitement around others", "typical_age": "6-8 months"},
            {"behavior": "Reaches to be picked up", "typical_age": "6-8 months"},
        ],
        "8-11": [
            {"behavior": "Shows stranger anxiety", "typical_age": "8-10 months"},
            {"behavior": "Plays interactive games", "typical_age": "9-11 months"},
            {"behavior": "Shows preferences for people", "typical_age": "9-11 months"},
            {"behavior": "May cry when parent leaves", "typical_age": "10-12 months"},
        ],
        "12-17": [
            {"behavior": "Shows affection to familiar people", "typical_age": "12-15 months"},
            {"behavior": "Imitates adult actions", "typical_age": "13-15 months"},
            {"behavior": "Plays alongside other children", "typical_age": "13-15 months"},
            {"behavior": "Shows empathy (cries when others cry)", "typical_age": "15-18 months"},
        ],
        "18-23": [
            {"behavior": "Shows parallel play", "typical_age": "18-21 months"},
            {"behavior": "Shows ownership (mine!)", "typical_age": "18-22 months"},
            {"behavior": "Helps with simple tasks", "typical_age": "18-22 months"},
            {"behavior": "Shows concern for others", "typical_age": "21-24 months"},
        ],
        "24-36": [
            {"behavior": "Engages in cooperative play", "typical_age": "24-30 months"},
            {"behavior": "Takes turns with assistance", "typical_age": "24-30 months"},
            {"behavior": "Shows imaginative play", "typical_age": "27-33 months"},
            {"behavior": "Understands sharing concept", "typical_age": "30-36 months"},
            {"behavior": "Expresses emotions verbally", "typical_age": "30-36 months"},
        ],
    },
}

def get_age_range(age_months: int) -> str:
    """Get the appropriate age range key for behavior lookup."""
    if age_months <= 3:
        return "0-3"
    elif age_months <= 7:
        return "4-7"
    elif age_months <= 11:
        return "8-11"
    elif age_months <= 17:
        return "12-17"
    elif age_months <= 23:
        return "18-23"
    else:
        return "24-36"

def generate_ai_feedback(age_months: int, weight: Optional[float], height: Optional[float],
                         head_circ: Optional[float], speech_vocab: Optional[int],
                         exhibited_behaviors: List[Dict]) -> str:
    """Generate AI-powered feedback based on child's metrics and exhibited behaviors."""

    feedback_parts = []
    age_range = get_age_range(age_months)

    # Growth assessment
    if weight and height:
        bmi = weight / ((height/100) ** 2)
        if 14 <= bmi <= 18:
            feedback_parts.append(f"Physical growth is on track with healthy weight-to-height ratio (BMI: {bmi:.1f}).")
        elif bmi < 14:
            feedback_parts.append("Consider discussing healthy weight gain strategies with your pediatrician.")
        elif bmi > 18:
            feedback_parts.append("Monitor activity levels and nutrition to maintain healthy growth patterns.")

    # 4-Pillar Development Assessment
    pillar_scores = {}
    pillar_names = {
        'motor_skills': 'Motor Skills',
        'attention': 'Attention',
        'communication': 'Communication',
        'social_skills': 'Social Skills'
    }

    for pillar_key, pillar_name in pillar_names.items():
        exhibited = sum(1 for b in exhibited_behaviors
                       if b.get('category_type') == pillar_key and b.get('is_exhibited'))
        expected = len(BEHAVIOR_DATABASE.get(pillar_key, {}).get(age_range, []))
        if pillar_key == 'motor_skills':
            # Combine gross and fine motor for expected count
            expected = (len(BEHAVIOR_DATABASE.get("gross_motor", {}).get(age_range, [])) +
                       len(BEHAVIOR_DATABASE.get("fine_motor", {}).get(age_range, [])))
        pillar_scores[pillar_key] = (exhibited / expected * 100) if expected > 0 else 0

    # Motor Skills Feedback
    motor_score = pillar_scores.get('motor_skills', 0)
    if motor_score >= 75:
        feedback_parts.append("🦵 Excellent motor development! Your child is meeting most age-expected physical milestones.")
    elif motor_score >= 50:
        feedback_parts.append("🦵 Good progress on motor skills. Continue encouraging daily physical activities and play.")
    else:
        feedback_parts.append("🦵 Consider adding more motor skill activities. Consult your pediatrician if you have concerns.")

    # Attention Feedback
    attention_score = pillar_scores.get('attention', 0)
    if attention_score >= 75:
        feedback_parts.append("🧠 Strong attention span and focus for age. Your child engages well with activities.")
    elif attention_score >= 50:
        feedback_parts.append("🧠 Attention skills are developing. Try short, engaging activities to build concentration.")
    else:
        feedback_parts.append("🧠 Consider activities that build focus gradually. Short, repeated sessions work best.")

    # Communication Feedback
    comm_score = pillar_scores.get('communication', 0)
    if speech_vocab is not None:
        expected_vocab = min(50, age_months * 3)
        if speech_vocab >= expected_vocab:
            feedback_parts.append("💬 Advanced speech development! Support with rich language exposure and reading.")
        elif speech_vocab < expected_vocab * 0.5:
            feedback_parts.append("💬 Speech and language may benefit from early intervention assessment.")
        elif comm_score >= 75:
            feedback_parts.append("💬 Communication skills are on track. Keep talking, reading, and singing together!")
    elif comm_score >= 75:
        feedback_parts.append("💬 Communication skills are on track. Keep talking, reading, and singing together!")
    elif comm_score >= 50:
        feedback_parts.append("💬 Communication is developing. Encourage by narrating daily activities and reading together.")
    else:
        feedback_parts.append("💬 Consider speech-language assessment if communication concerns persist.")

    # Social Skills Feedback
    social_score = pillar_scores.get('social_skills', 0)
    if social_score >= 75:
        feedback_parts.append("🤝 Wonderful social development! Your child shows strong interaction skills.")
    elif social_score >= 50:
        feedback_parts.append("🤝 Social skills are developing. Playdates and group activities help build confidence.")
    else:
        feedback_parts.append("🤝 Consider more social interaction opportunities. Parallel play is a normal early stage.")

    # Head circumference note
    if head_circ:
        feedback_parts.append("Head circumference is being tracked - consistent growth along a percentile curve is what matters most.")

    return " ".join(feedback_parts) if feedback_parts else "Continue monitoring development with regular pediatric check-ups. Every child develops at their own pace."

@app.post("/generate-behavior-checklist")
async def generate_behavior_checklist(metrics: ChildMetrics):
    """
    Generate personalized behavior checklist based on child's age and metrics.
    Returns categories with behaviors tailored to the child's developmental stage.
    Also inserts categories and behaviors into the database if they don't exist.

    4 Developmental Pillars: 🦵 Motor Skills, 🧠 Attention, 💬 Communication, 🤝 Social Skills

    Payload expects:
    - child_id: int
    - age_months: int
    - weight: Optional[float]
    - height: Optional[float]
    - head_circumference: Optional[float]
    - speech_vocabulary: Optional[int]
    - condition: Optional[str]
    """
    conn = None
    try:
        conn = get_db()
        age_range = get_age_range(metrics.age_months)

        # Get behaviors for age range - 4 Developmental Pillars
        categories = []

        # 🦵 Motor Skills Category (combines gross and fine motor)
        gross_behaviors = BEHAVIOR_DATABASE.get("gross_motor", {}).get(age_range, [])
        fine_behaviors = BEHAVIOR_DATABASE.get("fine_motor", {}).get(age_range, [])
        motor_behaviors = gross_behaviors + fine_behaviors
        categories.append({
            "category_name": "🦵 Motor Skills",
            "category_type": "motor_skills",
            "category_description": "Physical development including gross motor (walking, running, balance) and fine motor (grasping, drawing, coordination)",
            "behaviors": [{"behavior_details": b["behavior"], "typical_age": b["typical_age"]} for b in motor_behaviors]
        })

        # 🧠 Attention Category
        attention_behaviors = BEHAVIOR_DATABASE.get("attention", {}).get(age_range, [])
        categories.append({
            "category_name": "🧠 Attention",
            "category_type": "attention",
            "category_description": "Focus, concentration, and ability to sustain attention on activities and follow directions",
            "behaviors": [{"behavior_details": b["behavior"], "typical_age": b["typical_age"]} for b in attention_behaviors]
        })

        # 💬 Communication Category
        communication_behaviors = BEHAVIOR_DATABASE.get("communication", {}).get(age_range, [])
        categories.append({
            "category_name": "💬 Communication",
            "category_type": "communication",
            "category_description": "Language development including verbal expression, understanding, and non-verbal communication",
            "behaviors": [{"behavior_details": b["behavior"], "typical_age": b["typical_age"]} for b in communication_behaviors]
        })

        # 🤝 Social Skills Category
        social_behaviors = BEHAVIOR_DATABASE.get("social_skills", {}).get(age_range, [])
        categories.append({
            "category_name": "🤝 Social Skills",
            "category_type": "social_skills",
            "category_description": "Social interaction, emotional development, play skills, and relationship building",
            "behaviors": [{"behavior_details": b["behavior"], "typical_age": b["typical_age"]} for b in social_behaviors]
        })

        # Filter based on condition if provided
        if metrics.condition == "developmental_delay":
            # Include behaviors from younger age range for delayed children
            younger_range = get_age_range(max(0, metrics.age_months - 3))
            for cat in categories:
                type_mapping = {
                    "🦵 Motor Skills": ["gross_motor", "fine_motor"],
                    "🧠 Attention": ["attention"],
                    "💬 Communication": ["communication"],
                    "🤝 Social Skills": ["social_skills"]
                }
                types_to_check = type_mapping.get(cat["category_name"], [cat["category_type"].lower()])
                for type_key in types_to_check:
                    younger_behaviors = BEHAVIOR_DATABASE.get(type_key, {}).get(younger_range, [])
                    cat["behaviors"].extend([{"behavior_details": b["behavior"], "typical_age": b["typical_age"]} for b in younger_behaviors])

        # Insert categories and behaviors into database
        inserted_categories = 0
        inserted_behaviors = 0

        for cat in categories:
            category_id = ensure_category_exists(
                conn,
                cat["category_name"],
                cat["category_type"],
                cat["category_description"]
            )
            inserted_categories += 1

            for beh in cat["behaviors"]:
                behavior_id = ensure_behavior_exists(
                    conn,
                    category_id,
                    beh["behavior_details"],
                    behavior_type='milestone',
                    indicator=beh.get("typical_age", "AI-generated")
                )
                inserted_behaviors += 1

        return {
            "success": True,
            "child_id": metrics.child_id,
            "age_months": metrics.age_months,
            "age_range": age_range,
            "categories": categories,
            "inserted_categories": inserted_categories,
            "inserted_behaviors": inserted_behaviors,
            "message": f"Generated {sum(len(c['behaviors']) for c in categories)} age-appropriate behaviors"
        }

    except Exception as e:
        raise HTTPException(status_code=500, detail=f"AI generation error: {str(e)}")

@app.post("/generate-feedback")
async def generate_feedback(
    child_id: int,
    age_months: int,
    weight: Optional[float] = None,
    height: Optional[float] = None,
    head_circumference: Optional[float] = None,
    speech_vocabulary: Optional[int] = None,
    exhibited_behaviors: Optional[List[Dict]] = None
):
    """Generate AI-powered feedback based on child's complete profile."""
    try:
        feedback = generate_ai_feedback(
            age_months=age_months,
            weight=weight,
            height=height,
            head_circ=head_circumference,
            speech_vocab=speech_vocabulary,
            exhibited_behaviors=exhibited_behaviors or []
        )

        return {
            "success": True,
            "child_id": child_id,
            "feedback": feedback,
            "generated_at": str(__import__('datetime').datetime.now())
        }
    except Exception as e:
        raise HTTPException(status_code=500, detail=f"Feedback generation error: {str(e)}")

@app.get("/")
def root():
    return {
        "message": "Bright Steps Motor Skills AI API is running!",
        "endpoints": {
            "POST /generate-behavior-checklist": "Generate personalized behavior checklist",
            "POST /generate-feedback": "Generate AI-powered developmental feedback"
        }
    }

if __name__ == "__main__":
    import uvicorn
    uvicorn.run(app, host="0.0.0.0", port=8003)
