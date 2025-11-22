<?php
echo "<h2>Teste Ngrok</h2>";

// 1. Verificar se ngrok existe
echo "<h3>1. Verificando instalação:</h3>";
$ngrokPath = exec('which ngrok');
echo "Caminho: " . ($ngrokPath ?: "NÃO ENCONTRADO") . "<br>";

// 2. Verificar versão
echo "<h3>2. Versão:</h3>";
$version = exec('ngrok version 2>&1');
echo $version . "<br>";

// 3. Verificar processos rodando
echo "<h3>3. Processos ngrok:</h3>";
exec('ps aux | grep ngrok | grep -v grep', $processes);
if (empty($processes)) {
    echo "Nenhum processo ngrok rodando<br>";
} else {
    foreach ($processes as $process) {
        echo $process . "<br>";
    }
}

// 4. Tentar iniciar manualmente
echo "<h3>4. Tentando iniciar:</h3>";
$output = [];
$return = 0;
exec('timeout 3s ngrok http 80 2>&1', $output, $return);
echo "Código retorno: $return<br>";
echo "Saída:<br>";
foreach ($output as $line) {
    echo htmlspecialchars($line) . "<br>";
}

// 5. Verificar porta 80
echo "<h3>5. Porta 80:</h3>";
exec('netstat -tln | grep :80', $portCheck);
if (empty($portCheck)) {
    echo "Porta 80 não está em uso<br>";
} else {
    foreach ($portCheck as $port) {
        echo $port . "<br>";
    }
}

// 6. Verificar configuração ngrok
echo "<h3>6. Configuração:</h3>";
$configCheck = exec('ngrok config check 2>&1');
echo $configCheck . "<br>";
?>