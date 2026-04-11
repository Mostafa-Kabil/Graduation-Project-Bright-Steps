import json
import random

categories = ['health', 'nutrition', 'development', 'parenting', 'hygiene', 'safety', 'activities', 'education']
ageGroups = ['infant', 'toddler', 'preschool', 'school', 'all']

templates = {
    'health': [
        ('Understanding Fever in Children', 'A comprehensive guide to managing fevers, knowing when to worry, and what medications to use.'),
        ('The Immune System Boost', 'Nutrition and lifestyle habits to keep your child healthy during flu season.'),
        ('Sleep Training Basics', 'How to establish healthy sleep cycles for growing minds.'),
        ('Dealing with Colic', 'Identifying colic symptoms and effective soothing techniques.'),
        ('Common Childhood Rashes', 'A visual and descriptive guide to identifying benign versus serious skin conditions.')
    ],
    'nutrition': [
        ('First Solids Survival Guide', 'Everything you need to know about transitioning your baby to solid foods safely.'),
        ('Picky Eaters No More', 'Strategies for introducing new textures and flavors without the tears.'),
        ('Hydration Station', 'How much water does your child actually need?'),
        ('Brain-Boosting Lunches', 'Packable meals that improve focus and energy during school hours.'),
        ('Navigating Food Allergies', 'How to spot severe allergies early and manage safe eating environments.')
    ],
    'development': [
        ('Milestones to Watch For', 'Key developmental leaps during the first 36 months.'),
        ('The Terrible Twos Demystified', 'Understanding the cognitive leap behind the tantrums.'),
        ('Speech Delays vs Quirks', 'When should your child be talking, and when to seek speech therapy.'),
        ('Motor Skills 101', 'Activities to develop gross and fine motor coordination.'),
        ('Building Emotional Intelligence', 'Teaching toddlers how to name and manage big feelings.')
    ],
    'parenting': [
        ('Positive Discipline', 'How to enforce boundaries without breaking their spirit.'),
        ('Managing Overstimulation', 'Recognizing sensory overload and creating calm down corners.'),
        ('Screen Time Rules', 'Evidence-based guidelines on healthy digital consumption.'),
        ('The Co-Parenting Balance', 'Maintaining a unified front during stressful parenting moments.'),
        ('Fostering Independence', 'Teaching children how to perform basic tasks by themselves.')
    ],
    'hygiene': [
        ('Making Bath Time Fun', "Turning screams into splashes with engaging tub activities."),
        ('Potty Training 101', "The 3-day method vs child-led approaches."),
        ('Brushing Tiny Teeth', "Establishing a dental routine they won't fight."),
        ('Handwashing Habits', "Songs and games to ensure 20 seconds of scrubbing."),
        ('Hair Care for Kids', "Managing tangles, lice prevention, and gentle washing.")
    ],
    'safety': [
        ('Childproofing Room by Room', 'The ultimate checklist for a hazardous-free home.'),
        ('Car Seat Safety Guidelines', 'Rear-facing vs forward-facing: The latest AAP recommendations.'),
        ('Water Safety & Swimming', 'Drowning prevention and early swim-lesson guidance.'),
        ('Playground Awareness', 'Spotting unsafe equipment and teaching stranger danger.'),
        ('First Aid Essentials', 'What every parent must have in their emergency kit.')
    ],
    'activities': [
        ('Sensory Bins at Home', 'Cheap and easy sensory setups using rice, beans, and water.'),
        ('Rainy Day Obstacle Courses', 'Burning toddler energy using couch cushions.'),
        ('Nature Scavenger Hunts', 'Getting outside and learning about the local ecosystem.'),
        ('DIY Playdough Recipes', 'Safe, non-toxic recipes you can make in the kitchen.'),
        ('Music and Rhythm Games', 'Boosting auditory processing with DIY instruments.')
    ],
    'education': [
        ('Phonics Without Pressure', 'Introducing letters and sounds casually.'),
        ('Math in the Kitchen', 'Using baking to teach fractions and measurements.'),
        ('Raising Bilingual Kids', 'The one parent, one language approach outlaid.'),
        ('The Power of Reading Aloud', 'How 15 minutes of reading transforms childhood literacy.'),
        ('STEM for Toddlers', 'Basic physics and biology experiments for 3-year-olds.')
    ]
}

articles = []

for cat in categories:
    base_list = templates[cat]
    for i in range(13): # 8 * 13 = 104 articles
        base_title, base_summary = base_list[i % len(base_list)]
        
        modifier_prefix = random.choice(['The Ultimate Guide to ', 'Essential Tips for ', 'Expert Insight on ', 'Quick Guide: ', 'Deep Dive: ', ''])
        modifier_suffix = random.choice(['', ' - What Parents Must Know', ' - A Step-by-Step Approach', ' - Modern Pediatric Advice'])
        
        title = f'{modifier_prefix}{base_title}{modifier_suffix}'
        summary = base_summary if i < len(base_list) else base_summary + f' Insight #{i} on achieving the best results.'
        age = random.choice(ageGroups)
        
        content = f'''
        <div class="article-modal-category cat-{cat}">{cat.capitalize()}</div>
        <h2>{title}</h2>
        <div class="ai-generated-content" style="line-height:1.7; color: var(--text-primary); margin-top: 1.5rem;">
            <p><strong>Overview:</strong> {summary}</p>
            <p>Parenting is a continuous journey of learning and adapting. When it comes to <em>{base_title.lower()}</em>, having the right information can make all the difference. In this comprehensive guide, we'll walk through actionable steps, evidence-based recommendations, and practical tips designed specifically for families with children in the <strong>{age.capitalize()}</strong> age bracket.</p>
            
            <h3>Understanding the Basics</h3>
            <p>Before diving into advanced strategies, it is critical to grasp the foundational concepts. Many parents feel overwhelmed when faced with new developmental phases. Remember that every child is unique, and progress is rarely perfectly linear. Establishing a consistent, loving environment is your most powerful tool.</p>
            
            <ul>
                <li><strong>Consistency is Key:</strong> Children thrive on routine. Try to apply these guidelines in a predictable manner every day.</li>
                <li><strong>Monitor and Adjust:</strong> Keep a journal of your child's responses. If a specific approach isn't working after a few weeks, it's okay to pivot.</li>
                <li><strong>Seek Professional Advice:</strong> While this guide provides general best practices, always consult your registered pediatrician for tailored health and developmental counsel.</li>
            </ul>

            <h3>Practical Steps You Can Take Today</h3>
            <p>Start small. Trying to implement every strategy at once often leads to burnout for both the parent and the child. Pick one or two specific activities or rules from this guide and focus exclusively on mastering them this week.</p>
            
            <p>By staying patient and leaning on the Bright Steps community's resources, you are already setting your child up for incredible success!</p>
        </div>
        '''
        articles.append({
            'title': title.strip(),
            'summary': summary,
            'category': cat,
            'ageGroup': age,
            'content': content
        })

js_content = 'const articles = ' + json.dumps(articles, indent=4) + ';\n'

with open(r'c:\xampp\htdocs\Bright Steps Website\scripts\articles_data.js', 'w', encoding='utf-8') as f:
    f.write(js_content)

print(f'Successfully generated {len(articles)} articles into scripts/articles_data.js')
