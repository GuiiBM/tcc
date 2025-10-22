<?php
function buscarArtistas($conexao) {
    $stmt = mysqli_prepare($conexao, "SELECT artista_id, artista_nome, artista_cidade, artista_image FROM artista ORDER BY artista_nome");
    
    if (!$stmt || !mysqli_stmt_execute($stmt)) {
        error_log("Erro ao buscar artistas: " . mysqli_error($conexao));
        return [];
    }
    
    $result = mysqli_stmt_get_result($stmt);
    $artistas = [];
    
    while ($row = mysqli_fetch_assoc($result)) {
        $artistas[] = $row;
    }
    
    mysqli_stmt_close($stmt);
    return $artistas;
}

function exibirArtistas($artistas) {
    foreach ($artistas as $artista) {
        $nome = htmlspecialchars($artista['artista_nome'], ENT_QUOTES, 'UTF-8');
        $cidade = htmlspecialchars($artista['artista_cidade'], ENT_QUOTES, 'UTF-8');
        $imagem = $artista['artista_image'] ? htmlspecialchars($artista['artista_image'], ENT_QUOTES, 'UTF-8') : 'https://via.placeholder.com/300x280?text=Sem+Foto';
        
        echo "<div class='grid-card'>";
        echo "<div class='title-card'>";
        echo "<h2>$nome</h2>";
        echo "</div>";
        echo "<img src='$imagem' alt='$nome' class='image-music-card' onerror='this.src=\"Componentes/icones/icone.png\"'>";
        echo "<div class='autor-card'>";
        echo "<h4>$cidade</h4>";
        echo "</div>";
        echo "</div>";
    }
}
?>