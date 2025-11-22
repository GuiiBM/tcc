<?php
// Script para auto-inicialização do Ngrok com domínio fixo
function autoStartNgrok() {
    if (empty(shell_exec('pgrep -f ngrok'))) {
        $config = "version: \"3\"\nagent:\n    authtoken: 33Q3YmwuKMYt6YOPOKYlYXmUfJn_pxbGrjHBCRrz9zyzqLsj\ntunnels:\n    tcc:\n        proto: http\n        addr: 80\n        domain: charlyn-unpaying-zara.ngrok-free.dev\n";
        
        file_put_contents('/tmp/ngrok.yml', $config);
        shell_exec('pkill -f ngrok 2>/dev/null; sleep 1');
        shell_exec('nohup ngrok start tcc --config /tmp/ngrok.yml > /dev/null 2>&1 &');
        return true;
    }
    return false;
}

// Executar auto-start
autoStartNgrok();
?>