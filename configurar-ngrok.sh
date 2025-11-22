#!/bin/bash
# configurar-ngrok.sh - Script para configurar permissões do ngrok

echo "🔧 Configurando permissões para o ngrok..."

# Configurar permissões do arquivo my.cnf
sudo chown www-data:www-data /opt/lampp/etc/my.cnf

# Criar arquivo de permissões sudo para www-data
echo "www-data ALL=(ALL) NOPASSWD: /opt/lampp/lampp, /bin/sed, /opt/lampp/bin/mysql" | sudo tee /etc/sudoers.d/www-data

# Definir permissões corretas para o arquivo sudoers
sudo chmod 440 /etc/sudoers.d/www-data

# Verificar se ngrok está instalado
if ! command -v ngrok &> /dev/null; then
    echo "⚠️  Ngrok não está instalado. Instale com:"
    echo "   curl -s https://ngrok-agent.s3.amazonaws.com/ngrok.asc | sudo tee /etc/apt/trusted.gpg.d/ngrok.asc >/dev/null"
    echo "   echo 'deb https://ngrok-agent.s3.amazonaws.com buster main' | sudo tee /etc/apt/sources.list.d/ngrok.list"
    echo "   sudo apt update && sudo apt install ngrok"
    echo "   ngrok config add-authtoken SEU_TOKEN_AQUI"
else
    echo "✅ Ngrok já está instalado"
fi

echo "✅ Configuração concluída!"
echo "📝 Agora você pode usar o botão Ngrok no painel administrativo"