FROM php:8.4-cli-alpine AS log_service

RUN apk add --no-cache git zip bash autoconf g++ make \
    rabbitmq-c rabbitmq-c-dev \
    && pecl install amqp \
    && docker-php-ext-enable amqp \
    && apk del autoconf g++ make rabbitmq-c-dev \
    && rm -rf /tmp/pear

ENV COMPOSER_CACHE_DIR=/tmp/composer-cache
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

ARG USER_ID=1000
RUN adduser -u ${USER_ID} -D -H app
USER app

COPY --chown=app . /app
WORKDIR /app

EXPOSE 8337

CMD ["php", "-S", "0.0.0.0:8337", "-t", "public/"]
