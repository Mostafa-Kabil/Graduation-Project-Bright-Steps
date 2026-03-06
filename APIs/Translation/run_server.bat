@echo off
echo Starting Translation API server on port 8001...
python -m uvicorn app:app --reload --host 127.0.0.1 --port 8001
pause
