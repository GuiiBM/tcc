<?php
echo "<h2>Corrigindo Ngrok</h2>";

// Copiar configuração para o usuário www-data
$homeDir = '/var/www';
$configDir = $homeDir . '/.config/ngrok';
$configFile = $configDir . '/ngrok.yml';

// Criar diretório
if (!is_dir($configDir)) {
    mkdir($configDir, 0755, true);
    echo "Diretório criado: $configDir<br>";
}

// Copiar configuração
$config = "version: \"3\"\nagent:\n    authtoken: 33Q3YmwuKMYt6YOPOKYlYXmUfJn_pxbGrjHBCRrz9zyzqLsj\n";
file_put_contents($configFile, $config);
chmod($configFile, 0600);
echo "Configuração copiada<br>";

// Testar ngrok
echo "<h3>Testando ngrok:</h3>";
$output = [];
exec('HOME=/var/www ngrok version 2>&1', $output);
foreach ($output as $line) {
    echo htmlspecialchars($line) . "<br>";
}

// Tentar iniciar
echo "<h3>Iniciando ngrok:</h3>";
exec('HOME=/var/www nohup ngrok http 80 > /tmp/ngrok.log 2>&1 & echo $!', $pidOutput);
$pid = trim($pidOutput[0]);
echo "PID: $pid<br>";

sleep(3);

// Verificar se está rodando
exec("ps -p $pid", $check);
if (count($check) > 1) {
    echo "✓ Ngrok rodando!<br>";
    echo "<a href='http://localhost:4040' target='_blank'>Abrir painel ngrok</a>";
} else {
    echo "✗ Falha ao iniciar<br>";
    echo "Log: " . file_get_contents('/tmp/ngrok.log');
}
?>