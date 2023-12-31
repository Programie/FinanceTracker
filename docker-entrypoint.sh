#! /bin/bash

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
