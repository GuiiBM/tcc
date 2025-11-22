<?php
// Para todos os processos ngrok
shell_exec('pkill -f ngrok 2>/dev/null');
sleep(2);

// Cria configuração simples
$config = "version: \"3\"
agent:
    authtoken: 33Q3YmwuKMYt6YOPOKYlYXmUfJn_pxbGrjHBCRrz9zyzqLsj
tunnels:
    tcc:
        proto: http
        addr: 80
        domain: charlyn-unpaying-zara.ngrok-free.dev
";

$configDir = '/root/.config/ngrok';
if (!is_dir($configDir)) mkdir($configDir, 0755, true);
file_put_contents($configDir . '/ngrok.yml', $config);

// Inicia ngrok em background
$cmd = 'nohup ngrok start tcc > /tmp/ngrok_output.log 2>&1 & echo $!';
$pid = trim(shell_exec($cmd));

sleep(4);

// Verifica se está rodando
$running = !empty(shell_exec('pgrep -f ngrok'));

echo "<h2>Ngrok Status</h2>";
echo "<p>PID: $pid</p>";
echo "<p>Rodando: " . ($running ? "Sim" : "Não") . "</p>";

if (file_exists('/tmp/ngrok_output.log')) {
    echo "<h3>Log:</h3><pre>" . file_get_contents('/tmp/ngrok_output.log') . "</pre>";
}

// Testa a URL
echo "<h3>Teste de Conexão:</h3>";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://charlyn-unpaying-zara.ngrok-free.dev/tcc/');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<p>HTTP Code: $httpCode</p>";
if ($httpCode == 200) {
    echo "<p style='color: green;'>✓ URL funcionando!</p>";
} else {
    echo "<p style='color: red;'>✗ URL não está respondendo</p>";
}
?>