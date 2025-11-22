import subprocess
import time

# Inicia ngrok se não estiver rodando
result = subprocess.run(['pgrep', '-f', 'ngrok'], capture_output=True)
if result.returncode != 0:
    print("Iniciando ngrok...")
    subprocess.Popen(['ngrok', 'http', '80'], stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL)
    time.sleep(5)

# Verifica URL
try:
    import requests
    response = requests.get('http://localhost:4040/api/tunnels', timeout=5)
    data = response.json()
    
    https_url = None
    for tunnel in data['tunnels']:
        if tunnel['proto'] == 'https':
            https_url = tunnel['public_url']
            break
    
    if https_url:
        print(f"✓ Ngrok ativo: {https_url}")
    else:
        print("✓ Ngrok rodando - acesse http://localhost:4040 para ver detalhes")
        
except Exception as e:
    print("✓ Ngrok iniciado - aguarde alguns segundos e acesse http://localhost:4040")