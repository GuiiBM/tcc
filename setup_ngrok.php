<?php
// Script de configuração completa do Ngrok
function setupNgrokComplete() {
    $configDir = '/var/www/.config/ngrok';
    $configFile = $configDir . '/ngrok.yml';
    
    // Criar diretório se não existir
    if (!is_dir($configDir)) {
        mkdir($configDir, 0755, true);
        echo "Diretório de configuração criado: $configDir\n";
    }
    
    // Configuração completa do Ngrok
    $config = "version: \"3\"
agent:
    authtoken: 33Q3YmwuKMYt6YOPOKYlYXmUfJn_pxbGrjHBCRrz9zyzqLsj
tunnels:
    tcc:
        proto: http
        addr: 80
        domain: charlyn-unpaying-zara.ngrok-free.dev
";
    
    file_put_contents($configFile, $config);
    chmod($configFile, 0600);
    echo "Arquivo de configuração criado: $configFile\n";
    
    // Parar qualquer instância anterior
    shell_exec('pkill -f ngrok 2>/dev/null');
    echo "Instâncias anteriores do Ngrok finalizadas\n";
    
    sleep(2);
    
    // Iniciar Ngrok
    shell_exec('HOME=/var/www nohup ngrok start tcc > /tmp/ngrok_setup.log 2>&1 &');
    echo "Ngrok iniciado com domínio fixo\n";
    
    sleep(3);
    
    // Verificar se está rodando
    $running = !empty(shell_exec('pgrep -f ngrok'));
    
    if ($running) {
        echo "✓ Ngrok está rodando com sucesso!\n";
        echo "✓ URL configurada: https://charlyn-unpaying-zara.ngrok-free.dev/tcc/\n";
        echo "✓ Acesse o painel admin para controlar o Ngrok\n";
    } else {
        echo "✗ Erro ao iniciar o Ngrok\n";
        echo "Verifique o log em: /tmp/ngrok_setup.log\n";
    }
    
    return $running;
}

// Executar se chamado diretamente
if (basename(__FILE__) == basename($_SERVER['SCRIPT_NAME'])) {
    echo "<pre>";
    echo "=== CONFIGURAÇÃO AUTOMÁTICA DO NGROK ===\n\n";
    setupNgrokComplete();
    echo "\n=== CONFIGURAÇÃO CONCLUÍDA ===";
    echo "</pre>";
}
?>