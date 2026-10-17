FROM ghcr.io/huggingface/text-embeddings-inference:cpu-1.9.4@sha256:2538ea1c9640d3763b15af668039d24172d063b42337b0c27796fc2be180c78d AS upstream
FROM ubuntu:24.04@sha256:68ee22004a9109b44973313b02ff7b1de250f1de0f5ecb628d73f579aaff33ac
ARG RUNA_UID=1000
ARG RUNA_GID=1000
RUN apt-get update \
    && apt-get upgrade -y --no-install-recommends \
    && apt-get install -y --no-install-recommends ca-certificates curl libstdc++6 libssl3t64 libomp5 \
    && rm -rf /var/lib/apt/lists/* \
    && mkdir -p /data /usr/local/lib \
    && chown "$RUNA_UID:$RUNA_GID" /data \
    && ln -s /usr/lib/x86_64-linux-gnu/libomp.so.5 /usr/local/lib/libiomp5.so \
    && test -f /usr/local/lib/libiomp5.so
COPY --from=upstream /usr/local/bin/text-embeddings-router /usr/local/bin/text-embeddings-router
COPY --from=upstream /usr/local/lib/libmkl*.so* /usr/local/lib/
COPY --from=upstream /usr/local/libfakeintel.so /usr/local/libfakeintel.so
# Catch missing OpenMP aliases or incompatible glibc/OpenSSL before image acceptance.
RUN LD_LIBRARY_PATH=/usr/local/lib LD_PRELOAD=/usr/local/libfakeintel.so \
    text-embeddings-router --version
ENV HUGGINGFACE_HUB_CACHE=/data PORT=8080 HF_HUB_DISABLE_TELEMETRY=1 \
    USE_FLASH_ATTENTION=False LD_LIBRARY_PATH=/usr/local/lib LD_PRELOAD=/usr/local/libfakeintel.so
USER ${RUNA_UID}:${RUNA_GID}
ENTRYPOINT ["text-embeddings-router"]
CMD ["--json-output"]
HEALTHCHECK --interval=10s --timeout=5s --start-period=120s --retries=12 CMD curl --fail --silent --output /dev/null http://127.0.0.1:8080/health
