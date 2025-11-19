<?php
function uploadPropaganda($file, $uploadDir = null) {
    if ($uploadDir === null) {
        $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/tcc/Componentes/Armazenamento/propaganda/';
    }
    
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'Erro no upload: ' . $file['error']];
    }
    
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($file['type'], $allowedTypes)) {
        return ['success' => false, 'message' => 'Tipo não permitido: ' . $file['type']];
    }
    
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $fileName = uniqid() . '.' . $extension;
    $uploadPath = $uploadDir . $fileName;
    
    if (!is_writable($uploadDir)) {
        return ['success' => false, 'message' => 'Pasta sem permissão de escrita'];
    }
    
    if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
        return ['success' => true, 'message' => 'Propaganda adicionada com sucesso!'];
    }
    
    return ['success' => false, 'message' => 'Erro ao mover arquivo para: ' . $uploadPath];
}

function deletePropaganda($fileName, $uploadDir = null) {
    if ($uploadDir === null) {
        $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/tcc/Componentes/Armazenamento/propaganda/';
    }
    $filePath = $uploadDir . basename($fileName);
    if (file_exists($filePath)) {
        unlink($filePath);
        return ['success' => true, 'message' => 'Propaganda excluída com sucesso!'];
    }
    return ['success' => false, 'message' => 'Arquivo não encontrado.'];
}

function listarPropagandas($uploadDir = null) {
    if ($uploadDir === null) {
        $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/tcc/Componentes/Armazenamento/propaganda/';
    }
    return glob($uploadDir . '*.{jpg,jpeg,png,gif,webp,JPG,JPEG,PNG,GIF,WEBP}', GLOB_BRACE);
}
?>