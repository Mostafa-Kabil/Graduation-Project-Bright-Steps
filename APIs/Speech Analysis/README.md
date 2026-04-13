# Bright Steps - Speech Analysis Service

## Overview

This service provides comprehensive speech analysis for child development tracking using:
- **OpenAI Whisper** for accurate speech-to-text transcription
- **NLP Analysis** for vocabulary, sentence complexity, and readability metrics
- **Age-appropriate evaluations** based on WHO/CDC developmental milestones

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
pip install -r requirements.txt
```

Or install manually:
```bash
pip install fastapi uvicorn torch nltk textstat
pip install git+https://github.com/openai/whisper.git
pip install ffmpeg-python
```

### API Endpoints

#### `GET /`
Health check endpoint.

#### `POST /analyze`
Free Talk mode analysis.
- **Parameters:** `audio` (file), `age` (months)
- **Returns:** Transcript, vocabulary metrics, sentence complexity, readability scores, developmental feedback

#### `POST /analyze-compare`
Read & Compare mode analysis.
- **Parameters:** `audio` (file), `age` (months), `target_text` (string)
- **Returns:** All Free Talk metrics plus word-by-word comparison with match score

### Analysis Metrics

The service calculates:

| Metric | Description |
|--------|-------------|
| Vocabulary Size | Count of unique words used |
| Sentence Complexity | Average sentence length, structure analysis |
| Word Complexity | Syllable count, polysyllabic word detection |
| Readability Scores | Flesch Reading Ease, Flesch-Kincaid Grade Level |
| Overall Development Score | Composite score (0-100) |
| Developmental Feedback | Strengths, areas to practice, recommendations |

### Running Tests

```bash
cd "C:\xampp\htdocs\Bright Steps Website\APIs\Speech Analysis"
python -m pytest test_analysis.py -v
```

### Troubleshooting

**Error: "Speech AI is offline"**
- Make sure the Python server is running (check for the command window)
- Verify port 8000 is not blocked by firewall
- Check that FFmpeg is installed and in PATH

**Error: "Module not found"**
- Run: `pip install -r requirements.txt`

**Server won't start**
- Check if another application is using port 8000
- Try running the batch file as Administrator
- Verify Python is installed correctly

**FFmpeg errors**
- Ensure FFmpeg is installed and the bin directory is in PATH
- The app automatically injects the FFmpeg path, but verify it's correct

### Stopping the Server

- Press `Ctrl+C` in the command window
- Or simply close the window

### Testing the Server

Open your browser and go to: `http://localhost:8000`

You should see: `{"message":"Bright Steps Speech Analysis API is running!"}`
