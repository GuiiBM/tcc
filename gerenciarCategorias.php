<?php
// Admin: gêneros e humores que aparecem nos cartões coloridos da busca.
$paginaId = 'categorias';
$tituloPagina = 'Categorias';
include "Componentes/paginas/php/app.php";
include_once "Componentes/paginas/php/verificar_login.php";
redirecionarSeNaoAdmin();
include "Componentes/paginas/head.php";
include "Componentes/paginas/header.php";

$categorias = consultar($conexao, "SELECT c.*, (SELECT COUNT(*) FROM musica_categoria mc WHERE mc.categoria_id = c.categoria_id) AS total FROM categoria c ORDER BY c.categoria_tipo, c.categoria_ordem, c.categoria_nome");
?>
<div class="manage-page">
    <header class="page-head">
        <a class="btn-pill btn-ghost small" href="admin.php"><?= icone('chevron-left', 'inline') ?> Painel</a>
        <h1>Gêneros e humores</h1>
        <p class="muted">Aparecem como cartões coloridos em Buscar e podem ser marcados nas músicas.</p>
    </header>

    <section class="panel">
        <h2>Nova categoria</h2>
        <form class="form-inline" action="api/categorias.php" data-api-form data-reload>
            <input type="hidden" name="acao" value="salvar">
            <label class="field"><span>Nome</span><input type="text" name="nome" required maxlength="60"></label>
            <label class="field"><span>Tipo</span><select name="tipo"><option value="genero">Gênero</option><option value="humor">Humor / momento</option></select></label>
            <label class="field"><span>Cor</span><input type="color" name="cor" value="#d4af37"></label>
            <button type="submit" class="btn-pill btn-accent">Adicionar</button>
        </form>
    </section>

    <div class="genre-grid">
        <?php foreach ($categorias as $c): ?>
        <div class="genre-card is-editable" style="--genre: <?= e($c['categoria_cor']) ?>">
            <span><?= e($c['categoria_nome']) ?></span>
            <small><?= $c['categoria_tipo'] === 'humor' ? 'Humor' : 'Gênero' ?> • <?= pluralizar((int) $c['total'], 'música', 'músicas') ?></small>
            <details>
                <summary>Editar</summary>
                <form class="form-stack" action="api/categorias.php" data-api-form data-reload>
                    <input type="hidden" name="acao" value="salvar">
                    <input type="hidden" name="categoria_id" value="<?= (int) $c['categoria_id'] ?>">
                    <input type="text" name="nome" required maxlength="60" value="<?= e($c['categoria_nome']) ?>" aria-label="Nome">
                    <select name="tipo" aria-label="Tipo"><option value="genero" <?= $c['categoria_tipo'] === 'genero' ? 'selected' : '' ?>>Gênero</option><option value="humor" <?= $c['categoria_tipo'] === 'humor' ? 'selected' : '' ?>>Humor</option></select>
                    <input type="color" name="cor" value="<?= e($c['categoria_cor']) ?>" aria-label="Cor">
                    <button type="submit" class="btn-pill btn-light small">Salvar</button>
                </form>
                <form action="api/categorias.php" data-api-form data-reload data-confirm="Excluir “<?= e($c['categoria_nome']) ?>”? As músicas não são apagadas.">
                    <input type="hidden" name="acao" value="excluir">
                    <input type="hidden" name="categoria_id" value="<?= (int) $c['categoria_id'] ?>">
                    <button type="submit" class="btn-pill btn-ghost danger small">Excluir</button>
                </form>
            </details>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php include "Componentes/paginas/footer.php"; ?>
