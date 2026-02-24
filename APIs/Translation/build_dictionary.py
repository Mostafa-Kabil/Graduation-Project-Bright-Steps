"""
Extract all visible text from HTML pages, translate via the Translation API,
and output a JS dictionary file.
"""
import os
import re
import json
import urllib.request

API_URL = "http://127.0.0.1:8001/translate/batch"
HTML_DIR = os.path.join(os.path.dirname(__file__), "..", "..")
BATCH_SIZE = 40

def extract_texts_from_html(filepath):
    """Extract visible text from an HTML file using regex (no external deps)."""
    with open(filepath, "r", encoding="utf-8") as f:
        html = f.read()
    
    # Remove script/style/svg blocks
    html = re.sub(r'<script[\s\S]*?</script>', '', html, flags=re.IGNORECASE)
    html = re.sub(r'<style[\s\S]*?</style>', '', html, flags=re.IGNORECASE)
    html = re.sub(r'<svg[\s\S]*?</svg>', '', html, flags=re.IGNORECASE)
    
    # Remove HTML tags
    text = re.sub(r'<[^>]+>', '\n', html)
    
    # Extract meaningful text fragments
    texts = set()
    for line in text.split('\n'):
        line = line.strip()
        # Skip empty, very short, or numeric-only lines
        if len(line) < 2:
            continue
        if re.match(r'^[\d\s\.\,\$\%\-\+\@\#\&\*\(\)\[\]\{\}\/\\:;]+$', line):
            continue
        # Skip lines that look like code/paths
        if line.startswith('//') or line.startswith('/*'):
            continue
        # Skip copyright with year only
        if line.startswith('\u00a9') and len(line) < 10:
            continue
        texts.add(line)
    
    # Also extract placeholder values
    placeholders = re.findall(r'placeholder="([^"]+)"', html, re.IGNORECASE)
    for ph in placeholders:
        if len(ph) >= 2:
            texts.add(ph)
    
    # Extract title attributes
    titles = re.findall(r'title="([^"]+)"', html, re.IGNORECASE)
    for t in titles:
        if len(t) >= 2:
            texts.add(t)
    
    # Extract aria-label values
    labels = re.findall(r'aria-label="([^"]+)"', html, re.IGNORECASE)
    for l in labels:
        if len(l) >= 2:
            texts.add(l)
    
    return texts

def translate_batch(texts_list):
    """Call the Translation API batch endpoint."""
    data = json.dumps({"texts": texts_list, "source": "en", "target": "ar"}).encode("utf-8")
    req = urllib.request.Request(API_URL, data=data, headers={"Content-Type": "application/json"})
    try:
        with urllib.request.urlopen(req, timeout=30) as resp:
            result = json.loads(resp.read().decode("utf-8"))
            return [t["translated"] for t in result["translations"]]
    except Exception as e:
        print(f"  API error: {e}")
        return None

def main():
    # 1. Collect all texts from all HTML files
    all_texts = set()
    html_files = [f for f in os.listdir(HTML_DIR) if f.endswith('.html')]
    
    print(f"Found {len(html_files)} HTML files")
    for fname in sorted(html_files):
        fpath = os.path.join(HTML_DIR, fname)
        texts = extract_texts_from_html(fpath)
        print(f"  {fname}: {len(texts)} text fragments")
        all_texts.update(texts)
    
    # Remove duplicates and sort
    unique_texts = sorted(all_texts)
    print(f"\nTotal unique texts to translate: {len(unique_texts)}")
    
    # 2. Translate in batches
    translations = {}
    for i in range(0, len(unique_texts), BATCH_SIZE):
        batch = unique_texts[i:i+BATCH_SIZE]
        print(f"  Translating batch {i//BATCH_SIZE + 1}/{(len(unique_texts)-1)//BATCH_SIZE + 1} ({len(batch)} texts)...")
        translated = translate_batch(batch)
        if translated:
            for orig, trans in zip(batch, translated):
                translations[orig] = trans
        else:
            print(f"  FAILED - skipping batch")
    
    print(f"\nSuccessfully translated {len(translations)} texts")
    
    # 3. Save as JSON
    output_path = os.path.join(os.path.dirname(__file__), "translations_dict.json")
    with open(output_path, "w", encoding="utf-8") as f:
        json.dump(translations, f, ensure_ascii=False, indent=2)
    
    print(f"Saved to {output_path}")

if __name__ == "__main__":
    main()
