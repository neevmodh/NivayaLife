"""
Standalone chest X-ray classifier service.

Loads a pretrained TorchXRayVision DenseNet once at startup and serves it
over a small authenticated HTTP API. Laravel (App\\Services\\XrayVision\\XrayVisionClient)
calls this to enrich the Gemini vision description of xray-type reports with
real model output — never a hard dependency, the Laravel side treats any
failure here as optional enrichment it can skip.
"""
import io
import os

import numpy as np
import torch
import torchxrayvision as xrv
from fastapi import FastAPI, File, Header, HTTPException, UploadFile
from PIL import Image

# On a shared/CPU-quota-limited host (e.g. Railway's low-resource tiers),
# torch's default of spawning a thread per visible core causes severe
# contention rather than speedup — inference on a single small image was
# observed taking 2+ minutes. Forcing single-threaded execution avoids that
# thrash, mirroring the same fix already applied to Tesseract in the main
# app's OcrExtractor (OMP_THREAD_LIMIT=1).
torch.set_num_threads(1)
os.environ.setdefault("OMP_NUM_THREADS", "1")

SERVICE_TOKEN = os.environ.get("XRAY_VISION_TOKEN")
MAX_FINDINGS = 8

app = FastAPI()

# Loaded once per process, not per-request — this is the expensive part.
model = xrv.models.DenseNet(weights="densenet121-res224-all")
model.eval()


@app.get("/health")
def health():
    return {"status": "ok"}


@app.post("/analyze")
async def analyze(file: UploadFile = File(...), x_service_token: str | None = Header(default=None)):
    if SERVICE_TOKEN and x_service_token != SERVICE_TOKEN:
        raise HTTPException(status_code=401, detail="Invalid or missing service token.")

    try:
        image_bytes = await file.read()
        pil_image = Image.open(io.BytesIO(image_bytes)).convert("L")
        img = np.array(pil_image).astype(np.float64)

        # torchxrayvision expects [-1024, 1024]-normalized single-channel images
        # resized to 224x224 — the same preprocessing its own training pipeline uses.
        img = xrv.datasets.normalize(img, 255)
        img = img[None, :, :]
        transform = xrv.datasets.XRayCenterCrop()
        img = transform(img)
        resize = xrv.datasets.XRayResizer(224)
        img = resize(img)
    except Exception:
        raise HTTPException(status_code=400, detail="Could not read the uploaded file as an image.")

    with torch.no_grad():
        tensor = torch.from_numpy(img).unsqueeze(0).float()
        outputs = model(tensor).cpu().numpy()[0]

    pathologies = model.pathologies
    scored = [
        {"pathology": pathologies[i], "probability": round(float(outputs[i]), 4)}
        for i in range(len(pathologies))
        if pathologies[i]  # torchxrayvision pads with empty-string labels for unused outputs
    ]
    scored.sort(key=lambda item: item["probability"], reverse=True)

    return {"findings": scored[:MAX_FINDINGS]}
