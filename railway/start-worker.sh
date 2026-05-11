#!/bin/sh
set -e

php artisan config:cache

php artisan queue:work --sleep=3 --tries=3 --timeout=90
