FROM ollama/ollama:latest

# Bake both models into the image at build time — same reasoning as
# xray-vision-service's pretrained-weights RUN step: a fresh container
# shouldn't stall (or fail, on a network-restricted host) pulling multi-GB
# models on its first request. Ollama needs its own server running to serve
# a `pull`, so start it, wait for it to come up, pull, then stop it —
# the final CMD starts the real long-running server.
RUN (ollama serve &) \
    && sleep 5 \
    && ollama pull qwen2.5:3b-instruct \
    && ollama pull nomic-embed-text \
    && pkill ollama \
    && sleep 2

EXPOSE 11434
ENV OLLAMA_HOST=0.0.0.0:11434
# The base image's ENTRYPOINT is already ["ollama"] — CMD supplies just the
# subcommand, not "ollama serve" again (that produced "unknown command
# ollama for ollama" in the deployed logs).
CMD ["serve"]
