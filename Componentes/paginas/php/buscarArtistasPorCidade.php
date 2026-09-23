<?php
    include "DBConection.php";

if (isset($_GET['cidade']) && !empty(trim($_GET['cidade']))) {
    $cidade = trim($_GET['cidade']);
    $stmt = mysqli_prepare($conexao, "SELECT * FROM artista WHERE artista_cidade = ?");
    mysqli_stmt_bind_param($stmt, "s", $cidade);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (!$result) {
        header('Content-Type: application/json');
        echo json_encode([]);
        exit;
    }
    
    $artistas = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $artistas[] = $row;
    }
    
    header('Content-Type: application/json');
    echo json_encode($artistas);
} else {
    header('Content-Type: application/json');
    echo json_encode([]);
}
?>