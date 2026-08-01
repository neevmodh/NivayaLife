"""
Standalone clinical text service: PaddleOCR (a second-opinion OCR engine,
tried after Tesseract comes back unusable) plus scispaCy/medspaCy biomedical
entity recognition (drug/diagnosis mentions). Both capabilities are optional
enrichment from Laravel's side (App\\Services\\ClinicalNlp\\ClinicalNlpClient)
— any failure here just means the app falls back to its existing behavior.
"""
import base64
import io
import os
import re

import medspacy
import numpy as np
import spacy
from fastapi import FastAPI, Header, HTTPException
from paddleocr import PaddleOCR
from pydantic import BaseModel
from PIL import Image

SERVICE_TOKEN = os.environ.get("CLINICAL_NLP_TOKEN")
MAX_ENTITIES = 15

app = FastAPI()

# Loaded once per process — both are the expensive part.
ocr_engine = PaddleOCR(use_angle_cls=True, lang="en", show_log=False)
nlp = medspacy.load("en_core_sci_sm")


class Image_(BaseModel):
    data: str
    mimeType: str


class OcrRequest(BaseModel):
    images: list[Image_]


class EntitiesRequest(BaseModel):
    text: str


def check_token(x_service_token: str | None):
    if SERVICE_TOKEN and x_service_token != SERVICE_TOKEN:
        raise HTTPException(status_code=401, detail="Invalid or missing service token.")


def looks_usable(text: str) -> bool:
    """Mirrors OcrExtractor::looksUsable() on the PHP side — same bar for 'did OCR actually read something'."""
    text = text.strip()
    if not text:
        return False
    alnum_count = len(re.findall(r"[A-Za-z0-9]|[ऀ-ॿ]|[઀-૿]", text))
    return alnum_count >= 15 and (alnum_count / max(len(text), 1)) >= 0.4


@app.get("/health")
def health():
    return {"status": "ok"}


@app.post("/ocr")
def ocr(payload: OcrRequest, x_service_token: str | None = Header(default=None)):
    check_token(x_service_token)

    if not payload.images:
        raise HTTPException(status_code=400, detail="No images provided.")

    page_texts = []
    for image in payload.images:
        try:
            raw = base64.b64decode(image.data)
            pil_image = Image.open(io.BytesIO(raw)).convert("RGB")
            array = np.array(pil_image)
        except Exception:
            raise HTTPException(status_code=400, detail="Could not decode one of the provided images.")

        result = ocr_engine.ocr(array, cls=True)
        lines = [line[1][0] for block in (result or []) for line in (block or [])]
        page_texts.append("\n".join(lines))

    text = "\n\n".join(page_texts).strip()

    return {"text": text, "looks_usable": looks_usable(text)}


@app.post("/extract-entities")
def extract_entities(payload: EntitiesRequest, x_service_token: str | None = Header(default=None)):
    check_token(x_service_token)

    doc = nlp(payload.text[:20000])

    entities = []
    for ent in doc.ents:
        negated = bool(getattr(ent._, "is_negated", False))
        entities.append({"text": ent.text, "label": ent.label_, "negated": negated})

    return {"entities": entities[:MAX_ENTITIES]}
