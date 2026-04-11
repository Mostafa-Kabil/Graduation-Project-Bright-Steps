import os, re
base = r'c:\xampp\htdocs\Bright Steps Website'

files = ['index.php', 'about.php', 'contact.php', 'pricing.php', 'help.php', 'privacy.php', 'terms.php', 'for-doctors.php', 'for-clinics.php', 'for-parents.php']
for f in files:
    path = os.path.join(base, f)
    if not os.path.exists(path): continue
    
    with open(path, 'r', encoding='utf-8') as file:
        content = file.read()
        
    head_match = re.search(r'<header class="header">', content)
    if not head_match: 
        print(f'Skipped {f} (No header tag found)')
        continue
    
    # We must be careful! For index.php, we just extracted things so we CAN replace it.
    # What is the bounds?
    # We look for the closing </nav> that represents the mobile-menu.
    mob = content.find('<nav class="mobile-menu"')
    if (mob == -1):
        print(f'Skipped {f} (No mobile menu found)')
        continue
    
    mobile_nav_end = content.find('</nav>', mob)
    if mobile_nav_end == -1: 
        print(f'Skipped {f} (No nav end found)')
        continue
    
    end_idx = mobile_nav_end + 6
    
    new_content = content[:head_match.start()] + "<?php include \'includes/public_header.php\'; ?>" + content[end_idx:]
    
    # Notice the footer logic
    foot_start = new_content.find('<footer class="footer">')
    foot_end = new_content.find('</footer>', foot_start)
    
    if foot_start != -1 and foot_end != -1:
        new_content = new_content[:foot_start] + "<?php include \'includes/public_footer.php\'; ?>" + new_content[foot_end+9:]
        
    with open(path, 'w', encoding='utf-8') as file:
        file.write(new_content)
    print(f'Updated {f}')
