<?php
shell_exec('pkill -f ngrok 2>/dev/null');
sleep(2);

// Criar config no diretório atual
$configFile = '/tmp/ngrok.yml';
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

$cmd = "nohup ngrok start tcc --config $configFile > /tmp/ngrok_final.log 2>&1 & echo $!";
$pid = trim(shell_exec($cmd));

sleep(4);

echo "<h2>Ngrok Final</h2>";
echo "<p>Config: $configFile</p>";
echo "<p>PID: $pid</p>";
echo "<p>Rodando: " . (!empty(shell_exec('pgrep -f ngrok')) ? "Sim" : "Não") . "</p>";

if (file_exists('/tmp/ngrok_final.log')) {
    echo "<h3>Log:</h3><pre>" . file_get_contents('/tmp/ngrok_final.log') . "</pre>";
}

// Teste da URL
sleep(2);
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://charlyn-unpaying-zara.ngrok-free.dev/tcc/');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 5);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<h3>Teste URL:</h3>";
echo "<p>HTTP: $httpCode " . ($httpCode == 200 ? "✓" : "✗") . "</p>";
?>