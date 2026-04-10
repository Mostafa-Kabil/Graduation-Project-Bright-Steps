import os
import sys
import shutil

# ── Inject FFmpeg bin directory into PATH so Whisper's internal subprocess
#    calls (whisper/audio.py: load_audio) can locate ffmpeg.exe ──────────────

# Try multiple FFmpeg locations
_FFMPEG_PATHS = [
    r"C:\Users\mosta\Downloads\ffmpeg-8.0-full_build\ffmpeg-8.0-full_build\bin",
    r"C:\Users\Dell\AppData\Local\Microsoft\WinGet\Packages\Gyan.FFmpeg_Microsoft.Winget.Source_8wekyb3d8bbwe\ffmpeg-8.0.1-full_build\bin",
    r"C:\Program Files\ffmpeg\bin",
    r"C:\ffmpeg\bin",
]

# First check if ffmpeg is in system PATH
ffmpeg_in_path = shutil.which("ffmpeg")
if ffmpeg_in_path:
    _FFMPEG_BIN = os.path.dirname(ffmpeg_in_path)
else:
    # Try hardcoded paths
    _FFMPEG_BIN = None
    for path in _FFMPEG_PATHS:
        if os.path.exists(os.path.join(path, "ffmpeg.exe")):
            _FFMPEG_BIN = path
            break

if _FFMPEG_BIN:
    os.environ["PATH"] = _FFMPEG_BIN + os.pathsep + os.environ.get("PATH", "")
else:
    print("WARNING: FFmpeg not found. Audio conversion may fail.")

FFMPEG_EXE = os.path.join(_FFMPEG_BIN, "ffmpeg.exe") if _FFMPEG_BIN else "ffmpeg"

import whisper
import torch
import subprocess
import uuid
from fastapi import FastAPI, UploadFile, Form, HTTPException
from fastapi.responses import JSONResponse

app = FastAPI()

DEVICE = "cuda" if torch.cuda.is_available() else "cpu"
model = whisper.load_model("tiny.en", device=DEVICE)


def convert_to_wav(input_path: str, output_path: str):
    """Convert any audio/video file to 16kHz mono WAV using FFmpeg."""
    cmd = [
        FFMPEG_EXE,
        "-y",           # overwrite output
        "-i", input_path,
        "-ar", "16000", # 16 kHz
        "-ac", "1",     # mono
        "-f", "wav",
        output_path
    ]
    result = subprocess.run(cmd, capture_output=True, text=True)
    if result.returncode != 0:
        raise RuntimeError(f"FFmpeg failed: {result.stderr}")
    return output_path


def analyze_vocabulary(transcript: str):
    words = transcript.lower().split()
    unique_words = set(words)
    return len(unique_words), list(unique_words)


def evaluate_child_vocab(vocab_size: int, age_months: int):
    thresholds = {
        12: 5,
        18: 50,
        24: 200,
        36: 500,
        48: 1000,
        60: 2000,
        72: 3000,
    }
    nearest_age = max(age for age in thresholds if age <= age_months)
    expected_vocab = thresholds[nearest_age]
    lower_bound = expected_vocab * 0.8
    upper_bound = expected_vocab * 1.2

    if vocab_size < lower_bound:
        status = "Below expected range"
    elif vocab_size > upper_bound:
        status = "Above expected range"
    else:
        status = "Within expected range"

    return status, expected_vocab


@app.get("/")
def read_root():
    return {"message": "Bright Steps Speech Analysis API is running!"}


@app.post("/analyze")
async def analyze(audio: UploadFile, age: int = Form(...)):
    uid = uuid.uuid4().hex
    input_path = f"temp_{uid}_{os.path.basename(audio.filename or 'audio')}"
    wav_path = f"temp_{uid}_output.wav"

    try:
        # Save uploaded file
        with open(input_path, "wb") as f:
            f.write(await audio.read())

        # Convert to WAV
        try:
            convert_to_wav(input_path, wav_path)
        except RuntimeError as e:
            raise HTTPException(status_code=422, detail=str(e))

        # Transcribe
        result = model.transcribe(wav_path)
        transcript = result["text"].strip()

        # Analyse vocabulary
        vocab_size, unique_words = analyze_vocabulary(transcript)
        status, expected_vocab = evaluate_child_vocab(vocab_size, age)

        return JSONResponse({
            "transcript": transcript,
            "vocab_size": vocab_size,
            "unique_words": unique_words,
            "child_age_months": age,
            "expected_vocab": expected_vocab,
            "status": status,
        })

    finally:
        for p in [input_path, wav_path]:
            try:
                if os.path.exists(p):
                    os.remove(p)
            except Exception:
                pass
