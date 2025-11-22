<?php
// Para ngrok
shell_exec('pkill -f ngrok 2>/dev/null');
sleep(2);

// Cria config no diretório correto
$homeDir = posix_getpwuid(posix_getuid())['dir'];
$configDir = $homeDir . '/.config/ngrok';
if (!is_dir($configDir)) mkdir($configDir, 0755, true);

$config = "version: \"3\"
agent:
    authtoken: 33Q3YmwuKMYt6YOPOKYlYXmUfJn_pxbGrjHBCRrz9zyzqLsj
tunnels:
    tcc:
        proto: http
        addr: 80
        domain: charlyn-unpaying-zara.ngrok-free.dev
";

file_put_contents($configDir . '/ngrok.yml', $config);

// Inicia com caminho explícito
$cmd = "nohup ngrok start tcc --config $configDir/ngrok.yml > /tmp/ngrok_new.log 2>&1 & echo $!";
$pid = trim(shell_exec($cmd));

sleep(4);

echo "<h2>Ngrok Corrigido</h2>";
echo "<p>Config Dir: $configDir</p>";
echo "<p>PID: $pid</p>";
echo "<p>Rodando: " . (!empty(shell_exec('pgrep -f ngrok')) ? "Sim" : "Não") . "</p>";

if (file_exists('/tmp/ngrok_new.log')) {
    echo "<h3>Log:</h3><pre>" . file_get_contents('/tmp/ngrok_new.log') . "</pre>";
}
?>