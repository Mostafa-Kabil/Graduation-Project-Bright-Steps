import os
import sys
import re
import statistics

# ── Inject FFmpeg bin directory into PATH so Whisper's internal subprocess
#    calls (whisper/audio.py: load_audio) can locate ffmpeg.exe ──────────────
_FFMPEG_BIN = r"C:\Users\Dell\AppData\Local\Microsoft\WinGet\Packages\Gyan.FFmpeg_Microsoft.Winget.Source_8wekyb3d8bbwe\ffmpeg-8.0.1-full_build\bin"
os.environ["PATH"] = _FFMPEG_BIN + os.pathsep + os.environ.get("PATH", "")

import whisper
import torch
import subprocess
import uuid
from fastapi import FastAPI, UploadFile, Form, HTTPException
from fastapi.responses import JSONResponse

# NLTK setup - download required data on first run
try:
    import nltk
    nltk.data.find('tokenizers/punkt')
except LookupError:
    nltk.download('punkt', quiet=True)
try:
    nltk.data.find('taggers/averaged_perceptron_tagger')
except LookupError:
    nltk.download('averaged_perceptron_tagger', quiet=True)
except Exception:
    pass  # NLTK may not be fully installed, graceful degradation

import textstat

FFMPEG_EXE = os.path.join(_FFMPEG_BIN, "ffmpeg.exe")

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
    """Basic vocabulary analysis - returns unique word count and list."""
    words = transcript.lower().split()
    unique_words = set(words)
    return len(unique_words), list(unique_words)


def analyze_sentence_complexity(transcript: str) -> dict:
    """
    Analyze sentence structure and complexity.
    Returns metrics on sentence length, structure, and variety.
    """
    # Split into sentences (handle common punctuation)
    sentences = re.split(r'[.!?]+', transcript)
    sentences = [s.strip() for s in sentences if s.strip()]

    if not sentences:
        return {
            'sentence_count': 0,
            'avg_sentence_length': 0,
            'max_sentence_length': 0,
            'sentence_length_variance': 0,
            'complexity_score': 0
        }

    # Analyze each sentence
    sentence_lengths = []
    for sent in sentences:
        words = sent.split()
        sentence_lengths.append(len(words))

    avg_length = statistics.mean(sentence_lengths) if sentence_lengths else 0
    max_length = max(sentence_lengths) if sentence_lengths else 0
    variance = statistics.stdev(sentence_lengths) if len(sentence_lengths) > 1 else 0

    # Complexity score based on average sentence length
    # Longer sentences generally indicate more complex speech
    if avg_length < 3:
        complexity_score = 0.3
    elif avg_length < 5:
        complexity_score = 0.5
    elif avg_length < 8:
        complexity_score = 0.7
    elif avg_length < 12:
        complexity_score = 0.85
    else:
        complexity_score = 1.0

    return {
        'sentence_count': len(sentences),
        'avg_sentence_length': round(avg_length, 2),
        'max_sentence_length': max_length,
        'sentence_length_variance': round(variance, 2),
        'complexity_score': complexity_score
    }


def analyze_word_complexity(transcript: str) -> dict:
    """
    Analyze word-level complexity using syllable counts and word length.
    """
    words = re.findall(r'\b[a-zA-Z]+\b', transcript.lower())

    if not words:
        return {
            'avg_word_length': 0,
            'avg_syllables_per_word': 0,
            'polysyllabic_word_count': 0,
            'complexity_score': 0
        }

    # Calculate average word length
    avg_word_length = statistics.mean(len(w) for w in words)

    # Count syllables using textstat
    total_syllables = sum(textstat.syllable_count(w) for w in words)
    avg_syllables = total_syllables / len(words) if words else 0

    # Count polysyllabic words (3+ syllables) - these indicate advanced vocabulary
    polysyllabic_count = sum(1 for w in words if textstat.syllable_count(w) >= 3)

    # Complexity score based on syllable average
    if avg_syllables < 1.2:
        complexity_score = 0.3
    elif avg_syllables < 1.5:
        complexity_score = 0.5
    elif avg_syllables < 2.0:
        complexity_score = 0.7
    else:
        complexity_score = 0.9

    return {
        'avg_word_length': round(avg_word_length, 2),
        'avg_syllables_per_word': round(avg_syllables, 2),
        'polysyllabic_word_count': polysyllabic_count,
        'complexity_score': complexity_score
    }


