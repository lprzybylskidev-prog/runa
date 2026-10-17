FROM ghcr.io/huggingface/text-embeddings-inference:cuda-1.9.4@sha256:c831a5cedb89d62f7b0fdb59e2d73e8c30e10c1d5006897640e2ce58a71cedba
ARG RUNA_UID=1000
ARG RUNA_GID=1000
USER root
RUN apt-get update \
    && apt-get purge -y --auto-remove libssl-dev \
    && apt-get upgrade -y --no-install-recommends \
    && rm -rf /var/lib/apt/lists/* \
    && mkdir -p /data && chown "$RUNA_UID:$RUNA_GID" /data
ENV HF_HUB_DISABLE_TELEMETRY=1 USE_FLASH_ATTENTION=False
USER ${RUNA_UID}:${RUNA_GID}
HEALTHCHECK --interval=10s --timeout=5s --start-period=120s --retries=12 CMD curl --fail --silent --output /dev/null http://127.0.0.1:8080/health
