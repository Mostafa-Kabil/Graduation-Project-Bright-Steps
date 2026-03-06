"""
Bright Steps — Child Development Knowledge Base
Pre-built Q&A pairs organized by topic for the chatbot assistant.
"""

KNOWLEDGE_BASE = {
    "greetings": {
        "keywords": ["hello", "hi", "hey", "good morning", "good evening", "help", "start"],
        "responses": [
            "Hello! 👋 I'm the Bright Steps Assistant. I can help you with:\n\n"
            "🏃 **Motor skills** milestones\n"
            "🗣️ **Speech & language** development\n"
            "🧠 **Cognitive** milestones\n"
            "🍼 **Feeding & nutrition** tips\n"
            "😴 **Sleep** guidance\n"
            "📊 **Growth** tracking help\n"
            "👶 **General** parenting tips\n\n"
            "Just ask me anything about your child's development!"
        ]
    },
    "motor_skills": {
        "keywords": ["motor", "crawl", "walk", "sit", "stand", "roll", "run", "jump", "climb", "balance", "coordination", "movement", "physical"],
        "responses": [
            "**Motor Skills Milestones by Age:**\n\n"
            "🔹 **0-3 months:** Lifts head during tummy time, moves arms and legs\n"
            "🔹 **4-6 months:** Rolls over, reaches for toys, sits with support\n"
            "🔹 **7-9 months:** Sits alone, starts crawling, pulls to stand\n"
            "🔹 **10-12 months:** Stands alone, may take first steps\n"
            "🔹 **13-18 months:** Walks independently, starts climbing\n"
            "🔹 **19-24 months:** Runs, kicks a ball, walks up stairs\n"
            "🔹 **2-3 years:** Jumps, pedals tricycle, catches large ball\n"
            "🔹 **3-5 years:** Hops on one foot, skips, rides bike with training wheels\n\n"
            "💡 **Tip:** Encourage tummy time daily and provide safe spaces for exploring movement!"
        ]
    },
    "speech_language": {
        "keywords": ["speech", "talk", "word", "language", "babble", "sentence", "speak", "communicate", "verbal", "vocabulary", "say", "first word"],
        "responses": [
            "**Speech & Language Milestones:**\n\n"
            "🔹 **0-3 months:** Coos, makes vowel sounds\n"
            "🔹 **4-6 months:** Babbles (ba-ba, da-da), laughs\n"
            "🔹 **7-9 months:** Responds to name, understands 'no'\n"
            "🔹 **10-12 months:** Says first words (mama, dada)\n"
            "🔹 **13-18 months:** Uses 10-20 words, points to objects\n"
            "🔹 **19-24 months:** Combines 2 words, 50+ word vocabulary\n"
            "🔹 **2-3 years:** 3-4 word sentences, asks questions\n"
            "🔹 **3-5 years:** Tells stories, uses past tense, 1000+ words\n\n"
            "💡 **Tips to boost speech:**\n"
            "• Read aloud daily 📚\n"
            "• Narrate your activities\n"
            "• Ask open-ended questions\n"
            "• Sing songs together 🎵"
        ]
    },
    "cognitive": {
        "keywords": ["cognitive", "brain", "think", "learn", "smart", "intelligence", "problem", "solve", "memory", "attention", "focus", "count", "color", "shape"],
        "responses": [
            "**Cognitive Development Milestones:**\n\n"
            "🔹 **0-3 months:** Tracks objects with eyes, recognizes faces\n"
            "🔹 **4-6 months:** Explores with hands and mouth\n"
            "🔹 **7-9 months:** Object permanence (knows hidden toys still exist)\n"
            "🔹 **10-12 months:** Imitates actions, finds hidden objects\n"
            "🔹 **13-18 months:** Simple problem-solving, stacks blocks\n"
            "🔹 **19-24 months:** Sorts shapes, begins pretend play\n"
            "🔹 **2-3 years:** Counts to 10, knows some colors\n"
            "🔹 **3-5 years:** Understands time concepts, writes name\n\n"
            "💡 **Boost cognitive development with:**\n"
            "• Puzzles and building blocks 🧩\n"
            "• Sorting games (by color, shape, size)\n"
            "• Counting during daily activities\n"
            "• Reading interactive books"
        ]
    },
    "feeding_nutrition": {
        "keywords": ["feed", "food", "eat", "nutrition", "solid", "breast", "formula", "diet", "meal", "snack", "picky", "allergy", "vitamin", "milk", "weaning"],
        "responses": [
            "**Feeding & Nutrition Guide:**\n\n"
            "🍼 **0-6 months:** Breast milk or formula only\n"
            "🥣 **6 months:** Introduce single-ingredient purees (rice cereal, sweet potato, banana)\n"
            "🥕 **7-8 months:** Thicker purees, soft finger foods\n"
            "🍌 **9-11 months:** Chopped soft foods, self-feeding practice\n"
            "🍽️ **12+ months:** Family foods (cut small), transition from bottle\n"
            "🥗 **2-5 years:** Balanced meals, introduce variety\n\n"
            "💡 **Tips for picky eaters:**\n"
            "• Offer new foods 10-15 times before giving up\n"
            "• Let them see you eat the same food\n"
            "• Make food fun with shapes and colors 🌈\n"
            "• Don't force — keep mealtimes positive\n"
            "• Involve them in simple food preparation"
        ]
    },
    "sleep": {
        "keywords": ["sleep", "nap", "bedtime", "night", "wake", "routine", "tired", "rest", "crib", "bed"],
        "responses": [
            "**Sleep Guidelines by Age:**\n\n"
            "😴 **Newborn (0-3 months):** 14-17 hours total\n"
            "😴 **4-11 months:** 12-15 hours (including 2-3 naps)\n"
            "😴 **1-2 years:** 11-14 hours (including 1-2 naps)\n"
            "😴 **3-5 years:** 10-13 hours (may drop nap)\n\n"
            "💡 **Healthy sleep tips:**\n"
            "• Consistent bedtime routine (bath → book → bed)\n"
            "• Dark, cool room with white noise\n"
            "• Put baby down drowsy but awake\n"
            "• Avoid screens 1 hour before bed 📱❌\n"
            "• Same wake time every day\n\n"
            "⚠️ **Consult your pediatrician** if your child snores loudly, has frequent night terrors, or excessive daytime sleepiness."
        ]
    },
    "growth": {
        "keywords": ["growth", "height", "weight", "tall", "heavy", "percentile", "chart", "who", "measure", "grow", "head circumference", "bmi"],
        "responses": [
            "**Growth Tracking Tips:**\n\n"
            "📊 Your child's growth is tracked using WHO standards. Here's what matters:\n\n"
            "**Key measurements:**\n"
            "• **Height/Length** — Track monthly for infants, quarterly for toddlers\n"
            "• **Weight** — Most variable, don't worry about daily changes\n"
            "• **Head circumference** — Important in first 2 years\n\n"
            "**Understanding percentiles:**\n"
            "• Percentiles show how your child compares to others the same age\n"
            "• **Consistent growth** on their curve matters most\n"
            "• A sudden jump or drop in percentile is worth discussing with your doctor\n\n"
            "💡 Use the **Growth Chart** on your dashboard to visualize trends over time!\n\n"
            "📋 You can also **export a PDF report** to share with your pediatrician."
        ]
    },
    "social_emotional": {
        "keywords": ["social", "emotional", "friend", "play", "share", "cry", "tantrum", "behavior", "empathy", "feeling", "anxiety", "separation", "emotion"],
        "responses": [
            "**Social-Emotional Milestones:**\n\n"
            "🔹 **0-3 months:** Social smile, calms when picked up\n"
            "🔹 **4-6 months:** Laughs, enjoys social play\n"
            "🔹 **7-12 months:** Stranger anxiety, attachment to caregivers\n"
            "🔹 **1-2 years:** Parallel play, shows empathy, has tantrums\n"
            "🔹 **2-3 years:** Takes turns, begins cooperative play\n"
            "🔹 **3-5 years:** Has friends, follows rules, expresses complex emotions\n\n"
            "💡 **Supporting emotional development:**\n"
            "• Name emotions: 'I can see you're frustrated'\n"
            "• Validate feelings before problem-solving\n"
            "• Model healthy emotional expression\n"
            "• Read books about feelings 📚\n"
            "• Practice sharing and turn-taking through games"
        ]
    },
    "self_care": {
        "keywords": ["potty", "toilet", "train", "dress", "brush", "teeth", "wash", "hand", "shoe", "independent", "self care"],
        "responses": [
            "**Self-Care Milestones:**\n\n"
            "🔹 **6-12 months:** Drinks from a cup with help\n"
            "🔹 **12-18 months:** Uses a spoon, helps with undressing\n"
            "🔹 **18-24 months:** Washes hands with help, brushes teeth with help\n"
            "🔹 **2-3 years:** Toilet training readiness, puts on simple clothes\n"
            "🔹 **3-4 years:** Dresses independently, uses toilet alone\n"
            "🔹 **4-5 years:** Brushes teeth alone, ties shoelaces\n\n"
            "💡 **Potty training tips:**\n"
            "• Wait for readiness signs (stays dry 2+ hours, interest in toilet)\n"
            "• Use positive reinforcement 🌟\n"
            "• Expect accidents — stay patient!\n"
            "• Let them choose fun underwear\n"
            "• Keep a consistent schedule"
        ]
    },
    "safety": {
        "keywords": ["safe", "danger", "childproof", "poison", "fall", "choke", "burn", "drown", "emergency", "first aid", "accident"],
        "responses": [
            "**Child Safety Essentials:**\n\n"
            "🏠 **Home safety checklist:**\n"
            "• Cover electrical outlets\n"
            "• Secure furniture to walls (anti-tip brackets)\n"
            "• Lock cabinets with chemicals/medicines\n"
            "• Use stair gates\n"
            "• Keep small objects out of reach (choking hazard)\n\n"
            "🚗 **Car safety:**\n"
            "• Rear-facing car seat until age 2+\n"
            "• Never leave children alone in cars\n\n"
            "💧 **Water safety:**\n"
            "• Never leave child unattended near water\n"
            "• Start swim lessons around age 4\n\n"
            "📞 **Emergency:** Save your pediatrician's number and local emergency number in your phone."
        ]
    },
    "appointment": {
        "keywords": ["appointment", "doctor", "visit", "checkup", "specialist", "clinic", "book", "schedule", "vaccination", "vaccine"],
        "responses": [
            "**Appointment & Health Checkups:**\n\n"
            "📅 **Recommended well-child visits:**\n"
            "• 1, 2, 4, 6, 9, 12, 15, 18, 24 months\n"
            "• Then annually from age 3+\n\n"
            "💉 **Key vaccinations:**\n"
            "• Follow your country's immunization schedule\n"
            "• Keep a vaccination record\n\n"
            "💡 You can **book appointments** with specialists directly from your dashboard! "
            "Go to the Appointments section to see available time slots.\n\n"
            "📋 **Before your visit:**\n"
            "• Note any concerns or questions\n"
            "• Bring growth measurements\n"
            "• Export a PDF report from your dashboard to share with your doctor"
        ]
    },
    "bright_steps": {
        "keywords": ["bright steps", "platform", "app", "feature", "how to", "use", "dashboard", "account", "profile"],
        "responses": [
            "**Bright Steps Platform Guide:**\n\n"
            "📊 **Dashboard** — View your child's progress at a glance\n"
            "👶 **Child Profile** — Manage your child's information\n"
            "📈 **Growth Charts** — Track height, weight, head circumference\n"
            "⭐ **Milestones** — Track developmental achievements\n"
            "🏆 **Badges & Points** — Earn rewards for tracking progress\n"
            "📅 **Appointments** — Book sessions with specialists\n"
            "🗣️ **Speech Analysis** — Analyze your child's speech development\n"
            "📊 **WHO Comparison** — Compare growth to WHO standards\n"
            "📋 **PDF Reports** — Export detailed reports for your pediatrician\n\n"
            "Need help with a specific feature? Just ask! 😊"
        ]
    },
    "fallback": {
        "keywords": [],
        "responses": [
            "I'm not sure about that specific topic, but I'd love to help! 😊\n\n"
            "Here are some things I can help with:\n"
            "• 🏃 Motor skills development\n"
            "• 🗣️ Speech & language milestones\n"
            "• 🧠 Cognitive development\n"
            "• 🍼 Feeding & nutrition\n"
            "• 😴 Sleep guidance\n"
            "• 📊 Growth tracking\n"
            "• ❤️ Social-emotional development\n"
            "• 🚽 Self-care (potty training, etc.)\n"
            "• 🛡️ Child safety\n"
            "• 📅 Appointments & checkups\n\n"
            "Try asking about one of these topics!"
        ]
    }
}


def find_best_response(message: str) -> str:
    """Find the best matching response for a user message."""
    msg_lower = message.lower().strip()

    # Check each topic for keyword matches
    best_match = None
    best_score = 0

    for topic, data in KNOWLEDGE_BASE.items():
        if topic == "fallback":
            continue

        score = 0
        for keyword in data["keywords"]:
            if keyword in msg_lower:
                # Longer keyword matches are more specific
                score += len(keyword)

        if score > best_score:
            best_score = score
            best_match = topic

    if best_match and best_score > 0:
        import random
        responses = KNOWLEDGE_BASE[best_match]["responses"]
        return random.choice(responses) if len(responses) > 1 else responses[0]

    # Fallback
    return KNOWLEDGE_BASE["fallback"]["responses"][0]
