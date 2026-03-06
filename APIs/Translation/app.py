from fastapi import FastAPI, HTTPException
from fastapi.middleware.cors import CORSMiddleware
from fastapi.responses import JSONResponse
from pydantic import BaseModel
from deep_translator import GoogleTranslator
from typing import List, Optional
import re


# ── Arabic-Indic Numeral Conversion ───────────────────────────────────

WESTERN_TO_ARABIC_NUMERALS = str.maketrans('0123456789', '٠١٢٣٤٥٦٧٨٩')

def to_arabic_numerals(text: str) -> str:
    """Convert Western digits (0-9) to Arabic-Indic digits (٠-٩)."""
    if not text:
        return text
    return text.translate(WESTERN_TO_ARABIC_NUMERALS)

app = FastAPI(
    title="Bright Steps Translation API",
    description="English to Arabic translation API for the Bright Steps platform",
    version="1.0.0"
)

# Enable CORS so the website frontend can call this API
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)


# ── Request / Response Models ──────────────────────────────────────────

class TranslateRequest(BaseModel):
    text: str
    source: Optional[str] = "en"
    target: Optional[str] = "ar"
    convert_numbers: Optional[bool] = True


class BatchTranslateRequest(BaseModel):
    texts: List[str]
    source: Optional[str] = "en"
    target: Optional[str] = "ar"
    convert_numbers: Optional[bool] = True


class ConvertNumbersRequest(BaseModel):
    text: str


class TranslateResponse(BaseModel):
    original: str
    translated: str
    source: str
    target: str


class BatchTranslateResponse(BaseModel):
    translations: List[TranslateResponse]
    source: str
    target: str


# ── Routes ─────────────────────────────────────────────────────────────

@app.get("/")
def root():
    return {
        "message": "Bright Steps Translation API is running!",
        "endpoints": {
            "POST /translate": "Translate a single text",
            "POST /translate/batch": "Translate multiple texts at once",
            "GET  /languages": "List supported languages",
        },
    }


@app.post("/translate", response_model=TranslateResponse)
def translate_text(request: TranslateRequest):
    """Translate a single text string (default: English → Arabic)."""
    if not request.text.strip():
        raise HTTPException(status_code=400, detail="Text cannot be empty")

    try:
        translator = GoogleTranslator(source=request.source, target=request.target)
        translated = translator.translate(request.text)
        if request.convert_numbers and request.target == "ar":
            translated = to_arabic_numerals(translated)
        return TranslateResponse(
            original=request.text,
            translated=translated,
            source=request.source,
            target=request.target,
        )
    except Exception as e:
        raise HTTPException(status_code=500, detail=f"Translation failed: {str(e)}")


@app.post("/translate/batch", response_model=BatchTranslateResponse)
def translate_batch(request: BatchTranslateRequest):
    """Translate a list of texts in one request (default: English → Arabic)."""
    if not request.texts:
        raise HTTPException(status_code=400, detail="Texts list cannot be empty")

    try:
        translator = GoogleTranslator(source=request.source, target=request.target)
        results = []
        for text in request.texts:
            translated = translator.translate(text) if text.strip() else ""
            if request.convert_numbers and request.target == "ar":
                translated = to_arabic_numerals(translated)
            results.append(
                TranslateResponse(
                    original=text,
                    translated=translated,
                    source=request.source,
                    target=request.target,
                )
            )
        return BatchTranslateResponse(
            translations=results,
            source=request.source,
            target=request.target,
        )
    except Exception as e:
        raise HTTPException(status_code=500, detail=f"Batch translation failed: {str(e)}")


@app.post("/convert-numbers")
def convert_numbers(request: ConvertNumbersRequest):
    """Convert Western numerals (0-9) to Arabic-Indic numerals (٠-٩)."""
    return {"original": request.text, "converted": to_arabic_numerals(request.text)}


@app.get("/languages")
def supported_languages():
    """Return the list of languages supported by Google Translator."""
    try:
        langs = GoogleTranslator().get_supported_languages(as_dict=True)
        return JSONResponse(content={"languages": langs})
    except Exception as e:
        raise HTTPException(status_code=500, detail=f"Could not fetch languages: {str(e)}")
