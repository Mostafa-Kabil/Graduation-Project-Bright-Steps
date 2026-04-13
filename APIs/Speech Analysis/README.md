# Bright Steps - Speech Analysis Service

## How to Start the Speech Analysis AI

The speech analysis feature requires a Python FastAPI server to be running on port 8000.

### Option 1: Double-click the batch file (Easiest)

1. Navigate to: `APIs/Speech Analysis/`
2. Double-click `start-server.bat`
3. A command window will open and the server will start
4. Keep this window open while using speech analysis
5. The server is ready when you see: `Uvicorn running on http://0.0.0.0:8000`

### Option 2: Start manually from command line

```bash
cd "C:\xampp\htdocs\Bright Steps Website\APIs\Speech Analysis"
python -m uvicorn app:app --port 8000 --host 0.0.0.0
```

### Requirements

Make sure you have Python installed with the required packages:

```bash
pip install fastapi uvicorn openai-whisper torch
```

### Troubleshooting

**Error: "Speech AI is offline"**
- Make sure the Python server is running (check for the command window)
- Verify port 8000 is not blocked by firewall
- Check that FFmpeg is installed and in PATH

**Error: "Module not found"**
- Run: `pip install fastapi uvicorn openai-whisper torch`


**Server won't start**
- Check if another application is using port 8000
- Try running the batch file as Administrator
- Verify Python is installed correctly

### Stopping the Server

- Press `Ctrl+C` in the command window
- Or simply close the window

### Testing the Server

Open your browser and go to: `http://localhost:8000`

You should see: `{"message":"Bright Steps Speech Analysis API is running!"}`