def calculate_readability_scores(transcript: str) -> dict:
    """
    Calculate standard readability metrics using textstat.
    Adapted for speech analysis (typically lower scores than written text).
    """
    if not transcript.strip():
        return {
            'flesch_reading_ease': 0,
            'flesch_kincaid_grade': 0,
            'automated_readability_index': 0,
            'overall_readability_score': 0
        }

    # Flesch Reading Ease (higher = easier to read)
    fre = textstat.flesch_reading_ease(transcript)

    # Flesch-Kincaid Grade Level
    fkg = textstat.flesch_kincaid_grade(transcript)

    # Automated Readability Index
    ari = textstat.automated_readability_index(transcript)

    # Normalize to 0-1 score (adapted for children's speech)
    # For children, lower grade levels are expected
    if fkg < 1:
        readability_score = 1.0  # Very age-appropriate
    elif fkg < 3:
        readability_score = 0.85
    elif fkg < 5:
        readability_score = 0.7
    elif fkg < 8:
        readability_score = 0.5
    else:
        readability_score = 0.3  # Very advanced or unclear

    return {
        'flesch_reading_ease': round(fre, 2),
        'flesch_kincaid_grade': round(fkg, 2),
        'automated_readability_index': round(ari, 2),
        'overall_readability_score': readability_score
    }


def generate_developmental_feedback(
    vocab_size: int,
    age_months: int,
    sentence_complexity: dict,
    word_complexity: dict,
    readability: dict,
    status: str
) -> dict:
    """
    Generate age-appropriate developmental feedback based on analysis.
    """
    feedback = {
        'strengths': [],
        'areas_to_practice': [],
        'milestone_status': 'on_track',
        'recommendations': []
    }

    # Determine expected vocabulary for age
    thresholds = {12: 5, 18: 50, 24: 200, 36: 500, 48: 1000, 60: 2000, 72: 3000}
    nearest_age = max(age for age in thresholds if age <= age_months)
    expected_vocab = thresholds[nearest_age]

    # Vocabulary feedback
    vocab_ratio = vocab_size / expected_vocab if expected_vocab > 0 else 0

    if vocab_ratio >= 1.0:
        feedback['strengths'].append('Excellent vocabulary size for age!')
        feedback['recommendations'].append('Introduce more complex sentences and storytelling.')
    elif vocab_ratio >= 0.8:
        feedback['strengths'].append('Good vocabulary development.')
        feedback['recommendations'].append('Continue reading together daily to expand word knowledge.')
    else:
        feedback['areas_to_practice'].append('Building vocabulary through daily conversation.')
        feedback['recommendations'].append('Narrate daily activities and read picture books together.')

    # Sentence complexity feedback
    avg_sent_len = sentence_complexity.get('avg_sentence_length', 0)

    if avg_sent_len >= 5:
        feedback['strengths'].append('Using multi-word sentences.')
    elif avg_sent_len >= 3:
        feedback['strengths'].append('Starting to combine words into phrases.')
    else:
        feedback['areas_to_practice'].append('Encourage two-word combinations.')
        feedback['recommendations'].append('Model simple two-word phrases like "more milk" or "big ball".')

    # Word complexity feedback
    avg_syllables = word_complexity.get('avg_syllables_per_word', 0)

    if avg_syllables >= 2:
        feedback['strengths'].append('Using words with multiple syllables.')
    else:
        feedback['recommendations'].append('Introduce longer words naturally in conversation.')

    # Milestone status based on overall analysis
    if status == 'Above expected range' or (vocab_ratio >= 1.0 and avg_sent_len >= 4):
        feedback['milestone_status'] = 'advanced'
    elif status == 'Within expected range' or vocab_ratio >= 0.8:
        feedback['milestone_status'] = 'on_track'
    else:
        feedback['milestone_status'] = 'needs_attention'

    return feedback


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


def _transcribe_audio(audio: UploadFile):
    """Save, convert, transcribe and clean up. Returns (transcript, input_path, wav_path)."""
    uid = uuid.uuid4().hex
    input_path = f"temp_{uid}_{os.path.basename(audio.filename or 'audio')}"
    wav_path = f"temp_{uid}_output.wav"
    return input_path, wav_path


@app.get("/")
def read_root():
    return {"message": "Bright Steps Speech Analysis API is running!"}


