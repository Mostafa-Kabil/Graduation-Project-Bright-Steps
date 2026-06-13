import urllib.request
import json

data = json.dumps({
    'appointment_id': 10,
    'specialist_id': 40,
    'specialist_rating': 5,
    'specialist_comment': 'Great',
    'clinic_id': 9,
    'clinic_rating': 4,
    'clinic_comment': 'Good clinic'
}).encode('utf-8')

req = urllib.request.Request('http://localhost/Bright%20Steps%20Website/api_doctor_review.php?action=submit', data=data, method='POST')
req.add_header('Content-Type', 'application/json')
# Need to pass cookies for session, let's just bypass session auth for testing? No, I can't bypass unless I edit the file.
