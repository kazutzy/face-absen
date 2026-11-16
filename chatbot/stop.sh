#!/usr/bin/env bash
# Script untuk menghentikan FaceAbsen Chatbot AI Node.js Service

DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PID_FILE="$DIR/chatbot.pid"

if [ -f "$PID_FILE" ]; then
    PID=$(cat "$PID_FILE")
    if ps -p "$PID" > /dev/null 2>&1; then
        echo "Menghentikan Chatbot server (PID $PID)..."
        kill "$PID"
        sleep 1
        if ps -p "$PID" > /dev/null 2>&1; then
            kill -9 "$PID"
        fi
        rm -f "$PID_FILE"
        echo "Chatbot server telah dihentikan."
        exit 0
    else
        rm -f "$PID_FILE"
        echo "Chatbot server tidak sedang berjalan (PID usang dihapus)."
        exit 0
    fi
else
    # Coba cari proses node server.js
    PIDS=$(pgrep -f "chatbot/server.js")
    if [ -n "$PIDS" ]; then
        echo "Menghentikan proses chatbot server: $PIDS"
        kill $PIDS
        echo "Proses dihentikan."
    else
        echo "Chatbot server tidak sedang berjalan."
    fi
fi
