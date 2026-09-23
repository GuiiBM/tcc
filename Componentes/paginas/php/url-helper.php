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

function getBaseUrl() {
    $forwardedProto = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '';
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $forwardedProto === 'https';
    $protocol = $isHttps ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

    return $protocol . '://' . $host . getBasePath();
}
?>
