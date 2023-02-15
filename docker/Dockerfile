FROM ubuntu:22.04
LABEL maintainer="Joowd"
ARG WWWGROUP
WORKDIR /var/www/html
ENV DEBIAN_FRONTEND noninteractive
ENV TZ=UTC
RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone
RUN apt-get update \
    && apt-get install -y cron ca-certificates curl zip unzip git supervisor sqlite3 libcap2-bin libpng-dev python2 libc-ares-dev build-essential libcurl4-openssl-dev \
        php-cli php-dev php-pgsql php-sqlite3 php-gd php-curl php-imap php-mysql php-mbstring php-xml php-zip php-bcmath php-soap \
        php-intl php-readline php-ldap php-msgpack php-igbinary php-redis php-memcached php-pcov php-xdebug \
        composer nodejs npm \
    && npm install -g n \
    && n i 18 \
    && pecl install -D 'enable-sockets="yes" enable-openssl="yes" enable-http2="yes" enable-mysqlnd="yes" enable-swoole-json="yes" enable-swoole-curl="yes" enable-cares="yes" with-postgres="yes"' swoole \
    && echo "extension=swoole.so" >> /etc/php/8.1/cli/conf.d/20-swoole.ini \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*
RUN setcap "cap_net_bind_service=+ep" /usr/bin/php8.1
RUN groupadd --force -g $WWWGROUP sail
RUN useradd -ms /bin/bash --no-user-group -g $WWWGROUP -u 1337 sail
RUN echo "* * * * * /usr/bin/php /var/www/html/artisan schedule:run >> /dev/null 2>&1" > /var/spool/cron/crontabs/root
COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY php.ini /etc/php/8.1/cli/conf.d/99-sail.ini
COPY server.sh /usr/local/bin/server.sh
RUN chmod +x /usr/local/bin/server.sh
EXPOSE 8000
ENTRYPOINT ["server.sh"]
