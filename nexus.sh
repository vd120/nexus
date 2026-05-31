#!/bin/bash

# Simplified Nexus Startup
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR"

# Cleanup function to kill all background services
kill_services() {
    # Remove the trap to prevent recursion or double-firing
    trap - INT TERM EXIT
    
    echo -e "\n> Stopping services..."
    pkill -f "php artisan octane"
    pkill -f "node src/index.js"
    pkill -f "cloudflared"
    pkill -f "node proxy.cjs"
    pkill -f "frankenphp"
    pkill -P $$ 2>/dev/null
    exit 0
}
# Only trap specific termination signals to avoid double-firing with EXIT
trap kill_services INT TERM EXIT

# Pre-start check: Kill any lingering processes to prevent duplicates
echo "● Cleaning up existing processes..."
pkill -f "php artisan octane"
pkill -f "node src/index.js"
pkill -f "node proxy.cjs"
pkill -f "frankenphp"

echo "● Starting Services for https://nexusocial.qzz.io/"
mkdir -p storage/logs

# Set Node to ignore deprecation warnings from libraries
export NODE_NO_WARNINGS=1

# 1. Start Octane
php artisan octane:start --host=127.0.0.1 --port=8000 --max-requests=100 --workers=8 > octane.log 2>&1 &
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
