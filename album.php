<?php
// Página de álbum: capa grande, título, artista, ano, estatísticas e faixas.
$paginaId = 'album';
$paginaSpa = true;
include "Componentes/paginas/php/app.php";

$album = consultarUm($conexao, "SELECT al.*, a.artista_nome, a.artista_image, a.artista_image_pos FROM album al INNER JOIN artista a ON a.artista_id = al.album_artista WHERE al.album_id = ?", "i", [(int) ($_GET['id'] ?? 0)]);
$tituloPagina = $album ? $album['album_titulo'] . ' - ' . $album['artista_nome'] : 'Álbum não encontrado';
include "Componentes/paginas/head.php";
include "Componentes/paginas/header.php";

if (!$album):
    echo renderVazio('album', 'Álbum não encontrado', 'Ele pode ter sido removido pelo artista.', '<a class="btn-pill btn-light" href="index.php">Ir para o início</a>');
else:
    $musicas = consultarFaixas($conexao, "WHERE m.album_id = ? ORDER BY COALESCE(m.musica_faixa, 9999), m.musica_id", "i", [$album['album_id']]);
    $duracao = array_sum(array_column($musicas, 'musica_duracao'));
    $tipos = ['album' => 'Álbum', 'ep' => 'EP', 'single' => 'Single'];
    $outros = consultar($conexao, "SELECT al.*, a.artista_nome FROM album al INNER JOIN artista a ON a.artista_id = al.album_artista WHERE al.album_artista = ? AND al.album_id <> ? ORDER BY al.album_ano DESC, al.album_data DESC", "ii", [$album['album_artista'], $album['album_id']]);
    $meta = '<a class="hero-artist" href="artista.php?id=' . (int) $album['album_artista'] . '"><img src="' . e(imagemOuPadrao($album['artista_image'])) . '" alt=""' . estiloPosicao($album['artista_image_pos']) . '>' . e($album['artista_nome']) . '</a>'
        . ($album['album_ano'] ? '<span>' . (int) $album['album_ano'] . '</span>' : '')
        . '<span>' . pluralizar(count($musicas), 'música', 'músicas') . ($duracao ? ', <span class="muted-strong">' . formatarDuracaoTotal($duracao) . '</span>' : '') . '</span>';
?>
<div class="collection">
    <?= renderCabecalhoColecao(['tipo' => $tipos[$album['album_tipo']] ?? 'Álbum', 'titulo' => $album['album_titulo'], 'imagem' => $album['album_capa'], 'meta' => $meta]) ?>

    <div class="action-bar">
        <button type="button" class="play-big" data-action="play-all" aria-label="Tocar <?= e($album['album_titulo']) ?>" <?= $musicas ? '' : 'disabled' ?>><?= icone('play', 'icon-play') ?><?= icone('pause', 'icon-pause') ?></button>
        <button type="button" class="icon-btn big" data-action="shuffle-all" aria-label="Tocar em ordem aleatória" title="Tocar em ordem aleatória" <?= $musicas ? '' : 'disabled' ?>><?= icone('shuffle') ?></button>
        <?php if (podeEditarArtista($album['album_artista'])): ?>
        <a class="btn-pill btn-ghost" href="musicas.php?aba=albuns#album-<?= (int) $album['album_id'] ?>" data-no-spa><?= icone('edit') ?> Editar álbum</a>
        <?php endif; ?>
    </div>

    <?php if ($musicas): ?>
        <?= renderListaFaixas($conexao, $musicas, ['album' => false, 'capa' => false, 'numero' => 'faixa', 'contexto' => 'album:' . $album['album_id']]) ?>
    <?php else: ?>
        <?= renderVazio('music', 'Este álbum ainda não tem músicas') ?>
    <?php endif; ?>

    <p class="fine-print"><?= e(date('d/m/Y', strtotime($album['album_data']))) ?> • © <?= $album['album_ano'] ? (int) $album['album_ano'] : date('Y', strtotime($album['album_data'])) ?> <?= e($album['artista_nome']) ?></p>

    <?= renderCarrossel('Mais de ' . $album['artista_nome'], implode('', array_map('renderCardAlbum', $outros)), 'artista.php?id=' . (int) $album['album_artista']) ?>
</div>
<?php
endif;
include "Componentes/paginas/footer.php";
