# Bright Steps – Translation API (English ↔ Arabic)

A lightweight FastAPI service that translates text between English and Arabic using Google Translate (via `deep-translator`).

## Quick Start

```bash
# 1. Install dependencies
pip install -r requirements.txt

# 2. Run the server
run_server.bat
# or manually:
python -m uvicorn app:app --reload --host 127.0.0.1 --port 8001
```

The API will be available at **http://127.0.0.1:8001**  
Interactive docs at **http://127.0.0.1:8001/docs**

## Endpoints

### `POST /translate` – Translate a single text

**Request:**
```json
{
  "text": "Hello, how are you?",
  "source": "en",
  "target": "ar"
}
```

**Response:**
```json
{
  "original": "Hello, how are you?",
  "translated": "مرحبا، كيف حالك؟",
  "source": "en",
  "target": "ar"
}
```

### `POST /translate/batch` – Translate multiple texts

**Request:**
```json
{
  "texts": ["Good morning", "Welcome to Bright Steps"],
  "source": "en",
  "target": "ar"
}
```

**Response:**
```json
{
  "translations": [
    { "original": "Good morning",              "translated": "صباح الخير",             "source": "en", "target": "ar" },
    { "original": "Welcome to Bright Steps",   "translated": "مرحبا بكم في برايت ستبس", "source": "en", "target": "ar" }
  ],
  "source": "en",
  "target": "ar"
}
```

### `GET /languages` – List supported languages

Returns a dictionary of all language codes and names supported by Google Translate.

## Frontend Usage Example

```javascript
async function translateText(text) {
  const response = await fetch('http://127.0.0.1:8001/translate', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ text, source: 'en', target: 'ar' })
  });
  const data = await response.json();
  return data.translated;
}
```

## Notes

- The API runs on **port 8001** (Speech Analysis API uses port 8000).
- CORS is enabled so the Bright Steps website can call it directly.
- No API key is needed — it uses Google Translate's free tier via `deep-translator`.
