<?php
// Página inicial: saudação pelo horário, atalhos da biblioteca e
// prateleiras de "Tocadas recentemente", "Feito para você", etc.
$paginaId = 'home';
$paginaSpa = true;
$tituloPagina = 'Início';
include "Componentes/paginas/head.php";
include_once "Componentes/paginas/php/verificarPerfilCompleto.php";
include_once "Componentes/paginas/php/funcoesPropaganda.php";
include_once "Componentes/paginas/php/geo.php";
include "Componentes/paginas/header.php";

$usuario = usuarioAtual();
$uid = $usuario ? (int) $usuario['usuario_id'] : 0;

$hora = (int) date('G');
$saudacao = $hora >= 5 && $hora < 12 ? 'Bom dia' : ($hora >= 12 && $hora < 18 ? 'Boa tarde' : 'Boa noite');
$primeiroNome = $usuario ? explode(' ', trim($usuario['usuario_nome']))[0] : '';

// ----- Tocadas recentemente (histórico de faixas ouvidas por completo) -----
$recentes = $uid ? consultarFaixas($conexao, "INNER JOIN (
        SELECT musica_id, MAX(data_reproducao) AS ultima FROM historico
        WHERE usuario_id = ? AND musica_id IS NOT NULL GROUP BY musica_id
    ) h ON h.musica_id = m.musica_id ORDER BY h.ultima DESC LIMIT 12", "i", [$uid]) : [];

// ----- Feito para você: gêneros e artistas das músicas que o usuário
// curtiu/ouviu + artistas que segue, sem repetir o que ouviu nos últimos dias.
$feitoParaVoce = [];
if ($uid) {
    $feitoParaVoce = consultarFaixas($conexao, "WHERE (
            m.musica_id IN (SELECT mc.musica_id FROM musica_categoria mc WHERE mc.categoria_id IN (
                SELECT mc2.categoria_id FROM musica_categoria mc2 WHERE mc2.musica_id IN (
                    SELECT c.musica_id FROM curtidas c WHERE c.usuario_id = ? AND c.tipo_curtida = 'curtida'
                    UNION SELECT h.musica_id FROM historico h WHERE h.usuario_id = ? AND h.musica_id IS NOT NULL)))
            OR m.musica_artista IN (SELECT s.artista_id FROM seguidores s WHERE s.usuario_id = ?)
            OR m.musica_artista IN (SELECT mm.musica_artista FROM curtidas c2 INNER JOIN musica mm ON mm.musica_id = c2.musica_id WHERE c2.usuario_id = ? AND c2.tipo_curtida = 'curtida')
        )
        AND m.musica_id NOT IN (SELECT h2.musica_id FROM historico h2 WHERE h2.usuario_id = ? AND h2.musica_id IS NOT NULL AND h2.data_reproducao > DATE_SUB(NOW(), INTERVAL 2 DAY))
        AND m.musica_id NOT IN (SELECT c3.musica_id FROM curtidas c3 WHERE c3.usuario_id = ? AND c3.tipo_curtida = 'descurtida')
        ORDER BY RAND() LIMIT 12", "iiiiii", [$uid, $uid, $uid, $uid, $uid, $uid]);
}
if (count($feitoParaVoce) < 8) {
    $jaTem = array_map('intval', array_column($feitoParaVoce, 'musica_id'));
    $filtro = $jaTem ? 'WHERE m.musica_id NOT IN (' . implode(',', $jaTem) . ')' : '';
    $feitoParaVoce = array_merge($feitoParaVoce, consultarFaixas($conexao, "$filtro ORDER BY RAND() LIMIT " . (12 - count($feitoParaVoce))));
}

// ----- Recomendações: artistas locais e pouco ouvidos primeiro (geo.php) -----
$recomendadas = consultarRecomendadas($conexao, ['limite' => 12]);
$local = localizacaoVisitante($conexao);

