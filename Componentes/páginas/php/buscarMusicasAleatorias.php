<?php
include_once "DBConection.php";
include_once "funcoesMusicas.php";

header('Content-Type: application/json');

if (isset($conexao)) {
    $stmt = mysqli_prepare($conexao, "SELECT m.musica_id, m.musica_titulo, m.musica_capa, m.musica_link, a.artista_nome, a.artista_cidade FROM musica m INNER JOIN artista a ON m.musica_artista = a.artista_id ORDER BY RAND() LIMIT 10");
    
    if ($stmt && mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        $musicas = [];
        
        while ($row = mysqli_fetch_assoc($result)) {
            $musicas[] = $row;
        }
        
        echo json_encode($musicas);
    } else {
        echo json_encode([]);
    }
} else {
    echo json_encode([]);
}
?>