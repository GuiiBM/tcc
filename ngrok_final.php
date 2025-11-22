<?php
header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

function setupNgrokConfig() {
    $config = "version: \"3\"\nagent:\n    authtoken: 33Q3YmwuKMYt6YOPOKYlYXmUfJn_pxbGrjHBCRrz9zyzqLsj\ntunnels:\n    tcc:\n        proto: http\n        addr: 80\n        domain: charlyn-unpaying-zara.ngrok-free.dev\n";
    file_put_contents('/tmp/ngrok.yml', $config);
}

switch ($action) {
    case 'status':
        $running = !empty(shell_exec('pgrep -f ngrok'));
        echo json_encode(['running' => $running]);
        break;
        
    case 'start':
        setupNgrokConfig();
        shell_exec('pkill -f ngrok');
        sleep(1);
        
        shell_exec('nohup ngrok start tcc --config /tmp/ngrok.yml > /tmp/ngrok.log 2>&1 &');
        sleep(3);
        
        $running = !empty(shell_exec('pgrep -f ngrok'));
        if ($running) {
            echo json_encode([
                'success' => true, 
                'message' => 'Ngrok iniciado com domínio fixo!', 
                'url' => 'https://charlyn-unpaying-zara.ngrok-free.dev/tcc/'
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Falha ao iniciar Ngrok']);
        }
        break;
        
    case 'stop':
        shell_exec('pkill -f ngrok');
        echo json_encode(['success' => true, 'message' => 'Ngrok parado']);
        break;
        
    default:
        echo json_encode(['error' => 'Ação inválida']);
}
?>