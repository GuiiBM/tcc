#!/usr/bin/env python3
import subprocess
import time
import requests
import json
import os
import signal

def kill_existing_ngrok():
    """Mata processos ngrok existentes"""
    try:
        subprocess.run(['pkill', '-f', 'ngrok'], check=False)
        time.sleep(1)
    except:
        pass

def start_ngrok():
    """Inicia ngrok em background"""
    kill_existing_ngrok()
    
    # Inicia ngrok
    process = subprocess.Popen(['ngrok', 'http', '80'], 
                             stdout=subprocess.DEVNULL, 
                             stderr=subprocess.DEVNULL)
    
    # Aguarda inicialização
    time.sleep(3)
    
    return process

def get_ngrok_url():
    """Obtém a URL pública do ngrok"""
    try:
        response = requests.get('http://localhost:4040/api/tunnels')
        data = response.json()
        
        for tunnel in data['tunnels']:
            if tunnel['proto'] == 'https':
                return tunnel['public_url']
        
        return None
    except:
        return None

if __name__ == "__main__":
    print("Iniciando ngrok...")
    
    process = start_ngrok()
    url = get_ngrok_url()
    
    if url:
        print(f"✓ Ngrok iniciado com sucesso!")
        print(f"URL pública: {url}")
        print(f"PID do processo: {process.pid}")
        
        # Salva a URL em arquivo
        with open('ngrok_url.txt', 'w') as f:
            f.write(url)
            
    else:
        print("✗ Erro ao obter URL do ngrok")