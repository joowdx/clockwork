#!/bin/bash

apt-get update
apt-get upgrade
apt-get install supervisor redis-server cron libc-ares-dev -y
apt-get clean
rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

echo "extension=/home/site/wwwroot/bin/swoole.so" > "$(php-config --ini-dir)/10-swoole.ini"

cp /home/site/wwwroot/nginx.conf /etc/nginx/sites-enabled/default
cp /home/site/wwwroot/docker/supervisord.conf /etc/supervisor/conf.d/
sed -i -e 's\/var/www/html/\/home/site/wwwroot/\g' -e 's\/usr/bin/php\/usr/local/bin/php\g' -e 's/user=sail//g' -e 's/--watch//g' /etc/supervisor/conf.d/supervisord.conf

crontab -l | { cat; echo "* * * * * /usr/local/bin/php /home/site/wwwroot/artisan schedule:run >> /dev/null 2>&1"; } | crontab -

exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf & >> /dev/null 2>&1

service cron start
service supervisor start
service redis-server start
service nginx reload

rm /home/site/wwwroot/bootstrap/cache/*

php artisan optimize
