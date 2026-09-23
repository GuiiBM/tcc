<?php
// Atualização do perfil do usuário logado.
// POST acao=dados|foto|foto_artista|sobre|senha|qualidade|artista
// (foto, foto_artista e sobre aceitam "posicao" = enquadramento "x% y%")
include __DIR__ . '/../Componentes/paginas/php/app.php';
exigirPostApi();
exigirLoginApi();

$dados = dadosRequisicao();
$usuario = usuarioAtual();
$uid = (int) $usuario['usuario_id'];

// Apaga um arquivo antigo só se nenhuma foto (de usuário ou artista) usa ele.
function removerSeSemUso($conexao, $caminho) {
    if (!$caminho) {
        return;
    }
    $emUso = consultarUm($conexao, "SELECT 1 FROM usuarios WHERE usuario_foto = ? UNION SELECT 1 FROM artista WHERE artista_image = ? OR artista_sobre = ?", "sss", [$caminho, $caminho, $caminho]);
    if (!$emUso) {
        removerArquivoArmazenado($caminho);
    }
}

function artistaDoUsuario($conexao, $usuario) {
    if (!$usuario['artista_id']) {
        jsonErro('Sua conta não tem página de artista', 403);
    }
    return consultarUm($conexao, "SELECT * FROM artista WHERE artista_id = ?", "i", [$usuario['artista_id']]);
}

// Upload opcional de imagem; encerra com erro se o arquivo for inválido.
function uploadImagemOpcional($campo) {
    try {
        return salvarUploadValidado($_FILES[$campo] ?? null, 'imagem');
    } catch (Exception $e) {
        jsonErro($e->getMessage());
    }
}

