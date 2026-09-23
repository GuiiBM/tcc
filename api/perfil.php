<?php
// Atualização do perfil do usuário logado.
// POST acao=dados|foto|senha|qualidade|artista
include __DIR__ . '/../Componentes/paginas/php/app.php';
exigirPostApi();
exigirLoginApi();

$dados = dadosRequisicao();
$usuario = usuarioAtual();
$uid = (int) $usuario['usuario_id'];

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
        if (!empty($dados['remover'])) {
            $novaFoto = null;
        } else {
            try {
                $novaFoto = salvarUploadValidado($_FILES['foto'] ?? null, 'imagem');
            } catch (Exception $e) {
                jsonErro($e->getMessage());
            }
            if (!$novaFoto) {
                jsonErro('Escolha uma imagem');
            }
        }
        executar($conexao, "UPDATE usuarios SET usuario_foto = ? WHERE usuario_id = ?", "si", [$novaFoto, $uid]);
        // Se a foto do artista era a mesma do perfil (ou a padrão), acompanha a mudança.
        if ($usuario['artista_id']) {
            executar($conexao, "UPDATE artista SET artista_image = ? WHERE artista_id = ? AND (artista_image = ? OR artista_image IS NULL OR artista_image = '' OR artista_image = 'Componentes/icones/icone.png')",
                "sis", [$novaFoto ?: 'Componentes/icones/icone.png', $usuario['artista_id'], (string) $fotoAntiga]);
        }
        $emUso = consultarUm($conexao, "SELECT 1 FROM artista WHERE artista_image = ?", "s", [(string) $fotoAntiga]);
        if (!$emUso) {
            removerArquivoArmazenado($fotoAntiga);
        }
        $_SESSION['usuario_foto'] = $novaFoto;
        jsonResposta(['success' => true, 'message' => $novaFoto ? 'Foto atualizada' : 'Foto removida']);

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
        if (!$usuario['artista_id']) {
            jsonErro('Sua conta não tem página de artista', 403);
        }
        $artista = consultarUm($conexao, "SELECT * FROM artista WHERE artista_id = ?", "i", [$usuario['artista_id']]);
        $nome = trim($dados['artista_nome'] ?? '');
        if ($nome === '' || mb_strlen($nome) > 100) {
            jsonErro('Informe o nome artístico (até 100 caracteres)');
        }
        $link = trim($dados['artista_link'] ?? '');
        if ($link !== '' && !preg_match('#^https?://#i', $link)) {
            jsonErro('A página oficial precisa começar com http:// ou https://');
        }
        try {
            $imagem = salvarUploadValidado($_FILES['artista_image'] ?? null, 'imagem') ?: $artista['artista_image'];
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
        executar($conexao, "UPDATE artista SET artista_nome = ?, artista_cidade = ?, artista_link = ?, artista_descricao = ?, artista_image = ?, artista_capa = ? WHERE artista_id = ?",
            "ssssssi", [$nome, mb_substr(trim($dados['artista_cidade'] ?? ''), 0, 100), $link ?: null, mb_substr(trim($dados['artista_descricao'] ?? ''), 0, 2000), $imagem, $capa, $artista['artista_id']]);
        jsonResposta(['success' => true, 'message' => 'Página de artista atualizada']);

    default:
        jsonErro('Ação inválida');
}
