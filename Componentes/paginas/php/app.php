<?php
require_once __DIR__ . '/seguranca.php';
// app.php - Funções compartilhadas pelas páginas novas e pela API:
// sessão/usuário atual, consultas de faixas, renderização de listas e
// cartões, ícones e utilitários de resposta JSON.

date_default_timezone_set('America/Sao_Paulo');

iniciarSessaoSegura();

include_once __DIR__ . '/url-helper.php';
include_once __DIR__ . '/DBConection.php';

// ===================== Utilitários gerais =====================

function e($texto) {
    return htmlspecialchars((string) $texto, ENT_QUOTES, 'UTF-8');
}

// URL de um arquivo estático com versão = data de modificação, para o
// navegador guardar em cache e só baixar de novo quando o arquivo mudar.
function asset($caminho) {
    $arquivo = getProjectRoot() . '/' . $caminho;
    $versao = file_exists($arquivo) ? filemtime($arquivo) : 1;
    return $caminho . '?v=' . $versao;
}

function formatarDuracao($segundos) {
    $segundos = (int) $segundos;
    if ($segundos <= 0) {
        return '--:--';
    }
    $h = intdiv($segundos, 3600);
    $m = intdiv($segundos % 3600, 60);
    $s = $segundos % 60;
    return $h > 0 ? sprintf('%d:%02d:%02d', $h, $m, $s) : sprintf('%d:%02d', $m, $s);
}

// "1 h 12 min", "34 min 5 s" - usado nas estatísticas de álbum/playlist.
function formatarDuracaoTotal($segundos) {
    $segundos = (int) $segundos;
    $h = intdiv($segundos, 3600);
    $m = intdiv($segundos % 3600, 60);
    $s = $segundos % 60;
    if ($h > 0) {
        return "$h h $m min";
    }
    return $m > 0 ? "$m min $s s" : "$s s";
}

function pluralizar($n, $singular, $plural) {
    return $n . ' ' . ($n == 1 ? $singular : $plural);
}

function imagemOuPadrao($caminho, $padrao = 'Componentes/icones/icone.png') {
    return $caminho ? $caminho : $padrao;
}

// Enquadramento escolhido no editor de foto, no formato "x% y%" (0 a 100).
// Qualquer valor inválido vira o centro.
function posicaoImagem($posicao) {
    if (!preg_match('/^(\d{1,3}(?:\.\d+)?)% (\d{1,3}(?:\.\d+)?)%$/', trim((string) $posicao), $m)) {
        return '50% 50%';
    }
    return round(min(100, (float) $m[1]), 1) . '% ' . round(min(100, (float) $m[2]), 1) . '%';
}

// Atributo style com o enquadramento (vazio quando é o centro).
function estiloPosicao($posicao, $propriedade = 'object-position') {
    $p = posicaoImagem($posicao);
    return $p === '50% 50%' ? '' : ' style="' . $propriedade . ': ' . $p . '"';
}

function icone($nome, $classe = '') {
    $classe = $classe ? ' ' . $classe : '';
    return '<svg class="icon' . $classe . '" aria-hidden="true"><use href="#i-' . e($nome) . '"></use></svg>';
}

// ===================== Usuário atual =====================

function usuarioLogado() {
    return isset($_SESSION['usuario_id']);
}

function ehAdmin() {
    return isset($_SESSION['usuario_tipo']) && $_SESSION['usuario_tipo'] === 'admin';
}

function usuarioAtual() {
    static $usuario = false;
    global $conexao;
    if ($usuario !== false) {
        return $usuario;
    }
    $usuario = null;
    if (!usuarioLogado()) {
        return null;
    }
    $stmt = mysqli_prepare($conexao, "SELECT usuario_id, usuario_nome, usuario_email, usuario_foto, usuario_foto_pos, usuario_tipo, usuario_idade, usuario_cidade, usuario_descricao, usuario_qualidade, artista_id FROM usuarios WHERE usuario_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $_SESSION['usuario_id']);
    mysqli_stmt_execute($stmt);
    $usuario = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt)) ?: null;
    return $usuario;
}

