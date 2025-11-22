<?php
$configDir = '/var/www/.config/ngrok';
$configFile = $configDir . '/ngrok.yml';

if (!is_dir($configDir)) {
    mkdir($configDir, 0755, true);
}

$config = "version: \"3\"
agent:
    authtoken: 33Q3YmwuKMYt6YOPOKYlYXmUfJn_pxbGrjHBCRrz9zyzqLsj
tunnels:
    tcc:
        proto: http
        addr: 80
        domain: charlyn-unpaying-zara.ngrok-free.dev
";

file_put_contents($configFile, $config);
chmod($configFile, 0600);

shell_exec('pkill -f ngrok 2>/dev/null');
sleep(2);

$output = shell_exec('HOME=/var/www nohup ngrok start tcc --config /var/www/.config/ngrok/ngrok.yml > /tmp/ngrok.log 2>&1 & echo $!');

sleep(3);

$running = !empty(shell_exec('pgrep -f ngrok'));

echo "<h2>Status do Ngrok</h2>";
echo "<p>Processo iniciado: " . ($output ? "Sim (PID: $output)" : "Não") . "</p>";
echo "<p>Rodando: " . ($running ? "Sim" : "Não") . "</p>";
echo "<p>URL: https://charlyn-unpaying-zara.ngrok-free.dev/tcc/</p>";

if (file_exists('/tmp/ngrok.log')) {
    echo "<h3>Log:</h3><pre>" . file_get_contents('/tmp/ngrok.log') . "</pre>";
}
?>