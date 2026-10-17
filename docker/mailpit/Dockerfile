FROM axllent/mailpit:v1.31.6
ARG RUNA_UID=1000
ARG RUNA_GID=1000
COPY --chown=${RUNA_UID}:${RUNA_GID} docker/mailpit/.gitkeep /data/.gitkeep
USER ${RUNA_UID}:${RUNA_GID}
