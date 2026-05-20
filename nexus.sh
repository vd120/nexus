#!/bin/bash

# Simplified Nexus Startup
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR"

# Cleanup function to kill all background services
kill_services() {
    echo -e "
> Stopping services..."
    pkill -9 -f "php artisan octane"
    pkill -9 -f "node src/index.js"
    pkill -9 -f "cloudflared"
    pkill -9 -f "node proxy.cjs"
    pkill -P $$ # Kill remaining child processes
    exit 0
}
trap kill_services INT TERM EXIT

echo "● Starting Services for https://nexusocial.qzz.io/"
mkdir -p storage/logs

# 1. Start Octane
php artisan octane:start --host=127.0.0.1 --port=8000 --max-requests=100 --workers=4 > octane.log 2>&1 &
echo "  > Started Octane (8000)"

# 2. Start Socket Server
(cd socket-server && node src/index.js > ../socket.log 2>&1) &
echo "  > Started Socket Server (3001)"

# 3. Start Proxy
node proxy.cjs >> storage/logs/proxy.log 2>&1 &
echo "  > Started Proxy (8080)"

# 4. Start Tunnel
cloudflared tunnel --config .cloudflared/config.yml run nexusocial > tunnel.log 2>&1 &
echo "  > Started Tunnel"

# 5. Warm-up
php artisan nexus:warm --force
echo "✔ System Online."
echo "------------------------------------------------"
echo " Monitoring Visitors (Press Ctrl+C to stop)..."
echo "------------------------------------------------"

# Display visitor logs
tail -F storage/logs/proxy.log
