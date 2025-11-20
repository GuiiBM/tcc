<?php
include_once "DBConection.php";

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['musica_id']) || !is_numeric($input['musica_id'])) {
        echo json_encode(['success' => false, 'message' => 'ID da música inválido']);
        exit;
    }
    
    $musica_id = intval($input['musica_id']);
    $ip_usuario = $_SERVER['REMOTE_ADDR'];
    
    // Verificar se a música existe
    $stmt_check = mysqli_prepare($conexao, "SELECT musica_id FROM musica WHERE musica_id = ?");
    mysqli_stmt_bind_param($stmt_check, "i", $musica_id);
    mysqli_stmt_execute($stmt_check);
    $result_check = mysqli_stmt_get_result($stmt_check);
    
    if (mysqli_num_rows($result_check) === 0) {
        echo json_encode(['success' => false, 'message' => 'Música não encontrada']);
        exit;
    }
    
    // Verificar se já foi visualizada pelo mesmo IP nos últimos 3 segundos (evitar spam)
    $stmt_recent = mysqli_prepare($conexao, "SELECT visualizacao_id FROM visualizacoes WHERE musica_id = ? AND ip_usuario = ? AND data_visualizacao > DATE_SUB(NOW(), INTERVAL 3 SECOND)");
    mysqli_stmt_bind_param($stmt_recent, "is", $musica_id, $ip_usuario);
    mysqli_stmt_execute($stmt_recent);
    $result_recent = mysqli_stmt_get_result($stmt_recent);
    
    if (mysqli_num_rows($result_recent) > 0) {
        echo json_encode(['success' => true, 'message' => 'Visualização já registrada recentemente']);
        exit;
    }
    
    // Inserir nova visualização
    $stmt_insert = mysqli_prepare($conexao, "INSERT INTO visualizacoes (musica_id, ip_usuario) VALUES (?, ?)");
    mysqli_stmt_bind_param($stmt_insert, "is", $musica_id, $ip_usuario);
    
    if (mysqli_stmt_execute($stmt_insert)) {
        echo json_encode(['success' => true, 'message' => 'Visualização registrada']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro ao registrar visualização']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
}
?>