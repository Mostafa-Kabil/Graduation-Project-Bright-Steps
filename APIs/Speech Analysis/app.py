import whisper
import ffmpeg
import torch
from fastapi import FastAPI, UploadFile, Form
from fastapi.responses import HTMLResponse, JSONResponse
from collections import Counter
import os
import uuid
from fastapi import FastAPI

app = FastAPI()

@app.get("/")
def read_root():
    return {"message": "Server is running!"}

app = FastAPI()

DEVICE = "cuda" if torch.cuda.is_available() else "cpu"
model = whisper.load_model("tiny.en", device=DEVICE)

def convert_to_wav(input_path, output_path):
    (
        ffmpeg
        .input(input_path)
        .output(output_path, ar=16000, ac=1, format="wav")
        .overwrite_output()
        .run(quiet=True)
    )
    return output_path

def analyze_vocabulary(transcript):
    words = transcript.lower().split()
    unique_words = set(words)
    return len(unique_words), list(unique_words)

def evaluate_child_vocab(vocab_size, age_months):
    thresholds = {
        12: 5,    # ~5 words
        18: 50,   # ~50 words
        24: 200,  # ~200 words
        36: 500,  # ~500 words
        48: 1000, # ~1000 words
        60: 2000, # ~2000 words
        72: 3000  # ~3000 words
    }

    # Find the nearest lower milestone age
    nearest_age = max(age for age in thresholds if age <= age_months)
    expected_vocab = thresholds[nearest_age]

    # Define more realistic ranges
    lower_bound = expected_vocab * 0.8   # -20%
    upper_bound = expected_vocab * 1.2   # +20%

    if vocab_size < lower_bound:
        status = "Below expected range"
    elif vocab_size > upper_bound:
        status = "Above expected range"
    else:
        status = "Within expected range"

    return status, expected_vocab


@app.post("/analyze")
async def analyze(audio: UploadFile, age: int = Form(...)):
    input_path = f"temp_{uuid.uuid4().hex}_{audio.filename}"
    with open(input_path, "wb") as f:
        f.write(await audio.read())

    wav_path = f"{input_path}.wav"
    convert_to_wav(input_path, wav_path)

    result = model.transcribe(wav_path)
    transcript = result["text"]

    vocab_size, unique_words = analyze_vocabulary(transcript)
    status, expected_vocab = evaluate_child_vocab(vocab_size, age)

    os.remove(input_path)
    os.remove(wav_path)

    return JSONResponse({
        "transcript": transcript,
        "vocab_size": vocab_size,
        "unique_words": unique_words,
        "child_age_months": age,
        "expected_vocab": expected_vocab,
        "status": status
    })

@app.get("/", response_class=HTMLResponse)
def index():
    with open("static/index.php") as f:
        return f.read()
