FROM qdrant/qdrant:v1.19.2@sha256:b7b0444c4c351c970b98e90a6f89c2ee4287c65b44e52b4cb503fa5b2aa927ad AS upstream
FROM ubuntu:24.04@sha256:68ee22004a9109b44973313b02ff7b1de250f1de0f5ecb628d73f579aaff33ac
ARG RUNA_UID=1000
ARG RUNA_GID=1000
RUN apt-get update \
    && apt-get upgrade -y --no-install-recommends \
    && apt-get install -y --no-install-recommends ca-certificates curl libunwind8 liblzma5 \
    && rm -rf /var/lib/apt/lists/* \
    && mkdir -p /qdrant/storage /qdrant/snapshots \
    && chown -R "$RUNA_UID:$RUNA_GID" /qdrant/storage /qdrant/snapshots
COPY --from=upstream /qdrant/qdrant /qdrant/qdrant
COPY --from=upstream /qdrant/qdrant.spdx.json /qdrant/qdrant.spdx.json
COPY --from=upstream /qdrant/config /qdrant/config
RUN /qdrant/qdrant --version
WORKDIR /qdrant
ENV TZ=Etc/UTC RUN_MODE=production
USER ${RUNA_UID}:${RUNA_GID}
ENTRYPOINT ["/qdrant/qdrant"]
HEALTHCHECK --interval=10s --timeout=3s --retries=5 CMD curl --fail --silent --output /dev/null http://127.0.0.1:6333/readyz
