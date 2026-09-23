<?php
// Direitos do titular (LGPD):
// GET  ?acao=exportar -> baixa um JSON com todos os dados da conta (portabilidade)
// POST acao=excluir   -> apaga a conta e tudo o que foi publicado por ela, de
//                        forma definitiva (direito à eliminação)
include __DIR__ . '/../Componentes/paginas/php/app.php';
exigirLoginApi();

$usuario = consultarUm($conexao, "SELECT * FROM usuarios WHERE usuario_id = ?", "i", [$_SESSION['usuario_id']]);
$uid = (int) $usuario['usuario_id'];
$artistaId = (int) $usuario['artista_id'];

if (($_GET['acao'] ?? '') === 'exportar') {
    unset($usuario['usuario_senha'], $usuario['usuario_lat'], $usuario['usuario_lon'], $usuario['usuario_geo_cidade']);
    $dados = [
        'gerado_em' => date('c'),
        'perfil' => $usuario,
        'pagina_de_artista' => $artistaId ? consultarUm($conexao, "SELECT artista_nome, artista_cidade, artista_descricao, artista_link FROM artista WHERE artista_id = ?", "i", [$artistaId]) : null,
        'musicas_publicadas' => $artistaId ? consultar($conexao, "SELECT musica_titulo, musica_data_adicao FROM musica WHERE musica_artista = ?", "i", [$artistaId]) : [],
        'albuns' => $artistaId ? consultar($conexao, "SELECT album_titulo, album_tipo, album_ano FROM album WHERE album_artista = ?", "i", [$artistaId]) : [],
        'playlists' => array_map(function ($p) use ($conexao) {
            $p['musicas'] = array_column(consultar($conexao, "SELECT m.musica_titulo FROM playlist_musica pm INNER JOIN musica m ON m.musica_id = pm.musica_id WHERE pm.playlist_id = ? ORDER BY pm.posicao", "i", [$p['playlist_id']]), 'musica_titulo');
            return $p;
        }, consultar($conexao, "SELECT playlist_id, playlist_nome, playlist_descricao, playlist_publica, playlist_criada FROM playlist WHERE usuario_id = ?", "i", [$uid])),
        'curtidas' => consultar($conexao, "SELECT m.musica_titulo, c.tipo_curtida, c.data_curtida FROM curtidas c INNER JOIN musica m ON m.musica_id = c.musica_id WHERE c.usuario_id = ?", "i", [$uid]),
        'artistas_seguidos' => consultar($conexao, "SELECT a.artista_nome, s.data_seguiu FROM seguidores s INNER JOIN artista a ON a.artista_id = s.artista_id WHERE s.usuario_id = ?", "i", [$uid]),
        'historico' => consultar($conexao, "SELECT COALESCE(m.musica_titulo, e.episodio_titulo) AS titulo, h.data_reproducao FROM historico h LEFT JOIN musica m ON m.musica_id = h.musica_id LEFT JOIN episodio e ON e.episodio_id = h.episodio_id WHERE h.usuario_id = ? ORDER BY h.data_reproducao DESC", "i", [$uid]),
        'podcasts' => consultar($conexao, "SELECT podcast_titulo, podcast_autor, podcast_descricao, podcast_data FROM podcast WHERE usuario_id = ?", "i", [$uid]),
    ];
    enviarCabecalhosSeguranca(false, true);
    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="meus-dados-ressonance.json"');
    header('Cache-Control: no-store');
    echo json_encode($dados, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

exigirPostApi();
$dados = dadosRequisicao();
if (($dados['acao'] ?? '') !== 'excluir') {
    jsonErro('Ação inválida');
}

// Confirmação: senha (login com senha) ou digitar o próprio e-mail (login social).
$loginSocial = in_array($_SESSION['login_metodo'] ?? '', ['google', 'facebook', 'apple'], true);
if ($loginSocial) {
    if (mb_strtolower(trim($dados['confirmacao'] ?? '')) !== mb_strtolower($usuario['usuario_email'])) {
        jsonErro('Digite seu e-mail exatamente como aparece para confirmar');
    }
} else {
    if ($bloqueio = minutosBloqueado($conexao, 'senha', 5, 15, (string) $uid)) {
        jsonErro("Muitas tentativas. Tente novamente em $bloqueio minutos.", 429);
    }
    if (!password_verify((string) ($dados['confirmacao'] ?? ''), $usuario['usuario_senha'])) {
        registrarTentativa($conexao, 'senha', (string) $uid);
        jsonErro('Senha incorreta');
    }
}

// Arquivos enviados pela pessoa (músicas, capas, fotos, episódios).
$arquivos = [$usuario['usuario_foto'], ...array_column(consultar($conexao, "SELECT playlist_capa FROM playlist WHERE usuario_id = ?", "i", [$uid]), 'playlist_capa')];
if ($artistaId) {
    $artista = consultarUm($conexao, "SELECT artista_image, artista_capa FROM artista WHERE artista_id = ?", "i", [$artistaId]);
    $arquivos[] = $artista['artista_image'] ?? null;
    $arquivos[] = $artista['artista_capa'] ?? null;
    foreach (consultar($conexao, "SELECT musica_capa, musica_link, musica_link_baixa FROM musica WHERE musica_artista = ?", "i", [$artistaId]) as $m) {
        array_push($arquivos, $m['musica_capa'], $m['musica_link'], $m['musica_link_baixa']);
    }
    $arquivos = array_merge($arquivos, array_column(consultar($conexao, "SELECT album_capa FROM album WHERE album_artista = ?", "i", [$artistaId]), 'album_capa'));
}
foreach (consultar($conexao, "SELECT p.podcast_capa, e.episodio_audio FROM podcast p LEFT JOIN episodio e ON e.podcast_id = p.podcast_id WHERE p.usuario_id = ?", "i", [$uid]) as $p) {
    array_push($arquivos, $p['podcast_capa'], $p['episodio_audio']);
}

mysqli_begin_transaction($conexao);
$ok = executar($conexao, "DELETE FROM podcast WHERE usuario_id = ?", "i", [$uid])           // episódios caem junto (CASCADE)
    && executar($conexao, "DELETE FROM usuarios WHERE usuario_id = ?", "i", [$uid])         // playlists, curtidas, seguidores, histórico (CASCADE)
    && (!$artistaId || executar($conexao, "DELETE FROM artista WHERE artista_id = ?", "i", [$artistaId])); // músicas, álbuns e reproduções (CASCADE)
if (!$ok) {
    mysqli_rollback($conexao);
    jsonErro('Não foi possível excluir a conta. Tente novamente.', 500);
}
mysqli_commit($conexao);

foreach (array_unique(array_filter($arquivos)) as $arquivo) {
    removerArquivoArmazenado($arquivo);
}

$_SESSION = [];
session_destroy();
jsonResposta(['success' => true, 'message' => 'Conta excluída. Seus dados foram apagados.']);
