# syntax=docker/dockerfile:1.15.0

FROM redis:8-alpine

ENV TZ=America/Sao_Paulo

RUN set -xeu; \
    apk update;\
    apk add --no-cache tzdata nano ca-certificates;\
    ln -snf /usr/share/zoneinfo/"${TZ}" /etc/localtime;\
    echo "${TZ}" > /etc/timezone;\
    update-ca-certificates;\
    rm -rf /var/cache/apk/*;
