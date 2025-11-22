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
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'http://localhost:4040/api/tunnels');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    $response = curl_exec($ch);
    curl_close($ch);
    
    if ($response) {
        $data = json_decode($response, true);
        if (isset($data['tunnels'][0]['public_url'])) {
            return $data['tunnels'][0]['public_url'];
        }
    }
    return null;
}

function configurarMySQLParaNgrok() {
    $commands = [
        'sudo sed -i "s/bind-address.*/bind-address = 0.0.0.0/" /opt/lampp/etc/my.cnf',
        'sudo /opt/lampp/lampp restartmysql',
        'sudo /opt/lampp/bin/mysql -u root -e "CREATE USER IF NOT EXISTS \'usuario_ngrok\'@\'%\' IDENTIFIED BY \'senha_ngrok_123\';" 2>/dev/null',
        'sudo /opt/lampp/bin/mysql -u root -e "GRANT ALL PRIVILEGES ON musicas.* TO \'usuario_ngrok\'@\'%\'; FLUSH PRIVILEGES;" 2>/dev/null'
    ];
    
    foreach ($commands as $cmd) {
        executarComando($cmd);
        usleep(500000);
    }
}

function iniciarNgrok() {
    configurarMySQLParaNgrok();
    
    $command = 'nohup ngrok http 80 --host-header=rewrite > /tmp/ngrok.log 2>&1 &';
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
    executarComando('sudo sed -i "s/bind-address.*/bind-address = 127.0.0.1/" /opt/lampp/etc/my.cnf');
    executarComando('sudo /opt/lampp/lampp restartmysql');
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