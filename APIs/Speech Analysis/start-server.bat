@echo off
REM Bright Steps Speech Analysis API - Server Starter
REM This script starts the Python FastAPI server on port 8000

cd /d "%~dp0"

echo Starting Bright Steps Speech Analysis API...
echo.

REM Check if Python is available
where python >nul 2>&1
if %ERRORLEVEL% neq 0 (
    echo ERROR: Python is not installed or not in PATH
    echo Please install Python from https://python.org
    pause
    exit /b 1
)

REM Check if required packages are installed
echo Checking dependencies...
python -c "import fastapi, uvicorn, whisper, torch" 2>nul
if %ERRORLEVEL% neq 0 (
    echo Installing required packages...
    pip install fastapi uvicorn openai-whisper torch
)

REM Start the server
echo Starting server on http://localhost:8000
echo Press Ctrl+C to stop
echo.

python -m uvicorn app:app --host 0.0.0.0 --port 8000 --reload
