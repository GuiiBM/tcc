<?php
$homeDir = getenv('HOME') ?: '/home/' . get_current_user();
$configDir = $homeDir . '/.config/ngrok';
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

shell_exec('nohup ngrok start tcc > /tmp/ngrok.log 2>&1 &');
sleep(3);

$running = !empty(shell_exec('pgrep -f ngrok'));

echo "<h2>Ngrok Configurado</h2>";
echo "<p>Config: $configFile</p>";
echo "<p>Rodando: " . ($running ? "Sim" : "Não") . "</p>";
echo "<p>URL: https://charlyn-unpaying-zara.ngrok-free.dev/tcc/</p>";
?>