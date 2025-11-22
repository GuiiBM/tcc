<?php
header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

function isNgrokRunning() {
    return !empty(shell_exec('pgrep -f ngrok'));
}

function startNgrok() {
    $config = "version: \"3\"\nagent:\n    authtoken: 33Q3YmwuKMYt6YOPOKYlYXmUfJn_pxbGrjHBCRrz9zyzqLsj\ntunnels:\n    tcc:\n        proto: http\n        addr: 80\n        domain: charlyn-unpaying-zara.ngrok-free.dev\n";
    file_put_contents('/tmp/ngrok.yml', $config);
    
    shell_exec('pkill -f ngrok 2>/dev/null; sleep 1');
    shell_exec('nohup ngrok start tcc --config /tmp/ngrok.yml > /dev/null 2>&1 &');
    sleep(2);
    return isNgrokRunning();
}

function stopNgrok() {
    shell_exec('pkill -f ngrok');
    sleep(1);
    return !isNgrokRunning();
}

switch ($action) {
    case 'status':
        echo json_encode(['running' => isNgrokRunning()]);
        break;
        
    case 'toggle':
        $running = isNgrokRunning();
        
        if ($running) {
            $success = stopNgrok();
            echo json_encode([
                'success' => $success,
                'running' => false,
                'message' => $success ? 'Ngrok parado' : 'Erro ao parar Ngrok'
            ]);
        } else {
            $success = startNgrok();
            echo json_encode([
                'success' => $success,
                'running' => $success,
                'message' => $success ? 'Ngrok iniciado' : 'Erro ao iniciar Ngrok',
                'url' => $success ? 'https://charlyn-unpaying-zara.ngrok-free.dev' : null
            ]);
        }
        break;
        
    default:
        echo json_encode(['error' => 'Ação inválida']);
}
?>