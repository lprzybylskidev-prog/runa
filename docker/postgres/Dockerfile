# syntax=docker/dockerfile:1
FROM golang:1.27.3-alpine3.24@sha256:b8c9b82affbbb82a020bbcf998dad2ba9516944ac7e883524642a481a9505e9c AS gosu-build
WORKDIR /build
# gosu 1.20 source; retain checksum verification and the upstream module checksums.
ADD --checksum=sha256:2b70da8a5480fafedc16bb1fc91b572f064d03df736cbd91a616af2f1b66ab51 https://codeload.github.com/tianon/gosu/tar.gz/29b939aa5bd4dc588af765b8248f6e39150d2565 /tmp/gosu.tar.gz
RUN tar -xzf /tmp/gosu.tar.gz --strip-components=1 \
    && go mod download \
    && go mod verify \
    && CGO_ENABLED=0 go build -trimpath -ldflags='-w' -o /out/gosu . \
    && go version -m /out/gosu

FROM postgres:18.6-alpine3.24@sha256:77f585114c32fbca283dc835b0596f4e52b51b4c6662d7810b2f4084f60a1873
RUN apk add --upgrade --no-cache 'libcurl>=8.23.0-r0' 'brotli-libs>=1.2.1-r0'
COPY --from=gosu-build --chmod=755 /out/gosu /usr/local/bin/gosu
RUN gosu --version && gosu nobody true
USER postgres
