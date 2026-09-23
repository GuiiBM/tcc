<?php
// stream.php - Entrega o áudio a partir de um link temporário assinado.
//
// stream.php?t=m|e&id=N&q=a|b&e=EXPIRA&s=ASSINATURA
//   t: m = música, e = episódio; q: a = original, b = versão compacta
// O link vale por VALIDADE_LINK_AUDIO segundos e só na sessão que o recebeu
// (a assinatura inclui o ID da sessão). Aceita pedidos por partes (Range),
// então o player baixa aos poucos e pode pular para qualquer ponto.
// A pasta de áudios é bloqueada para acesso direto (.htaccess).
require_once __DIR__ . '/Componentes/paginas/php/seguranca.php';

function negar($codigo, $motivo) {
    http_response_code($codigo);
    header('Content-Type: text/plain; charset=utf-8');
    header('Cache-Control: no-store');
    exit($motivo);
}

iniciarSessaoSegura();
$sessao = session_id();
// Libera a sessão logo: se ficasse aberta durante o envio do áudio, todas as
// outras requisições do mesmo usuário esperariam a música terminar.
session_write_close();

$tipo = $_GET['t'] ?? '';
$id = (int) ($_GET['id'] ?? 0);
$qualidade = $_GET['q'] ?? 'a';
$expira = (int) ($_GET['e'] ?? 0);
$assinatura = (string) ($_GET['s'] ?? '');

if (!in_array($tipo, ['m', 'e'], true) || $id <= 0 || !in_array($qualidade, ['a', 'b'], true)) {
    negar(400, 'Link inválido');
}
if ($expira < time()) {
    negar(403, 'Link expirado');
}
include __DIR__ . '/Componentes/paginas/php/DBConection.php';
if (!hash_equals(assinaturaAudio($tipo, $id, $qualidade, $expira, $sessao), $assinatura)) {
    negar(403, 'Link inválido para esta sessão');
}

// Anti-hotlink: o pedido precisa vir de uma página deste mesmo site.
$origem = $_SERVER['HTTP_SEC_FETCH_SITE'] ?? '';
if ($origem !== '' && !in_array($origem, ['same-origin', 'none'], true)) {
    negar(403, 'Acesso externo não permitido');
}
$referer = $_SERVER['HTTP_REFERER'] ?? '';
if ($referer !== '' && parse_url($referer, PHP_URL_HOST) !== parse_url('//' . ($_SERVER['HTTP_HOST'] ?? ''), PHP_URL_HOST)) {
    negar(403, 'Acesso externo não permitido');
}

if ($tipo === 'm') {
    $stmt = mysqli_prepare($conexao, "SELECT musica_link AS a, musica_link_baixa AS b FROM musica WHERE musica_id = ?");
} else {
    $stmt = mysqli_prepare($conexao, "SELECT episodio_audio AS a, NULL AS b FROM episodio WHERE episodio_id = ?");
}
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$linha = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_close($conexao); // não segura uma conexão do MySQL durante o envio

$caminho = $linha[$qualidade] ?? null;
if (!$caminho) {
    negar(404, 'Áudio não encontrado');
}
$base = realpath(__DIR__ . '/Componentes/Armazenamento');
$arquivo = realpath(__DIR__ . '/' . $caminho);
if (!$arquivo || !$base || strpos($arquivo, $base . DIRECTORY_SEPARATOR) !== 0 || !is_file($arquivo)) {
    negar(404, 'Áudio não encontrado');
}

$tipos = ['mp3' => 'audio/mpeg', 'm4a' => 'audio/mp4', 'ogg' => 'audio/ogg', 'wav' => 'audio/wav', 'flac' => 'audio/flac'];
$extensao = strtolower(pathinfo($arquivo, PATHINFO_EXTENSION));
$tamanho = filesize($arquivo);
$inicio = 0;
$fim = $tamanho - 1;

if (isset($_SERVER['HTTP_RANGE'])) {
    if (!preg_match('/^bytes=(\d*)-(\d*)$/', trim($_SERVER['HTTP_RANGE']), $m) || ($m[1] === '' && $m[2] === '')) {
        header("Content-Range: bytes */$tamanho");
        negar(416, 'Intervalo inválido');
    }
    if ($m[1] === '') {           // últimos N bytes
        $inicio = max(0, $tamanho - (int) $m[2]);
    } else {
        $inicio = (int) $m[1];
        $fim = $m[2] === '' ? $fim : min($fim, (int) $m[2]);
    }
    if ($inicio > $fim || $inicio >= $tamanho) {
        header("Content-Range: bytes */$tamanho");
        negar(416, 'Intervalo inválido');
    }
    http_response_code(206);
    header("Content-Range: bytes $inicio-$fim/$tamanho");
}

while (ob_get_level()) {
    ob_end_clean();
}
@set_time_limit(0);
header('Content-Type: ' . ($tipos[$extensao] ?? 'application/octet-stream'));
header('Content-Length: ' . ($fim - $inicio + 1));
header('Accept-Ranges: bytes');
header('Content-Disposition: inline');
header('Cache-Control: private, max-age=' . VALIDADE_LINK_AUDIO);
header('X-Content-Type-Options: nosniff');

if ($_SERVER['REQUEST_METHOD'] === 'HEAD') {
    exit;
}
$f = fopen($arquivo, 'rb');
fseek($f, $inicio);
$restante = $fim - $inicio + 1;
while ($restante > 0 && !feof($f) && !connection_aborted()) {
    $bloco = fread($f, min(65536, $restante));
    if ($bloco === false) {
        break;
    }
    echo $bloco;
    flush();
    $restante -= strlen($bloco);
}
fclose($f);
