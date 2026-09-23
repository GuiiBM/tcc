<?php
include_once 'DBConection.php';
include_once __DIR__ . '/url-helper.php';

function uploadPropaganda($file, $uploadDir = null) {
    global $conexao;

    if ($uploadDir === null) {
        $uploadDir = getArmazenamentoPath('propaganda');
    }
    
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'Erro no upload: ' . $file['error']];
    }
    
    $allowedTypes = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
    ];

    // Detecta o tipo real do arquivo lendo seus bytes (Content-Type do cliente é falsificável)
    $realType = mime_content_type($file['tmp_name']);
    if (!isset($allowedTypes[$realType])) {
        return ['success' => false, 'message' => 'Tipo não permitido: ' . $realType];
    }

    // Extensão sempre derivada do tipo real detectado, nunca do nome enviado pelo cliente
    $extension = $allowedTypes[$realType];
    $fileName = uniqid() . '.' . $extension;
    $uploadPath = $uploadDir . $fileName;
    
    if (!is_writable($uploadDir)) {
        return ['success' => false, 'message' => 'Pasta sem permissão de escrita'];
    }
    
    if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
        // Obter próxima ordem
        $result = mysqli_query($conexao, "SELECT MAX(propaganda_ordem) as max_ordem FROM propagandas");
        $row = mysqli_fetch_assoc($result);
        $novaOrdem = ($row['max_ordem'] ?? 0) + 1;
        
        // Inserir no banco
        $stmt = mysqli_prepare($conexao, "INSERT INTO propagandas (propaganda_nome, propaganda_ordem) VALUES (?, ?)");
        mysqli_stmt_bind_param($stmt, "si", $fileName, $novaOrdem);
        mysqli_stmt_execute($stmt);
        
        return ['success' => true, 'message' => 'Propaganda adicionada com sucesso!'];
    }
    
    return ['success' => false, 'message' => 'Erro ao mover arquivo para: ' . $uploadPath];
}

function deletePropaganda($fileName, $uploadDir = null) {
    global $conexao;
    
    if ($uploadDir === null) {
        $uploadDir = getArmazenamentoPath('propaganda');
    }

    $filePath = $uploadDir . basename($fileName);
    
    if (file_exists($filePath)) {
        unlink($filePath);
        
        // Remover do banco
        $stmt = mysqli_prepare($conexao, "DELETE FROM propagandas WHERE propaganda_nome = ?");
        mysqli_stmt_bind_param($stmt, "s", $fileName);
        mysqli_stmt_execute($stmt);
        
        return ['success' => true, 'message' => 'Propaganda excluída com sucesso!'];
    }
    
    return ['success' => false, 'message' => 'Arquivo não encontrado.'];
}

function listarPropagandasOrdenadas() {
    global $conexao;
    
    $result = mysqli_query($conexao, "SELECT * FROM propagandas WHERE propaganda_ativa = 1 ORDER BY propaganda_ordem ASC");
    $propagandas = [];
    
    while ($row = mysqli_fetch_assoc($result)) {
        $propagandas[] = $row;
    }
    
    return $propagandas;
}

function moverPropaganda($propagandaId, $direcao) {
    global $conexao;
    $propagandaId = intval($propagandaId);

    // Obter propaganda atual
    $stmt = mysqli_prepare($conexao, "SELECT * FROM propagandas WHERE propaganda_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $propagandaId);
    mysqli_stmt_execute($stmt);
    $propaganda = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    if (!$propaganda) {
        return ['success' => false, 'message' => 'Propaganda não encontrada'];
    }

    $ordemAtual = $propaganda['propaganda_ordem'];

    if ($direcao === 'subir') {
        // Encontrar propaganda anterior
        $stmt = mysqli_prepare($conexao, "SELECT * FROM propagandas WHERE propaganda_ordem < ? ORDER BY propaganda_ordem DESC LIMIT 1");
        mysqli_stmt_bind_param($stmt, "i", $ordemAtual);
        mysqli_stmt_execute($stmt);
        $propagandaAnterior = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

        if ($propagandaAnterior) {
            // Trocar ordens
            $stmtUp = mysqli_prepare($conexao, "UPDATE propagandas SET propaganda_ordem = ? WHERE propaganda_id = ?");
            mysqli_stmt_bind_param($stmtUp, "ii", $propagandaAnterior['propaganda_ordem'], $propagandaId);
            mysqli_stmt_execute($stmtUp);
            $stmtUp2 = mysqli_prepare($conexao, "UPDATE propagandas SET propaganda_ordem = ? WHERE propaganda_id = ?");
            mysqli_stmt_bind_param($stmtUp2, "ii", $ordemAtual, $propagandaAnterior['propaganda_id']);
            mysqli_stmt_execute($stmtUp2);
            return ['success' => true, 'message' => 'Propaganda movida para cima'];
        }
    } else {
        // Encontrar propaganda posterior
        $stmt = mysqli_prepare($conexao, "SELECT * FROM propagandas WHERE propaganda_ordem > ? ORDER BY propaganda_ordem ASC LIMIT 1");
        mysqli_stmt_bind_param($stmt, "i", $ordemAtual);
        mysqli_stmt_execute($stmt);
        $propagandaPosterior = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

        if ($propagandaPosterior) {
            // Trocar ordens
            $stmtDown = mysqli_prepare($conexao, "UPDATE propagandas SET propaganda_ordem = ? WHERE propaganda_id = ?");
            mysqli_stmt_bind_param($stmtDown, "ii", $propagandaPosterior['propaganda_ordem'], $propagandaId);
            mysqli_stmt_execute($stmtDown);
            $stmtDown2 = mysqli_prepare($conexao, "UPDATE propagandas SET propaganda_ordem = ? WHERE propaganda_id = ?");
            mysqli_stmt_bind_param($stmtDown2, "ii", $ordemAtual, $propagandaPosterior['propaganda_id']);
            mysqli_stmt_execute($stmtDown2);
            return ['success' => true, 'message' => 'Propaganda movida para baixo'];
        }
    }

    return ['success' => false, 'message' => 'Não é possível mover nesta direção'];
}
?>