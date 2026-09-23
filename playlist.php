<?php
// Página de playlist: capa (ou mosaico), nome, dono, estatísticas, faixas
// e, para o dono, editar/excluir e remover músicas.
$paginaId = 'playlist';
$paginaSpa = true;
include "Componentes/paginas/php/app.php";

$playlist = consultarUm($conexao, "SELECT p.*, u.usuario_nome AS dono_nome, u.usuario_foto AS dono_foto, u.usuario_foto_pos AS dono_foto_pos FROM playlist p INNER JOIN usuarios u ON u.usuario_id = p.usuario_id WHERE p.playlist_id = ?", "i", [(int) ($_GET['id'] ?? 0)]);
$ehDono = $playlist && (int) $playlist['usuario_id'] === (int) ($_SESSION['usuario_id'] ?? 0);
if ($playlist && !$playlist['playlist_publica'] && !$ehDono) {
    $playlist = null; // privada de outra pessoa: trata como inexistente
}
$tituloPagina = $playlist ? $playlist['playlist_nome'] : 'Playlist não encontrada';
include "Componentes/paginas/head.php";
include "Componentes/paginas/header.php";

if (!$playlist):
    echo renderVazio('music', 'Playlist não encontrada', 'Ela pode ser privada ou ter sido excluída.', '<a class="btn-pill btn-light" href="biblioteca.php">Ir para a biblioteca</a>');
else:
    $musicas = consultarFaixas($conexao, "INNER JOIN playlist_musica pm ON pm.musica_id = m.musica_id WHERE pm.playlist_id = ? ORDER BY pm.posicao, pm.adicionada", "i", [$playlist['playlist_id']], 'pm.adicionada');
    $duracao = array_sum(array_column($musicas, 'musica_duracao'));
    $mosaico = capasPlaylists($conexao, [$playlist['playlist_id']])[(int) $playlist['playlist_id']] ?? [];
    $meta = '<span class="hero-owner">' . ($playlist['dono_foto'] ? '<img src="' . e($playlist['dono_foto']) . '" alt="" referrerpolicy="no-referrer"' . estiloPosicao($playlist['dono_foto_pos']) . '>' : '') . e($playlist['dono_nome']) . '</span>'
        . '<span>' . pluralizar(count($musicas), 'música', 'músicas') . ($duracao ? ', <span class="muted-strong">' . formatarDuracaoTotal($duracao) . '</span>' : '') . '</span>';
    $dadosEdicao = json_encode([
        'id' => (int) $playlist['playlist_id'],
        'nome' => $playlist['playlist_nome'],
        'descricao' => $playlist['playlist_descricao'] ?? '',
        'capa' => $playlist['playlist_capa'],
        'publica' => (bool) $playlist['playlist_publica'],
    ], JSON_UNESCAPED_UNICODE);
?>
<div class="collection">
    <?= renderCabecalhoColecao([
        'tipo' => $playlist['playlist_publica'] ? 'Playlist pública' : 'Playlist privada',
        'titulo' => $playlist['playlist_nome'],
        'imagem' => $playlist['playlist_capa'],
        'mosaico' => $playlist['playlist_capa'] ? [] : $mosaico,
        'descricao' => $playlist['playlist_descricao'],
        'meta' => $meta,
    ]) ?>

    <div class="action-bar">
        <button type="button" class="play-big" data-action="play-all" aria-label="Tocar <?= e($playlist['playlist_nome']) ?>" <?= $musicas ? '' : 'disabled' ?>><?= icone('play', 'icon-play') ?><?= icone('pause', 'icon-pause') ?></button>
        <button type="button" class="icon-btn big" data-action="shuffle-all" aria-label="Tocar em ordem aleatória" title="Tocar em ordem aleatória" <?= $musicas ? '' : 'disabled' ?>><?= icone('shuffle') ?></button>
        <?php if ($ehDono): ?>
        <button type="button" class="btn-pill btn-ghost" data-action="edit-playlist" data-playlist="<?= e($dadosEdicao) ?>"><?= icone('edit') ?> Editar detalhes</button>
        <button type="button" class="btn-pill btn-ghost danger" data-action="delete-playlist" data-id="<?= (int) $playlist['playlist_id'] ?>" data-nome="<?= e($playlist['playlist_nome']) ?>"><?= icone('trash') ?> Excluir</button>
        <?php endif; ?>
    </div>

    <?php if ($musicas): ?>
        <?php
        $lista = renderListaFaixas($conexao, $musicas, [
            'contexto' => 'playlist:' . $playlist['playlist_id'],
            'playlistId' => $playlist['playlist_id'],
            'extra' => function ($linha) { return e(date('d/m/Y', strtotime($linha['adicionada']))); },
            'cabecalhoExtra' => 'Adicionada em',
        ]);
        // Marca a lista como do dono para o menu mostrar "Remover desta playlist".
        echo $ehDono ? preg_replace('/data-playlist-id="/', 'data-owner="1" data-playlist-id="', $lista, 1) : $lista;
        ?>
    <?php elseif ($ehDono): ?>
        <?= renderVazio('music', 'Vamos adicionar músicas', 'Use o menu ⋯ de qualquer música e escolha “Adicionar à playlist”.', '<a class="btn-pill btn-light" href="buscar.php">Buscar músicas</a>') ?>
    <?php else: ?>
        <?= renderVazio('music', 'Esta playlist está vazia') ?>
    <?php endif; ?>
</div>
<?php
endif;
include "Componentes/paginas/footer.php";
