<?php
header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'status':
        $running = !empty(exec('pgrep -f ngrok'));
        $url = null;
        
        if ($running) {
            $response = @file_get_contents('http://localhost:4040/api/tunnels');
            if ($response) {
                $data = json_decode($response, true);
                foreach ($data['tunnels'] ?? [] as $tunnel) {
                    if ($tunnel['proto'] === 'https') {
                        $url = $tunnel['public_url'];
                        break;
                    }
                }
            }
        }
        
        echo json_encode(['running' => $running, 'url' => $url]);
        break;
        
    case 'start':
        exec('pkill -f ngrok');
        sleep(1);
        
        // Usar configuração do usuário atual
        exec('ngrok http 80 > /dev/null 2>&1 &');
        sleep(3);
        
        $url = null;
        for ($i = 0; $i < 5; $i++) {
            $response = @file_get_contents('http://localhost:4040/api/tunnels');
            if ($response) {
                $data = json_decode($response, true);
                foreach ($data['tunnels'] ?? [] as $tunnel) {
                    if ($tunnel['proto'] === 'https') {
                        $url = $tunnel['public_url'];
                        break 2;
                    }
                }
            }
            sleep(2);
        }
        
        if ($url) {
            echo json_encode(['success' => true, 'message' => 'Ngrok iniciado', 'url' => $url]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Falha ao obter URL']);
        }
        break;
        
    case 'stop':
        exec('pkill -f ngrok');
        echo json_encode(['success' => true, 'message' => 'Ngrok parado']);
        break;
        
    default:
        echo json_encode(['error' => 'Ação inválida']);
}
?>