<?php
    include "php/DBConection.php";

if (isset($_GET['cidade'])) {
    $cidade = mysqli_real_escape_string($conexao, $_GET['cidade']);
    $sql = "SELECT * FROM artista WHERE artista_cidade = '$cidade'";
    $result = mysqli_query($conexao, $sql);
    
    $artistas = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $artistas[] = $row;
    }
    
    header('Content-Type: application/json');
    echo json_encode($artistas);
}
?>