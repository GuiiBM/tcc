<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Script para iniciar ngrok
    $script = '#!/bin/bash
pkill -f ngrok
sleep 1
ngrok http 80 &
';
    
    file_put_contents('/tmp/start_ngrok.sh', $script);
    chmod('/tmp/start_ngrok.sh', 0755);
    
    // Executa o script
    exec('/tmp/start_ngrok.sh > /dev/null 2>&1 &');
    
    sleep(2);
    
    // Verifica se ngrok está rodando
    $check = exec('pgrep -f ngrok');
    
    if ($check) {
        echo json_encode(['success' => true, 'message' => 'Ngrok iniciado! Acesse http://localhost:4040 para ver a URL']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Ngrok não iniciou. Verifique se está instalado.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
}
?>