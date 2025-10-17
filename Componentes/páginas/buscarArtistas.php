<?php
    include "Componentes/páginas/DBConection.php";

if (isset($_GET['q'])) {
    $query = mysqli_real_escape_string($conexao, $_GET['q']);
    $sql = "SELECT * FROM artista WHERE artista_nome LIKE '%$query%' LIMIT 5";
    $result = mysqli_query($conexao, $sql);
    
    $artistas = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $artistas[] = $row;
    }
    
    header('Content-Type: application/json');
    echo json_encode($artistas);
}
?>