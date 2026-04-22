/**
 * Bright Steps – Chatbot Widget (Hybrid: Embedded KB + OpenAI Fallback)
 * First pass: keyword-matching against a rich embedded knowledge base.
 * Second pass: if no confident match or explicit child-data question, calls OpenAI via api_chatbot.php.
 */

(function () {
    // ── Embedded Knowledge Base ───────────────────────────────────────
    const KNOWLEDGE = {
        greetings: {
            keywords: ["hello", "hi", "hey", "good morning", "good evening", "help", "start"],
            confidence: 0.9,
            response: null // dynamically generated with child name
        },
        bright_steps: {
            keywords: ["bright steps", "platform", "app", "feature", "how to", "use", "dashboard", "account", "profile"],
            confidence: 0.85,
            response:
                "<strong>Bright Steps Platform Guide:</strong>\n\n" +
                "📊 <strong>Dashboard</strong> — Overview at a glance\n" +
                "👶 <strong>Child Profile</strong> — Manage child info\n" +
                "📈 <strong>Growth Charts</strong> — WHO-standard tracking\n" +
                "🏆 <strong>Badges & Points</strong> — Earn rewards\n" +
                "📅 <strong>Appointments</strong> — Book specialists\n" +
                "🗣️ <strong>Speech Analysis</strong> — AI-powered analysis\n" +
                "🏃 <strong>Motor Skills</strong> — Milestone checklist\n\n" +
                "Need help with a specific feature? Just ask! 😊"
        }
    };

    const CONFIDENCE_THRESHOLD = 3; // minimum keyword-length score to trust the match

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

        // Return null if not confident enough — triggers OpenAI fallback
        if (bestScore < CONFIDENCE_THRESHOLD) return null;

        let reply = KNOWLEDGE[bestTopic].response;

        // Handle dynamic greeting
        if (bestTopic === 'greetings') {
            const ctx = getChildContext();
            reply = `Hello! 👋 I'm the Bright Steps Assistant.${ctx.name ? ` I see you're tracking <strong>${ctx.name}</strong>'s development` + (ctx.age ? ` (${ctx.age})` : '') + '.' : ''}\n\n` +
                "I can help you with:\n" +
                "🏃 <strong>Motor skills</strong> milestones\n" +
                "🗣️ <strong>Speech & language</strong> development\n" +
                "🧠 <strong>Cognitive</strong> milestones\n" +
                "🍼 <strong>Feeding & nutrition</strong> tips\n" +
                "😴 <strong>Sleep</strong> guidance\n" +
                "📊 <strong>Growth</strong> tracking help\n" +
                "🧼 <strong>Hygiene</strong> & self-care\n" +
                "🛡️ <strong>Safety</strong> essentials\n" +
                "🏥 <strong>Health</strong> & appointments\n\n" +
                "Just ask me anything about your child's development!";
            return reply;
        }

        // Add personalized child context epilogue only for bright_steps if needed
        return reply;
    }

    // ── Get child context from dashboard data ────────────────────────
    function getChildContext() {
        const d = window.dashboardData;
        if (!d || !d.children || d.children.length === 0) return {};
        const idx = window._selectedChildIndex || 0;
        const c = d.children[idx];
        if (!c) return {};

        return {
            name: c.first_name,
            childId: c.child_id,
            age: c.age_display || '',
            ageMonths: c.age_months || 0,
            gender: c.gender,
            growth: c.growth || null,
            speech: c._speech || null,  // may not be in dashboardData
            motorPct: c._motorPct,       // may not be in dashboardData
            conditions: c.health_condition || ''
        };
    }

    // ── Create Widget HTML ────────────────────────────────────────────
    function createChatWidget() {
        if (!document.getElementById('chatbot-styles')) {
            const style = document.createElement('style');
            style.id = 'chatbot-styles';
            style.textContent = `
                .chatbot-toggle{position:fixed;bottom:1.5rem;right:1.5rem;width:56px;height:56px;border-radius:50%;background:linear-gradient(135deg,#6366f1,#8b5cf6);color:#fff;border:none;cursor:pointer;box-shadow:0 4px 20px rgba(99,102,241,0.4);z-index:10000;display:flex;align-items:center;justify-content:center;transition:all .3s ease}
                .chatbot-toggle:hover{transform:scale(1.1);box-shadow:0 6px 25px rgba(99,102,241,0.5)}
                .chatbot-toggle.active{background:linear-gradient(135deg,#ef4444,#f97316);box-shadow:0 4px 20px rgba(239,68,68,0.4)}
                .chatbot-toggle svg{width:24px;height:24px}
                .chatbot-panel{position:fixed;bottom:6rem;right:1.5rem;width:400px;max-height:560px;background:#fff;border-radius:20px;box-shadow:0 10px 40px rgba(0,0,0,0.18);display:flex;flex-direction:column;z-index:9999;opacity:0;visibility:hidden;transform:translateY(20px) scale(0.95);transition:all .3s ease;overflow:hidden}
                .chatbot-panel.open{opacity:1;visibility:visible;transform:translateY(0) scale(1)}
                .chatbot-header{display:flex;align-items:center;justify-content:space-between;padding:1rem 1.25rem;background:linear-gradient(135deg,#6366f1,#8b5cf6);color:#fff}
                .chatbot-header-info{display:flex;align-items:center;gap:.75rem}
                .chatbot-avatar{font-size:1.5rem;width:36px;height:36px;background:rgba(255,255,255,0.2);border-radius:50%;display:flex;align-items:center;justify-content:center}
                .chatbot-name{font-weight:600;font-size:.95rem}
                .chatbot-status{font-size:.75rem;opacity:.8;color:#a5f3c4}
                .chatbot-close{background:none;border:none;color:#fff;font-size:1.25rem;cursor:pointer;opacity:.7;transition:opacity .2s}
                .chatbot-close:hover{opacity:1}
                .chatbot-messages{flex:1;overflow-y:auto;padding:1rem;display:flex;flex-direction:column;gap:.75rem;max-height:360px;min-height:200px}
                .chat-msg{display:flex;max-width:85%}
                .chat-msg.user{align-self:flex-end}
                .chat-msg.bot{align-self:flex-start}
                .chat-bubble{padding:.75rem 1rem;border-radius:12px;font-size:.875rem;line-height:1.6;word-wrap:break-word}
                .chat-msg.user .chat-bubble{background:linear-gradient(135deg,#6366f1,#8b5cf6);color:#fff;border-bottom-right-radius:4px}
                .chat-msg.bot .chat-bubble{background:#f1f5f9;color:#1e293b;border-bottom-left-radius:4px}
                .chat-bubble.typing{display:flex;align-items:center;gap:4px;padding:1rem 1.25rem}
                .chat-bubble.typing span{width:8px;height:8px;background:#94a3b8;border-radius:50%;animation:cbTyping 1.4s ease-in-out infinite}
                .chat-bubble.typing span:nth-child(2){animation-delay:.2s}
                .chat-bubble.typing span:nth-child(3){animation-delay:.4s}
                @keyframes cbTyping{0%,60%,100%{transform:translateY(0)}30%{transform:translateY(-6px)}}
                .chatbot-topics{display:flex;flex-wrap:wrap;gap:.5rem;padding:.5rem 1rem;border-top:1px solid #e2e8f0}
                .topic-chip{padding:.35rem .75rem;background:#f1f5f9;border-radius:20px;font-size:.75rem;cursor:pointer;transition:all .2s;white-space:nowrap;color:#475569}
                .topic-chip:hover{background:#e0e7ff;color:#4338ca}
                .chatbot-input-area{display:flex;gap:.5rem;padding:.75rem 1rem;border-top:1px solid #e2e8f0}
                .chatbot-input{flex:1;padding:.6rem 1rem;border:2px solid #e2e8f0;border-radius:12px;font-size:.875rem;outline:none;transition:border-color .2s;font-family:inherit;background:#fff;color:#1e293b}
                .chatbot-input:focus{border-color:#6366f1}
                .chatbot-send{width:40px;height:40px;border:none;background:linear-gradient(135deg,#6366f1,#8b5cf6);color:#fff;border-radius:10px;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:transform .2s;flex-shrink:0}
                .chatbot-send:hover{transform:scale(1.05)}
                .chatbot-send svg{width:18px;height:18px}
                .chatbot-ai-badge{display:inline-flex;align-items:center;gap:3px;font-size:.65rem;color:#6366f1;background:#eef2ff;padding:2px 6px;border-radius:6px;margin-top:4px}
                @media(max-width:480px){.chatbot-panel{right:.5rem;left:.5rem;bottom:5rem;width:auto;max-height:70vh}.chatbot-toggle{bottom:1rem;right:1rem}}
            `;
            document.head.appendChild(style);
        }

        const ctx = getChildContext();
        const childGreeting = ctx.name ? `I'm here to help with <strong>${ctx.name}</strong>'s development!` : 'Ask me anything about your child\'s development!';

        const btn = document.createElement('button');
        btn.id = 'chatbot-toggle';
        btn.className = 'chatbot-toggle';
        btn.innerHTML = `
            <svg class="chat-icon" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
            </svg>
            <svg class="close-icon" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" style="display:none">
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
                        Hello! 👋 ${childGreeting}<br><br>
                        Try: <em>"When should my baby start walking?"</em>
                    </div>
                </div>
            </div>
            <div class="chatbot-topics" id="chatbot-topics">
                <span class="topic-chip" onclick="sendTopicMessage('motor skills')">🏃 Motor</span>
                <span class="topic-chip" onclick="sendTopicMessage('speech development')">🗣️ Speech</span>
                <span class="topic-chip" onclick="sendTopicMessage('sleep guidance')">😴 Sleep</span>
                <span class="topic-chip" onclick="sendTopicMessage('feeding tips')">🍼 Feeding</span>
                <span class="topic-chip" onclick="sendTopicMessage('growth tracking')">📊 Growth</span>
                <span class="topic-chip" onclick="sendTopicMessage('activities for my child')">🎮 Activities</span>
                <span class="topic-chip" onclick="sendTopicMessage('health checkup')">🏥 Health</span>
            </div>
            <div class="chatbot-input-area">
                <input type="text" id="chatbot-input" class="chatbot-input" 
                       placeholder="Ask about ${ctx.name || 'your child'}'s development..." 
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

    // ── Send Message (Hybrid: KB first, then OpenAI fallback) ────────
    window.sendMessage = async function () {
        const input = document.getElementById('chatbot-input');
        const msg = input.value.trim();
        if (!msg) return;

        input.value = '';
        addMessage(msg, 'user');

        const topics = document.getElementById('chatbot-topics');
        if (topics) topics.style.display = 'none';

        // 1) Try embedded knowledge base first
        const kbReply = findResponse(msg);

        if (kbReply) {
            // Confident KB match — respond instantly
            const typingId = addTypingIndicator();
            setTimeout(() => {
                removeTypingIndicator(typingId);
                addMessage(kbReply, 'bot');
            }, 300 + Math.random() * 400);
        } else {
            // 2) No confident match — call OpenAI via api_chatbot.php
            const typingId = addTypingIndicator();
            try {
                const ctx = getChildContext();

                // Debug: log context being sent
                console.log('🤖 Chatbot context:', ctx);
                console.log('🤖 Sending message:', msg, 'child_id:', ctx.childId);

                const res = await fetch('../../api_chatbot.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    credentials: 'same-origin',
                    body: JSON.stringify({ message: msg, child_id: ctx.childId || null })
                });

                console.log('🤖 HTTP Response:', res.status, res.statusText);

                if (!res.ok) {
                    console.error('Chatbot HTTP error:', res.status, res.statusText);
                    throw new Error('HTTP ' + res.status);
                }

                const data = await res.json();
                console.log('🤖 API response:', data);
                removeTypingIndicator(typingId);

                if (data.success && data.reply) {
                    addMessage(data.reply + '\n<span class="chatbot-ai-badge">✨ AI-powered response</span>', 'bot');
                } else if (data.error) {
                    let errorMsg = '⚠️ ' + data.error;
                    if (data.debug) {
                        console.error('API Error Details:', data.debug);
                        if (typeof data.debug === 'object') {
                            errorMsg += ' (Session: ' + (data.debug.session_data ? 'active' : 'empty') + ')';
                        }
                    }
                    addMessage(errorMsg, 'bot');
                } else {
                    addMessage(getFallbackResponse(), 'bot');
                }
            } catch (e) {
                console.error('🤖 Chatbot API error:', e);
                removeTypingIndicator(typingId);
                const errorMsg = e.message || 'Unknown error';
                addMessage('⚠️ Error: ' + errorMsg + '. Check browser console (F12) for details.', 'bot');
            }
        }
    };

    function getFallbackResponse() {
        return "I'm having a little trouble connecting to my AI brain right now, but I still have my local knowledge base! 😊<br><br>" +
            "Please select a topic you want advice on:<br>" +
            "<div style='margin-top:1rem;display:flex;flex-wrap:wrap;gap:0.5rem;'>" +
            "<span class='topic-chip' onclick='sendTopicMessage(\"motor skills\")' style='display:inline-block;margin:0;'>🏃 Motor</span>" +
            "<span class='topic-chip' onclick='sendTopicMessage(\"speech development\")' style='display:inline-block;margin:0;'>🗣️ Speech</span>" +
            "<span class='topic-chip' onclick='sendTopicMessage(\"sleep guidance\")' style='display:inline-block;margin:0;'>😴 Sleep</span>" +
            "<span class='topic-chip' onclick='sendTopicMessage(\"feeding tips\")' style='display:inline-block;margin:0;'>🍼 Feeding</span>" +
            "<span class='topic-chip' onclick='sendTopicMessage(\"growth tracking\")' style='display:inline-block;margin:0;'>📊 Growth</span>" +
            "<span class='topic-chip' onclick='sendTopicMessage(\"health checkup\")' style='display:inline-block;margin:0;'>🏥 Health</span>" +
            "</div>";
    }

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

    // ── Refresh greeting when child changes ───────────────────────────
    window.refreshChatbotGreeting = function() {
        const ctx = getChildContext();
        const childGreeting = ctx.name ? `I'm here to help with <strong>${ctx.name}</strong>'s development!` : 'Ask me anything about your child\'s development!';

        // Update the greeting in the chat panel
        const container = document.getElementById('chatbot-messages');
        if (container) {
            const firstMsg = container.querySelector('.chat-msg.bot .chat-bubble');
            if (firstMsg) {
                firstMsg.innerHTML = `Hello! 👋 ${childGreeting}<br><br>Try: <em>"When should my baby start walking?"</em>`;
            }

            // Update input placeholder
            const input = document.getElementById('chatbot-input');
            if (input) {
                input.placeholder = `Ask about ${ctx.name || 'your child'}'s development...`;
            }
        }

        // Also update the panel header child name if it exists
        const headerStatus = document.querySelector('.chatbot-status');
        if (headerStatus && ctx.name) {
            headerStatus.textContent = `● Online - Helping with ${ctx.name}`;
        }
    };

    // ── Init ──────────────────────────────────────────────────────────
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', createChatWidget);
    } else {
        createChatWidget();
    }
})();
