@echo off
echo Starting Bright Steps Child Profile API on port 8009...
pip install -r requirements.txt
uvicorn app:app --reload --port 8009
pause