// Admin ou o usuário dono do perfil de artista pode editar o artista e as
// músicas/álbuns dele.
function podeEditarArtista($artistaId) {
    if (ehAdmin()) {
        return true;
    }
    $usuario = usuarioAtual();
    return $usuario && $usuario['artista_id'] && (int) $usuario['artista_id'] === (int) $artistaId;
}

// ===================== Respostas da API =====================

function jsonResposta($dados, $status = 200) {
    enviarCabecalhosSeguranca(false, true);
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($dados, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function jsonErro($mensagem, $status = 400) {
    jsonResposta(['success' => false, 'message' => $mensagem], $status);
}

function exigirLoginApi() {
    if (!usuarioLogado()) {
        jsonErro('Faça login para continuar', 401);
    }
}

// Todas as ações que alteram dados exigem POST com o cabeçalho
// X-Requested-With enviado pelo nosso JavaScript. Um site de terceiros não
// consegue mandar esse cabeçalho para cá (o navegador bloqueia), o que
// protege contra CSRF.
function exigirPostApi() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonErro('Método não permitido', 405);
    }
    if (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') !== 'fetch') {
        jsonErro('Requisição inválida', 403);
    }
}

// Lê o corpo da requisição tanto como JSON quanto como formulário.
function dadosRequisicao() {
    static $dados = null;
    if ($dados !== null) {
        return $dados;
    }
    $tipo = $_SERVER['CONTENT_TYPE'] ?? '';
    if (stripos($tipo, 'application/json') !== false) {
        $dados = json_decode(file_get_contents('php://input'), true) ?: [];
    } else {
        $dados = $_POST;
    }
    return $dados;
}

// ===================== Uploads =====================

