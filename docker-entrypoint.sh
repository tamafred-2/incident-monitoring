#!/bin/sh
# Runs each time the container boots on Railway.
set -e

# Apply any pending database migrations (idempotent — only runs new ones).
php artisan migrate --force

# Recreate the public/storage symlink so uploaded photos are served.
# Harmless if it already exists.
php artisan storage:link || true

# Serve the app on the port Railway assigns. `exec` makes PHP PID 1 so it
# receives stop/restart signals cleanly.
exec php artisan serve --host 0.0.0.0 --port "${PORT:-8080}"
