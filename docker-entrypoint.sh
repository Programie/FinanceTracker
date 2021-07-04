#! /bin/bash

printf '[PHP]\ndate.timezone = "%s"\n' "$TZ" > /usr/local/etc/php/conf.d/tzone.ini

case "$1" in
    frontend)
        exec apache2-foreground
    ;;

    backend)
        exec gosu www-data /app/bin/updater.php
    ;;

    *)
        exec "$@"
esac
