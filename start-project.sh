#!/bin/bash

# Ensure background processes are killed when this script exits (e.g. Ctrl+C)
trap 'kill 0' SIGINT

echo "Starting Socket.IO server on 0.0.0.0:3001..."
cd socket-server
node src/index.js &
cd ..

echo "Starting Laravel server on 0.0.0.0:8000..."
php artisan serve --host=0.0.0.0 --port=8000
