<?php
    $host = "localhost";
    $usuario = "root";
    $senha = "";
    $banco = "musicas";
    
    $conexao = mysqli_connect($host, $usuario, $senha, $banco);
    
    if (!$conexao) {
        error_log("Erro de conexão MySQL: " . mysqli_connect_error());
        die("Erro de conexão com o banco de dados");
    }
    
    mysqli_set_charset($conexao, "utf8");
?>