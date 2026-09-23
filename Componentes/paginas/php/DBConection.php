<?php
    // O arquivo é incluído por várias páginas e componentes na mesma
    // requisição; reaproveita a conexão já aberta em vez de abrir outra.
    require_once __DIR__ . '/seguranca.php';
    if (isset($conexao) && $conexao instanceof mysqli) {
        return;
    }

    // Credenciais do banco, em ordem de prioridade:
    // 1) Componentes/paginas/php/dbConfig.php (ignorado pelo git) - é o que
    //    se usa no InfinityFree e em hospedagens PHP comuns;
    // 2) Variáveis de ambiente DB_HOST, DB_PORT, DB_USER, DB_PASS, DB_NAME;
    // 3) Valores padrão do XAMPP local.
    if (file_exists(__DIR__ . '/dbConfig.php')) {
        include_once __DIR__ . '/dbConfig.php';
    }

    $host = defined('DB_HOST') ? DB_HOST : (getenv('DB_HOST') ?: "localhost");
    $porta = (int) (defined('DB_PORT') ? DB_PORT : (getenv('DB_PORT') ?: 3306));
    $usuario = defined('DB_USER') ? DB_USER : (getenv('DB_USER') ?: "root");
    $senha = defined('DB_PASS') ? DB_PASS : (getenv('DB_PASS') !== false ? getenv('DB_PASS') : "");
    $banco = defined('DB_NAME') ? DB_NAME : (getenv('DB_NAME') ?: "musicas");

    // O código do projeto trata erros pelo retorno (false) das funções
    // mysqli; a partir do PHP 8.1 o padrão virou lançar exceções, o que
    // transformaria erros esperados (ex: e-mail duplicado) em erro fatal.
    mysqli_report(MYSQLI_REPORT_OFF);

    $conexao = @mysqli_connect($host, $usuario, $senha, $banco, $porta);

    if (!$conexao) {
        error_log("Erro MySQL: " . mysqli_connect_error());
        http_response_code(500);
        die("Erro de conexão com o banco de dados");
    }

    mysqli_set_charset($conexao, "utf8mb4");

    include_once __DIR__ . '/migracoes.php';
    garantirEsquema($conexao);
?>
