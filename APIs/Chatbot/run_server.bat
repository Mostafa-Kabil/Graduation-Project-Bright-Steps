@echo off
echo Starting Bright Steps Chatbot API on port 8015...
pip install -r requirements.txt
uvicorn app:app --reload --port 8015
pause
