#!/bin/bash

# Mata processos ngrok existentes
pkill -f ngrok

# Inicia ngrok em background
nohup ngrok http 80 > ngrok.log 2>&1 &

# Aguarda alguns segundos para o ngrok inicializar
sleep 3

# Mostra o status
echo "Ngrok iniciado! Verificando URL..."
curl -s http://localhost:4040/api/tunnels | grep -o '"public_url":"[^"]*' | grep -o 'https://[^"]*' | head -1