switch ($dados['acao'] ?? '') {
    case 'dados':
        $nome = trim($dados['nome'] ?? '');
        if ($nome === '' || mb_strlen($nome) > 100) {
            jsonErro('Informe um nome com até 100 caracteres');
        }
        $idade = ($dados['idade'] ?? '') === '' ? null : (int) $dados['idade'];
        if ($idade !== null && ($idade < 13 || $idade > 120)) {
            jsonErro('Idade deve estar entre 13 e 120 anos');
        }
        $cidade = mb_substr(trim($dados['cidade'] ?? ''), 0, 100);
        $descricao = mb_substr(trim($dados['descricao'] ?? ''), 0, 1000);
        executar($conexao, "UPDATE usuarios SET usuario_nome = ?, usuario_idade = ?, usuario_cidade = ?, usuario_descricao = ? WHERE usuario_id = ?", "sissi", [$nome, $idade, $cidade, $descricao, $uid]);
        $_SESSION['usuario_nome'] = $nome;
        jsonResposta(['success' => true, 'message' => 'Dados atualizados']);

    case 'foto':
        $fotoAntiga = $usuario['usuario_foto'];
        $posicao = posicaoImagem($dados['posicao'] ?? '');
        if (!empty($dados['remover'])) {
            $novaFoto = null;
            $posicao = null;
        } else {
            $novaFoto = uploadImagemOpcional('foto');
            if (!$novaFoto) {
                if (!$fotoAntiga) {
                    jsonErro('Escolha uma imagem');
                }
                // Mesma foto, só o enquadramento mudou (a do artista acompanha se for a mesma imagem).
                executar($conexao, "UPDATE usuarios SET usuario_foto_pos = ? WHERE usuario_id = ?", "si", [$posicao, $uid]);
                if ($usuario['artista_id']) {
                    executar($conexao, "UPDATE artista SET artista_image_pos = ? WHERE artista_id = ? AND artista_image = ?", "sis", [$posicao, $usuario['artista_id'], $fotoAntiga]);
                }
                jsonResposta(['success' => true, 'message' => 'Enquadramento salvo']);
            }
        }
        executar($conexao, "UPDATE usuarios SET usuario_foto = ?, usuario_foto_pos = ? WHERE usuario_id = ?", "ssi", [$novaFoto, $posicao, $uid]);
        // Se a foto do artista era a mesma do perfil (ou a padrão), acompanha a mudança.
        if ($usuario['artista_id']) {
            executar($conexao, "UPDATE artista SET artista_image = ?, artista_image_pos = ? WHERE artista_id = ? AND (artista_image = ? OR artista_image IS NULL OR artista_image = '' OR artista_image = 'Componentes/icones/icone.png')",
                "ssis", [$novaFoto ?: 'Componentes/icones/icone.png', $posicao, $usuario['artista_id'], (string) $fotoAntiga]);
        }
        removerSeSemUso($conexao, $fotoAntiga);
        $_SESSION['usuario_foto'] = $novaFoto;
        jsonResposta(['success' => true, 'message' => $novaFoto ? 'Foto atualizada' : 'Foto removida']);

    case 'foto_artista':
        $artista = artistaDoUsuario($conexao, $usuario);
        $posicao = posicaoImagem($dados['posicao'] ?? '');
        $nova = uploadImagemOpcional('foto');
        if ($nova) {
            executar($conexao, "UPDATE artista SET artista_image = ?, artista_image_pos = ? WHERE artista_id = ?", "ssi", [$nova, $posicao, $artista['artista_id']]);
            removerSeSemUso($conexao, $artista['artista_image']);
            jsonResposta(['success' => true, 'message' => 'Foto do artista atualizada']);
        }
        executar($conexao, "UPDATE artista SET artista_image_pos = ? WHERE artista_id = ?", "si", [$posicao, $artista['artista_id']]);
        jsonResposta(['success' => true, 'message' => 'Enquadramento salvo']);

    case 'sobre':
        $artista = artistaDoUsuario($conexao, $usuario);
        $antiga = $artista['artista_sobre'];
        if (!empty($dados['remover'])) {
            executar($conexao, "UPDATE artista SET artista_sobre = NULL, artista_sobre_pos = NULL WHERE artista_id = ?", "i", [$artista['artista_id']]);
            removerSeSemUso($conexao, $antiga);
            jsonResposta(['success' => true, 'message' => 'Imagem do "Sobre" removida. Sua foto de artista volta a ser usada.']);
        }
        $posicao = posicaoImagem($dados['posicao'] ?? '');
        $nova = uploadImagemOpcional('foto');
        if ($nova) {
            executar($conexao, "UPDATE artista SET artista_sobre = ?, artista_sobre_pos = ? WHERE artista_id = ?", "ssi", [$nova, $posicao, $artista['artista_id']]);
            removerSeSemUso($conexao, $antiga);
            jsonResposta(['success' => true, 'message' => 'Imagem do "Sobre" atualizada']);
        }
        if ($antiga) {
            executar($conexao, "UPDATE artista SET artista_sobre_pos = ? WHERE artista_id = ?", "si", [$posicao, $artista['artista_id']]);
        } else {
            // Sem imagem própria o "Sobre" usa a foto do artista: salva o enquadramento só para esta seção
            // copiando a foto como imagem do "Sobre".
            executar($conexao, "UPDATE artista SET artista_sobre = artista_image, artista_sobre_pos = ? WHERE artista_id = ? AND artista_image IS NOT NULL AND artista_image <> ''", "si", [$posicao, $artista['artista_id']]);
        }
        jsonResposta(['success' => true, 'message' => 'Enquadramento salvo']);

    case 'senha':
        $nova = (string) ($dados['nova_senha'] ?? '');
        if (mb_strlen($nova) < 6) {
            jsonErro('A nova senha precisa ter pelo menos 6 caracteres');
        }
        if ($nova !== (string) ($dados['confirmar_senha'] ?? '')) {
            jsonErro('A confirmação não confere com a nova senha');
        }
        $loginSocial = in_array($_SESSION['login_metodo'] ?? '', ['google', 'facebook', 'apple'], true);
        if (!$loginSocial) {
            if ($bloqueio = minutosBloqueado($conexao, 'senha', 5, 15, (string) $uid)) {
                jsonErro("Muitas tentativas. Tente novamente em $bloqueio minutos.", 429);
            }
            $atual = consultarUm($conexao, "SELECT usuario_senha FROM usuarios WHERE usuario_id = ?", "i", [$uid]);
            if (!$atual || !password_verify((string) ($dados['senha_atual'] ?? ''), $atual['usuario_senha'])) {
                registrarTentativa($conexao, 'senha', (string) $uid);
                jsonErro('Senha atual incorreta');
            }
        }
        executar($conexao, "UPDATE usuarios SET usuario_senha = ? WHERE usuario_id = ?", "si", [hashSenha($nova), $uid]);
        jsonResposta(['success' => true, 'message' => 'Senha alterada']);

    case 'qualidade':
        $qualidade = $dados['qualidade'] ?? 'auto';
        if (!in_array($qualidade, ['auto', 'alta', 'baixa'], true)) {
            jsonErro('Opção inválida');
        }
        executar($conexao, "UPDATE usuarios SET usuario_qualidade = ? WHERE usuario_id = ?", "si", [$qualidade, $uid]);
        jsonResposta(['success' => true, 'message' => 'Preferência salva. Vale a partir da próxima música.', 'qualidade' => $qualidade]);

    case 'artista':
        $artista = artistaDoUsuario($conexao, $usuario);
        $nome = trim($dados['artista_nome'] ?? '');
        if ($nome === '' || mb_strlen($nome) > 100) {
            jsonErro('Informe o nome artístico (até 100 caracteres)');
        }
        $link = trim($dados['artista_link'] ?? '');
        if ($link !== '' && !preg_match('#^https?://#i', $link)) {
            jsonErro('A página oficial precisa começar com http:// ou https://');
        }
        try {
            $novaImagem = salvarUploadValidado($_FILES['artista_image'] ?? null, 'imagem');
            $capa = salvarUploadValidado($_FILES['artista_capa'] ?? null, 'imagem');
        } catch (Exception $e) {
            jsonErro($e->getMessage());
        }
        if ($capa) {
            removerArquivoArmazenado($artista['artista_capa']);
        } elseif (!empty($dados['remover_capa'])) {
            removerArquivoArmazenado($artista['artista_capa']);
            $capa = null;
        } else {
            $capa = $artista['artista_capa'];
        }
        // Foto nova começa centralizada; o enquadramento é ajustado em editarFoto.php.
        $imagem = $novaImagem ?: $artista['artista_image'];
        $posicaoImagem = $novaImagem ? null : $artista['artista_image_pos'];
        executar($conexao, "UPDATE artista SET artista_nome = ?, artista_cidade = ?, artista_link = ?, artista_descricao = ?, artista_image = ?, artista_image_pos = ?, artista_capa = ? WHERE artista_id = ?",
            "sssssssi", [$nome, mb_substr(trim($dados['artista_cidade'] ?? ''), 0, 100), $link ?: null, mb_substr(trim($dados['artista_descricao'] ?? ''), 0, 2000), $imagem, $posicaoImagem, $capa, $artista['artista_id']]);
        jsonResposta(['success' => true, 'message' => 'Página de artista atualizada']);

    default:
        jsonErro('Ação inválida');
}
