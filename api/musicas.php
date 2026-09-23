<?php
// Gerenciamento de músicas e álbuns (dono do perfil de artista ou admin).
// POST acao=criar_musica|editar_musica|excluir_musica|criar_album|editar_album|excluir_album
include __DIR__ . '/../Componentes/paginas/php/app.php';
include_once __DIR__ . '/../Componentes/paginas/php/verificarPerfilCompleto.php';
exigirPostApi();
exigirLoginApi();

$dados = dadosRequisicao();
$usuario = usuarioAtual();

// Artista em nome de quem a ação é feita: o admin escolhe; os demais usam o
// próprio perfil de artista (criado automaticamente se ainda não existir).
function artistaDaAcao($conexao, $dados, $usuario) {
    if (ehAdmin() && !empty($dados['artista_id'])) {
        $id = (int) $dados['artista_id'];
        if (!consultarUm($conexao, "SELECT artista_id FROM artista WHERE artista_id = ?", "i", [$id])) {
            jsonErro('Artista não encontrado', 404);
        }
        return $id;
    }
    if (!$usuario['artista_id']) {
        completarPerfilAutomatico($usuario['usuario_id'], $conexao);
        $usuario = consultarUm($conexao, "SELECT artista_id FROM usuarios WHERE usuario_id = ?", "i", [$usuario['usuario_id']]);
    }
    if (empty($usuario['artista_id'])) {
        jsonErro('Não foi possível encontrar seu perfil de artista');
    }
    return (int) $usuario['artista_id'];
}

function musicaEditavel($conexao, $musicaId) {
    $musica = consultarUm($conexao, "SELECT * FROM musica WHERE musica_id = ?", "i", [$musicaId]);
    if (!$musica) {
        jsonErro('Música não encontrada', 404);
    }
    if (!podeEditarArtista($musica['musica_artista'])) {
        jsonErro('Você não pode alterar esta música', 403);
    }
    return $musica;
}

function albumEditavel($conexao, $albumId) {
    $album = consultarUm($conexao, "SELECT * FROM album WHERE album_id = ?", "i", [$albumId]);
    if (!$album) {
        jsonErro('Álbum não encontrado', 404);
    }
    if (!podeEditarArtista($album['album_artista'])) {
        jsonErro('Você não pode alterar este álbum', 403);
    }
    return $album;
}

// Álbum escolhido no formulário, validando que é do mesmo artista.
function albumDoFormulario($conexao, $dados, $artistaId) {
    $albumId = (int) ($dados['album_id'] ?? 0);
    if (!$albumId) {
        return null;
    }
    $album = consultarUm($conexao, "SELECT album_id, album_capa FROM album WHERE album_id = ? AND album_artista = ?", "ii", [$albumId, $artistaId]);
    if (!$album) {
        jsonErro('O álbum escolhido não pertence a este artista');
    }
    return $album;
}

function salvarCategorias($conexao, $musicaId, $categorias) {
    executar($conexao, "DELETE FROM musica_categoria WHERE musica_id = ?", "i", [$musicaId]);
    foreach (array_unique(array_map('intval', (array) $categorias)) as $categoriaId) {
        if ($categoriaId > 0) {
            executar($conexao, "INSERT IGNORE INTO musica_categoria (musica_id, categoria_id) SELECT ?, categoria_id FROM categoria WHERE categoria_id = ?", "ii", [$musicaId, $categoriaId]);
        }
    }
}

function validarTitulo($titulo, $rotulo = 'o título') {
    $titulo = trim((string) $titulo);
    if ($titulo === '' || mb_strlen($titulo) > 100) {
        jsonErro("Informe $rotulo (até 100 caracteres)");
    }
    return $titulo;
}

function duracaoInformada($dados) {
    $d = (int) ($dados['duracao'] ?? 0);
    return $d > 0 && $d < 6 * 3600 ? $d : null;
}

function upload($campo, $tipo, $rotulo) {
    try {
        return salvarUploadValidado($_FILES[$campo] ?? null, $tipo);
    } catch (Exception $e) {
        jsonErro("$rotulo: " . $e->getMessage());
    }
}

