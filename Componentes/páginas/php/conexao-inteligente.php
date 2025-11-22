<?php
// conexao-inteligente.php - Conexão MySQL que funciona com Ngrok e Local
function conectarMySQL() {
    $isNgrok = (isset($_SERVER['HTTP_HOST']) && 
                strpos($_SERVER['HTTP_HOST'], 'ngrok') !== false);
    
    if ($isNgrok) {
        // Configuração Ngrok
        $host = "127.0.0.1";
        $usuario = "usuario_ngrok";
        $senha = "senha_ngrok_123";
    } else {
        // Configuração Local XAMPP
        $host = "localhost";
        $usuario = "root";
        $senha = "";
    }
    
    $banco = "musicas";
    $conexao = mysqli_connect($host, $usuario, $senha, $banco);
    
    if (!$conexao) {
        error_log("Erro MySQL (" . ($isNgrok ? 'NGROK' : 'LOCAL') . "): " . mysqli_connect_error());
        die("Erro de conexão com o banco de dados");
    }
    
    mysqli_set_charset($conexao, "utf8");
    return $conexao;
}

// Função para verificar se está rodando no ngrok
function isRunningOnNgrok() {
    return (isset($_SERVER['HTTP_HOST']) && 
            strpos($_SERVER['HTTP_HOST'], 'ngrok') !== false);
}

// Função para obter a URL base correta
function getBaseUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    return $protocol . '://' . $_SERVER['HTTP_HOST'];
}
?>