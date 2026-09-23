<?php
// Histórico: últimas faixas (músicas e episódios) ouvidas por completo.
$paginaId = 'historico';
$paginaSpa = true;
$tituloPagina = 'Histórico';
include "Componentes/paginas/php/app.php";
if (!usuarioLogado()) {
    header('Location: login.php');
    exit;
}
include "Componentes/paginas/head.php";
include_once "Componentes/paginas/php/podcasts.php";
include "Componentes/paginas/header.php";

$uid = (int) $_SESSION['usuario_id'];
$musicas = consultarFaixas($conexao, "INNER JOIN historico h ON h.musica_id = m.musica_id WHERE h.usuario_id = ? ORDER BY h.data_reproducao DESC LIMIT 100", "i", [$uid], 'h.data_reproducao');
$episodios = consultar($conexao, "SELECT e.*, p.podcast_titulo, p.podcast_capa, h.data_reproducao FROM historico h INNER JOIN episodio e ON e.episodio_id = h.episodio_id INNER JOIN podcast p ON p.podcast_id = e.podcast_id WHERE h.usuario_id = ? ORDER BY h.data_reproducao DESC LIMIT 30", "i", [$uid]);

function quandoOuviu($data) {
    $ts = strtotime($data);
    $diff = time() - $ts;
    if ($diff < 60) return 'agora mesmo';
    if ($diff < 3600) return 'há ' . pluralizar(intdiv($diff, 60), 'minuto', 'minutos');
    if ($diff < 86400) return 'há ' . pluralizar(intdiv($diff, 3600), 'hora', 'horas');
    if ($diff < 86400 * 7) return 'há ' . pluralizar(intdiv($diff, 86400), 'dia', 'dias');
    return date('d/m/Y', $ts);
}
?>
<div class="collection">
    <header class="page-head">
        <h1><?= icone('history') ?> Histórico de reprodução</h1>
        <p class="muted">Músicas e episódios que você ouviu até o fim.</p>
        <?php if ($musicas || $episodios): ?>
        <form action="api/historico.php" data-api-form data-reload>
            <input type="hidden" name="acao" value="limpar">
            <button type="submit" class="btn-pill btn-ghost"><?= icone('trash') ?> Limpar histórico</button>
        </form>
        <?php endif; ?>
    </header>

    <?php if ($musicas): ?>
        <?= renderListaFaixas($conexao, $musicas, [
            'contexto' => 'historico',
            'extra' => function ($linha) { return e(quandoOuviu($linha['data_reproducao'])); },
            'cabecalhoExtra' => 'Ouvida',
        ]) ?>
    <?php endif; ?>

    <?php if ($episodios): ?>
        <h2 class="section-title">Episódios</h2>
        <?= renderListaEpisodios($episodios, true, 'historico-episodios') ?>
    <?php endif; ?>

    <?php if (!$musicas && !$episodios): ?>
        <?= renderVazio('history', 'Nada por aqui ainda', 'Quando você ouvir uma música até o fim, ela aparece aqui.', '<a class="btn-pill btn-light" href="index.php">Começar a ouvir</a>') ?>
    <?php endif; ?>
</div>
<?php include "Componentes/paginas/footer.php"; ?>