switch ($dados['acao'] ?? '') {
    case 'criar_musica':
        $artistaId = artistaDaAcao($conexao, $dados, $usuario);
        $titulo = validarTitulo($dados['titulo'] ?? '');
        $album = albumDoFormulario($conexao, $dados, $artistaId);
        $audio = upload('audio', 'audio', 'Áudio');
        if (!$audio) {
            jsonErro('Envie o arquivo de áudio');
        }
        $capa = upload('capa', 'imagem', 'Capa') ?: ($album['album_capa'] ?? null);
        if (!$capa) {
            removerArquivoArmazenado($audio);
            jsonErro('Envie uma capa ou escolha um álbum que já tenha capa');
        }
        $baixa = upload('audio_baixa', 'audio', 'Versão compacta');
        $faixa = (int) ($dados['faixa'] ?? 0) ?: null;
        $letra = trim($dados['letra'] ?? '') ?: null;
        $albumId = $album['album_id'] ?? null;
        $duracao = duracaoInformada($dados);
        $ok = executar($conexao, "INSERT INTO musica (musica_titulo, musica_capa, musica_link, musica_link_baixa, musica_artista, album_id, musica_faixa, musica_letra, musica_duracao) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
            "ssssiiisi", [$titulo, $capa, $audio, $baixa, $artistaId, $albumId, $faixa, $letra, $duracao]);
        if (!$ok) {
            jsonErro('Erro ao salvar a música');
        }
        $musicaId = mysqli_insert_id($conexao);
        salvarCategorias($conexao, $musicaId, $dados['categorias'] ?? []);
        jsonResposta(['success' => true, 'id' => $musicaId, 'message' => 'Música publicada!']);

    case 'editar_musica':
        $musica = musicaEditavel($conexao, (int) ($dados['musica_id'] ?? 0));
        $titulo = validarTitulo($dados['titulo'] ?? '');
        $album = albumDoFormulario($conexao, $dados, $musica['musica_artista']);
        $capa = $musica['musica_capa'];
        $audio = $musica['musica_link'];
        $baixa = $musica['musica_link_baixa'];
        $duracao = $musica['musica_duracao'];
        if ($nova = upload('capa', 'imagem', 'Capa')) {
            removerArquivoArmazenado($capa);
            $capa = $nova;
        }
        if ($novo = upload('audio', 'audio', 'Áudio')) {
            removerArquivoArmazenado($audio);
            $audio = $novo;
            $duracao = duracaoInformada($dados); // áudio novo: duração nova
        }
        if ($novaBaixa = upload('audio_baixa', 'audio', 'Versão compacta')) {
            removerArquivoArmazenado($baixa);
            $baixa = $novaBaixa;
        } elseif (!empty($dados['remover_baixa'])) {
            removerArquivoArmazenado($baixa);
            $baixa = null;
        }
        $faixa = (int) ($dados['faixa'] ?? 0) ?: null;
        $albumId = $album['album_id'] ?? null;
        $letra = array_key_exists('letra', $dados) ? (trim($dados['letra']) ?: null) : $musica['musica_letra'];
        executar($conexao, "UPDATE musica SET musica_titulo = ?, musica_capa = ?, musica_link = ?, musica_link_baixa = ?, album_id = ?, musica_faixa = ?, musica_letra = ?, musica_duracao = ? WHERE musica_id = ?",
            "ssssiisii", [$titulo, $capa, $audio, $baixa, $albumId, $faixa, $letra, $duracao, $musica['musica_id']]);
        salvarCategorias($conexao, $musica['musica_id'], $dados['categorias'] ?? []);
        jsonResposta(['success' => true, 'message' => 'Música atualizada']);

    case 'salvar_letra':
        $musica = musicaEditavel($conexao, (int) ($dados['musica_id'] ?? 0));
        $letra = trim($dados['letra'] ?? '') ?: null;
        executar($conexao, "UPDATE musica SET musica_letra = ? WHERE musica_id = ?", "si", [$letra, $musica['musica_id']]);
        jsonResposta(['success' => true, 'message' => $letra ? 'Letra salva' : 'Letra removida']);

    case 'excluir_musica':
        $musica = musicaEditavel($conexao, (int) ($dados['musica_id'] ?? 0));
        executar($conexao, "DELETE FROM musica WHERE musica_id = ?", "i", [$musica['musica_id']]);
        removerArquivoArmazenado($musica['musica_link']);
        removerArquivoArmazenado($musica['musica_link_baixa']);
        // A capa pode ser compartilhada com o álbum ou outras músicas.
        if (!consultarUm($conexao, "SELECT 1 FROM musica WHERE musica_capa = ? UNION SELECT 1 FROM album WHERE album_capa = ?", "ss", [$musica['musica_capa'], $musica['musica_capa']])) {
            removerArquivoArmazenado($musica['musica_capa']);
        }
        jsonResposta(['success' => true, 'message' => 'Música excluída', 'redirect' => 'musicas.php?aba=musicas']);

    case 'criar_album':
    case 'editar_album':
        $editando = $dados['acao'] === 'editar_album';
        $album = $editando ? albumEditavel($conexao, (int) ($dados['album_id'] ?? 0)) : null;
        $artistaId = $editando ? (int) $album['album_artista'] : artistaDaAcao($conexao, $dados, $usuario);
        $titulo = validarTitulo($dados['titulo'] ?? '', 'o nome do álbum');
        $tipo = in_array($dados['tipo'] ?? '', ['album', 'ep', 'single'], true) ? $dados['tipo'] : 'album';
        $ano = (int) ($dados['ano'] ?? 0);
        $ano = $ano >= 1900 && $ano <= (int) date('Y') + 1 ? $ano : null;
        $capa = upload('capa', 'imagem', 'Capa');
        if ($editando) {
            if ($capa) {
                removerArquivoArmazenado($album['album_capa']);
            } else {
                $capa = $album['album_capa'];
            }
            executar($conexao, "UPDATE album SET album_titulo = ?, album_tipo = ?, album_ano = ?, album_capa = ? WHERE album_id = ?", "ssisi", [$titulo, $tipo, $ano, $capa, $album['album_id']]);
            jsonResposta(['success' => true, 'message' => 'Álbum atualizado']);
        }
        executar($conexao, "INSERT INTO album (album_titulo, album_capa, album_artista, album_tipo, album_ano) VALUES (?, ?, ?, ?, ?)", "ssisi", [$titulo, $capa, $artistaId, $tipo, $ano]);
        jsonResposta(['success' => true, 'id' => mysqli_insert_id($conexao), 'message' => 'Álbum criado']);

    case 'excluir_album':
        $album = albumEditavel($conexao, (int) ($dados['album_id'] ?? 0));
        // As músicas continuam existindo, como singles (album_id vira NULL).
        executar($conexao, "DELETE FROM album WHERE album_id = ?", "i", [$album['album_id']]);
        if (!consultarUm($conexao, "SELECT 1 FROM musica WHERE musica_capa = ?", "s", [(string) $album['album_capa']])) {
            removerArquivoArmazenado($album['album_capa']);
        }
        jsonResposta(['success' => true, 'message' => 'Álbum excluído. As músicas continuam publicadas como singles.']);

    default:
        jsonErro('Ação inválida');
}
