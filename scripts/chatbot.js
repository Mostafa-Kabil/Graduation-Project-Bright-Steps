/**
 * Bright Steps – Chatbot Widget (Enhanced Knowledge-Based)
 * Provides personalized child development insights based on child profile data.
 * No external AI dependency – all responses are generated from embedded knowledge + child data.
 */

(function () {
    // ── Age-based developmental milestones knowledge ───────────────────
    const MILESTONES_DB = {
        // 0-3 months
        '0-3': {
            motor: 'Lifts head during tummy time, starts pushing up, may grasp objects briefly.',
            speech: 'Coos and makes vowel sounds (ah, oh). Responds to sounds by turning head.',
            social: 'Begins to smile at people, can briefly calm self, tries to look at parent.',
            cognitive: 'Follows moving objects with eyes, recognizes familiar people at a distance.',
            feeding: 'Exclusively breastfed or formula. Feeds every 2-3 hours. ~120-150ml per feed.',
            sleep: 'Sleeps 14-17 hours/day. Wakes every 2-3 hours for feeding.'
        },
        '4-6': {
            motor: 'Rolls over both ways, begins to sit without support, reaches for toys.',
            speech: 'Babbles with consonant sounds (ba, da, ma). Laughs and squeals.',
            social: 'Responds to emotions, enjoys looking in mirror, likes to play with others.',
            cognitive: 'Brings things to mouth, shows curiosity, reaches for toy with one hand.',
            feeding: 'Start introducing solids (purées). Iron-rich foods recommended. Still breastfeed/formula.',
            sleep: 'Sleeps 12-16 hours/day including 2-3 naps. May start sleeping through the night.'
        },
        '7-9': {
            motor: 'Sits well without support, starts crawling, pulls to stand.',
            speech: 'Strings vowels together (ah, eh, oh). Responds to own name.',
            social: 'May be clingy with familiar adults, has favorite toys, plays peek-a-boo.',
            cognitive: 'Watches the path of something as it falls, looks for things hidden.',
            feeding: 'Mashed/soft foods. 3 meals + breast/formula. Introduce finger foods.',
            sleep: 'Sleeps 12-15 hours/day including 2 naps. Consistent bedtime routine important.'
        },
        '10-12': {
            motor: 'Pulls to stand, cruises along furniture, may take first steps.',
            speech: 'Says "mama" and "dada" with meaning. May say 1-3 words.',
            social: 'Shows anxiety around strangers, cries when parent leaves, plays favorites.',
            cognitive: 'Explores things by shaking, banging. Finds hidden objects easily.',
            feeding: 'Most table foods okay. 3 meals + 2 snacks. Self-feeding with fingers.',
            sleep: 'Sleeps 12-14 hours/day including 1-2 naps. Separation anxiety may affect sleep.'
        },
        '13-18': {
            motor: 'Walks independently, may begin running, climbs on/off furniture.',
            speech: 'Says several single words (5-10). Points to show interest. Understands "no".',
            social: 'Hands you a book to read, may have temper tantrums, shows affection.',
            cognitive: 'Points to get attention, shows interest in a toy by handing it to others.',
            feeding: 'Eats most family foods. Uses cup independently. 3 meals + 2 snacks.',
            sleep: 'Sleeps 11-14 hours/day including 1-2 naps. Transitioning to 1 nap.'
        },
        '19-24': {
            motor: 'Runs, kicks a ball, walks up and down stairs with help, scribbles.',
            speech: 'Knows 50+ words. Starts 2-word phrases ("want milk"). Follows simple instructions.',
            social: 'Shows defiant behavior, plays alongside other children, copies others.',
            cognitive: 'Begins make-believe play, sorts shapes and colors, completes sentences in books.',
            feeding: 'Can eat most foods. Learns to use spoon/fork. 3 meals + 2 snacks daily.',
            sleep: 'Sleeps 11-14 hours/day. Usually 1 nap of 1-3 hours.'
        },
        '25-36': {
            motor: 'Climbs well, runs easily, pedals a tricycle, walks up/down stairs alternating feet.',
            speech: 'Says 200-1000 words. 2-3 word sentences. Strangers can understand most words.',
            social: 'Takes turns in games, shows concern for crying friend, understands "mine".',
            cognitive: 'Plays make-believe, works toys with buttons/levers/moving parts, does puzzles.',
            feeding: 'Full family diet. Can use utensils well. Encourage healthy choices.',
            sleep: 'Sleeps 10-13 hours/day. Many drop nap between 3-4 years.'
        },
        '37-48': {
            motor: 'Hops on one foot, catches a bounced ball, cuts with scissors.',
            speech: 'Says first name, talks about daily activities, speaks in 4+ word sentences.',
            social: 'Cooperates with children, prefers to play with others, can tell stories.',
            cognitive: 'Names some colors and numbers, understands counting, starts to understand time.',
            feeding: 'Independent eating. Focus on balanced nutrition. 3 meals + 1-2 snacks.',
            sleep: 'Sleeps 10-12 hours/day. Most children drop daytime nap by age 4.'
        },
        '49-72': {
            motor: 'Stands on one foot 10+ seconds, hops/skips, can do somersaults.',
            speech: 'Speaks very clearly. Tells stories. Uses future tense. 2000+ word vocabulary.',
            social: 'Wants to please friends, agrees to rules, can distinguish real from pretend.',
            cognitive: 'Counts 10+ objects, draws a person with 6+ body parts, prints some letters.',
            feeding: 'Eats independently with good manners. Understands healthy vs unhealthy foods.',
            sleep: 'Sleeps 10-12 hours. Usually no nap needed.'
        }
    };

    // ── Article-based recommendations ─────────────────────────────────
    const ARTICLE_RECS = {
        motor: [
            { title: 'Tummy Time Activities for Strong Babies', tip: 'Start with 3-5 minutes of tummy time, 2-3 times a day. Place colorful toys just out of reach to encourage movement.' },
            { title: 'Indoor Obstacle Courses for Toddlers', tip: 'Use pillows, tunnels, and soft blocks to create a safe obstacle course. This builds gross motor skills and coordination.' },
            { title: 'Fine Motor Play Ideas', tip: 'Threading beads, playdough, and stacking blocks help develop fine motor skills and hand-eye coordination.' }
        ],
        speech: [
            { title: 'Reading Aloud: The Key to Language Development', tip: 'Read to your child for at least 15-20 minutes daily. Point at pictures and ask "What\'s this?" to encourage vocabulary.' },
            { title: 'Songs and Rhymes for Speech Development', tip: 'Singing nursery rhymes helps with rhythm, pronunciation, and memory. "Itsy Bitsy Spider" and "Wheels on the Bus" are excellent choices.' },
            { title: 'Encouraging First Words', tip: 'Narrate your daily activities: "Mommy is cooking dinner. Look at the red tomato!" This builds vocabulary naturally.' }
        ],
        sleep: [
            { title: 'Creating the Perfect Bedtime Routine', tip: 'Follow a consistent sequence: bath → pajamas → book → lullaby → bed. Start 30 minutes before target bedtime.' },
            { title: 'Managing Night Wakings', tip: 'Keep the room dark and quiet during night feeds. Avoid stimulation. This teaches baby the difference between day and night.' },
            { title: 'Nap Transitions Guide', tip: 'Watch for signs of readiness: fighting naps, taking longer to fall asleep, or waking earlier. Transition gradually over 2-3 weeks.' }
        ],
        feeding: [
            { title: 'Starting Solid Foods', tip: 'Begin with single-ingredient purées (sweet potato, avocado, banana). Wait 3-5 days between new foods to check for allergies.' },
            { title: 'Dealing with Picky Eaters', tip: 'Offer the same food up to 15 times before assuming rejection. Make meals fun with shapes and colors. Never force-feed.' },
            { title: 'Healthy Snack Ideas for Toddlers', tip: 'Try cheese cubes, banana slices, soft-cooked veggie sticks, yogurt, or whole grain crackers with hummus.' }
        ],
        social: [
            { title: 'Building Social Skills Through Play', tip: 'Arrange short playdates (30-60 min for toddlers). Practice sharing with a timer: "Your turn for 2 minutes, then friend\'s turn."' },
            { title: 'Managing Separation Anxiety', tip: 'Practice short separations first. Always say goodbye (never sneak out). Create a goodbye ritual like a special handshake.' },
            { title: 'Teaching Emotional Intelligence', tip: 'Name emotions when you see them: "You seem frustrated." Use picture books about feelings. Validate their emotions before redirecting.' }
        ],
        attention: [
            { title: 'Focus-Building Activities', tip: 'Puzzles, sorting games, and treasure hunts help develop attention span. Start with 5-minute activities and gradually increase duration.' },
            { title: 'Reducing Screen Time', tip: 'Replace screen time with interactive activities. For under 2: zero screen time recommended. For 2-5: max 1 hour of quality content.' },
            { title: 'Mindfulness for Young Children', tip: 'Try "belly breathing" — place a stuffed animal on their tummy and watch it rise and fall. This builds focus and calm.' }
        ],
        health: [
            { title: 'Vaccination Schedule Guide', tip: 'Keep your child\'s immunization record up to date. Consult your pediatrician for the recommended schedule in your region.' },
            { title: 'When to See a Pediatrician', tip: 'Seek immediate care for: high fever (>38.5°C in infants), difficulty breathing, persistent vomiting, or unusual lethargy.' },
            { title: 'Common Childhood Illnesses', tip: 'Keep a first-aid kit ready. Learn to manage mild fevers, colds, and minor injuries at home. Know when to escalate to a doctor.' }
        ],
        growth: [
            { title: 'Understanding Growth Charts', tip: 'Growth percentiles show your child compared to others the same age. Consistent growth along a curve matters more than the percentile number.' },
            { title: 'Nutrition for Healthy Growth', tip: 'Ensure adequate protein, calcium, and vitamin D. Dairy, lean meats, beans, and fortified cereals support healthy growth.' },
            { title: 'Growth Spurts Guide', tip: 'Growth spurts cause increased hunger, fussiness, and sleep changes. They typically last 2-3 days. Extra feeding and comfort help.' }
        ],
        safety: [
            { title: 'Childproofing Your Home', tip: 'Cover outlets, secure furniture to walls, use safety gates, and keep small objects out of reach. Reassess as your child grows.' },
            { title: 'Water Safety Essentials', tip: 'Never leave a child unattended near water — even in shallow baths. Start swim lessons from age 1. Fence pools on all 4 sides.' },
            { title: 'Car Seat Safety', tip: 'Rear-facing until at least age 2 or until reaching the car seat\'s maximum height/weight. Check seat installation with a certified technician.' }
        ],
        hygiene: [
            { title: 'Teaching Handwashing', tip: 'Sing "Happy Birthday" twice for 20-second hand washing. Use a step stool so they can reach the sink independently.' },
            { title: 'Dental Care for Babies', tip: 'Start brushing as soon as the first tooth appears. Use a rice-grain amount of fluoride toothpaste. First dentist visit by age 1.' },
            { title: 'Potty Training Readiness', tip: 'Signs of readiness: staying dry for 2+ hours, showing interest in the toilet, pulling at wet diapers. Average age: 24-36 months.' }
        ]
    };

    function getAgeRange(ageMonths) {
        if (ageMonths <= 3) return '0-3';
        if (ageMonths <= 6) return '4-6';
        if (ageMonths <= 9) return '7-9';
        if (ageMonths <= 12) return '10-12';
        if (ageMonths <= 18) return '13-18';
        if (ageMonths <= 24) return '19-24';
        if (ageMonths <= 36) return '25-36';
        if (ageMonths <= 48) return '37-48';
        return '49-72';
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
            growthHistory: c.growth_history || [],
            speech: c._speech || null,
            motorPct: c._motorPct,
            badges: c.badge_count || 0,
            conditions: c.health_condition || '',
            ssn: c.ssn || ''
        };
    }

    // ── Build child profile summary ──────────────────────────────────
    function buildChildProfileResponse() {
        const ctx = getChildContext();
        if (!ctx.name) return 'No child profile found. Please add a child to your account first. 👶';

        const ageRange = getAgeRange(ctx.ageMonths);
        const milestones = MILESTONES_DB[ageRange];

        let response = `<div style="margin-bottom:1rem;">`;
        response += `<div style="display:flex;align-items:center;gap:0.75rem;margin-bottom:0.75rem;padding:0.75rem;background:linear-gradient(135deg,rgba(99,102,241,0.1),rgba(139,92,246,0.1));border-radius:12px;">`;
        response += `<div style="width:42px;height:42px;border-radius:50%;background:linear-gradient(135deg,#6366f1,#8b5cf6);display:flex;align-items:center;justify-content:center;font-size:1.25rem;color:white;">${ctx.gender === 'Male' ? '👦' : '👧'}</div>`;
        response += `<div><strong style="font-size:1rem;">${ctx.name}</strong><br><span style="font-size:0.8rem;color:#64748b;">${ctx.age} • ${ctx.gender || 'Not specified'}</span></div>`;
        response += `</div>`;

        // Growth data
        if (ctx.growth) {
            response += `<div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:0.5rem;margin-bottom:0.75rem;">`;
            response += `<div style="text-align:center;padding:0.5rem;background:#f0fdf4;border-radius:8px;"><span style="font-size:0.7rem;color:#166534;display:block;">Weight</span><strong style="font-size:0.95rem;">${ctx.growth.weight} kg</strong></div>`;
            response += `<div style="text-align:center;padding:0.5rem;background:#eff6ff;border-radius:8px;"><span style="font-size:0.7rem;color:#1e40af;display:block;">Height</span><strong style="font-size:0.95rem;">${ctx.growth.height} cm</strong></div>`;
            if (ctx.growth.head_circumference) {
                response += `<div style="text-align:center;padding:0.5rem;background:#fef3c7;border-radius:8px;"><span style="font-size:0.7rem;color:#92400e;display:block;">Head</span><strong style="font-size:0.95rem;">${ctx.growth.head_circumference} cm</strong></div>`;
            }
            response += `</div>`;
        }

        // Badges
        if (ctx.badges > 0) {
            response += `<div style="padding:0.5rem;background:#faf5ff;border-radius:8px;margin-bottom:0.75rem;text-align:center;">🏆 <strong>${ctx.badges}</strong> badges earned!</div>`;
        }

        response += `</div>`;
        response += `<strong>What would you like to know about ${ctx.name}?</strong>`;
        response += buildTopicButtons();
        return response;
    }

    // ── Build topic-specific response ────────────────────────────────
    function buildTopicResponse(topic) {
        const ctx = getChildContext();
        const name = ctx.name || 'your child';
        const ageMonths = ctx.ageMonths || 12;
        const ageRange = getAgeRange(ageMonths);
        const milestones = MILESTONES_DB[ageRange] || MILESTONES_DB['10-12'];
        const t = topic.toLowerCase();

        let response = `<div style="border-left:3px solid #6366f1;padding-left:0.75rem;margin-bottom:0.75rem;">`;
        response += `<strong style="font-size:0.95rem;">📋 ${capitalize(topic)} — ${name} (${ctx.age || 'age unknown'})</strong>`;
        response += `</div>`;

        // Age-appropriate milestones
        let milestoneKey = null;
        let icon = '📌';
        let articleKey = topic;

        if (t.includes('motor')) {
            milestoneKey = 'motor'; icon = '🏃'; articleKey = 'motor';
        } else if (t.includes('speech') || t.includes('language') || t.includes('talk') || t.includes('communication')) {
            milestoneKey = 'speech'; icon = '🗣️'; articleKey = 'speech';
        } else if (t.includes('social') || t.includes('friend') || t.includes('play')) {
            milestoneKey = 'social'; icon = '🤝'; articleKey = 'social';
        } else if (t.includes('cognitive') || t.includes('brain') || t.includes('learn') || t.includes('attention')) {
            milestoneKey = 'cognitive'; icon = '🧠'; articleKey = 'attention';
        } else if (t.includes('sleep') || t.includes('nap') || t.includes('bedtime')) {
            milestoneKey = 'sleep'; icon = '😴'; articleKey = 'sleep';
        } else if (t.includes('feed') || t.includes('eat') || t.includes('food') || t.includes('nutrition')) {
            milestoneKey = 'feeding'; icon = '🍼'; articleKey = 'feeding';
        } else if (t.includes('growth') || t.includes('weight') || t.includes('height')) {
            milestoneKey = 'motor'; icon = '📊'; articleKey = 'growth';
        } else if (t.includes('health') || t.includes('doctor') || t.includes('checkup') || t.includes('vaccine')) {
            milestoneKey = null; icon = '🏥'; articleKey = 'health';
        } else if (t.includes('safety') || t.includes('childproof')) {
            milestoneKey = null; icon = '🛡️'; articleKey = 'safety';
        } else if (t.includes('hygiene') || t.includes('potty') || t.includes('teeth') || t.includes('dental')) {
            milestoneKey = null; icon = '🧼'; articleKey = 'hygiene';
        } else if (t.includes('activit')) {
            milestoneKey = 'motor'; icon = '🎮'; articleKey = 'motor';
        }

        // Milestone for this age
        if (milestoneKey && milestones[milestoneKey]) {
            response += `<div style="background:linear-gradient(135deg,#f0f9ff,#e0f2fe);padding:0.75rem;border-radius:10px;margin-bottom:0.75rem;">`;
            response += `<strong>${icon} Expected Milestones (${ageRange} months):</strong><br>`;
            response += `${milestones[milestoneKey]}`;
            response += `</div>`;
        }

        // Child-specific data
        if (t.includes('growth') && ctx.growth) {
            response += `<div style="background:#f0fdf4;padding:0.75rem;border-radius:10px;margin-bottom:0.75rem;">`;
            response += `<strong>📊 ${name}'s Current Measurements:</strong><br>`;
            response += `• Weight: <strong>${ctx.growth.weight} kg</strong><br>`;
            response += `• Height: <strong>${ctx.growth.height} cm</strong>`;
            if (ctx.growth.head_circumference) {
                response += `<br>• Head Circumference: <strong>${ctx.growth.head_circumference} cm</strong>`;
            }
            if (ctx.growth.recorded_at) {
                response += `<br><span style="font-size:0.75rem;color:#64748b;">Last recorded: ${new Date(ctx.growth.recorded_at).toLocaleDateString()}</span>`;
            }
            response += `</div>`;
        }

        if (t.includes('motor') && ctx.motorPct !== undefined) {
            response += `<div style="background:#f0fdf4;padding:0.75rem;border-radius:10px;margin-bottom:0.75rem;">`;
            response += `<strong>🏃 ${name}'s Motor Skills Progress:</strong><br>`;
            response += `<div style="background:#e2e8f0;border-radius:8px;height:8px;margin:0.5rem 0;"><div style="height:100%;background:linear-gradient(90deg,#22c55e,#16a34a);border-radius:8px;width:${Math.min(ctx.motorPct, 100)}%;transition:width 0.5s;"></div></div>`;
            response += `<strong>${ctx.motorPct}%</strong> of milestones completed`;
            response += `</div>`;
        }

        if (t.includes('speech') && ctx.speech) {
            response += `<div style="background:#faf5ff;padding:0.75rem;border-radius:10px;margin-bottom:0.75rem;">`;
            response += `<strong>🗣️ ${name}'s Speech Analysis:</strong><br>`;
            if (ctx.speech.clarity_score) response += `• Clarity Score: <strong>${ctx.speech.clarity_score}%</strong><br>`;
            if (ctx.speech.word_count) response += `• Words Detected: <strong>${ctx.speech.word_count}</strong><br>`;
            response += `</div>`;
        }

        // Article recommendations
        const articles = ARTICLE_RECS[articleKey] || ARTICLE_RECS['health'];
        if (articles) {
            response += `<div style="margin-top:0.75rem;">`;
            response += `<strong>📚 Recommended Reading:</strong><br>`;
            articles.forEach(a => {
                response += `<div style="background:#f8fafc;padding:0.6rem;border-radius:8px;margin-top:0.5rem;border-left:3px solid #6366f1;">`;
                response += `<strong style="font-size:0.85rem;">${a.title}</strong><br>`;
                response += `<span style="font-size:0.8rem;color:#475569;">💡 ${a.tip}</span>`;
                response += `</div>`;
            });
            response += `</div>`;
        }

        // Personalized recommendation
        response += `<div style="background:linear-gradient(135deg,#eef2ff,#e0e7ff);padding:0.75rem;border-radius:10px;margin-top:0.75rem;">`;
        response += `<strong>✨ Personalized Tip for ${name}:</strong><br>`;
        response += getPersonalizedTip(t, ctx);
        response += `</div>`;

        response += buildTopicButtons();
        return response;
    }

    function getPersonalizedTip(topic, ctx) {
        const name = ctx.name || 'your child';
        const age = ctx.ageMonths || 12;

        if (topic.includes('motor')) {
            if (age < 6) return `At ${ctx.age}, focus on tummy time and reaching activities. Place toys slightly out of ${name}'s reach during floor play.`;
            if (age < 12) return `${name} is at a great age for crawling adventures! Create safe spaces to explore. Try the "tunnel chase" game.`;
            if (age < 24) return `Encourage ${name} to walk independently. Dancing to music is excellent for balance and coordination!`;
            return `${name} is ready for more complex activities like climbing, jumping, and pedaling. Consider a balance bike!`;
        }
        if (topic.includes('speech') || topic.includes('communication')) {
            if (age < 6) return `Talk to ${name} frequently using a warm, animated voice. Narrate everything: "Now we're changing your diaper!"`;
            if (age < 12) return `${name} is in a critical period for language! Respond to every babble as if it's a real conversation.`;
            if (age < 24) return `Read to ${name} daily and ask simple questions: "Where is the dog?" Point and label objects throughout the day.`;
            return `Encourage ${name} to tell stories about their day. Ask open-ended questions like "What was the best part of today?"`;
        }
        if (topic.includes('sleep')) {
            if (age < 6) return `Create a dark, quiet sleep environment for ${name}. White noise can help. Swaddling may still be comforting.`;
            if (age < 12) return `${name} should have a consistent bedtime routine. Try: bath → book → lullaby → bed. Keep it to 30 minutes.`;
            if (age < 24) return `If ${name} is resisting bedtime, offer choices: "Do you want the blue pajamas or the red ones?" This gives a sense of control.`;
            return `${name} may be ready to drop naps. If bedtime is a struggle, try pushing it 15 minutes later.`;
        }
        if (topic.includes('feed') || topic.includes('eat')) {
            if (age < 6) return `${name} should be exclusively breastfed or formula-fed. No solid foods before 4-6 months.`;
            if (age < 12) return `Introduce new foods to ${name} one at a time. Sweet potato, avocado, and banana are great starters!`;
            if (age < 24) return `Let ${name} self-feed with finger foods. It's messy but develops independence and fine motor skills!`;
            return `Involve ${name} in meal prep! Washing vegetables, stirring ingredients — it makes them more likely to eat what they helped make.`;
        }
        if (topic.includes('growth')) {
            if (ctx.growth) return `${name}'s latest measurements show ${ctx.growth.weight}kg and ${ctx.growth.height}cm. Track regularly for consistent growth patterns.`;
            return `Regular growth monitoring helps catch potential issues early. Log ${name}'s measurements monthly.`;
        }
        if (topic.includes('social')) {
            if (age < 12) return `${name} learns social skills from watching you! Make eye contact, smile, and talk during everyday activities.`;
            if (age < 24) return `Parallel play is normal at this age. ${name} will start interactive play soon. Practice sharing with simple turn-taking games.`;
            return `Arrange regular playdates for ${name}. Practice emotion words: "I see you're feeling frustrated. It's okay to feel that way."`;
        }
        return `Every child develops at their own pace. If you have concerns about ${name}'s development, consider booking an appointment with a specialist through Bright Steps.`;
    }

    function capitalize(str) {
        return str.charAt(0).toUpperCase() + str.slice(1);
    }

    function buildTopicButtons() {
        return `<div style="margin-top:1rem;display:flex;flex-wrap:wrap;gap:0.4rem;">` +
            `<span class="topic-chip" onclick="sendTopicMessage('motor skills')" style="display:inline-block;margin:0;">🏃 Motor Skills</span>` +
            `<span class="topic-chip" onclick="sendTopicMessage('speech development')" style="display:inline-block;margin:0;">🗣️ Speech</span>` +
            `<span class="topic-chip" onclick="sendTopicMessage('sleep guidance')" style="display:inline-block;margin:0;">😴 Sleep</span>` +
            `<span class="topic-chip" onclick="sendTopicMessage('feeding tips')" style="display:inline-block;margin:0;">🍼 Feeding</span>` +
            `<span class="topic-chip" onclick="sendTopicMessage('growth tracking')" style="display:inline-block;margin:0;">📊 Growth</span>` +
            `<span class="topic-chip" onclick="sendTopicMessage('social skills')" style="display:inline-block;margin:0;">🤝 Social</span>` +
            `<span class="topic-chip" onclick="sendTopicMessage('attention & cognitive')" style="display:inline-block;margin:0;">🧠 Cognitive</span>` +
            `<span class="topic-chip" onclick="sendTopicMessage('health checkup')" style="display:inline-block;margin:0;">🏥 Health</span>` +
            `<span class="topic-chip" onclick="sendTopicMessage('safety tips')" style="display:inline-block;margin:0;">🛡️ Safety</span>` +
            `<span class="topic-chip" onclick="sendTopicMessage('hygiene & potty')" style="display:inline-block;margin:0;">🧼 Hygiene</span>` +
            `<span class="topic-chip" onclick="sendTopicMessage('child profile')" style="display:inline-block;margin:0;">👶 Profile</span>` +
            `</div>`;
    }

    // ── Create Widget HTML ────────────────────────────────────────────
    function createChatWidget() {
        if (document.getElementById('chatbot-toggle')) return; // Already created

        const ctx = getChildContext();
        const childGreeting = ctx.name
            ? `I'm here to help with <strong>${ctx.name}</strong>'s development! (${ctx.age || ''})`
            : 'Add a child profile to get personalized insights!';

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
                        <div class="chatbot-status">● Online${ctx.name ? ' — Helping with ' + ctx.name : ''}</div>
                    </div>
                </div>
                <button class="chatbot-close" onclick="toggleChatbot()" aria-label="Close chat">✕</button>
            </div>
            <div class="chatbot-messages" id="chatbot-messages">
                <div class="chat-msg bot">
                    <div class="chat-bubble">
                        Hello! 👋 ${childGreeting}<br><br>
                        <strong>Choose a topic to get personalized insights:</strong>
                        ${buildTopicButtons()}
                    </div>
                </div>
            </div>
        `;

        document.body.appendChild(btn);
        document.body.appendChild(panel);

        btn.addEventListener('click', toggleChatbot);
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
    };

    // ── Send Message ─────────────────────────────────────────────────
    window.sendMessage = async function (msg) {
        if (!msg || !msg.trim()) return;

        const input = document.getElementById('chatbot-input');
        if (input) input.value = '';

        addMessage(msg, 'user');

        let reply;
        const lowerMsg = msg.toLowerCase().trim();

        // Handle profile request
        if (lowerMsg.includes('profile') || lowerMsg.includes('child info') || lowerMsg.includes('my child') || lowerMsg.includes('who is')) {
            reply = buildChildProfileResponse();
        }
        // Handle greeting
        else if (['hello', 'hi', 'hey', 'help', 'start'].some(g => lowerMsg === g || lowerMsg.startsWith(g + ' '))) {
            const ctx = getChildContext();
            reply = `Hello! 👋 ${ctx.name ? `I'm tracking <strong>${ctx.name}</strong>'s development (${ctx.age}).` : 'Welcome to Bright Steps!'}<br><br>`;
            reply += `<strong>Here's what I can help with:</strong>`;
            reply += buildTopicButtons();
        }
        // Handle all topic queries with rich data
        else {
            reply = buildTopicResponse(msg);
        }

        const typingId = addTypingIndicator();
        setTimeout(() => {
            removeTypingIndicator(typingId);
            addMessage(reply, 'bot');
        }, 400 + Math.random() * 400);
    };

    window.sendTopicMessage = function (topic) {
        sendMessage(topic);
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
        const container = document.getElementById('chatbot-messages');
        if (container) {
            const firstMsg = container.querySelector('.chat-msg.bot .chat-bubble');
            if (firstMsg) {
                const childGreeting = ctx.name
                    ? `I'm here to help with <strong>${ctx.name}</strong>'s development! (${ctx.age || ''})`
                    : 'Add a child profile to get personalized insights!';
                firstMsg.innerHTML = `Hello! 👋 ${childGreeting}<br><br><strong>Choose a topic to get personalized insights:</strong>${buildTopicButtons()}`;
            }
        }

        const headerStatus = document.querySelector('.chatbot-status');
        if (headerStatus && ctx.name) {
            headerStatus.textContent = `● Online — Helping with ${ctx.name}`;
        }
    };

    // ── Init ──────────────────────────────────────────────────────────
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', createChatWidget);
    } else {
        createChatWidget();
    }
})();
