<?php
// Lista de faixas de uma coleção, para os botões de "play" dos cartões.
// GET ?tipo=album|playlist|artista|curtidas|genero|podcast&id=N
include __DIR__ . '/../Componentes/paginas/php/app.php';

$tipo = $_GET['tipo'] ?? '';
$id = (int) ($_GET['id'] ?? 0);

switch ($tipo) {
    case 'album':
        $linhas = consultarFaixas($conexao, "WHERE m.album_id = ? ORDER BY COALESCE(m.musica_faixa, 9999), m.musica_id", "i", [$id]);
        break;

    case 'artista':
        // Populares primeiro (mais ouvidas), como no botão play da página do artista.
        $linhas = consultarFaixas($conexao, "WHERE m.musica_artista = ? ORDER BY (SELECT COUNT(*) FROM visualizacoes v WHERE v.musica_id = m.musica_id) DESC, m.musica_id DESC", "i", [$id]);
        break;

    case 'genero':
        $linhas = consultarFaixas($conexao, "INNER JOIN musica_categoria mc ON mc.musica_id = m.musica_id WHERE mc.categoria_id = ? ORDER BY m.musica_data_adicao DESC", "i", [$id]);
        break;

    case 'playlist':
        $playlist = consultarUm($conexao, "SELECT usuario_id, playlist_publica FROM playlist WHERE playlist_id = ?", "i", [$id]);
        if (!$playlist || (!$playlist['playlist_publica'] && (int) $playlist['usuario_id'] !== (int) ($_SESSION['usuario_id'] ?? 0))) {
            jsonErro('Playlist não encontrada', 404);
        }
        $linhas = consultarFaixas($conexao, "INNER JOIN playlist_musica pm ON pm.musica_id = m.musica_id WHERE pm.playlist_id = ? ORDER BY pm.posicao, pm.adicionada", "i", [$id]);
        break;

    case 'curtidas':
        exigirLoginApi();
        $linhas = consultarFaixas($conexao, "INNER JOIN curtidas c ON c.musica_id = m.musica_id WHERE c.usuario_id = ? AND c.tipo_curtida = 'curtida' ORDER BY c.data_curtida DESC", "i", [$_SESSION['usuario_id']]);
        break;

    case 'podcast':
        $episodios = consultar($conexao, "SELECT e.*, p.podcast_titulo, p.podcast_capa FROM episodio e INNER JOIN podcast p ON p.podcast_id = e.podcast_id WHERE e.podcast_id = ? ORDER BY e.episodio_data DESC", "i", [$id]);
        jsonResposta(['success' => true, 'faixas' => array_map('episodioParaArray', $episodios)]);

    default:
        jsonErro('Tipo inválido');
}

jsonResposta(['success' => true, 'faixas' => array_map('faixaParaArray', $linhas)]);
