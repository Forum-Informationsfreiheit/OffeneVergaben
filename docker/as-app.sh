#!/bin/sh
# Run a command as the application user.
#
# Under rootful Docker the container starts as root, so we drop to www-data.
# Under rootless podman with `userns_mode: keep-id` the container already starts as
# your host UID — which the image remapped www-data to — so we just exec.
set -e

if [ "$(id -u)" = "0" ]; then
    exec runuser -u www-data -- "$@"
fi

exec "$@"
