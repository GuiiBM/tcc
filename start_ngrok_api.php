<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Mata processos ngrok existentes
    exec('pkill -f ngrok', $output, $return);
    sleep(1);
    
    // Comando para iniciar ngrok
    $cmd = 'nohup /usr/local/bin/ngrok http 80 </dev/null >/dev/null 2>&1 & echo $!';
    $pid = exec($cmd);
    
    if ($pid) {
        sleep(3);
        
        // Verifica se o processo ainda está rodando
        $check = exec("ps -p $pid -o pid=");
        
        if ($check) {
            // Tenta obter URL por até 10 segundos
            for ($i = 0; $i < 10; $i++) {
                $response = @file_get_contents('http://localhost:4040/api/tunnels');
                
                if ($response) {
                    $data = json_decode($response, true);
                    if (isset($data['tunnels']) && count($data['tunnels']) > 0) {
                        foreach ($data['tunnels'] as $tunnel) {
                            if ($tunnel['proto'] === 'https') {
                                echo json_encode(['success' => true, 'url' => $tunnel['public_url']]);
                                exit;
                            }
                        }
                    }
                }
                sleep(1);
            }
            
            echo json_encode(['success' => true, 'message' => 'Ngrok iniciado - acesse http://localhost:4040']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Processo ngrok não iniciou']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Falha ao executar ngrok']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
}
?>