<?php
function buscarArtistas($conexao) {
    $sql = "SELECT * FROM artista ORDER BY artista_nome";
    $result = mysqli_query($conexao, $sql);
    $artistas = [];
    
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $artistas[] = $row;
        }
    }
    
    return $artistas;
}

function exibirArtistas($artistas) {
    foreach ($artistas as $artista) {
        echo "<div class='card'>";
        echo "<div class='card-image'>";
        echo "<img src='" . htmlspecialchars($artista['artista_image']) . "' alt='" . htmlspecialchars($artista['artista_nome']) . "'>";
        echo "</div>";
        echo "<div class='card-content'>";
        echo "<h3 class='card-title'>" . htmlspecialchars($artista['artista_nome']) . "</h3>";
        echo "<p class='card-artist'>" . htmlspecialchars($artista['artista_pais']) . "</p>";
        echo "</div>";
        echo "</div>";
    }
}
?>