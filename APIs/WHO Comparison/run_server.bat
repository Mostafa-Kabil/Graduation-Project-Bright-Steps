@echo off
echo Starting Bright Steps WHO Comparison API on port 8007...
pip install -r requirements.txt
uvicorn app:app --reload --port 8007
pause
