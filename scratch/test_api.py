import urllib.request
import json

try:
    response = urllib.request.urlopen('http://localhost/Bright%20Steps%20Website/api/api_get_specialist.php?id=40')
    data = response.read().decode('utf-8')
    print("Response data:")
    print(data)
    
    # Try parsing JSON to verify it's valid
    parsed = json.loads(data)
    print("Parsed correctly!")
    
except Exception as e:
    print("Error:", e)
    
