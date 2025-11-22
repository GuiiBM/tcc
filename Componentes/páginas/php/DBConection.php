<?php
    // Detectar se está rodando no ngrok
    $isNgrok = (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'ngrok') !== false);
    
    if ($isNgrok) {
        // Configuração Ngrok - usar root temporariamente
        $host = "localhost";
        $usuario = "root";
        $senha = "";
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
?>