@app.post("/analyze")
async def analyze(audio: UploadFile, age: int = Form(...)):
    """Free Talk mode — transcribe and perform comprehensive NLP analysis."""
    uid = uuid.uuid4().hex
    input_path = f"temp_{uid}_{os.path.basename(audio.filename or 'audio')}"
    wav_path = f"temp_{uid}_output.wav"

    try:
        with open(input_path, "wb") as f:
            f.write(await audio.read())

        try:
            convert_to_wav(input_path, wav_path)
        except RuntimeError as e:
            raise HTTPException(status_code=422, detail=str(e))

        result = model.transcribe(wav_path)
        transcript = result["text"].strip()

        # Basic vocabulary analysis
        vocab_size, unique_words = analyze_vocabulary(transcript)
        status, expected_vocab = evaluate_child_vocab(vocab_size, age)

        # Enhanced NLP analysis
        sentence_complexity = analyze_sentence_complexity(transcript)
        word_complexity = analyze_word_complexity(transcript)
        readability = calculate_readability_scores(transcript)

        # Generate developmental feedback
        feedback = generate_developmental_feedback(
            vocab_size=vocab_size,
            age_months=age,
            sentence_complexity=sentence_complexity,
            word_complexity=word_complexity,
            readability=readability,
            status=status
        )

        # Calculate overall development score (0-100)
        overall_score = round((
            sentence_complexity['complexity_score'] * 25 +
            word_complexity['complexity_score'] * 25 +
            readability['overall_readability_score'] * 25 +
            (1.0 if status == 'Above expected range' else 0.8 if status == 'Within expected range' else 0.5) * 25
        ), 1)

        return JSONResponse({
            "transcript": transcript,
            "vocab_size": vocab_size,
            "unique_words": unique_words,
            "child_age_months": age,
            "expected_vocab": expected_vocab,
            "status": status,
            # Enhanced metrics
            "sentence_complexity": sentence_complexity,
            "word_complexity": word_complexity,
            "readability_scores": readability,
            "developmental_feedback": feedback,
            "overall_development_score": overall_score
        })

    finally:
        for p in [input_path, wav_path]:
            try:
                if os.path.exists(p):
                    os.remove(p)
            except Exception:
                pass


# Age-appropriate word lists for guided practice
AGE_WORD_LISTS = {
    12: {
        'label': '12-17 months',
        'categories': {
            'family': ['mama', 'dada', 'baby'],
            'greetings': ['hi', 'bye'],
            'objects': ['ball', 'cup', 'book'],
            'animals': ['dog', 'cat', 'duck'],
            'actions': ['up', 'go', 'no']
        }
    },
    18: {
        'label': '18-23 months',
        'categories': {
            'family': ['mama', 'dada', 'baby', 'dolly'],
            'body_parts': ['eye', 'ear', 'nose', 'hand'],
            'objects': ['shoe', 'book', 'car', 'ball'],
            'animals': ['bird', 'dog', 'cat', 'cow'],
            'actions': ['sit', 'go', 'more', 'eat'],
            'descriptive': ['hot', 'big', 'red']
        }
    },
    24: {
        'label': '24-35 months',
        'categories': {
            'family': ['mama', 'dada', 'baby', 'sister'],
            'body_parts': ['eye', 'ear', 'mouth', 'hand', 'foot'],
            'objects': ['apple', 'book', 'shoe', 'chair'],
            'animals': ['bird', 'dog', 'cat', 'fish'],
            'actions': ['run', 'jump', 'play', 'eat', 'sleep'],
            'descriptive': ['happy', 'sad', 'big', 'small', 'red', 'blue']
        }
    },
    36: {
        'label': '36-47 months',
        'categories': {
            'family': ['mommy', 'daddy', 'brother', 'sister', 'family'],
            'objects': ['orange', 'school', 'table', 'window'],
            'animals': ['elephant', 'monkey', 'rabbit', 'turtle'],
            'actions': ['dance', 'sing', 'draw', 'build', 'share'],
            'descriptive': ['beautiful', 'colorful', 'friendly', 'funny'],
            'places': ['outside', 'park', 'school', 'home']
        }
    },
    48: {
        'label': '48-59 months',
        'categories': {
            'family': ['grandma', 'grandpa', 'cousin', 'family'],
            'objects': ['butterfly', 'airplane', 'bicycle', 'telephone'],
            'animals': ['butterfly', 'elephant', 'giraffe', 'penguin'],
            'actions': ['remember', 'imagine', 'create', 'explore'],
            'descriptive': ['wonderful', 'amazing', 'delicious', 'exciting'],
            'places': ['garden', 'playground', 'library', 'market']
        }
    },
    60: {
        'label': '60-72 months',
        'categories': {
            'objects': ['strawberry', 'hospital', 'telescope', 'microscope'],
            'animals': ['butterfly', 'crocodile', 'kangaroo', 'dolphin'],
            'actions': ['accomplish', 'celebrate', 'discover', 'experiment'],
            'descriptive': ['wonderful', 'magnificent', 'extraordinary', 'adventurous'],
            'places': ['neighborhood', 'community', 'classroom', 'hospital'],
            'concepts': ['imagination', 'celebration', 'friendship', 'adventure']
        }
    }
}


