import subprocess
import time
import os

# Mata processos ngrok existentes
os.system('pkill -f ngrok 2>/dev/null')
time.sleep(1)

# Inicia ngrok
subprocess.Popen(['ngrok', 'http', '80'])
time.sleep(3)

# Verifica URL
try:
    import requests
    response = requests.get('http://localhost:4040/api/tunnels')
    data = response.json()
    
    for tunnel in data['tunnels']:
        if tunnel['proto'] == 'https':
            print(f"✓ Ngrok rodando: {tunnel['public_url']}")
            break
except:
    print("✓ Ngrok iniciado - verifique em http://localhost:4040")