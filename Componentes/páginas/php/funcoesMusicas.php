<?php
function buscarMusicas($conexao) {
    $stmt = mysqli_prepare($conexao, "SELECT m.musica_id, m.musica_titulo, m.musica_capa, m.musica_link, a.artista_nome, a.artista_cidade FROM musica m INNER JOIN artista a ON m.musica_artista = a.artista_id ORDER BY m.musica_id DESC");
    
    if (!$stmt || !mysqli_stmt_execute($stmt)) {
        error_log("Erro ao buscar músicas: " . mysqli_error($conexao));
        return [];
    }
    
    $result = mysqli_stmt_get_result($stmt);
    $musicas = [];
    
    while ($row = mysqli_fetch_assoc($result)) {
        $musicas[] = $row;
    }
    
    mysqli_stmt_close($stmt);
    return $musicas;
}

function exibirMusicas($musicas) {
    if (empty($musicas)) {
        echo "<p>Nenhuma música cadastrada.</p>";
        return;
    }
    
    foreach ($musicas as $musica) {
        $titulo = htmlspecialchars($musica['musica_titulo'], ENT_QUOTES, 'UTF-8');
        $artista = htmlspecialchars($musica['artista_nome'], ENT_QUOTES, 'UTF-8');
        $cidade = htmlspecialchars($musica['artista_cidade'], ENT_QUOTES, 'UTF-8');
        $capa = htmlspecialchars($musica['musica_capa'], ENT_QUOTES, 'UTF-8');
        $link = htmlspecialchars($musica['musica_link'], ENT_QUOTES, 'UTF-8');
        
        echo "<div class='grid-card' onclick=\"playMusic('$link', '$titulo', '$artista')\">
            <div class='title-card'>
                <h3>$titulo</h3>
            </div>
            <img src='$capa' alt='$titulo' class='image-music-card'>
            <div class='autor-card'>
                <h4>$artista - $cidade</h4>
            </div>
        </div>";
    }
}
?>