def get_age_appropriate_words(age_months: int, count: int = 10) -> dict:
    """
    Generate age-appropriate word list based on child's age.
    Returns words from relevant developmental categories.
    """
    # Find the nearest age bracket
    ages = sorted(AGE_WORD_LISTS.keys())
    nearest_age = max(a for a in ages if a <= age_months) if any(a <= age_months for a in ages) else min(ages)

    age_data = AGE_WORD_LISTS[nearest_age]
    all_words = []

    # Collect words from all categories
    for category, words in age_data['categories'].items():
        all_words.extend(words)

    # Select words (prioritize variety)
    selected_words = []
    words_per_category = max(2, count // len(age_data['categories']))

    for category, words in age_data['categories'].items():
        import random
        shuffled = words.copy()
        random.shuffle(shuffled)
        selected_words.extend(shuffled[:words_per_category])

    # Fill remaining slots if needed
    while len(selected_words) < count and len(all_words) > len(selected_words):
        remaining = [w for w in all_words if w not in selected_words]
        if remaining:
            import random
            selected_words.append(random.choice(remaining))
        else:
            break

    return {
        'words': selected_words[:count],
        'age_label': age_data['label'],
        'age_months': age_months,
        'categories': list(age_data['categories'].keys())
    }


@app.post("/generate-words")
async def generate_words(age: int = Form(...)):
    """Generate age-appropriate words for guided speech practice."""
    result = get_age_appropriate_words(age)
    return JSONResponse(result)


@app.get("/word-lists")
async def get_word_lists():
    """Get all available word lists by age bracket."""
    return JSONResponse({
        'age_brackets': {
            str(age): data['label'] for age, data in AGE_WORD_LISTS.items()
        }
    })


@app.post("/analyze-compare")
async def analyze_compare(audio: UploadFile, age: int = Form(...), target_text: str = Form(...)):
    """Read & Compare mode — transcribe then compare spoken words against target words."""
    uid = uuid.uuid4().hex
    input_path = f"temp_{uid}_{os.path.basename(audio.filename or 'audio')}"
    wav_path = f"temp_{uid}_output.wav"

    try:
        with open(input_path, "wb") as f:
            f.write(await audio.read())

        try:
            convert_to_wav(input_path, wav_path)
        except RuntimeError as e:
            raise HTTPException(status_code=422, detail=str(e))

        result = model.transcribe(wav_path)
        transcript = result["text"].strip()

        # Standard vocab analysis
        vocab_size, unique_words = analyze_vocabulary(transcript)
        status, expected_vocab = evaluate_child_vocab(vocab_size, age)

        # Enhanced NLP analysis
        sentence_complexity = analyze_sentence_complexity(transcript)
        word_complexity = analyze_word_complexity(transcript)
        readability = calculate_readability_scores(transcript)

        # Generate developmental feedback
        feedback = generate_developmental_feedback(
            vocab_size=vocab_size,
            age_months=age,
            sentence_complexity=sentence_complexity,
            word_complexity=word_complexity,
            readability=readability,
            status=status
        )

        # Word-by-word comparison against target
        target_words = [w.strip(".,!?").lower() for w in target_text.split() if w.strip()]
        spoken_words = set(w.strip(".,!?").lower() for w in transcript.split() if w.strip())

        word_hits = [w for w in target_words if w in spoken_words]
        word_misses = [w for w in target_words if w not in spoken_words]
        match_score = round(len(word_hits) / len(target_words) * 100, 1) if target_words else 0.0

        # Calculate pronunciation accuracy feedback
        if match_score >= 80:
            feedback['strengths'].append('Excellent word recognition and pronunciation!')
        elif match_score >= 50:
            feedback['areas_to_practice'].append('Practice saying the target words more clearly.')
        else:
            feedback['areas_to_practice'].append('Focus on recognizing and repeating target words.')

        # Calculate overall development score (0-100)
        overall_score = round((
            sentence_complexity['complexity_score'] * 20 +
            word_complexity['complexity_score'] * 20 +
            readability['overall_readability_score'] * 20 +
            (match_score / 100) * 25 +
            (1.0 if status == 'Above expected range' else 0.8 if status == 'Within expected range' else 0.5) * 15
        ), 1)

        # Add match score to feedback
        feedback['match_score'] = match_score
        feedback['target_word_count'] = len(target_words)
        feedback['words_correctly_spoken'] = len(word_hits)

        return JSONResponse({
            "transcript": transcript,
            "vocab_size": vocab_size,
            "unique_words": unique_words,
            "child_age_months": age,
            "expected_vocab": expected_vocab,
            "status": status,
            # Enhanced metrics
            "sentence_complexity": sentence_complexity,
            "word_complexity": word_complexity,
            "readability_scores": readability,
            "developmental_feedback": feedback,
            "overall_development_score": overall_score,
            # Read & Compare extras
            "match_score": match_score,
            "word_hits": word_hits,
            "word_misses": word_misses,
            "target_words": target_words,
        })

    finally:
        for p in [input_path, wav_path]:
            try:
                if os.path.exists(p):
                    os.remove(p)
            except Exception:
                pass