$lancamentos = consultarFaixas($conexao, "ORDER BY m.musica_data_adicao DESC LIMIT 12");
$albuns = consultar($conexao, "SELECT al.*, a.artista_nome FROM album al INNER JOIN artista a ON a.artista_id = al.album_artista ORDER BY al.album_data DESC LIMIT 12");
// Artistas: os mais próximos (quando há localização) e menos ouvidos primeiro.
$distanciaArtista = sqlDistancia($local);
$artistas = consultar($conexao, "SELECT a.artista_id, a.artista_nome, a.artista_image FROM artista a WHERE " . SQL_ARTISTA_VISIVEL . "
    ORDER BY ($distanciaArtista IS NULL), $distanciaArtista,
    (SELECT COUNT(*) FROM visualizacoes v INNER JOIN musica mv2 ON mv2.musica_id = v.musica_id WHERE mv2.musica_artista = a.artista_id) ASC LIMIT 12");
$podcasts = consultar($conexao, "SELECT * FROM podcast ORDER BY podcast_data DESC LIMIT 12");
$playlistsUsuario = $uid ? array_slice(playlistsDoUsuario($conexao, $uid), 0, 7) : [];
$playlistsPublicas = consultar($conexao, "SELECT p.*, u.usuario_nome AS dono_nome FROM playlist p INNER JOIN usuarios u ON u.usuario_id = p.usuario_id
    WHERE p.playlist_publica = 1 AND p.usuario_id <> ? AND EXISTS (SELECT 1 FROM playlist_musica pm WHERE pm.playlist_id = p.playlist_id)
    ORDER BY p.playlist_criada DESC LIMIT 12", "i", [$uid]);
$mosaicos = capasPlaylists($conexao, array_merge(array_column($playlistsUsuario, 'playlist_id'), array_column($playlistsPublicas, 'playlist_id')));
$propagandas = listarPropagandasOrdenadas();

$cards = function ($linhas) { return implode('', array_map('renderCardFaixa', $linhas)); };
?>
<div class="home">
    <section class="home-hero">
        <h1 class="greeting" data-greeting data-name="<?= e($primeiroNome) ?>"><?= e($saudacao) ?><?= $primeiroNome ? ', ' . e($primeiroNome) : '' ?></h1>

        <?php if ($usuario): ?>
        <div class="quick-grid">
            <a class="quick-tile" href="curtidas.php">
                <span class="quick-thumb liked-thumb"><?= icone('heart-fill') ?></span>
                <strong>Músicas Curtidas</strong>
                <button type="button" class="card-play" data-action="play-url" data-url="api/faixas.php?tipo=curtidas" aria-label="Tocar Músicas Curtidas"><?= icone('play') ?></button>
            </a>
            <?php foreach ($playlistsUsuario as $p):
                $capas = $mosaicos[(int) $p['playlist_id']] ?? []; ?>
            <a class="quick-tile" href="playlist.php?id=<?= (int) $p['playlist_id'] ?>">
                <span class="quick-thumb"><img src="<?= e(imagemOuPadrao($p['playlist_capa'] ?: ($capas[0] ?? ''))) ?>" alt="" loading="lazy"></span>
                <strong><?= e($p['playlist_nome']) ?></strong>
                <button type="button" class="card-play" data-action="play-url" data-url="api/faixas.php?tipo=playlist&amp;id=<?= (int) $p['playlist_id'] ?>" aria-label="Tocar <?= e($p['playlist_nome']) ?>"><?= icone('play') ?></button>
            </a>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="welcome-card">
            <div>
                <h2>Ouça artistas independentes de graça</h2>
                <p>Crie uma conta com o Google para curtir músicas, montar playlists, seguir artistas e retomar de onde parou.</p>
            </div>
            <a class="btn-pill btn-accent" href="login.php" data-no-spa>Entrar com Google</a>
        </div>
        <?php endif; ?>
    </section>

    <?= renderCarrossel('Tocadas recentemente', $cards($recentes), 'historico.php') ?>
    <?= renderCarrossel($local ? 'Recomendações perto de você' : 'Recomendações', implode('', array_map(function ($l) { return renderCardFaixa($l, motivoRecomendacao($l)); }, $recomendadas)), 'recomendados.php') ?>
    <?= renderCarrossel($usuario ? 'Feito para você' : 'Descubra', $cards($feitoParaVoce)) ?>

    <?php if (!empty($propagandas)): ?>
    <section class="home-ads" aria-label="Publicidade">
        <?= renderPropagandas($propagandas) ?>
    </section>
    <?php endif; ?>

    <?= renderCarrossel('Lançamentos', $cards($lancamentos)) ?>
    <?= renderCarrossel('Álbuns e EPs', implode('', array_map('renderCardAlbum', $albuns))) ?>
    <?= renderCarrossel($local ? 'Artistas da sua região' : 'Artistas', implode('', array_map('renderCardArtista', $artistas)), 'artistas.php') ?>
    <?= renderCarrossel('Playlists da comunidade', implode('', array_map(function ($p) use ($mosaicos) { return renderCardPlaylist($p, $mosaicos[(int) $p['playlist_id']] ?? []); }, $playlistsPublicas))) ?>
    <?= renderCarrossel('Podcasts', implode('', array_map('renderCardPodcast', $podcasts)), 'podcasts.php') ?>
</div>
<?php
if ($usuario) {
    mostrarAlertaPerfilIncompleto();
}
include "Componentes/paginas/footer.php";
