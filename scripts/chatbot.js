/**
 * Bright Steps – Chatbot Widget (Self-Contained)
 * Runs entirely in the browser — no Python server needed!
 * Knowledge base is embedded directly in JavaScript.
 */

(function () {
    // ── Embedded Knowledge Base ───────────────────────────────────────
    const KNOWLEDGE = {
        greetings: {
            keywords: ["hello", "hi", "hey", "good morning", "good evening", "help", "start"],
            response:
                "Hello! 👋 I'm the Bright Steps Assistant. I can help you with:\n\n" +
                "🏃 <strong>Motor skills</strong> milestones\n" +
                "🗣️ <strong>Speech & language</strong> development\n" +
                "🧠 <strong>Cognitive</strong> milestones\n" +
                "🍼 <strong>Feeding & nutrition</strong> tips\n" +
                "😴 <strong>Sleep</strong> guidance\n" +
                "📊 <strong>Growth</strong> tracking help\n" +
                "👶 <strong>General</strong> parenting tips\n\n" +
                "Just ask me anything about your child's development!"
        },
        motor_skills: {
            keywords: ["motor", "crawl", "walk", "sit", "stand", "roll", "run", "jump", "climb", "balance", "coordination", "movement", "physical", "step"],
            response:
                "<strong>Motor Skills Milestones by Age:</strong>\n\n" +
                "🔹 <strong>0-3 months:</strong> Lifts head during tummy time, moves arms and legs\n" +
                "🔹 <strong>4-6 months:</strong> Rolls over, reaches for toys, sits with support\n" +
                "🔹 <strong>7-9 months:</strong> Sits alone, starts crawling, pulls to stand\n" +
                "🔹 <strong>10-12 months:</strong> Stands alone, may take first steps\n" +
                "🔹 <strong>13-18 months:</strong> Walks independently, starts climbing\n" +
                "🔹 <strong>19-24 months:</strong> Runs, kicks a ball, walks up stairs\n" +
                "🔹 <strong>2-3 years:</strong> Jumps, pedals tricycle, catches large ball\n" +
                "🔹 <strong>3-5 years:</strong> Hops on one foot, skips, rides bike with training wheels\n\n" +
                "💡 <strong>Tip:</strong> Encourage tummy time daily and provide safe spaces for exploring movement!"
        },
        speech_language: {
            keywords: ["speech", "talk", "word", "language", "babble", "sentence", "speak", "communicate", "verbal", "vocabulary", "say", "first word"],
            response:
                "<strong>Speech & Language Milestones:</strong>\n\n" +
                "🔹 <strong>0-3 months:</strong> Coos, makes vowel sounds\n" +
                "🔹 <strong>4-6 months:</strong> Babbles (ba-ba, da-da), laughs\n" +
                "🔹 <strong>7-9 months:</strong> Responds to name, understands 'no'\n" +
                "🔹 <strong>10-12 months:</strong> Says first words (mama, dada)\n" +
                "🔹 <strong>13-18 months:</strong> Uses 10-20 words, points to objects\n" +
                "🔹 <strong>19-24 months:</strong> Combines 2 words, 50+ word vocabulary\n" +
                "🔹 <strong>2-3 years:</strong> 3-4 word sentences, asks questions\n" +
                "🔹 <strong>3-5 years:</strong> Tells stories, uses past tense, 1000+ words\n\n" +
                "💡 <strong>Tips to boost speech:</strong>\n" +
                "• Read aloud daily 📚\n" +
                "• Narrate your activities\n" +
                "• Ask open-ended questions\n" +
                "• Sing songs together 🎵"
        },
        cognitive: {
            keywords: ["cognitive", "brain", "think", "learn", "smart", "intelligence", "problem", "solve", "memory", "attention", "focus", "count", "color", "shape"],
            response:
                "<strong>Cognitive Development Milestones:</strong>\n\n" +
                "🔹 <strong>0-3 months:</strong> Tracks objects with eyes, recognizes faces\n" +
                "🔹 <strong>4-6 months:</strong> Explores with hands and mouth\n" +
                "🔹 <strong>7-9 months:</strong> Object permanence (knows hidden toys still exist)\n" +
                "🔹 <strong>10-12 months:</strong> Imitates actions, finds hidden objects\n" +
                "🔹 <strong>13-18 months:</strong> Simple problem-solving, stacks blocks\n" +
                "🔹 <strong>19-24 months:</strong> Sorts shapes, begins pretend play\n" +
                "🔹 <strong>2-3 years:</strong> Counts to 10, knows some colors\n" +
                "🔹 <strong>3-5 years:</strong> Understands time concepts, writes name\n\n" +
                "💡 <strong>Boost cognitive development with:</strong>\n" +
                "• Puzzles and building blocks 🧩\n" +
                "• Sorting games (by color, shape, size)\n" +
                "• Counting during daily activities\n" +
                "• Reading interactive books"
        },
        feeding: {
            keywords: ["feed", "food", "eat", "nutrition", "solid", "breast", "formula", "diet", "meal", "snack", "picky", "allergy", "vitamin", "milk", "weaning"],
            response:
                "<strong>Feeding & Nutrition Guide:</strong>\n\n" +
                "🍼 <strong>0-6 months:</strong> Breast milk or formula only\n" +
                "🥣 <strong>6 months:</strong> Introduce single-ingredient purees (rice cereal, sweet potato, banana)\n" +
                "🥕 <strong>7-8 months:</strong> Thicker purees, soft finger foods\n" +
                "🍌 <strong>9-11 months:</strong> Chopped soft foods, self-feeding practice\n" +
                "🍽️ <strong>12+ months:</strong> Family foods (cut small), transition from bottle\n" +
                "🥗 <strong>2-5 years:</strong> Balanced meals, introduce variety\n\n" +
                "💡 <strong>Tips for picky eaters:</strong>\n" +
                "• Offer new foods 10-15 times before giving up\n" +
                "• Let them see you eat the same food\n" +
                "• Make food fun with shapes and colors 🌈\n" +
                "• Don't force — keep mealtimes positive"
        },
        sleep: {
            keywords: ["sleep", "nap", "bedtime", "night", "wake", "routine", "tired", "rest", "crib", "bed"],
            response:
                "<strong>Sleep Guidelines by Age:</strong>\n\n" +
                "😴 <strong>Newborn (0-3 months):</strong> 14-17 hours total\n" +
                "😴 <strong>4-11 months:</strong> 12-15 hours (including 2-3 naps)\n" +
                "😴 <strong>1-2 years:</strong> 11-14 hours (including 1-2 naps)\n" +
                "😴 <strong>3-5 years:</strong> 10-13 hours (may drop nap)\n\n" +
                "💡 <strong>Healthy sleep tips:</strong>\n" +
                "• Consistent bedtime routine (bath → book → bed)\n" +
                "• Dark, cool room with white noise\n" +
                "• Put baby down drowsy but awake\n" +
                "• Avoid screens 1 hour before bed 📱❌\n" +
                "• Same wake time every day\n\n" +
                "⚠️ <strong>Consult your pediatrician</strong> if your child snores loudly, has frequent night terrors, or excessive daytime sleepiness."
        },
        growth: {
            keywords: ["growth", "height", "weight", "tall", "heavy", "percentile", "chart", "who", "measure", "grow", "head circumference", "bmi"],
            response:
                "<strong>Growth Tracking Tips:</strong>\n\n" +
                "📊 Your child's growth is tracked using WHO standards.\n\n" +
                "<strong>Key measurements:</strong>\n" +
                "• <strong>Height/Length</strong> — Track monthly for infants, quarterly for toddlers\n" +
                "• <strong>Weight</strong> — Most variable, don't worry about daily changes\n" +
                "• <strong>Head circumference</strong> — Important in first 2 years\n\n" +
                "<strong>Understanding percentiles:</strong>\n" +
                "• Percentiles show how your child compares to others the same age\n" +
                "• <strong>Consistent growth</strong> on their curve matters most\n" +
                "• A sudden jump or drop is worth discussing with your doctor\n\n" +
                "💡 Use the <strong>Growth Chart</strong> on your dashboard to visualize trends!"
        },
        social_emotional: {
            keywords: ["social", "emotional", "friend", "play", "share", "cry", "tantrum", "behavior", "empathy", "feeling", "anxiety", "separation", "emotion"],
            response:
                "<strong>Social-Emotional Milestones:</strong>\n\n" +
                "🔹 <strong>0-3 months:</strong> Social smile, calms when picked up\n" +
                "🔹 <strong>4-6 months:</strong> Laughs, enjoys social play\n" +
                "🔹 <strong>7-12 months:</strong> Stranger anxiety, attachment to caregivers\n" +
                "🔹 <strong>1-2 years:</strong> Parallel play, shows empathy, has tantrums\n" +
                "🔹 <strong>2-3 years:</strong> Takes turns, begins cooperative play\n" +
                "🔹 <strong>3-5 years:</strong> Has friends, follows rules, expresses complex emotions\n\n" +
                "💡 <strong>Supporting emotional development:</strong>\n" +
                "• Name emotions: 'I can see you're frustrated'\n" +
                "• Validate feelings before problem-solving\n" +
                "• Model healthy emotional expression\n" +
                "• Read books about feelings 📚"
        },
        self_care: {
            keywords: ["potty", "toilet", "train", "dress", "brush", "teeth", "wash", "hand", "shoe", "independent", "self care"],
            response:
                "<strong>Self-Care Milestones:</strong>\n\n" +
                "🔹 <strong>6-12 months:</strong> Drinks from a cup with help\n" +
                "🔹 <strong>12-18 months:</strong> Uses a spoon, helps with undressing\n" +
                "🔹 <strong>18-24 months:</strong> Washes hands with help, brushes teeth with help\n" +
                "🔹 <strong>2-3 years:</strong> Toilet training readiness, puts on simple clothes\n" +
                "🔹 <strong>3-4 years:</strong> Dresses independently, uses toilet alone\n" +
                "🔹 <strong>4-5 years:</strong> Brushes teeth alone, ties shoelaces\n\n" +
                "💡 <strong>Potty training tips:</strong>\n" +
                "• Wait for readiness signs (stays dry 2+ hours, interest in toilet)\n" +
                "• Use positive reinforcement 🌟\n" +
                "• Expect accidents — stay patient!\n" +
                "• Keep a consistent schedule"
        },
        safety: {
            keywords: ["safe", "danger", "childproof", "poison", "fall", "choke", "burn", "drown", "emergency", "first aid", "accident"],
            response:
                "<strong>Child Safety Essentials:</strong>\n\n" +
                "🏠 <strong>Home safety checklist:</strong>\n" +
                "• Cover electrical outlets\n" +
                "• Secure furniture to walls (anti-tip brackets)\n" +
                "• Lock cabinets with chemicals/medicines\n" +
                "• Use stair gates\n" +
                "• Keep small objects out of reach (choking hazard)\n\n" +
                "🚗 <strong>Car safety:</strong>\n" +
                "• Rear-facing car seat until age 2+\n" +
                "• Never leave children alone in cars\n\n" +
                "💧 <strong>Water safety:</strong>\n" +
                "• Never leave child unattended near water\n" +
                "• Start swim lessons around age 4\n\n" +
                "📞 <strong>Emergency:</strong> Save your pediatrician's number and local emergency number!"
        },
        appointment: {
            keywords: ["appointment", "doctor", "visit", "checkup", "specialist", "clinic", "book", "schedule", "vaccination", "vaccine"],
            response:
                "<strong>Appointment & Health Checkups:</strong>\n\n" +
                "📅 <strong>Recommended well-child visits:</strong>\n" +
                "• 1, 2, 4, 6, 9, 12, 15, 18, 24 months\n" +
                "• Then annually from age 3+\n\n" +
                "💉 <strong>Key vaccinations:</strong>\n" +
                "• Follow your country's immunization schedule\n" +
                "• Keep a vaccination record\n\n" +
                "💡 You can <strong>book appointments</strong> with specialists directly from your dashboard!\n\n" +
                "📋 <strong>Before your visit:</strong>\n" +
                "• Note any concerns or questions\n" +
                "• Bring growth measurements\n" +
                "• Export a PDF report to share with your doctor"
        },
        bright_steps: {
            keywords: ["bright steps", "platform", "app", "feature", "how to", "use", "dashboard", "account", "profile"],
            response:
                "<strong>Bright Steps Platform Guide:</strong>\n\n" +
                "📊 <strong>Dashboard</strong> — View your child's progress at a glance\n" +
                "👶 <strong>Child Profile</strong> — Manage your child's information\n" +
                "📈 <strong>Growth Charts</strong> — Track height, weight, head circumference\n" +
                "⭐ <strong>Milestones</strong> — Track developmental achievements\n" +
                "🏆 <strong>Badges & Points</strong> — Earn rewards for tracking progress\n" +
                "📅 <strong>Appointments</strong> — Book sessions with specialists\n" +
                "🗣️ <strong>Speech Analysis</strong> — Analyze your child's speech development\n" +
                "📊 <strong>WHO Comparison</strong> — Compare growth to WHO standards\n\n" +
                "Need help with a specific feature? Just ask! 😊"
        }
    };

    const FALLBACK =
        "I'm not sure about that specific topic, but I'd love to help! 😊\n\n" +
        "Here are some things I can help with:\n" +
        "• 🏃 Motor skills development\n" +
        "• 🗣️ Speech & language milestones\n" +
        "• 🧠 Cognitive development\n" +
        "• 🍼 Feeding & nutrition\n" +
        "• 😴 Sleep guidance\n" +
        "• 📊 Growth tracking\n" +
        "• ❤️ Social-emotional development\n" +
        "• 🚽 Self-care (potty training, etc.)\n" +
        "• 🛡️ Child safety\n" +
        "• 📅 Appointments & checkups\n\n" +
        "Try asking about one of these topics!";

    // ── Knowledge Matching ────────────────────────────────────────────
    function findResponse(message) {
        const msg = message.toLowerCase().trim();
        let bestTopic = null;
        let bestScore = 0;

        for (const [topic, data] of Object.entries(KNOWLEDGE)) {
            let score = 0;
            for (const kw of data.keywords) {
                if (msg.includes(kw)) score += kw.length;
            }
            if (score > bestScore) {
                bestScore = score;
                bestTopic = topic;
            }
        }

        let reply = bestTopic ? KNOWLEDGE[bestTopic].response : FALLBACK;

        // Add age-specific note if child data is available
        if (window.dashboardData && window.dashboardData.children && window.dashboardData.children.length > 0) {
            const age = window.dashboardData.children[0].age_months;
            if (age && bestTopic && bestTopic !== 'greetings') {
                let tip = '';
                if (age <= 6) tip = 'focus on tummy time, sensory play, and responding to coos and babbles.';
                else if (age <= 12) tip = 'encourage crawling, object exploration, and simple word repetition.';
                else if (age <= 24) tip = 'support first steps, expand vocabulary with narration, and introduce self-feeding.';
                else if (age <= 36) tip = 'encourage running, simple sentences, and pretend play.';
                else if (age <= 60) tip = 'practice counting, storytelling, and social skills with peers.';
                else tip = 'support reading readiness, creative play, and emotional expression.';
                reply += '\n\n📌 <em>Based on your child\'s age (' + age + ' months), ' + tip + '</em>';
            }
        }

        return reply;
    }

    // ── Create Widget HTML ────────────────────────────────────────────
    function createChatWidget() {
        const btn = document.createElement('button');
        btn.id = 'chatbot-toggle';
        btn.className = 'chatbot-toggle';
        btn.innerHTML = `
            <svg class="chat-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
            </svg>
            <svg class="close-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="display:none">
                <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
            </svg>
        `;
        btn.setAttribute('aria-label', 'Chat with assistant');

        const panel = document.createElement('div');
        panel.id = 'chatbot-panel';
        panel.className = 'chatbot-panel';
        panel.innerHTML = `
            <div class="chatbot-header">
                <div class="chatbot-header-info">
                    <div class="chatbot-avatar">🤖</div>
                    <div>
                        <div class="chatbot-name">Bright Steps Assistant</div>
                        <div class="chatbot-status">● Online</div>
                    </div>
                </div>
                <button class="chatbot-close" onclick="toggleChatbot()" aria-label="Close chat">✕</button>
            </div>
            <div class="chatbot-messages" id="chatbot-messages">
                <div class="chat-msg bot">
                    <div class="chat-bubble">
                        Hello! 👋 I'm your Bright Steps Assistant.<br>
                        Ask me anything about your child's development!<br><br>
                        Try: <em>"When should my baby start walking?"</em>
                    </div>
                </div>
            </div>
            <div class="chatbot-topics" id="chatbot-topics">
                <span class="topic-chip" onclick="sendTopicMessage('motor skills')">🏃 Motor Skills</span>
                <span class="topic-chip" onclick="sendTopicMessage('speech development')">🗣️ Speech</span>
                <span class="topic-chip" onclick="sendTopicMessage('sleep guidance')">😴 Sleep</span>
                <span class="topic-chip" onclick="sendTopicMessage('feeding tips')">🍼 Feeding</span>
                <span class="topic-chip" onclick="sendTopicMessage('growth tracking')">📊 Growth</span>
            </div>
            <div class="chatbot-input-area">
                <input type="text" id="chatbot-input" class="chatbot-input" 
                       placeholder="Ask about child development..." 
                       autocomplete="off">
                <button class="chatbot-send" id="chatbot-send" onclick="sendMessage()" aria-label="Send">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M22 2L11 13M22 2l-7 20-4-9-9-4 20-7z"/>
                    </svg>
                </button>
            </div>
        `;

        document.body.appendChild(btn);
        document.body.appendChild(panel);

        btn.addEventListener('click', toggleChatbot);
        document.getElementById('chatbot-input').addEventListener('keypress', (e) => {
            if (e.key === 'Enter') sendMessage();
        });
    }

    // ── Toggle ────────────────────────────────────────────────────────
    window.toggleChatbot = function () {
        const panel = document.getElementById('chatbot-panel');
        const btn = document.getElementById('chatbot-toggle');
        const chatIcon = btn.querySelector('.chat-icon');
        const closeIcon = btn.querySelector('.close-icon');

        const isOpen = panel.classList.toggle('open');
        chatIcon.style.display = isOpen ? 'none' : 'block';
        closeIcon.style.display = isOpen ? 'block' : 'none';
        btn.classList.toggle('active', isOpen);

        if (isOpen) document.getElementById('chatbot-input').focus();
    };

    // ── Send Message (fully client-side) ──────────────────────────────
    window.sendMessage = function () {
        const input = document.getElementById('chatbot-input');
        const msg = input.value.trim();
        if (!msg) return;

        input.value = '';
        addMessage(msg, 'user');

        const topics = document.getElementById('chatbot-topics');
        if (topics) topics.style.display = 'none';

        // Simulate typing delay for natural feel
        const typingId = addTypingIndicator();
        setTimeout(() => {
            removeTypingIndicator(typingId);
            const reply = findResponse(msg);
            addMessage(reply, 'bot');
        }, 400 + Math.random() * 600);
    };

    window.sendTopicMessage = function (topic) {
        document.getElementById('chatbot-input').value = topic;
        sendMessage();
    };

    // ── Message Helpers ───────────────────────────────────────────────
    function addMessage(text, sender) {
        const container = document.getElementById('chatbot-messages');
        const div = document.createElement('div');
        div.className = `chat-msg ${sender}`;
        div.innerHTML = `<div class="chat-bubble">${text.replace(/\n/g, '<br>')}</div>`;
        container.appendChild(div);
        container.scrollTop = container.scrollHeight;
    }

    function addTypingIndicator() {
        const container = document.getElementById('chatbot-messages');
        const id = 'typing-' + Date.now();
        const div = document.createElement('div');
        div.className = 'chat-msg bot';
        div.id = id;
        div.innerHTML = `<div class="chat-bubble typing"><span></span><span></span><span></span></div>`;
        container.appendChild(div);
        container.scrollTop = container.scrollHeight;
        return id;
    }

    function removeTypingIndicator(id) {
        const el = document.getElementById(id);
        if (el) el.remove();
    }

    // ── Init ──────────────────────────────────────────────────────────
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', createChatWidget);
    } else {
        createChatWidget();
    }
})();
