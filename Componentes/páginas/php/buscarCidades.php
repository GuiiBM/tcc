<?php
    include "DBConection.php";

if (isset($_GET['q'])) {
    $query = mysqli_real_escape_string($conexao, $_GET['q']);
    $sql = "SELECT artista_cidade, GROUP_CONCAT(artista_nome SEPARATOR ', ') as artistas FROM artista WHERE artista_cidade LIKE '%$query%' GROUP BY artista_cidade LIMIT 5";
    $result = mysqli_query($conexao, $sql);
    
    $cidades = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $cidades[] = $row;
    }
    
    header('Content-Type: application/json');
    echo json_encode($cidades);
}
?>