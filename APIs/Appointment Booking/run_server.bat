@echo off
echo Starting Bright Steps Appointment Booking API on port 8012...
pip install -r requirements.txt
uvicorn app:app --reload --port 8012
pause
