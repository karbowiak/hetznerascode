FROM php:7.4-alpine

ARG BUILD_DATE
ARG VCS_REF

ENV COMPOSER_ALLOW_SUPERUSER 1

RUN set -ex \
  	&& apk update \
    && apk add --no-cache \
      git \
      mysql-client \
      curl \
      openssh-client \
      icu \
      libpng \
      freetype \
      libjpeg-turbo \
      libsodium-dev \
      yaml-dev \
      libzip-dev \
      postgresql-dev \
      libffi-dev \
    && apk add --no-cache --virtual \
      build-dependencies \
      icu-dev \
      libxml2-dev \
      freetype-dev \
      libpng-dev \
      libjpeg-turbo-dev \
      g++ \
      make \
      autoconf \
    && docker-php-source extract \
    && pecl install \
      yaml \
    && docker-php-ext-enable \
      yaml \
    && docker-php-source delete \
    && docker-php-ext-configure pgsql -with-pgsql=/usr/local/pgsql \
    && docker-php-ext-configure gd \
    && docker-php-ext-install -j$(nproc) \
      pcntl \
      pgsql \
      pdo_mysql \
      pdo_pgsql \
      intl \
      zip \
      gd \
      bcmath \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    && cd  / && rm -fr /src \
    && apk del build-dependencies \
    && rm -rf /tmp/*

USER www-data

WORKDIR /var/www
CMD ["php", "-a"]
