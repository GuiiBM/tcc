<?php
echo "Status Ngrok: ";

// Verificar se está rodando
$running = exec('pgrep -f ngrok');
if ($running) {
    echo "✓ Rodando (PID: $running)<br>";
    
    // Tentar obter URL
    $tunnels = @file_get_contents('http://localhost:4040/api/tunnels');
    if ($tunnels) {
        $data = json_decode($tunnels, true);
        if (isset($data['tunnels']) && count($data['tunnels']) > 0) {
            foreach ($data['tunnels'] as $tunnel) {
                if ($tunnel['proto'] === 'https') {
                    echo "URL: <a href='{$tunnel['public_url']}' target='_blank'>{$tunnel['public_url']}</a><br>";
                    break;
                }
            }
        }
    }
} else {
    echo "✗ Não está rodando<br>";
}
?>