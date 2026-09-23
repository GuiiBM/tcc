<?php
// Minhas músicas e álbuns: publicar, editar e excluir músicas e álbuns.
// O admin vê e gerencia os de todos os artistas.
$paginaId = 'musicas';
$tituloPagina = 'Minhas músicas';
include "Componentes/paginas/php/app.php";
include_once "Componentes/paginas/php/verificar_login.php";
redirecionarSeNaoLogado();
include_once "Componentes/paginas/php/formularioMusica.php";
include "Componentes/paginas/head.php";
include "Componentes/paginas/header.php";

$usuario = usuarioAtual();
$admin = ehAdmin();
$artistaId = (int) $usuario['artista_id'];

if ($admin) {
    $musicas = consultarFaixas($conexao, "ORDER BY m.musica_data_adicao DESC", '', [], 'm.musica_letra IS NOT NULL AS com_letra');
    $albuns = consultar($conexao, "SELECT al.*, a.artista_nome, (SELECT COUNT(*) FROM musica m WHERE m.album_id = al.album_id) AS total FROM album al INNER JOIN artista a ON a.artista_id = al.album_artista ORDER BY a.artista_nome, al.album_titulo");
    $artistas = consultar($conexao, "SELECT artista_id, artista_nome, artista_cidade FROM artista ORDER BY artista_nome");
} else {
    $musicas = $artistaId ? consultarFaixas($conexao, "WHERE m.musica_artista = ? ORDER BY m.musica_data_adicao DESC", "i", [$artistaId]) : [];
    $albuns = $artistaId ? consultar($conexao, "SELECT al.*, a.artista_nome, (SELECT COUNT(*) FROM musica m WHERE m.album_id = al.album_id) AS total FROM album al INNER JOIN artista a ON a.artista_id = al.album_artista WHERE al.album_artista = ? ORDER BY al.album_titulo", "i", [$artistaId]) : [];
    $artistas = null;
}
$aba = in_array($_GET['aba'] ?? '', ['enviar', 'musicas', 'albuns'], true) ? $_GET['aba'] : ($musicas ? 'musicas' : 'enviar');
$tipos = ['album' => 'Álbum', 'ep' => 'EP', 'single' => 'Single'];
?>
<div class="manage-page">
    <header class="page-head">
        <h1><?= $admin ? 'Músicas e álbuns (todos os artistas)' : 'Minhas músicas e álbuns' ?></h1>
        <?php if (!$admin && $artistaId): ?>
        <a class="btn-pill btn-ghost" href="artista.php?id=<?= $artistaId ?>"><?= icone('user') ?> Ver minha página</a>
        <?php endif; ?>
    </header>

    <div class="chips" role="tablist" data-tabs>
        <button type="button" class="chip<?= $aba === 'enviar' ? ' is-active' : '' ?>" role="tab" data-tab="enviar"><?= icone('upload', 'inline') ?> Publicar música</button>
        <button type="button" class="chip<?= $aba === 'musicas' ? ' is-active' : '' ?>" role="tab" data-tab="musicas">Músicas (<?= count($musicas) ?>)</button>
        <button type="button" class="chip<?= $aba === 'albuns' ? ' is-active' : '' ?>" role="tab" data-tab="albuns">Álbuns (<?= count($albuns) ?>)</button>
    </div>

    <section class="panel" data-tab-panel="enviar" <?= $aba === 'enviar' ? '' : 'hidden' ?>>
        <h2>Publicar música</h2>
        <?= renderFormularioMusica($conexao, null, $albuns, $artistas) ?>
    </section>

    <section data-tab-panel="musicas" <?= $aba === 'musicas' ? '' : 'hidden' ?>>
        <?php if ($musicas): ?>
        <div class="manage-list">
            <?php foreach ($musicas as $m): ?>
            <div class="manage-row">
                <img src="<?= e(imagemOuPadrao($m['musica_capa'])) ?>" alt="" loading="lazy">
                <div class="manage-text">
                    <strong><?= e($m['musica_titulo']) ?></strong>
                    <small><?= $admin ? e($m['artista_nome']) . ' • ' : '' ?><?= $m['album_titulo'] ? e($m['album_titulo']) : 'Single' ?> • <?= formatarDuracao($m['musica_duracao']) ?><?= $m['tem_letra'] ? ' • Letra' : '' ?><?= $m['musica_link_baixa'] ? ' • Versão compacta' : '' ?></small>
                </div>
                <a class="btn-pill btn-ghost small" href="editarMusica.php?id=<?= (int) $m['musica_id'] ?>"><?= icone('edit', 'inline') ?> Editar</a>
                <form action="api/musicas.php" data-api-form data-reload data-confirm="Excluir “<?= e($m['musica_titulo']) ?>”? O arquivo de áudio será apagado.">
                    <input type="hidden" name="acao" value="excluir_musica">
                    <input type="hidden" name="musica_id" value="<?= (int) $m['musica_id'] ?>">
                    <button type="submit" class="icon-btn danger" aria-label="Excluir <?= e($m['musica_titulo']) ?>"><?= icone('trash') ?></button>
                </form>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
            <?= renderVazio('music', 'Nenhuma música publicada ainda', 'Use “Publicar música” para enviar a primeira.') ?>
        <?php endif; ?>
    </section>

    <section data-tab-panel="albuns" <?= $aba === 'albuns' ? '' : 'hidden' ?>>
        <div class="panel">
            <h2>Novo álbum</h2>
            <form class="form-stack" action="api/musicas.php" enctype="multipart/form-data" data-api-form data-redirect="musicas.php?aba=albuns">
                <input type="hidden" name="acao" value="criar_album">
                <div class="form-grid">
                    <label class="field"><span>Nome</span><input type="text" name="titulo" required maxlength="100"></label>
                    <?php if ($admin): ?>
                    <label class="field"><span>Artista</span><select name="artista_id" required><option value="">Escolha…</option><?php foreach ($artistas as $a): ?><option value="<?= (int) $a['artista_id'] ?>"><?= e($a['artista_nome']) ?></option><?php endforeach; ?></select></label>
                    <?php endif; ?>
                    <label class="field"><span>Tipo</span><select name="tipo"><?php foreach ($tipos as $v => $r): ?><option value="<?= $v ?>"><?= $r ?></option><?php endforeach; ?></select></label>
                    <label class="field"><span>Ano</span><input type="number" name="ano" min="1900" max="<?= date('Y') + 1 ?>" value="<?= date('Y') ?>"></label>
                    <label class="field"><span>Capa</span><input type="file" name="capa" accept="image/*"></label>
                </div>
                <div><button type="submit" class="btn-pill btn-accent">Criar álbum</button></div>
            </form>
        </div>

        <?php foreach ($albuns as $al): ?>
        <details class="panel manage-album" id="album-<?= (int) $al['album_id'] ?>">
            <summary>
                <img src="<?= e(imagemOuPadrao($al['album_capa'])) ?>" alt="" loading="lazy">
                <span class="manage-text"><strong><?= e($al['album_titulo']) ?></strong><small><?= $tipos[$al['album_tipo']] ?? 'Álbum' ?><?= $al['album_ano'] ? ' • ' . (int) $al['album_ano'] : '' ?><?= $admin ? ' • ' . e($al['artista_nome']) : '' ?> • <?= pluralizar((int) $al['total'], 'música', 'músicas') ?></small></span>
                <a class="btn-pill btn-ghost small" href="album.php?id=<?= (int) $al['album_id'] ?>">Abrir</a>
            </summary>
            <form class="form-stack" action="api/musicas.php" enctype="multipart/form-data" data-api-form data-reload>
                <input type="hidden" name="acao" value="editar_album">
                <input type="hidden" name="album_id" value="<?= (int) $al['album_id'] ?>">
                <div class="form-grid">
                    <label class="field"><span>Nome</span><input type="text" name="titulo" required maxlength="100" value="<?= e($al['album_titulo']) ?>"></label>
                    <label class="field"><span>Tipo</span><select name="tipo"><?php foreach ($tipos as $v => $r): ?><option value="<?= $v ?>" <?= $al['album_tipo'] === $v ? 'selected' : '' ?>><?= $r ?></option><?php endforeach; ?></select></label>
                    <label class="field"><span>Ano</span><input type="number" name="ano" min="1900" max="<?= date('Y') + 1 ?>" value="<?= e($al['album_ano']) ?>"></label>
                    <label class="field"><span>Trocar capa</span><input type="file" name="capa" accept="image/*"></label>
                </div>
                <p class="muted small">Para colocar músicas neste álbum, edite cada música e escolha o álbum (e o número da faixa).</p>
                <div class="row-actions"><button type="submit" class="btn-pill btn-accent">Salvar álbum</button></div>
            </form>
            <form action="api/musicas.php" data-api-form data-reload data-confirm="Excluir o álbum “<?= e($al['album_titulo']) ?>”? As músicas continuam publicadas como singles.">
                <input type="hidden" name="acao" value="excluir_album">
                <input type="hidden" name="album_id" value="<?= (int) $al['album_id'] ?>">
                <button type="submit" class="btn-pill btn-ghost danger small"><?= icone('trash', 'inline') ?> Excluir álbum</button>
            </form>
        </details>
        <?php endforeach; ?>
    </section>
</div>
<?php include "Componentes/paginas/footer.php"; ?>
