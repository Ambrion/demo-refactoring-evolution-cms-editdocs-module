FROM php:8.1-fpm-alpine

ARG PUID=1000
ARG PGID=1000
ENV PUID ${PUID}
ENV PGID ${PGID}

ARG XDEBUG_CLIENT_HOST
ARG XDEBUG_CLIENT_PORT
ARG XDEBUG_START_WITH_REQUEST
ARG XDEBUG_MODE
ARG INI_FILE=/usr/local/etc/php/php.ini

RUN apk --update --no-cache add  \
        linux-headers \
        build-base \
        autoconf \
        git \
        zlib-dev \
        sudo \
        libxml2-dev \
        openssl-dev

RUN pecl install xdebug && docker-php-ext-enable xdebug \
    && apk --no-cache add libzip-dev \
    && docker-php-ext-install zip \
    && docker-php-ext-install soap  \
    && docker-php-ext-install ftp

# Install GD
RUN set -eux; \
    apk add --no-cache \
        freetype-dev \
        libjpeg-turbo-dev \
        libpng-dev \
        libwebp-dev; \
    docker-php-ext-configure gd \
        --with-freetype \
        --with-jpeg \
        --with-webp; \
    docker-php-ext-install gd;

# Install intl
RUN set -eux; \
    apk add --no-cache \
        icu-dev; \
    docker-php-ext-install intl;

# Install MySQL PDO and related extensions
RUN docker-php-ext-configure pdo_mysql --with-pdo-mysql=mysqlnd \
    && docker-php-ext-configure mysqli --with-mysqli=mysqlnd \
    && docker-php-ext-install mysqli pdo pdo_mysql

COPY ./php.local.ini $INI_FILE

RUN apk add --no-cache --virtual .build-deps gettext \
    && envsubst < ${INI_FILE} > ${INI_FILE}.tmp && mv ${INI_FILE}.tmp ${INI_FILE}

RUN sed -i 's/;ping.path/ping.path/' /usr/local/etc/php-fpm.d/www.conf

### user ###
# Set defaults for PUID and PGID if not provided
RUN PU=${PUID:-1000} && \
    PG=${PGID:-1000} && \
    addgroup -g $PG dev && \
    adduser -D -u $PU -G dev dev && \
    echo dev:pwd | chpasswd

RUN echo "%dev ALL=(ALL:ALL) NOPASSWD: ALL" >> /etc/sudoers

# Alpine использует /etc/ssl/cert.pem как основной бандл!
# Российские сертификаты
RUN curl -sSL https://gu-st.ru/content/Other/doc/russiantrustedca.pem -o /tmp/russiantrustedca.pem \
    && cat /tmp/russiantrustedca.pem >> /etc/ssl/cert.pem \
    && rm /tmp/russiantrustedca.pem

RUN apk add --no-cache curl openssl

COPY ./openssl.cnf /etc/ssl/openssl.cnf
ENV OPENSSL_CONF /etc/ssl/openssl.cnf

COPY --from=composer/composer:latest-bin /composer /usr/local/bin/composer

USER www-data
