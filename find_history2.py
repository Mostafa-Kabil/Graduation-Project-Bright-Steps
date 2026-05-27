import os
import json

history_dir = r"C:\Users\mosta\AppData\Roaming\Code\User\History"
for root, dirs, files in os.walk(history_dir):
    for f in files:
        if f == "entries.json":
            path = os.path.join(root, f)
            try:
                with open(path, 'r', encoding='utf-8') as file:
                    data = json.load(file)
                    resource = data.get('resource', '')
                    if 'xampp' in resource.lower() and 'dashboard' in resource.lower():
                        print("Found " + resource + " in " + path)
                        for entry in data.get('entries', []):
                            print("  - " + entry.get('id') + " at " + str(entry.get('timestamp')))
            except:
                pass
