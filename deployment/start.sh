#!/bin/bash

apt-get update
apt-get install supervisor redis-server cron libc-ares-dev -y
apt-get clean
rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

cp /home/site/wwwroot/deployment/swoole.ini "$(php-config --ini-dir)/10-swoole.ini"
cp /home/site/wwwroot/deployment/opcache.ini "$(php-config --ini-dir)/10-opcache.ini"
cp /home/site/wwwroot/deployment/nginx.conf /etc/nginx/sites-enabled/default
cp /home/site/wwwroot/deployment/supervisord.conf /etc/supervisor/supervisord.conf

rm /home/site/wwwroot/bootstrap/cache/*

php artisan optimize

crontab -l | { cat; echo "* * * * * /usr/local/bin/php /home/site/wwwroot/artisan schedule:run >> /dev/null 2>&1"; } | crontab -

service cron start
service supervisor start
service redis-server start
service nginx reload
