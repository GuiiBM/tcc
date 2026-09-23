<?php
// CRUD de playlists do usuário.
// GET  ?acao=listar                                   -> playlists do usuário
// POST acao=criar     (nome, descricao, publica, capa[arquivo])
// POST acao=editar    (playlist_id, nome, descricao, publica, capa, remover_capa)
// POST acao=excluir   (playlist_id)
// POST acao=adicionar (playlist_id, musica_id)
// POST acao=remover   (playlist_id, musica_id)
include __DIR__ . '/../Componentes/paginas/php/app.php';
exigirLoginApi();

$usuarioId = (int) $_SESSION['usuario_id'];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $playlists = array_map(function ($p) {
        return [
            'id' => (int) $p['playlist_id'],
            'nome' => $p['playlist_nome'],
            'capa' => $p['playlist_capa'],
            'publica' => (bool) $p['playlist_publica'],
            'total' => (int) $p['total'],
        ];
    }, playlistsDoUsuario($conexao, $usuarioId));
    jsonResposta(['success' => true, 'playlists' => $playlists]);
}

exigirPostApi();
$dados = dadosRequisicao();
$acao = $dados['acao'] ?? '';

// Garante que a playlist existe e pertence ao usuário logado.
function playlistDoDono($conexao, $playlistId, $usuarioId) {
    $playlist = consultarUm($conexao, "SELECT * FROM playlist WHERE playlist_id = ?", "i", [$playlistId]);
    if (!$playlist) {
        jsonErro('Playlist não encontrada', 404);
    }
    if ((int) $playlist['usuario_id'] !== $usuarioId) {
        jsonErro('Você só pode alterar suas próprias playlists', 403);
    }
    return $playlist;
}

function validarNomePlaylist($nome) {
    $nome = trim((string) $nome);
    if ($nome === '') {
        jsonErro('Dê um nome para a playlist');
    }
    if (mb_strlen($nome) > 100) {
        jsonErro('O nome pode ter no máximo 100 caracteres');
    }
    return $nome;
}

switch ($acao) {
    case 'criar':
        $nome = validarNomePlaylist($dados['nome'] ?? '');
        $descricao = mb_substr(trim($dados['descricao'] ?? ''), 0, 300);
        $publica = !empty($dados['publica']) ? 1 : 0;
        try {
            $capa = salvarUploadValidado($_FILES['capa'] ?? null, 'imagem');
        } catch (Exception $e) {
            jsonErro('Capa: ' . $e->getMessage());
        }
        executar($conexao, "INSERT INTO playlist (usuario_id, playlist_nome, playlist_descricao, playlist_capa, playlist_publica) VALUES (?, ?, ?, ?, ?)", "isssi", [$usuarioId, $nome, $descricao, $capa, $publica]);
        jsonResposta(['success' => true, 'id' => mysqli_insert_id($conexao), 'nome' => $nome, 'message' => 'Playlist criada']);

    case 'editar':
        $playlist = playlistDoDono($conexao, (int) ($dados['playlist_id'] ?? 0), $usuarioId);
        $nome = validarNomePlaylist($dados['nome'] ?? '');
        $descricao = mb_substr(trim($dados['descricao'] ?? ''), 0, 300);
        $publica = !empty($dados['publica']) ? 1 : 0;
        $capa = $playlist['playlist_capa'];
        try {
            $novaCapa = salvarUploadValidado($_FILES['capa'] ?? null, 'imagem');
        } catch (Exception $e) {
            jsonErro('Capa: ' . $e->getMessage());
        }
        if ($novaCapa) {
            removerArquivoArmazenado($capa);
            $capa = $novaCapa;
        } elseif (!empty($dados['remover_capa'])) {
            removerArquivoArmazenado($capa);
            $capa = null;
        }
        executar($conexao, "UPDATE playlist SET playlist_nome = ?, playlist_descricao = ?, playlist_capa = ?, playlist_publica = ? WHERE playlist_id = ?", "sssii", [$nome, $descricao, $capa, $publica, $playlist['playlist_id']]);
        jsonResposta(['success' => true, 'message' => 'Playlist atualizada']);

    case 'excluir':
        $playlist = playlistDoDono($conexao, (int) ($dados['playlist_id'] ?? 0), $usuarioId);
        executar($conexao, "DELETE FROM playlist WHERE playlist_id = ?", "i", [$playlist['playlist_id']]);
        removerArquivoArmazenado($playlist['playlist_capa']);
        jsonResposta(['success' => true, 'message' => 'Playlist excluída']);

    case 'adicionar':
        $playlist = playlistDoDono($conexao, (int) ($dados['playlist_id'] ?? 0), $usuarioId);
        $musicaId = (int) ($dados['musica_id'] ?? 0);
        if (!consultarUm($conexao, "SELECT musica_id FROM musica WHERE musica_id = ?", "i", [$musicaId])) {
            jsonErro('Música não encontrada', 404);
        }
        if (consultarUm($conexao, "SELECT 1 FROM playlist_musica WHERE playlist_id = ? AND musica_id = ?", "ii", [$playlist['playlist_id'], $musicaId])) {
            jsonErro('Essa música já está em “' . $playlist['playlist_nome'] . '”', 409);
        }
        $posicao = consultarUm($conexao, "SELECT COALESCE(MAX(posicao), 0) + 1 AS proxima FROM playlist_musica WHERE playlist_id = ?", "i", [$playlist['playlist_id']]);
        executar($conexao, "INSERT INTO playlist_musica (playlist_id, musica_id, posicao) VALUES (?, ?, ?)", "iii", [$playlist['playlist_id'], $musicaId, $posicao['proxima']]);
        jsonResposta(['success' => true, 'message' => 'Adicionada a “' . $playlist['playlist_nome'] . '”']);

    case 'remover':
        $playlist = playlistDoDono($conexao, (int) ($dados['playlist_id'] ?? 0), $usuarioId);
        executar($conexao, "DELETE FROM playlist_musica WHERE playlist_id = ? AND musica_id = ?", "ii", [$playlist['playlist_id'], (int) ($dados['musica_id'] ?? 0)]);
        jsonResposta(['success' => true, 'message' => 'Removida da playlist']);

    default:
        jsonErro('Ação inválida');
}
