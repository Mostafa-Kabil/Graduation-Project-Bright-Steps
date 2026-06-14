@echo off
REM Bright Steps Motor Skills API - Server Starter
cd /d "%~dp0"

echo Starting Bright Steps Motor Skills API...
echo.

where python >nul 2>&1
if %ERRORLEVEL% neq 0 (
    echo ERROR: Python is not installed or not in PATH
    exit /b 1
)

echo Checking dependencies...
python -c "import fastapi, uvicorn, mysql.connector" 2>nul
if %ERRORLEVEL% neq 0 (
    echo Installing required packages...
    pip install fastapi uvicorn mysql-connector-python python-dotenv
)

echo Checking for existing processes on port 8003...
for /f "tokens=5" %%a in ('netstat -aon ^| findstr :8003 ^| findstr LISTENING') do (
    echo Found process %%a on port 8003, killing it...
    taskkill /PID %%a /F >nul 2>&1
)
timeout /t 2 /nobreak >nul

echo Starting server on http://localhost:8003
python app.py
