import os
import re

# 1. Update app.py
app_py_path = r"c:\xampp\htdocs\Bright Steps Website\APIs\Motor Skills\app.py"
with open(app_py_path, "r", encoding="utf-8") as f:
    app_content = f.read()

# Replace the Motor Skills combined category with two separate categories
old_motor_block = """        # 🦵 Motor Skills Category (combines gross and fine motor)
        gross_behaviors = BEHAVIOR_DATABASE.get("gross_motor", {}).get(age_range, [])
        fine_behaviors = BEHAVIOR_DATABASE.get("fine_motor", {}).get(age_range, [])
        motor_behaviors = gross_behaviors + fine_behaviors
        categories.append({
            "category_name": "🦵 Motor Skills",
            "category_type": "motor_skills",
            "category_description": "Physical development including gross motor (walking, running, balance) and fine motor (grasping, drawing, coordination)",
            "behaviors": [{"behavior_details": b["behavior"], "typical_age": b["typical_age"]} for b in motor_behaviors]
        })"""

new_motor_block = """        # ⚡ Gross Motor Category
        gross_behaviors = BEHAVIOR_DATABASE.get("gross_motor", {}).get(age_range, [])
        categories.append({
            "category_name": "⚡ Gross Motor",
            "category_type": "gross_motor",
            "category_description": "Physical development involving large muscle movements like walking, running, and balance",
            "behaviors": [{"behavior_details": b["behavior"], "typical_age": b["typical_age"]} for b in gross_behaviors]
        })

        # 🤏 Fine Motor Category
        fine_behaviors = BEHAVIOR_DATABASE.get("fine_motor", {}).get(age_range, [])
        categories.append({
            "category_name": "🤏 Fine Motor",
            "category_type": "fine_motor",
            "category_description": "Physical development involving small muscle movements like grasping, drawing, and coordination",
            "behaviors": [{"behavior_details": b["behavior"], "typical_age": b["typical_age"]} for b in fine_behaviors]
        })"""

if old_motor_block in app_content:
    app_content = app_content.replace(old_motor_block, new_motor_block)
else:
    print("Could not find motor block in app.py to replace")

# Replace type mapping in app.py
old_mapping = """                    "🦵 Motor Skills": ["gross_motor", "fine_motor"],
                    "🧠 Attention": ["attention"],
                    "💬 Communication": ["communication"],
                    "🤝 Social Skills": ["social_skills"]"""

new_mapping = """                    "⚡ Gross Motor": ["gross_motor"],
                    "🤏 Fine Motor": ["fine_motor"],
                    "🧠 Attention": ["attention"],
                    "💬 Communication": ["communication"],
                    "🤝 Social Skills": ["social_skills"]"""

app_content = app_content.replace(old_mapping, new_mapping)

with open(app_py_path, "w", encoding="utf-8") as f:
    f.write(app_content)
print("Updated app.py")


# 2. Update dashboard.js
dashboard_js_path = r"c:\xampp\htdocs\Bright Steps Website\dashboards\parent\dashboard.js"
with open(dashboard_js_path, "r", encoding="utf-8") as f:
    js_content = f.read()

# Fix updateTrafficLightAlerts
old_alert = """                alertEl.innerHTML = `${status.icon} <span style="font-size:0.7rem;margin-left:0.25rem;color:${status.color};font-weight:600;">${status.label}</span>`;"""

new_alert = """                alertEl.style.display = 'flex';
                alertEl.style.flexDirection = 'column';
                alertEl.style.alignItems = 'center';
                alertEl.style.justifyContent = 'center';
                alertEl.style.gap = '2px';
                alertEl.style.flexShrink = '0';
                
                let labelHtml = status.label;
                if(status.label === 'Needs Attention') {
                    labelHtml = 'Needs<br>Attention';
                }
                
                alertEl.innerHTML = `<span style="font-size:1.1rem; line-height:1;">${status.icon}</span><span style="font-size:0.65rem; line-height:1.1; color:${status.color}; font-weight:700; text-align:center;">${labelHtml}</span>`;"""

if old_alert in js_content:
    js_content = js_content.replace(old_alert, new_alert)
else:
    print("Could not find alert block in dashboard.js to replace")

with open(dashboard_js_path, "w", encoding="utf-8") as f:
    f.write(js_content)
print("Updated dashboard.js")
