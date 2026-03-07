@echo off
echo Starting Bright Steps Session Management API on port 8011...
pip install -r requirements.txt
uvicorn app:app --reload --port 8011
pause
