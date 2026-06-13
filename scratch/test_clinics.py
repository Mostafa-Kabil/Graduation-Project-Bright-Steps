import urllib.request
import json

try:
    response = urllib.request.urlopen('http://localhost/Bright%20Steps%20Website/api_get_clinics.php')
    data = response.read().decode('utf-8')
    parsed = json.loads(data)
    print("Clinics 0 to 5:")
    for c in parsed.get('clinics', [])[:5]:
        print(f"Name: {c.get('clinic_name')}, Logo: {repr(c.get('logo'))}")
except Exception as e:
    print("Error:", e)
