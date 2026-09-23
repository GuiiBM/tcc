<?php
// Meus podcasts: criar/editar programas e publicar episódios.
$paginaId = 'gerenciar-podcasts';
$tituloPagina = 'Meus podcasts';
include "Componentes/paginas/php/app.php";
include_once "Componentes/paginas/php/verificar_login.php";
redirecionarSeNaoLogado();
include_once "Componentes/paginas/php/podcasts.php";
include "Componentes/paginas/head.php";
include "Componentes/paginas/header.php";

$uid = (int) $_SESSION['usuario_id'];
$podcasts = ehAdmin()
    ? consultar($conexao, "SELECT p.*, (SELECT COUNT(*) FROM episodio e WHERE e.podcast_id = p.podcast_id) AS total FROM podcast p ORDER BY p.podcast_titulo")
    : consultar($conexao, "SELECT p.*, (SELECT COUNT(*) FROM episodio e WHERE e.podcast_id = p.podcast_id) AS total FROM podcast p WHERE p.usuario_id = ? ORDER BY p.podcast_titulo", "i", [$uid]);
$selecionado = null;
foreach ($podcasts as $p) {
    if ((int) $p['podcast_id'] === (int) ($_GET['podcast'] ?? 0)) {
        $selecionado = $p;
    }
}
$episodios = $selecionado ? consultar($conexao, "SELECT * FROM episodio WHERE podcast_id = ? ORDER BY episodio_data DESC", "i", [$selecionado['podcast_id']]) : [];
$maxMb = round(limiteUploadBytes() / 1048576);
$usuario = usuarioAtual();
?>
<div class="manage-page">
    <header class="page-head">
        <h1><?= ehAdmin() ? 'Podcasts (todos)' : 'Meus podcasts' ?></h1>
        <a class="btn-pill btn-ghost" href="podcasts.php"><?= icone('podcast', 'inline') ?> Ver podcasts</a>
    </header>

    <?php if (!$selecionado): ?>
    <section class="panel">
        <h2>Novo podcast</h2>
        <form class="form-stack" action="api/podcasts.php" enctype="multipart/form-data" data-api-form>
            <input type="hidden" name="acao" value="salvar_podcast">
            <div class="form-grid">
                <label class="field"><span>Nome do programa</span><input type="text" name="titulo" required maxlength="150"></label>
                <label class="field"><span>Autor / apresentador</span><input type="text" name="autor" required maxlength="100" value="<?= e($usuario['usuario_nome']) ?>"></label>
                <label class="field"><span>Capa (quadrada)</span><input type="file" name="capa" accept="image/*" required></label>
            </div>
            <label class="field"><span>Descrição</span><textarea name="descricao" rows="3" maxlength="3000"></textarea></label>
            <div><button type="submit" class="btn-pill btn-accent">Criar podcast</button></div>
        </form>
    </section>

    <?php if ($podcasts): ?>
    <div class="manage-list">
        <?php foreach ($podcasts as $p): ?>
        <div class="manage-row">
            <img src="<?= e(imagemOuPadrao($p['podcast_capa'])) ?>" alt="" loading="lazy">
            <div class="manage-text"><strong><?= e($p['podcast_titulo']) ?></strong><small><?= e($p['podcast_autor']) ?> • <?= pluralizar((int) $p['total'], 'episódio', 'episódios') ?></small></div>
            <a class="btn-pill btn-light small" href="gerenciarPodcasts.php?podcast=<?= (int) $p['podcast_id'] ?>">Gerenciar</a>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php else: ?>
    <p><a class="btn-pill btn-ghost small" href="gerenciarPodcasts.php"><?= icone('chevron-left', 'inline') ?> Todos os podcasts</a></p>

    <section class="panel">
        <h2>Publicar episódio em “<?= e($selecionado['podcast_titulo']) ?>”</h2>
        <form class="form-stack" action="api/podcasts.php" enctype="multipart/form-data" data-api-form data-reload data-music-form data-max-bytes="<?= limiteUploadBytes() ?>">
            <input type="hidden" name="acao" value="salvar_episodio">
            <input type="hidden" name="podcast_id" value="<?= (int) $selecionado['podcast_id'] ?>">
            <input type="hidden" name="duracao" value="">
            <label class="field"><span>Título do episódio</span><input type="text" name="titulo" required maxlength="200"></label>
            <label class="field"><span>Descrição / notas do episódio</span><textarea name="descricao" rows="4" maxlength="5000"></textarea></label>
            <div class="form-grid">
                <label class="field"><span>Arquivo de áudio (até <?= $maxMb ?> MB)</span><input type="file" name="audio" accept="audio/*,.mp3,.m4a,.ogg" data-max-check data-duration-source></label>
                <label class="field"><span>…ou link do áudio (https://)</span><input type="url" name="audio_url" placeholder="https://exemplo.com/episodio.mp3" data-duration-url>
                    <small>Para episódios longos, maiores que o limite de upload, hospede o MP3 fora (ex: Internet Archive) e cole o link direto.</small></label>
            </div>
            <div class="row-actions"><button type="submit" class="btn-pill btn-accent">Publicar episódio</button><span class="upload-status" data-upload-status></span></div>
        </form>
    </section>

    <?php if ($episodios): ?>
    <section>
        <h2 class="section-title">Episódios</h2>
        <?php foreach ($episodios as $ep): ?>
        <details class="panel manage-album">
            <summary>
                <span class="manage-text"><strong><?= e($ep['episodio_titulo']) ?></strong><small><?= e(formatarDataCurta($ep['episodio_data'])) ?> • <?= $ep['episodio_duracao'] ? formatarDuracaoTotal($ep['episodio_duracao']) : 'duração calculada ao tocar' ?></small></span>
            </summary>
            <form class="form-stack" action="api/podcasts.php" enctype="multipart/form-data" data-api-form data-reload data-music-form data-max-bytes="<?= limiteUploadBytes() ?>">
                <input type="hidden" name="acao" value="salvar_episodio">
                <input type="hidden" name="podcast_id" value="<?= (int) $selecionado['podcast_id'] ?>">
                <input type="hidden" name="episodio_id" value="<?= (int) $ep['episodio_id'] ?>">
                <input type="hidden" name="duracao" value="">
                <label class="field"><span>Título</span><input type="text" name="titulo" required maxlength="200" value="<?= e($ep['episodio_titulo']) ?>"></label>
                <label class="field"><span>Descrição</span><textarea name="descricao" rows="3" maxlength="5000"><?= e($ep['episodio_descricao']) ?></textarea></label>
                <div class="form-grid">
                    <label class="field"><span>Trocar áudio</span><input type="file" name="audio" accept="audio/*" data-max-check data-duration-source></label>
                    <label class="field"><span>…ou novo link</span><input type="url" name="audio_url" placeholder="https://..." data-duration-url></label>
                </div>
                <div class="row-actions"><button type="submit" class="btn-pill btn-accent">Salvar episódio</button></div>
            </form>
            <form action="api/podcasts.php" data-api-form data-reload data-confirm="Excluir o episódio “<?= e($ep['episodio_titulo']) ?>”?">
                <input type="hidden" name="acao" value="excluir_episodio">
                <input type="hidden" name="episodio_id" value="<?= (int) $ep['episodio_id'] ?>">
                <button type="submit" class="btn-pill btn-ghost danger small"><?= icone('trash', 'inline') ?> Excluir episódio</button>
            </form>
        </details>
        <?php endforeach; ?>
    </section>
    <?php endif; ?>

    <section class="panel">
        <h2>Dados do podcast</h2>
        <form class="form-stack" action="api/podcasts.php" enctype="multipart/form-data" data-api-form data-reload>
            <input type="hidden" name="acao" value="salvar_podcast">
            <input type="hidden" name="podcast_id" value="<?= (int) $selecionado['podcast_id'] ?>">
            <div class="form-grid">
                <label class="field"><span>Nome</span><input type="text" name="titulo" required maxlength="150" value="<?= e($selecionado['podcast_titulo']) ?>"></label>
                <label class="field"><span>Autor</span><input type="text" name="autor" required maxlength="100" value="<?= e($selecionado['podcast_autor']) ?>"></label>
                <label class="field"><span>Trocar capa</span><input type="file" name="capa" accept="image/*"></label>
            </div>
            <label class="field"><span>Descrição</span><textarea name="descricao" rows="3" maxlength="3000"><?= e($selecionado['podcast_descricao']) ?></textarea></label>
            <div class="row-actions">
                <button type="submit" class="btn-pill btn-accent">Salvar</button>
                <a class="btn-pill btn-ghost" href="podcast.php?id=<?= (int) $selecionado['podcast_id'] ?>">Ver página</a>
            </div>
        </form>
        <form action="api/podcasts.php" data-api-form data-confirm="Excluir o podcast “<?= e($selecionado['podcast_titulo']) ?>” e todos os episódios?">
            <input type="hidden" name="acao" value="excluir_podcast">
            <input type="hidden" name="podcast_id" value="<?= (int) $selecionado['podcast_id'] ?>">
            <button type="submit" class="btn-pill btn-ghost danger small"><?= icone('trash', 'inline') ?> Excluir podcast</button>
        </form>
    </section>
    <?php endif; ?>
</div>
<?php include "Componentes/paginas/footer.php"; ?>
