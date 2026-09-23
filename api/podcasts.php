<?php
// Gerenciamento de podcasts e episódios (dono do podcast ou admin).
// POST acao=salvar_podcast|excluir_podcast|salvar_episodio|excluir_episodio
include __DIR__ . '/../Componentes/paginas/php/app.php';
exigirPostApi();
exigirLoginApi();

$dados = dadosRequisicao();
$uid = (int) $_SESSION['usuario_id'];

function podcastEditavel($conexao, $podcastId, $uid) {
    $podcast = consultarUm($conexao, "SELECT * FROM podcast WHERE podcast_id = ?", "i", [$podcastId]);
    if (!$podcast) {
        jsonErro('Podcast não encontrado', 404);
    }
    if (!ehAdmin() && (int) $podcast['usuario_id'] !== $uid) {
        jsonErro('Você não pode alterar este podcast', 403);
    }
    return $podcast;
}

function uploadPodcast($campo, $tipo, $rotulo) {
    try {
        return salvarUploadValidado($_FILES[$campo] ?? null, $tipo);
    } catch (Exception $e) {
        jsonErro("$rotulo: " . $e->getMessage());
    }
}

switch ($dados['acao'] ?? '') {
    case 'salvar_podcast':
        $titulo = trim($dados['titulo'] ?? '');
        $autor = trim($dados['autor'] ?? '');
        if ($titulo === '' || mb_strlen($titulo) > 150) {
            jsonErro('Informe o nome do podcast (até 150 caracteres)');
        }
        if ($autor === '' || mb_strlen($autor) > 100) {
            jsonErro('Informe o autor/apresentador (até 100 caracteres)');
        }
        $descricao = mb_substr(trim($dados['descricao'] ?? ''), 0, 3000);
        $capa = uploadPodcast('capa', 'imagem', 'Capa');
        if (!empty($dados['podcast_id'])) {
            $podcast = podcastEditavel($conexao, (int) $dados['podcast_id'], $uid);
            if ($capa) {
                removerArquivoArmazenado($podcast['podcast_capa']);
            } else {
                $capa = $podcast['podcast_capa'];
            }
            executar($conexao, "UPDATE podcast SET podcast_titulo = ?, podcast_autor = ?, podcast_descricao = ?, podcast_capa = ? WHERE podcast_id = ?", "ssssi", [$titulo, $autor, $descricao, $capa, $podcast['podcast_id']]);
            jsonResposta(['success' => true, 'message' => 'Podcast atualizado']);
        }
        if (!$capa) {
            jsonErro('Envie uma capa para o podcast');
        }
        executar($conexao, "INSERT INTO podcast (podcast_titulo, podcast_autor, podcast_descricao, podcast_capa, usuario_id) VALUES (?, ?, ?, ?, ?)", "ssssi", [$titulo, $autor, $descricao, $capa, $uid]);
        $id = mysqli_insert_id($conexao);
        jsonResposta(['success' => true, 'id' => $id, 'message' => 'Podcast criado! Agora publique o primeiro episódio.', 'redirect' => 'gerenciarPodcasts.php?podcast=' . $id]);

    case 'excluir_podcast':
        $podcast = podcastEditavel($conexao, (int) ($dados['podcast_id'] ?? 0), $uid);
        $episodios = consultar($conexao, "SELECT episodio_audio FROM episodio WHERE podcast_id = ?", "i", [$podcast['podcast_id']]);
        executar($conexao, "DELETE FROM podcast WHERE podcast_id = ?", "i", [$podcast['podcast_id']]);
        foreach ($episodios as $ep) {
            removerArquivoArmazenado($ep['episodio_audio']);
        }
        removerArquivoArmazenado($podcast['podcast_capa']);
        jsonResposta(['success' => true, 'message' => 'Podcast excluído', 'redirect' => 'gerenciarPodcasts.php']);

    case 'salvar_episodio':
        $podcast = podcastEditavel($conexao, (int) ($dados['podcast_id'] ?? 0), $uid);
        $titulo = trim($dados['titulo'] ?? '');
        if ($titulo === '' || mb_strlen($titulo) > 200) {
            jsonErro('Informe o título do episódio (até 200 caracteres)');
        }
        $descricao = mb_substr(trim($dados['descricao'] ?? ''), 0, 5000);
        $duracao = (int) ($dados['duracao'] ?? 0);
        $duracao = $duracao > 0 && $duracao < 12 * 3600 ? $duracao : null;

        // Áudio: arquivo enviado ou link externo (útil para episódios longos,
        // maiores que o limite de upload da hospedagem).
        $audio = uploadPodcast('audio', 'audio', 'Áudio');
        $url = trim($dados['audio_url'] ?? '');
        if (!$audio && $url !== '') {
            if (!preg_match('#^https://#i', $url) || !filter_var($url, FILTER_VALIDATE_URL)) {
                jsonErro('O link do áudio precisa ser um endereço https:// válido');
            }
            $audio = $url;
        }

        if (!empty($dados['episodio_id'])) {
            $episodio = consultarUm($conexao, "SELECT * FROM episodio WHERE episodio_id = ? AND podcast_id = ?", "ii", [(int) $dados['episodio_id'], $podcast['podcast_id']]);
            if (!$episodio) {
                jsonErro('Episódio não encontrado', 404);
            }
            if ($audio) {
                removerArquivoArmazenado($episodio['episodio_audio']);
            } else {
                $audio = $episodio['episodio_audio'];
                $duracao = $duracao ?: $episodio['episodio_duracao'];
            }
            executar($conexao, "UPDATE episodio SET episodio_titulo = ?, episodio_descricao = ?, episodio_audio = ?, episodio_duracao = ? WHERE episodio_id = ?", "sssii", [$titulo, $descricao, $audio, $duracao, $episodio['episodio_id']]);
            jsonResposta(['success' => true, 'message' => 'Episódio atualizado']);
        }
        if (!$audio) {
            jsonErro('Envie o arquivo de áudio ou informe um link');
        }
        executar($conexao, "INSERT INTO episodio (podcast_id, episodio_titulo, episodio_descricao, episodio_audio, episodio_duracao) VALUES (?, ?, ?, ?, ?)", "isssi", [$podcast['podcast_id'], $titulo, $descricao, $audio, $duracao]);
        jsonResposta(['success' => true, 'message' => 'Episódio publicado!']);

    case 'excluir_episodio':
        $episodio = consultarUm($conexao, "SELECT e.*, p.usuario_id FROM episodio e INNER JOIN podcast p ON p.podcast_id = e.podcast_id WHERE e.episodio_id = ?", "i", [(int) ($dados['episodio_id'] ?? 0)]);
        if (!$episodio) {
            jsonErro('Episódio não encontrado', 404);
        }
        podcastEditavel($conexao, (int) $episodio['podcast_id'], $uid);
        executar($conexao, "DELETE FROM episodio WHERE episodio_id = ?", "i", [$episodio['episodio_id']]);
        removerArquivoArmazenado($episodio['episodio_audio']);
        jsonResposta(['success' => true, 'message' => 'Episódio excluído']);

    default:
        jsonErro('Ação inválida');
}
