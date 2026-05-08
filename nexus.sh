#!/bin/bash

# Nexus Unified Startup Script - Ultra Simple
set +m # Disable job control notifications (silences 'Killed' messages)
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR"

# Colors
G='\033[38;5;46m'; W='\033[38;5;255m'; R='\033[38;5;196m'; B='\033[38;5;21m'; D='\033[38;5;240m'; NC='\033[0m'

function show_logo() {
    clear
    echo -e "\n"
    local logo=(
        "     _   _  _____  __  __ _   _  ____  "
        "    | \ | || ____| \ \/ /| | | |/ ___| "
        "    |  \| ||  _|    \  / | | | |\___ \ "
        "    | |\  || |___   /  \ | |_| | ___) |"
        "    |_| \_||_____| /_/\_\ \___/ |____/ "
    )
    local chars="01"
    for line in "${logo[@]}"; do
        # Matrix Digital Rain Reveal
        for i in {1..5}; do
            local rain=""
            for (( j=0; j<${#line}; j++ )); do
                if [[ "${line:$j:1}" == " " ]]; then
                    rain+=" "
                else
                    rain+="${chars:RANDOM%2:1}"
                fi
            done
            echo -ne "\r${G}${rain}${NC}"
            sleep 0.02
        done
        echo -e "\r${G}${line}${NC}"
        sleep 0.04
    done
    echo -e "\n          ${W}N E X U S   S Y S T E M${NC}\n"
}

function kill_services() {
    # Force kill all background components from previous sessions
    pkill -9 -f "php artisan octane" 2>/dev/null
    pkill -9 -f "frankenphp" 2>/dev/null
    pkill -9 -f "node src/index.js" 2>/dev/null
    pkill -9 -f "cloudflared tunnel" 2>/dev/null
    pkill -9 -f "ngrok" 2>/dev/null
    pkill -9 -f "node proxy.cjs" 2>/dev/null
    pkill -9 -f "tail -n 0 -f storage/logs" 2>/dev/null
    pkill -9 -f "awk -v p=" 2>/dev/null
}

# Env handling
ORIGINAL_APP_URL=$(grep '^APP_URL=' .env | cut -d'=' -f2- 2>/dev/null || echo "http://localhost:8000")
ORIGINAL_SOCKET_URL=$(grep '^SOCKET_IO_URL=' .env | cut -d'=' -f2- 2>/dev/null || echo "http://localhost:3001")
ORIGINAL_SANCTUM=$(grep '^SANCTUM_STATEFUL_DOMAINS=' .env | cut -d'=' -f2- 2>/dev/null)
ORIGINAL_SESSION=$(grep '^SESSION_DOMAIN=' .env | cut -d'=' -f2- 2>/dev/null)
ORIGINAL_SECURE=$(grep '^SESSION_SECURE_COOKIE=' .env | cut -d'=' -f2- 2>/dev/null)
ORIGINAL_GOOGLE=$(grep '^GOOGLE_REDIRECT_URI=' .env | cut -d'=' -f2- 2>/dev/null)
ORIGINAL_OCTANE_HOST=$(grep '^OCTANE_HTTPS=' .env | cut -d'=' -f2- 2>/dev/null)
ORIGINAL_ASSET_URL=$(grep '^ASSET_URL=' .env | cut -d'=' -f2- 2>/dev/null)

RESTARTING=0
CLEANED_UP=0
cleanup() {
    [ "$CLEANED_UP" -eq 1 ] && return
    CLEANED_UP=1
    
    # 1. Kill background processes quietly
    [ ! -z "$WATCHDOG_PID" ] && kill -9 $WATCHDOG_PID 2>/dev/null
    [ ! -z "$LOG1_PID" ] && kill -9 $LOG1_PID 2>/dev/null
    [ ! -z "$LOG2_PID" ] && kill -9 $LOG2_PID 2>/dev/null
    jobs -p | xargs kill -9 2>/dev/null
    
    # 2. Reset scrolling region and move cursor to bottom
    echo -ne "\e[r"
    tput cup $(tput lines) 0
    
    echo -e "\n${D}> Stopping systems...${NC}"
    
    # 3. Kill external services
    kill_services 2>/dev/null
    
    # 4. Restore Env
    sed -i "s|^APP_URL=.*|APP_URL=$ORIGINAL_APP_URL|" .env 2>/dev/null
    sed -i "s|^SOCKET_IO_URL=.*|SOCKET_IO_URL=$ORIGINAL_SOCKET_URL|" .env 2>/dev/null
    sed -i "s|^SANCTUM_STATEFUL_DOMAINS=.*|SANCTUM_STATEFUL_DOMAINS=$ORIGINAL_SANCTUM|" .env 2>/dev/null
    sed -i "s|^SESSION_DOMAIN=.*|SESSION_DOMAIN=$ORIGINAL_SESSION|" .env 2>/dev/null
    sed -i "s|^SESSION_SECURE_COOKIE=.*|SESSION_SECURE_COOKIE=$ORIGINAL_SECURE|" .env 2>/dev/null
    sed -i "s|^ASSET_URL=.*|ASSET_URL=$ORIGINAL_ASSET_URL|" .env 2>/dev/null
    sed -i "s|^GOOGLE_REDIRECT_URI=.*|GOOGLE_REDIRECT_URI=$ORIGINAL_GOOGLE|" .env 2>/dev/null
    
    php artisan config:clear >/dev/null 2>&1
    php artisan octane:stop >/dev/null 2>&1
    
    if [ "$RESTARTING" -eq 1 ]; then
        echo -e "${G}✔ Restarting System...${NC}\n"
        exec "$0" "$@"
    fi

    echo -e "${G}✔ System offline.${NC}\n"
    exit 0
}

trap cleanup INT TERM EXIT

# Start
kill_services 2>/dev/null
show_logo

# 1. Define URLs (Static for this project)
APP_URL="https://stickit-fearlessly-braiden.ngrok-free.dev"
DOMAIN=$(echo "$APP_URL" | sed 's|https://||')
SOCKET_URL="$APP_URL"

# 2. Patch Environment IMMEDIATELY
echo -e "${G}Patching Environment...${NC}"
sed -i "s|^APP_URL=.*|APP_URL=$APP_URL|" .env
sed -i "s|^ASSET_URL=.*|ASSET_URL=$APP_URL|" .env
sed -i "s|^SOCKET_IO_URL=.*|SOCKET_IO_URL=$SOCKET_URL|" .env
if grep -q "^INTERNAL_SOCKET_URL=" .env; then
    sed -i "s|^INTERNAL_SOCKET_URL=.*|INTERNAL_SOCKET_URL=http://127.0.0.1:3001|" .env
else
    echo "INTERNAL_SOCKET_URL=http://127.0.0.1:3001" >> .env
fi
sed -i "s|^SANCTUM_STATEFUL_DOMAINS=.*|SANCTUM_STATEFUL_DOMAINS=$DOMAIN,localhost,127.0.0.1|" .env
sed -i "s|^SESSION_DOMAIN=.*|SESSION_DOMAIN=|" .env
sed -i "s|^SESSION_SECURE_COOKIE=.*|SESSION_SECURE_COOKIE=true|" .env
sed -i "s|^GOOGLE_REDIRECT_URI=.*|GOOGLE_REDIRECT_URI=$APP_URL/auth/google/callback|" .env

# Launch
echo -e "${D}> Initializing kernel...${NC}"
mkdir -p storage/logs
touch storage/logs/laravel-server.log
touch storage/logs/socket-server.log
touch storage/logs/proxy.log
touch storage/logs/octane.log

# Clear config (Never cache in development to prevent session/env issues)
php artisan config:clear >/dev/null 2>&1
php artisan route:clear >/dev/null 2>&1
php artisan view:clear >/dev/null 2>&1
php artisan event:clear >/dev/null 2>&1

# 1. Start Octane FIRST and wait for it
( php artisan octane:start --host=0.0.0.0 --port=8000 --max-requests=100 --workers=4 --watch > storage/logs/laravel-server.log 2>&1 ) & disown
PHP_PID=$!
sleep 3

# 2. Start Socket Server
cd socket-server
( node src/index.js >> ../storage/logs/socket-server.log 2>&1 ) & disown
SOCKET_PID=$!
cd ..

# 3. Start Proxy (Unifies Web and Sockets on port 8080)
( node proxy.cjs > storage/logs/proxy.log 2>&1 ) & disown
PROXY_PID=$!

echo -en "${D}> Waiting for Engine...${NC} "
for i in {1..20}; do
    if curl -s http://127.0.0.1:8080 > /dev/null; then
        echo -e "${G}Ready.${NC}"
        break
    fi
    printf "${D}.${NC}"
    sleep 1
done

# 4. Start Tunnel (Single Ngrok tunnel for everything)
( ngrok http --url=stickit-fearlessly-braiden.ngrok-free.dev 8080 > /dev/null 2>&1 ) & disown

# Dashboard
echo -e "${D}  ----------------------------${NC}"
echo -e "  ${W}STATUS:${NC}   ${G}ONLINE${NC}"
echo -e "  ${W}KERNEL:${NC}   ${D}Unified Proxy Tunnel${NC}\n"
echo -e "  ${G}URL:${NC} ${W}$APP_URL${NC}\n"
echo -e "${D}  ----------------------------${NC}"
echo -e "  ${W}CONSOLES:${NC} ${D}Monitoring...${NC}"
echo -e "  ${W}COMMANDS:${NC} ${G}[r]${NC} ${D}Restart${NC}  ${R}[CTRL+C]${NC} ${D}Stop${NC}\n\n"

# Set scrolling region (Everything above is 23 lines)
if [ "$(tput lines)" -gt 24 ]; then
    echo -ne "\e[24;r"
    tput cup 23 0
fi

# Watchdog Engine (Automatic Recovery)
( 
    while true; do
        [ "$CLEANED_UP" -eq 1 ] && exit 0
        
        # Monitor Socket Server
        if ! kill -0 $SOCKET_PID 2>/dev/null; then
            echo -e "\n${R}[SKT] Crashed! Restarting...${NC}"
            cd socket-server && ( node src/index.js >> ../storage/logs/socket-server.log 2>&1 ) & disown
            SOCKET_PID=$!
            cd ..
        fi

        # Monitor PHP/Octane
        if ! kill -0 $PHP_PID 2>/dev/null; then
            echo -e "\n${R}[SYS] Crashed! Recovering...${NC}"
            ( php artisan octane:start --host=0.0.0.0 --port=8000 --max-requests=100 --workers=4 --watch >> storage/logs/laravel-server.log 2>&1 ) & disown
            PHP_PID=$!
        fi

        # Monitor Proxy
        if ! kill -0 $PROXY_PID 2>/dev/null; then
            echo -e "\n${R}[PRX] Crashed! Re-aligning...${NC}"
            ( node proxy.cjs >> storage/logs/proxy.log 2>&1 ) & disown
            PROXY_PID=$!
        fi

        sleep 5
    done 
) & disown
WATCHDOG_PID=$!

# Unified Log Stream (New lines only)
function stream_log() {
    tail -n 0 -f "$1" | awk -v p="$2" -v g="$G" -v r="$R" -v b="$B" -v w="$W" -v n="$NC" '{
        gsub(/INFO/, g "INFO" n); gsub(/ERROR/, r "ERROR" n); gsub(/SUCCESS/, g "SUCCESS" n);
        gsub(/ 200 /, " " g "200" n " "); gsub(/ 304 /, " " b "304" n " ");
        gsub(/ 40[0-9] /, " " r "&" n " "); gsub(/ 50[0-9] /, " " r "&" n " ");
        gsub(/ GET /, " " w "GET" n " "); gsub(/ POST /, " " w "POST" n " ");
        print p $0; fflush();
    }'
}

( stream_log "storage/logs/laravel-server.log" "${G}[SYS]${NC} " ) & disown
LOG1_PID=$!
( stream_log "storage/logs/socket-server.log" "${B}[SKT]${NC} " ) & disown
LOG2_PID=$!

# Block and keep script alive
while true; do
    read -t 1 -n 1 -s key 2>/dev/null
    if [[ $key == "r" ]]; then
        RESTARTING=1
        cleanup
    fi
    
    # If both logs stop, exit loop
    if ! kill -0 $LOG1_PID 2>/dev/null && ! kill -0 $LOG2_PID 2>/dev/null; then
        break
    fi
done

