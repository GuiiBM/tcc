# 🌐 Configuração do Ngrok no Painel Administrativo

## 📋 Pré-requisitos

1. **Instalar o Ngrok** (se ainda não estiver instalado):
```bash
curl -s https://ngrok-agent.s3.amazonaws.com/ngrok.asc | sudo tee /etc/apt/trusted.gpg.d/ngrok.asc >/dev/null
echo 'deb https://ngrok-agent.s3.amazonaws.com buster main' | sudo tee /etc/apt/sources.list.d/ngrok.list
sudo apt update && sudo apt install ngrok
```

2. **Configurar token do Ngrok**:
```bash
ngrok config add-authtoken SEU_TOKEN_AQUI
```
> Obtenha seu token em: https://dashboard.ngrok.com/get-started/your-authtoken

## ⚙️ Configuração Inicial

Execute o script de configuração:
```bash
cd /opt/lampp/htdocs/tcc
./configurar-ngrok.sh
```

## 🚀 Como Usar

1. **Acesse o painel administrativo** do seu sistema
2. **Clique no botão "🌐 Iniciar Ngrok"** na barra de botões
3. **Aguarde** o processamento (pode levar alguns segundos)
4. **Copie a URL pública** gerada para acessar seu site externamente

## 🔄 Funcionalidades

- **Iniciar Ngrok**: Configura MySQL e inicia o túnel público
- **Parar Ngrok**: Para o túnel e restaura configurações locais
- **Status em tempo real**: Mostra se o ngrok está rodando
- **Cópia automática**: Botão para copiar a URL pública

## 🔧 Configuração Automática do MySQL

O sistema automaticamente:
- Configura MySQL para aceitar conexões externas
- Cria usuário específico para ngrok
- Restaura configurações locais ao parar

## 📁 Arquivos Criados

- `ngrok-manager.php`: Gerenciador backend do ngrok
- `conexao-inteligente.php`: Conexão MySQL que detecta ngrok automaticamente
- `configurar-ngrok.sh`: Script de configuração inicial

## 🔒 Segurança

- Usuário MySQL específico para ngrok: `usuario_ngrok`
- Senha: `senha_ngrok_123`
- Permissões limitadas apenas ao banco `musicas`

## 🐛 Solução de Problemas

Se o ngrok não iniciar:
1. Verifique se o token está configurado: `ngrok config check`
2. Verifique os logs: `cat /tmp/ngrok.log`
3. Certifique-se que a porta 80 não está sendo usada por outro processo