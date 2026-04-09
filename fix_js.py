import sys

with open('dashboards/parent/dashboard.js', 'r', encoding='utf-8') as f:
    text = f.read()

# Replace the specific syntax error
text = text.replace("how ' + cn + '\\\'s stature", "how ' + cn + '\\'s stature")

with open('dashboards/parent/dashboard.js', 'w', encoding='utf-8') as f:
    f.write(text)
print("JS fixed!")
