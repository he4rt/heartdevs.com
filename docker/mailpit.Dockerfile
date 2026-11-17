# syntax=docker/dockerfile:1

FROM axllent/mailpit:v1.28 AS main

ENV TZ=America/Sao_Paulo

RUN set -eux;\
    apk update;\
    apk add --no-cache tzdata nano ca-certificates;\
    ln -snf /usr/share/zoneinfo/"${TZ}" /etc/localtime;\
    echo "${TZ}" > /etc/timezone;\
    update-ca-certificates;\
    rm -rf /var/cache/apk/*;
