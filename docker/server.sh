#!/usr/bin/env sh

if [ ! -d /.composer ]; then
    mkdir /.composer
fi

chmod -R ugo+rw /.composer

if [ $# -gt 0 ];then
    exec su-exec sail "$@"
else
    /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
fi
