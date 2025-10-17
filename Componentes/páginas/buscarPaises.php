<?php
    include "Componentes/páginas/DBConection.php";

if (isset($_GET['q'])) {
    $query = mysqli_real_escape_string($conexao, $_GET['q']);
    $sql = "SELECT artista_pais, GROUP_CONCAT(artista_nome SEPARATOR ', ') as artistas FROM artista WHERE artista_pais LIKE '%$query%' GROUP BY artista_pais LIMIT 5";
    $result = mysqli_query($conexao, $sql);
    
    $paises = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $paises[] = $row;
    }
    
    header('Content-Type: application/json');
    echo json_encode($paises);
}
?>