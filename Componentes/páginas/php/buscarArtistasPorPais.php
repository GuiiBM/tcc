<?php
    include "DBConection.php";

if (isset($_GET['pais'])) {
    $pais = mysqli_real_escape_string($conexao, $_GET['pais']);
    $sql = "SELECT * FROM artista WHERE artista_pais = '$pais'";
    $result = mysqli_query($conexao, $sql);
    
    $artistas = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $artistas[] = $row;
    }
    
    header('Content-Type: application/json');
    echo json_encode($artistas);
}
?>