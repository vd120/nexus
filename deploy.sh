#!/bin/bash
set -e
npm run build && php artisan octane:reload
