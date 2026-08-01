FROM python:3.11-slim

WORKDIR /app

RUN apt-get update && apt-get install -y --no-install-recommends \
    libgl1 \
    libglib2.0-0 \
    libgomp1 \
    && rm -rf /var/lib/apt/lists/*

COPY requirements.txt ./

# Installed in separate stages, smallest/lightest first — keeps each pip
# resolve+build small (Docker layer cache also preserves each completed
# stage, so a later failure doesn't re-download/rebuild everything already
# installed). scipy is pinned explicitly and ahead of paddleocr/scikit-image
# (which would otherwise pull in whatever the newest scipy is transitively)
# because scipy's latest manylinux wheel was repeatedly observed to ship a
# corrupted _ccallback_c extension (zlib.error on import) — 1.13.1 is a
# long-stable release with no such issue.
RUN pip install --no-cache-dir fastapi==0.115.6 "uvicorn[standard]==0.34.0" python-multipart==0.0.20 pillow==11.1.0 numpy==1.26.4
RUN pip install --no-cache-dir spacy==3.7.5
RUN pip install --no-cache-dir https://s3-us-west-2.amazonaws.com/ai2-s2-scispacy/releases/v0.5.4/en_core_sci_sm-0.5.4.tar.gz
RUN pip install --no-cache-dir medspacy==1.3.1
RUN pip install --no-cache-dir paddlepaddle==2.6.2
RUN pip install --no-cache-dir scipy==1.13.1
RUN pip install --no-cache-dir paddleocr==2.9.1 pyclipper==1.3.0.post6

COPY main.py ./

# Deliberately NOT importing/instantiating PaddleOCR or medspacy here to
# pre-bake weights into the image (as originally intended) — doing so
# repeatedly hit a Railway build-daemon instability where reading back one
# of the many large compiled .so files pulled in by this import chain
# (scipy, scikit-image, scikit-learn, matplotlib, opencv — a different one
# each time) intermittently raised zlib.error on import, never the same
# file twice. That's consistent with a build-environment resource/overlay-fs
# issue rather than a bad wheel, and it didn't reproduce for the lighter
# medspacy-only import. Both model objects are still created at Python
# **process** start (main.py module scope), just at container boot instead
# of build time — first boot pays a one-time weight-download cost instead of
# it being pre-baked; the healthcheck timeout below gives that room.

EXPOSE 8080
CMD ["sh", "-c", "uvicorn main:app --host 0.0.0.0 --port ${PORT:-8080}"]
