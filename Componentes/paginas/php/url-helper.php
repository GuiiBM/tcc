<?php
// url-helper.php - Helpers para URLs e caminhos que funcionam tanto no XAMPP
// local (site dentro de /tcc/) quanto em hospedagem real (site na raiz do
// domínio) sem precisar trocar nada no código na hora de publicar.

// Caminho absoluto da raiz do projeto, calculado a partir deste arquivo (que
// está sempre em Componentes/paginas/php/), não do arquivo que faz o include -
// assim funciona não importa de onde é chamado.
function getProjectRoot() {
    return rtrim(str_replace('\\', '/', dirname(__DIR__, 3)), '/');
}

// Caminho absoluto de uma pasta dentro de Componentes/Armazenamento/.
function getArmazenamentoPath($subpasta = '') {
    $subpasta = ltrim($subpasta, '/');
    return getProjectRoot() . '/Componentes/Armazenamento/' . ($subpasta !== '' ? $subpasta . '/' : '');
}

// Prefixo de URL onde o projeto está publicado: "/" quando está na raiz do
// domínio (hospedagem normal), ou "/subpasta/" quando está dentro de uma
// subpasta (ex: XAMPP local em htdocs/tcc).
function getBasePath() {
    $documentRoot = isset($_SERVER['DOCUMENT_ROOT']) ? realpath($_SERVER['DOCUMENT_ROOT']) : false;
    $projectRoot = getProjectRoot();

    if ($documentRoot === false) {
        return '/';
    }

    $documentRoot = rtrim(str_replace('\\', '/', $documentRoot), '/');

    if (strpos($projectRoot, $documentRoot) === 0) {
        $rel = substr($projectRoot, strlen($documentRoot));
        return $rel === '' ? '/' : rtrim($rel, '/') . '/';
    }

    return '/';
}

// Endereço completo do site (usado nos retornos do login do Facebook/Apple).
// Em hospedagens atrás de proxy o PHP pode não saber que a conexão é HTTPS;
// nesse caso defina SITE_URL no dbConfig.php, ex:
//   define('SITE_URL', 'https://ressonance.hyperphp.com/');
function getBaseUrl() {
    if (!defined('SITE_URL') && file_exists(__DIR__ . '/dbConfig.php')) {
        include_once __DIR__ . '/dbConfig.php';
    }
    if (defined('SITE_URL') && SITE_URL !== '') {
        return rtrim(SITE_URL, '/') . '/';
    }

    $forwardedProto = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '';
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $forwardedProto === 'https';
    $protocol = $isHttps ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

    return $protocol . '://' . $host . getBasePath();
}
?>
