<?php
function processarUpload($arquivo, $tipo) {
    $extensoesImagem = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $extensoesAudio = ['mp3', 'wav', 'ogg', 'flac', 'm4a'];
    
    // Verificar se arquivo foi enviado
    if (!isset($arquivo) || !is_array($arquivo)) {
        return ['erro' => 'Nenhum arquivo enviado'];
    }
    
    // Verificar erros de upload
    if ($arquivo['error'] !== UPLOAD_ERR_OK) {
        $erros = [
            UPLOAD_ERR_INI_SIZE => 'Arquivo muito grande (limite do servidor)',
            UPLOAD_ERR_FORM_SIZE => 'Arquivo muito grande (limite do formulário)',
            UPLOAD_ERR_PARTIAL => 'Upload incompleto',
            UPLOAD_ERR_NO_FILE => 'Nenhum arquivo selecionado',
            UPLOAD_ERR_NO_TMP_DIR => 'Pasta temporária não encontrada',
            UPLOAD_ERR_CANT_WRITE => 'Erro de escrita no disco',
            UPLOAD_ERR_EXTENSION => 'Upload bloqueado por extensão'
        ];
        return ['erro' => $erros[$arquivo['error']] ?? 'Erro desconhecido no upload'];
    }
    
    // Verificar se arquivo existe
    if (!file_exists($arquivo['tmp_name'])) {
        return ['erro' => 'Arquivo temporário não encontrado'];
    }
    
    $nomeOriginal = $arquivo['name'];
    $extensao = strtolower(pathinfo($nomeOriginal, PATHINFO_EXTENSION));
    $nomeUnico = bin2hex(random_bytes(16)) . '.' . $extensao;
    
    // Validar extensão
    if (!preg_match('/^[a-zA-Z0-9]+$/', $extensao)) {
        return ['erro' => 'Extensão de arquivo inválida'];
    }
    
    // Determinar pasta de destino
    $pastaBase = realpath(dirname(__FILE__) . '/../../../') . '/Componentes/Armazenamento/';
    
    if ($tipo === 'imagem' && in_array($extensao, $extensoesImagem)) {
        $pastaDestino = $pastaBase . 'imagens/';
        $caminhoRetorno = 'Componentes/Armazenamento/imagens/' . $nomeUnico;
    } elseif ($tipo === 'audio' && in_array($extensao, $extensoesAudio)) {
        $pastaDestino = $pastaBase . 'audios/';
        $caminhoRetorno = 'Componentes/Armazenamento/audios/' . $nomeUnico;
    } else {
        return ['erro' => "Tipo de arquivo não permitido. Extensão: $extensao"];
    }
    
    // Criar pasta se não existir
    if (!is_dir($pastaDestino)) {
        if (!mkdir($pastaDestino, 0777, true)) {
            return ['erro' => 'Não foi possível criar pasta de destino'];
        }
    }
    
    // Verificar e corrigir permissões
    if (!is_writable($pastaDestino)) {
        chmod($pastaDestino, 0777);
        // Tentar criar arquivo de teste
        $teste = $pastaDestino . 'teste_' . time() . '.tmp';
        if (!file_put_contents($teste, 'teste')) {
            return ['erro' => 'Pasta sem permissão de escrita: ' . $pastaDestino];
        }
        unlink($teste);
    }
    
    $destino = $pastaDestino . $nomeUnico;
    
    // Mover arquivo
    if (move_uploaded_file($arquivo['tmp_name'], $destino)) {
        chmod($destino, 0644);
        return ['sucesso' => true, 'caminho' => $caminhoRetorno];
    } else {
        return ['erro' => 'Falha ao mover arquivo para: ' . $destino];
    }
}
?>