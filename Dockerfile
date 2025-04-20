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


FROM ghcr.io/programie/php-docker

ARG S6_OVERLAY_VERSION=3.2.0.2

ENV WEB_ROOT=/app/httpdocs
ENV S6_KEEP_ENV=1

WORKDIR /app

RUN apt-get update && \
    apt-get install -y curl ca-certificates gosu xz-utils && \
    install-php 8.2 dom intl pdo-mysql && \
    a2enmod rewrite && \
    curl -L https://github.com/just-containers/s6-overlay/releases/download/v${S6_OVERLAY_VERSION}/s6-overlay-noarch.tar.xz | tar -C / -Jxp && \
    curl -L https://github.com/just-containers/s6-overlay/releases/download/v${S6_OVERLAY_VERSION}/s6-overlay-x86_64.tar.xz | tar -C / -Jxp

COPY --from=composer /app/vendor /app/vendor
COPY --from=webpack /app/httpdocs/assets /app/httpdocs/assets
COPY --from=webpack /app/webpack.assets.json /app/webpack.assets.json

COPY s6-services /etc/services.d
COPY bootstrap.php /app/bootstrap.php
COPY cli-config.php /app/cli-config.php
COPY bin /app/bin
COPY httpdocs /app/httpdocs
COPY src /app/src

RUN curl -o /tmp/coin_map.json https://raw.githubusercontent.com/ErikThiart/cryptocurrency-icons/master/coin_map.json && \
    /app/bin/update-coinmap.php /tmp/coin_map.json && \
    rm /tmp/coin_map.json

ENTRYPOINT ["/init"]
CMD ["apache2-foreground"]
