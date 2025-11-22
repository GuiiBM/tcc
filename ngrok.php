<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Mata processos existentes
    exec('pkill -f ngrok');
    sleep(1);
    
    // Inicia ngrok
    $output = [];
    $return = 0;
    exec('ngrok http 80 > /tmp/ngrok.log 2>&1 & echo $!', $output, $return);
    
    if (!empty($output)) {
        $pid = trim($output[0]);
        sleep(3);
        
        // Verifica se está rodando
        exec("ps -p $pid", $check);
        
        if (count($check) > 1) {
            echo json_encode(['success' => true, 'message' => 'Ngrok iniciado! Acesse http://localhost:4040']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Falha ao iniciar']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro no comando']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'POST apenas']);
}
?>