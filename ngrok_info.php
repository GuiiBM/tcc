<?php
header('Content-Type: application/json');

function getNgrokInfo() {
    $running = !empty(shell_exec('pgrep -f ngrok'));
    
    $info = [
        'running' => $running,
        'configured_url' => 'https://charlyn-unpaying-zara.ngrok-free.dev/tcc/',
        'status' => $running ? 'Ativo' : 'Inativo'
    ];
    
    if ($running) {
        // Tentar obter informações da API do ngrok
        $apiResponse = @file_get_contents('http://localhost:4040/api/tunnels');
        if ($apiResponse) {
            $data = json_decode($apiResponse, true);
            if (isset($data['tunnels']) && count($data['tunnels']) > 0) {
                foreach ($data['tunnels'] as $tunnel) {
                    if ($tunnel['proto'] === 'https') {
                        $info['actual_url'] = $tunnel['public_url'] . '/tcc/';
                        break;
                    }
                }
            }
        }
    }
    
    return $info;
}

echo json_encode(getNgrokInfo());
?>