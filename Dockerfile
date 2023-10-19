FROM node:18 AS webpack

WORKDIR /app

COPY package.json package-lock.json /app/
RUN npm install

COPY webpack.config.js /app/
COPY src/main/resources/assets /app/src/main/resources/assets
RUN npm run build


FROM composer AS composer

WORKDIR /app

COPY composer.* /app/
RUN composer install --no-dev --ignore-platform-reqs


FROM ghcr.io/programie/php

ENV WEB_ROOT=/app/httpdocs

WORKDIR /app

RUN apt-get update && \
    apt-get install -y curl ca-certificates gosu && \
    install-php 8.0 dom intl pdo-mysql && \
    a2enmod rewrite

COPY --from=composer /app/vendor /app/vendor
COPY --from=webpack /app/httpdocs/assets /app/httpdocs/assets
COPY --from=webpack /app/webpack.assets.json /app/webpack.assets.json

COPY docker-entrypoint.sh /entrypoint.sh
COPY bootstrap.php /app/bootstrap.php
COPY cli-config.php /app/cli-config.php
COPY bin /app/bin
COPY httpdocs /app/httpdocs
COPY src /app/src

RUN curl -o /tmp/coin_map.json https://raw.githubusercontent.com/ErikThiart/cryptocurrency-icons/master/coin_map.json && \
    /app/bin/update-coinmap.php /tmp/coin_map.json && \
    rm /tmp/coin_map.json

ENTRYPOINT ["/entrypoint.sh"]
CMD ["frontend"]