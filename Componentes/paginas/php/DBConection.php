<?php
    // Em produção, crie Componentes/páginas/php/dbConfig.php (ignorado pelo
    // git, assim como clientId.php/clientSecret.php) definindo DB_HOST,
    // DB_USER, DB_PASS e DB_NAME com as credenciais dadas pela hospedagem.
    // Sem esse arquivo, usa os valores padrão do XAMPP local.
    if (file_exists(__DIR__ . '/dbConfig.php')) {
        include_once __DIR__ . '/dbConfig.php';
    }

    $host = defined('DB_HOST') ? DB_HOST : "localhost";
    $usuario = defined('DB_USER') ? DB_USER : "root";
    $senha = defined('DB_PASS') ? DB_PASS : "";
    $banco = defined('DB_NAME') ? DB_NAME : "musicas";
    $conexao = mysqli_connect($host, $usuario, $senha, $banco);

    if (!$conexao) {
        error_log("Erro MySQL: " . mysqli_connect_error());
        die("Erro de conexão com o banco de dados");
    }

    mysqli_set_charset($conexao, "utf8");
?>