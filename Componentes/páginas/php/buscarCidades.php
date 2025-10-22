<?php
    include "DBConection.php";

if (isset($_GET['q']) && !empty(trim($_GET['q']))) {
    $query = '%' . trim($_GET['q']) . '%';
    $stmt = mysqli_prepare($conexao, "SELECT artista_cidade, GROUP_CONCAT(artista_nome SEPARATOR ', ') as artistas FROM artista WHERE artista_cidade LIKE ? GROUP BY artista_cidade LIMIT 5");
    mysqli_stmt_bind_param($stmt, "s", $query);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (!$result) {
        header('Content-Type: application/json');
        echo json_encode([]);
        exit;
    }
    
    $cidades = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $cidades[] = $row;
    }
    
    header('Content-Type: application/json');
    echo json_encode($cidades);
} else {
    header('Content-Type: application/json');
    echo json_encode([]);
}
?>