// Salva um upload validando o tipo real do arquivo. Retorna o caminho
// relativo (para gravar no banco) ou lança Exception com a mensagem de erro.
function salvarUploadValidado($arquivo, $tipo) {
    include_once __DIR__ . '/processarUpload.php';

    if (!isset($arquivo) || !is_array($arquivo) || $arquivo['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($arquivo['error'] === UPLOAD_ERR_OK && $tipo === 'imagem') {
        $mime = function_exists('mime_content_type') ? mime_content_type($arquivo['tmp_name']) : '';
        if ($mime && strpos($mime, 'image/') !== 0) {
            throw new Exception('O arquivo enviado não é uma imagem válida');
        }
    }
    $resultado = processarUpload($arquivo, $tipo);
    if (isset($resultado['erro'])) {
        throw new Exception($resultado['erro']);
    }
    return $resultado['caminho'];
}

// Remove um arquivo enviado anteriormente (só dentro de Armazenamento).
function removerArquivoArmazenado($caminho) {
    if (!$caminho || strpos($caminho, 'Componentes/Armazenamento/') !== 0 || strpos($caminho, '..') !== false) {
        return;
    }
    $arquivo = getProjectRoot() . '/' . $caminho;
    if (is_file($arquivo)) {
        @unlink($arquivo);
    }
}

// Limite de upload do servidor em bytes (o InfinityFree limita a 10 MB).
function limiteUploadBytes() {
    $converter = function ($valor) {
        $valor = trim((string) $valor);
        if ($valor === '') {
            return PHP_INT_MAX;
        }
        $numero = (float) $valor;
        switch (strtolower(substr($valor, -1))) {
            case 'g': $numero *= 1024;
            case 'm': $numero *= 1024;
            case 'k': $numero *= 1024;
        }
        return (int) $numero;
    };
    $limite = min($converter(ini_get('upload_max_filesize')), $converter(ini_get('post_max_size')));
    return $limite > 0 ? $limite : 10 * 1024 * 1024;
}

// ===================== Faixas =====================

define('SQL_FAIXA_CAMPOS', "m.musica_id, m.musica_titulo, m.musica_capa, m.musica_link, m.musica_link_baixa,
    m.musica_duracao, m.musica_faixa, m.musica_data_adicao, m.album_id, al.album_titulo,
    a.artista_id, a.artista_nome, a.artista_cidade,
    (m.musica_letra IS NOT NULL AND m.musica_letra <> '') AS tem_letra");

// Artistas que aparecem nas listagens públicas: os que têm músicas ou que
// foram cadastrados pelo admin (sem conta de usuário). Assim, contas de
// ouvintes que ainda não publicaram nada não poluem "Artistas" e a busca.
define('SQL_ARTISTA_VISIVEL', "(EXISTS (SELECT 1 FROM musica mv WHERE mv.musica_artista = a.artista_id)
    OR NOT EXISTS (SELECT 1 FROM usuarios uv WHERE uv.artista_id = a.artista_id))");

define('SQL_FAIXA_JOINS', "FROM musica m
    INNER JOIN artista a ON m.musica_artista = a.artista_id
    LEFT JOIN album al ON al.album_id = m.album_id");

// Executa uma consulta preparada e devolve todas as linhas.
function consultar($conexao, $sql, $tipos = '', $parametros = []) {
    $stmt = mysqli_prepare($conexao, $sql);
    if (!$stmt) {
        error_log('Erro SQL: ' . mysqli_error($conexao) . ' - ' . $sql);
        return [];
    }
    if ($tipos !== '') {
        mysqli_stmt_bind_param($stmt, $tipos, ...$parametros);
    }
    if (!mysqli_stmt_execute($stmt)) {
        error_log('Erro SQL: ' . mysqli_stmt_error($stmt));
        return [];
    }
    $resultado = mysqli_stmt_get_result($stmt);
    $linhas = $resultado ? mysqli_fetch_all($resultado, MYSQLI_ASSOC) : [];
    mysqli_stmt_close($stmt);
    return $linhas;
}

function consultarUm($conexao, $sql, $tipos = '', $parametros = []) {
    $linhas = consultar($conexao, $sql, $tipos, $parametros);
    return $linhas[0] ?? null;
}

// Executa INSERT/UPDATE/DELETE; retorna true/false.
function executar($conexao, $sql, $tipos = '', $parametros = []) {
    $stmt = mysqli_prepare($conexao, $sql);
    if (!$stmt) {
        error_log('Erro SQL: ' . mysqli_error($conexao) . ' - ' . $sql);
        return false;
    }
    if ($tipos !== '') {
        mysqli_stmt_bind_param($stmt, $tipos, ...$parametros);
    }
    $ok = mysqli_stmt_execute($stmt);
    if (!$ok) {
        error_log('Erro SQL: ' . mysqli_stmt_error($stmt));
    }
    mysqli_stmt_close($stmt);
    return $ok;
}

// Faixas com o trecho SQL depois dos JOINs (WHERE/ORDER/LIMIT).
function consultarFaixas($conexao, $resto, $tipos = '', $parametros = [], $camposExtras = '') {
    $campos = SQL_FAIXA_CAMPOS . ($camposExtras ? ', ' . $camposExtras : '');
    return consultar($conexao, "SELECT $campos " . SQL_FAIXA_JOINS . " $resto", $tipos, $parametros);
}

// Converte uma linha do banco no objeto de faixa usado pelo player (JS).
function faixaParaArray($linha) {
    return [
        'kind' => 'musica',
        'id' => (int) $linha['musica_id'],
        'title' => $linha['musica_titulo'],
        'artist' => $linha['artista_nome'],
        'artistId' => (int) $linha['artista_id'],
        'album' => $linha['album_titulo'] ?? null,
        'albumId' => !empty($linha['album_id']) ? (int) $linha['album_id'] : null,
        'cover' => imagemOuPadrao($linha['musica_capa']),
        // Nunca o caminho do arquivo: links temporários assinados (stream.php).
        'src' => linkAudio('m', $linha['musica_id'], 'a'),
        'srcLow' => !empty($linha['musica_link_baixa']) ? linkAudio('m', $linha['musica_id'], 'b') : null,
        'duration' => isset($linha['musica_duracao']) ? (int) $linha['musica_duracao'] : 0,
        'lyrics' => !empty($linha['tem_letra']),
    ];
}

function episodioParaArray($linha) {
    return [
        'kind' => 'episodio',
        'id' => (int) $linha['episodio_id'],
        'title' => $linha['episodio_titulo'],
        'artist' => $linha['podcast_titulo'],
        'artistId' => null,
        'podcastId' => (int) $linha['podcast_id'],
        'album' => null,
        'albumId' => null,
        'cover' => imagemOuPadrao($linha['podcast_capa']),
        // Episódio com link externo (https) vai direto; arquivo enviado usa link assinado.
        'src' => preg_match('#^https://#i', $linha['episodio_audio']) ? $linha['episodio_audio'] : linkAudio('e', $linha['episodio_id'], 'a'),
        'srcLow' => null,
        'duration' => (int) ($linha['episodio_duracao'] ?? 0),
        'lyrics' => false,
    ];
}

function dadosFaixaAttr($faixa) {
    return e(json_encode($faixa, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
}

// IDs das músicas curtidas pelo usuário logado (para pintar os corações).
function idsCurtidos($conexao) {
    static $ids = null;
    if ($ids !== null) {
        return $ids;
    }
    $ids = [];
    if (!usuarioLogado()) {
        return $ids;
    }
    $linhas = consultar($conexao, "SELECT musica_id FROM curtidas WHERE usuario_id = ? AND tipo_curtida = 'curtida'", "i", [$_SESSION['usuario_id']]);
    foreach ($linhas as $linha) {
        $ids[(int) $linha['musica_id']] = true;
    }
    return $ids;
}

function botaoCurtir($musicaId, $curtida, $classeExtra = '') {
    $rotulo = $curtida ? 'Remover de Músicas Curtidas' : 'Salvar em Músicas Curtidas';
    return '<button type="button" class="like-toggle ' . $classeExtra . ($curtida ? ' is-liked' : '') . '" data-like-id="' . (int) $musicaId . '" aria-pressed="' . ($curtida ? 'true' : 'false') . '" aria-label="' . $rotulo . '" title="' . $rotulo . '">'
        . icone('heart', 'icon-heart-outline') . icone('heart-fill', 'icon-heart-fill') . '</button>';
}

// Lista de faixas estilo tabela (álbum, playlist, artista, busca, etc).
// Opções: album (bool) mostra coluna de álbum; capa (bool) mostra a capa;
// numero ('indice'|'faixa'); extra (callable $linha => html) coluna extra;
// cabecalhoExtra (string); playlistId (int) habilita "remover da playlist".
function renderListaFaixas($conexao, $linhas, $opcoes = []) {
    $opcoes = array_merge([
        'album' => true,
        'capa' => true,
        'numero' => 'indice',
        'extra' => null,
        'cabecalhoExtra' => '',
        'playlistId' => null,
        'contexto' => '',
        'cabecalho' => true,
    ], $opcoes);

    if (empty($linhas)) {
        return '';
    }

    $curtidas = idsCurtidos($conexao);
    $classes = 'tracklist' . ($opcoes['album'] ? ' has-album' : '') . ($opcoes['extra'] ? ' has-extra' : '');
    $html = '<div class="' . $classes . '" data-tracklist data-context="' . e($opcoes['contexto']) . '"'
        . ($opcoes['playlistId'] ? ' data-playlist-id="' . (int) $opcoes['playlistId'] . '"' : '') . '>';

    if ($opcoes['cabecalho']) {
        $html .= '<div class="tl-head" aria-hidden="true"><span class="tl-num">#</span><span>Título</span>'
            . ($opcoes['album'] ? '<span class="tl-album">Álbum</span>' : '')
            . ($opcoes['extra'] ? '<span class="tl-extra">' . e($opcoes['cabecalhoExtra']) . '</span>' : '')
            . '<span class="tl-dur">' . icone('clock') . '</span></div>';
    }

    foreach ($linhas as $i => $linha) {
        $faixa = faixaParaArray($linha);
        $numero = $opcoes['numero'] === 'faixa' && !empty($linha['musica_faixa']) ? (int) $linha['musica_faixa'] : $i + 1;
        $curtida = isset($curtidas[$faixa['id']]);

        $html .= '<div class="tl-row" role="button" tabindex="0" data-track="' . dadosFaixaAttr($faixa) . '" data-track-key="musica:' . $faixa['id'] . '">';
        $html .= '<div class="tl-num"><span class="tl-index">' . $numero . '</span>'
            . '<span class="tl-play">' . icone('play') . '</span><span class="tl-eq" aria-hidden="true"><i></i><i></i><i></i></span></div>';
        $html .= '<div class="tl-main">';
        if ($opcoes['capa']) {
            $html .= '<img class="tl-cover" src="' . e($faixa['cover']) . '" alt="" loading="lazy" width="40" height="40">';
        }
        $html .= '<div class="tl-text"><span class="tl-title">' . e($faixa['title']) . '</span>'
            . '<a class="tl-artist" href="artista.php?id=' . $faixa['artistId'] . '">' . e($faixa['artist']) . '</a></div></div>';
        if ($opcoes['album']) {
            $html .= '<div class="tl-album">' . ($faixa['albumId'] ? '<a href="album.php?id=' . $faixa['albumId'] . '">' . e($faixa['album']) . '</a>' : '<span class="muted">Single</span>') . '</div>';
        }
        if ($opcoes['extra']) {
            $html .= '<div class="tl-extra">' . call_user_func($opcoes['extra'], $linha) . '</div>';
        }
        $html .= '<div class="tl-actions">' . botaoCurtir($faixa['id'], $curtida, 'tl-like')
            . '<span class="tl-dur" data-duration-for="' . $faixa['id'] . '">' . formatarDuracao($faixa['duration']) . '</span>'
            . '<button type="button" class="icon-btn tl-more" data-action="track-menu" aria-label="Mais opções para ' . e($faixa['title']) . '">' . icone('more') . '</button>'
            . '</div>';
        $html .= '</div>';
    }

    return $html . '</div>';
}

// ===================== Cartões =====================

// Propagandas em formato retrato (4:5): carrossel com a imagem inteira e um
// fundo desfocado da própria imagem preenchendo as sobras.
function renderPropagandas($propagandas) {
    $html = '<div class="ad-slider" data-ad-slider><span class="ad-label">Patrocinado</span><div class="ad-stage">';
    foreach (array_values($propagandas) as $i => $p) {
        $img = e('Componentes/Armazenamento/propaganda/' . $p['propaganda_nome']);
        $html .= '<button type="button" class="ad-slide' . ($i === 0 ? ' is-active' : '') . '" data-ad-image="' . $img . '" aria-label="Ampliar anúncio ' . ($i + 1) . '"' . ($i === 0 ? '' : ' tabindex="-1"') . '>'
            . '<img class="ad-bg" src="' . $img . '" alt="" aria-hidden="true" loading="lazy">'
            . '<img class="ad-img" src="' . $img . '" alt="Anúncio ' . ($i + 1) . '" loading="lazy"></button>';
    }
    $html .= '</div>';
    if (count($propagandas) > 1) {
        $html .= '<div class="ad-controls"><button type="button" class="icon-btn" data-ad-step="-1" aria-label="Anúncio anterior">' . icone('chevron-left') . '</button><div class="ad-dots">';
        foreach (array_values($propagandas) as $i => $p) {
            $html .= '<button type="button" class="ad-dot' . ($i === 0 ? ' is-active' : '') . '" data-ad-go="' . $i . '" aria-label="Anúncio ' . ($i + 1) . '"></button>';
        }
        $html .= '</div><button type="button" class="icon-btn" data-ad-step="1" aria-label="Próximo anúncio">' . icone('chevron-right') . '</button></div>';
    }
    return $html . '</div>';
}

function renderCardFaixa($linha, $nota = '') {
    $faixa = faixaParaArray($linha);
    return '<div class="media-card" data-track="' . dadosFaixaAttr($faixa) . '" data-track-key="musica:' . $faixa['id'] . '">'
        . '<div class="media-cover"><img src="' . e($faixa['cover']) . '" alt="" loading="lazy">'
        . '<button type="button" class="card-play" data-action="play-card" aria-label="Tocar ' . e($faixa['title']) . '">' . icone('play', 'icon-play') . icone('pause', 'icon-pause') . '</button></div>'
        . '<div class="media-title" title="' . e($faixa['title']) . '">' . e($faixa['title']) . '</div>'
        . '<a class="media-sub" href="artista.php?id=' . $faixa['artistId'] . '">' . e($faixa['artist']) . '</a>'
        . ($nota ? '<span class="media-note">' . e($nota) . '</span>' : '')
        . '<button type="button" class="icon-btn card-more" data-action="track-menu" aria-label="Mais opções">' . icone('more') . '</button>'
        . '</div>';
}

function renderCardColecao($href, $imagem, $titulo, $subtitulo, $urlFaixas = '', $redondo = false, $mosaico = [], $posicao = '') {
    $capa = $mosaico && count($mosaico) >= 4
        ? '<div class="mosaic">' . implode('', array_map(function ($img) { return '<img src="' . e($img) . '" alt="" loading="lazy">'; }, array_slice($mosaico, 0, 4))) . '</div>'
        : '<img src="' . e(imagemOuPadrao($imagem ?: ($mosaico[0] ?? ''))) . '" alt="" loading="lazy"' . ($imagem ? estiloPosicao($posicao) : '') . '>';
    return '<a class="media-card' . ($redondo ? ' is-round' : '') . '" href="' . e($href) . '">'
        . '<div class="media-cover">' . $capa
        . ($urlFaixas ? '<button type="button" class="card-play" data-action="play-url" data-url="' . e($urlFaixas) . '" aria-label="Tocar ' . e($titulo) . '">' . icone('play') . '</button>' : '')
        . '</div>'
        . '<div class="media-title" title="' . e($titulo) . '">' . e($titulo) . '</div>'
        . '<div class="media-sub">' . e($subtitulo) . '</div>'
        . '</a>';
}

function renderCardAlbum($album) {
    $tipos = ['album' => 'Álbum', 'ep' => 'EP', 'single' => 'Single'];
    $sub = trim(($album['album_ano'] ? $album['album_ano'] . ' • ' : '') . ($tipos[$album['album_tipo']] ?? 'Álbum')
        . (isset($album['artista_nome']) ? ' • ' . $album['artista_nome'] : ''));
    return renderCardColecao('album.php?id=' . (int) $album['album_id'], $album['album_capa'], $album['album_titulo'], $sub, 'api/faixas.php?tipo=album&id=' . (int) $album['album_id']);
}

function renderCardArtista($artista) {
    return renderCardColecao('artista.php?id=' . (int) $artista['artista_id'], $artista['artista_image'], $artista['artista_nome'], 'Artista', 'api/faixas.php?tipo=artista&id=' . (int) $artista['artista_id'], true, [], $artista['artista_image_pos'] ?? '');
}

function renderCardPlaylist($playlist, $mosaico = []) {
    $sub = isset($playlist['dono_nome']) ? 'De ' . $playlist['dono_nome'] : 'Playlist';
    return renderCardColecao('playlist.php?id=' . (int) $playlist['playlist_id'], $playlist['playlist_capa'], $playlist['playlist_nome'], $sub, 'api/faixas.php?tipo=playlist&id=' . (int) $playlist['playlist_id'], false, $mosaico);
}

function renderCardPodcast($podcast) {
    return renderCardColecao('podcast.php?id=' . (int) $podcast['podcast_id'], $podcast['podcast_capa'], $podcast['podcast_titulo'], $podcast['podcast_autor'], 'api/faixas.php?tipo=podcast&id=' . (int) $podcast['podcast_id']);
}

// Seção com título e trilha horizontal rolável.
function renderCarrossel($titulo, $itensHtml, $linkVerTudo = '', $id = '') {
    if (trim($itensHtml) === '') {
        return '';
    }
    return '<section class="shelf"' . ($id ? ' id="' . e($id) . '"' : '') . '>'
        . '<div class="shelf-head"><h2>' . e($titulo) . '</h2>'
        . '<div class="shelf-tools">' . ($linkVerTudo ? '<a class="shelf-more" href="' . e($linkVerTudo) . '">Mostrar tudo</a>' : '')
        . '<button type="button" class="icon-btn shelf-nav" data-shelf="-1" aria-label="Rolar para a esquerda">' . icone('chevron-left') . '</button>'
        . '<button type="button" class="icon-btn shelf-nav" data-shelf="1" aria-label="Rolar para a direita">' . icone('chevron-right') . '</button></div></div>'
        . '<div class="shelf-track">' . $itensHtml . '</div></section>';
}

// Capas das 4 primeiras músicas de cada playlist (para o mosaico).
function capasPlaylists($conexao, $idsPlaylists) {
    $capas = [];
    if (empty($idsPlaylists)) {
        return $capas;
    }
    $marcadores = implode(',', array_fill(0, count($idsPlaylists), '?'));
    $linhas = consultar($conexao, "SELECT pm.playlist_id, m.musica_capa FROM playlist_musica pm INNER JOIN musica m ON m.musica_id = pm.musica_id WHERE pm.playlist_id IN ($marcadores) ORDER BY pm.playlist_id, pm.posicao, pm.adicionada", str_repeat('i', count($idsPlaylists)), array_values($idsPlaylists));
    foreach ($linhas as $linha) {
        $id = (int) $linha['playlist_id'];
        if (!isset($capas[$id])) {
            $capas[$id] = [];
        }
        if (count($capas[$id]) < 4 && $linha['musica_capa'] && !in_array($linha['musica_capa'], $capas[$id], true)) {
            $capas[$id][] = $linha['musica_capa'];
        }
    }
    return $capas;
}

// Playlists do usuário logado (barra lateral, menu "adicionar à playlist").
function playlistsDoUsuario($conexao, $usuarioId) {
    return consultar($conexao, "SELECT p.playlist_id, p.playlist_nome, p.playlist_capa, p.playlist_publica,
        (SELECT COUNT(*) FROM playlist_musica pm WHERE pm.playlist_id = p.playlist_id) AS total
        FROM playlist p WHERE p.usuario_id = ? ORDER BY p.playlist_criada DESC", "i", [$usuarioId]);
}

// Cabeçalho grande de coleção (álbum, playlist, gênero, podcast).
function renderCabecalhoColecao($opcoes) {
    $o = array_merge(['tipo' => '', 'titulo' => '', 'imagem' => '', 'mosaico' => [], 'descricao' => '', 'meta' => '', 'cor' => '', 'redondo' => false], $opcoes);
    $capa = $o['mosaico'] && count($o['mosaico']) >= 4
        ? '<div class="mosaic">' . implode('', array_map(function ($img) { return '<img src="' . e($img) . '" alt="">'; }, array_slice($o['mosaico'], 0, 4))) . '</div>'
        : '<img src="' . e(imagemOuPadrao($o['imagem'] ?: ($o['mosaico'][0] ?? ''))) . '" alt="">';
    $estilo = $o['cor'] ? ' style="--hero-color: ' . e($o['cor']) . '"' : '';
    return '<header class="hero"' . $estilo . '>'
        . '<div class="hero-cover' . ($o['redondo'] ? ' is-round' : '') . '">' . $capa . '</div>'
        . '<div class="hero-info"><span class="hero-type">' . e($o['tipo']) . '</span>'
        . '<h1 class="hero-title">' . e($o['titulo']) . '</h1>'
        . ($o['descricao'] ? '<p class="hero-desc">' . nl2br(e($o['descricao'])) . '</p>' : '')
        . '<div class="hero-meta">' . $o['meta'] . '</div></div></header>';
}

function renderVazio($icone, $titulo, $texto = '', $acaoHtml = '') {
    return '<div class="empty">' . icone($icone, 'empty-icon') . '<h2>' . e($titulo) . '</h2>'
        . ($texto ? '<p>' . e($texto) . '</p>' : '') . $acaoHtml . '</div>';
}
?>
