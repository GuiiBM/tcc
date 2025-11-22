<?php
// ngrok-manager.php - Gerenciador de Ngrok para o painel admin
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
include "Componentes/páginas/php/verificar_login.php";
redirecionarSeNaoAdmin();

header('Content-Type: application/json; charset=utf-8');

function executarComando($comando) {
    return shell_exec($comando . ' 2>&1');
}

function isNgrokRunning() {
    $output = executarComando('ps aux | grep ngrok');
    return strpos($output, 'ngrok http') !== false;
}

function getNgrokUrl() {
    $response = @file_get_contents('http://localhost:4040/api/tunnels');
    
    if ($response) {
        $data = json_decode($response, true);
        if (isset($data['tunnels']) && count($data['tunnels']) > 0) {
            foreach ($data['tunnels'] as $tunnel) {
                if ($tunnel['proto'] === 'https') {
                    return $tunnel['public_url'];
                }
            }
        }
    }
    return null;
}



function iniciarNgrok() {
    // Garantir configuração
    $configDir = '/var/www/.config/ngrok';
    $configFile = $configDir . '/ngrok.yml';
    
    if (!is_dir($configDir)) {
        mkdir($configDir, 0755, true);
    }
    
    if (!file_exists($configFile)) {
        $config = "version: \"3\"\nagent:\n    authtoken: 33Q3YmwuKMYt6YOPOKYlYXmUfJn_pxbGrjHBCRrz9zyzqLsj\n";
        file_put_contents($configFile, $config);
        chmod($configFile, 0600);
    }
    
    $command = 'HOME=/var/www nohup ngrok http 80 > /tmp/ngrok.log 2>&1 &';
    executarComando($command);
    
    for ($i = 0; $i < 10; $i++) {
        sleep(2);
        $url = getNgrokUrl();
        if ($url) return $url;
    }
    return null;
}

function pararNgrok() {
    executarComando('pkill -f ngrok');
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'status':
        $running = isNgrokRunning();
        $url = $running ? getNgrokUrl() : null;
        echo json_encode([
            'running' => $running,
            'url' => $url,
            'status' => $running ? 'Ngrok está rodando' : 'Ngrok parado'
        ]);
        break;
        
    case 'start':
        if (isNgrokRunning()) {
            $url = getNgrokUrl();
            echo json_encode([
                'success' => true,
                'message' => 'Ngrok já está rodando',
                'url' => $url
            ]);
        } else {
            $url = iniciarNgrok();
            if ($url) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Ngrok iniciado com sucesso',
                    'url' => $url
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Erro ao iniciar ngrok'
                ]);
            }
        }
        break;
        
    case 'stop':
        pararNgrok();
        echo json_encode([
            'success' => true,
            'message' => 'Ngrok parado e configuração local restaurada'
        ]);
        break;
        
    default:
        echo json_encode(['error' => 'Ação inválida']);
}
?>