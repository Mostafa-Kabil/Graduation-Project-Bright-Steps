from fastapi import FastAPI
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel
from typing import Optional
import os
from datetime import datetime
from dotenv import load_dotenv
from knowledge_base import find_best_response

load_dotenv(os.path.join(os.path.dirname(__file__), '..', '..', '.env'))

app = FastAPI(
    title="Bright Steps Chatbot API",
    description="AI-powered parenting assistant with child development knowledge base",
    version="1.0.0"
)

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)


# ── Models ─────────────────────────────────────────────────────────────

class ChatRequest(BaseModel):
    message: str
    child_age_months: Optional[int] = None  # Optional context

class ChatResponse(BaseModel):
    reply: str
    timestamp: str


# ── Routes ─────────────────────────────────────────────────────────────

@app.get("/")
def root():
    return {
        "message": "Bright Steps Chatbot API is running! 🤖",
        "endpoints": {
            "POST /chat": "Send a message and get a response",
            "GET  /topics": "List available topics",
        }
    }


@app.post("/chat", response_model=ChatResponse)
def chat(req: ChatRequest):
    """Send a message to the chatbot and get a helpful response."""
    if not req.message.strip():
        return ChatResponse(
            reply="Please type a question and I'll do my best to help! 😊",
            timestamp=datetime.now().isoformat()
        )

    # Get response from knowledge base
    reply = find_best_response(req.message)

    # Add age-specific context if provided
    if req.child_age_months is not None and req.child_age_months > 0:
        age_note = f"\n\n📌 *Based on your child's age ({req.child_age_months} months), "
        if req.child_age_months <= 6:
            age_note += "focus on tummy time, sensory play, and responding to coos and babbles.*"
        elif req.child_age_months <= 12:
            age_note += "encourage crawling, object exploration, and simple word repetition.*"
        elif req.child_age_months <= 24:
            age_note += "support first steps, expand vocabulary with narration, and introduce self-feeding.*"
        elif req.child_age_months <= 36:
            age_note += "encourage running, simple sentences, and pretend play.*"
        elif req.child_age_months <= 60:
            age_note += "practice counting, storytelling, and social skills with peers.*"
        else:
            age_note += "support reading readiness, creative play, and emotional expression.*"
        reply += age_note

    return ChatResponse(
        reply=reply,
        timestamp=datetime.now().isoformat()
    )


@app.get("/topics")
def list_topics():
    """List all available topics the chatbot can help with."""
    return {
        "topics": [
            {"icon": "🏃", "name": "Motor Skills", "example": "When should my baby start walking?"},
            {"icon": "🗣️", "name": "Speech & Language", "example": "How many words should a 2-year-old say?"},
            {"icon": "🧠", "name": "Cognitive Development", "example": "When do babies understand object permanence?"},
            {"icon": "🍼", "name": "Feeding & Nutrition", "example": "When should I start solid foods?"},
            {"icon": "😴", "name": "Sleep", "example": "How much sleep does my toddler need?"},
            {"icon": "📊", "name": "Growth Tracking", "example": "What do growth percentiles mean?"},
            {"icon": "❤️", "name": "Social-Emotional", "example": "Is stranger anxiety normal?"},
            {"icon": "🚽", "name": "Self-Care", "example": "When to start potty training?"},
            {"icon": "🛡️", "name": "Child Safety", "example": "How to childproof my home?"},
            {"icon": "📅", "name": "Appointments", "example": "When are well-child checkups?"},
            {"icon": "🌟", "name": "Bright Steps Features", "example": "How do I use the growth chart?"},
        ]
    }
