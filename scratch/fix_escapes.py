import re

# Read the file
with open('js/specialist-profile.js', 'r', encoding='utf-8') as f:
    content = f.read()

# Replace escaped backticks with actual backticks
content = content.replace(r'\`', '`')

# Replace escaped dollars with actual dollars
content = content.replace(r'\$', '$')

# Write back
with open('js/specialist-profile.js', 'w', encoding='utf-8') as f:
    f.write(content)
