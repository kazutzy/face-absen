#!/usr/bin/env bash
# Script untuk menjalankan FaceAbsen Chatbot AI Node.js Service di background

DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PID_FILE="$DIR/chatbot.pid"
LOG_FILE="$DIR/chatbot.log"

# Cari binary node
NODE_BIN="/opt/lampp/htdocs/projectrpl/node-runtime/bin/node"
if [ ! -f "$NODE_BIN" ]; then
    NODE_BIN="$(which node)"
fi

if [ -z "$NODE_BIN" ]; then
    echo "Error: Node.js binary tidak ditemukan."
    exit 1
fi

if [ -f "$PID_FILE" ]; then
    PID=$(cat "$PID_FILE")
    if ps -p "$PID" > /dev/null 2>&1; then
        echo "Chatbot server sudah berjalan dengan PID $PID."
        exit 0
    else
        rm -f "$PID_FILE"
    fi
fi

echo "Memulai FaceAbsen Chatbot AI Server dengan Node.js ($NODE_BIN)..."
nohup "$NODE_BIN" "$DIR/server.js" >> "$LOG_FILE" 2>&1 &
PID=$!
echo $PID > "$PID_FILE"

sleep 1
if ps -p "$PID" > /dev/null 2>&1; then
    echo "Chatbot server berhasil dijalankan dengan PID $PID di port 3001."
else
    echo "Gagal menjalankan chatbot server. Periksa $LOG_FILE."
    exit 1
fi
