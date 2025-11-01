<?php
function buscarArtistas($conexao) {
    $stmt = mysqli_prepare($conexao, "SELECT a.artista_id, a.artista_nome, a.artista_cidade, a.artista_image, u.usuario_descricao FROM artista a LEFT JOIN usuarios u ON a.artista_id = u.artista_id ORDER BY a.artista_nome");
    
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
    if (empty($artistas)) {
        echo "<p>Nenhum artista cadastrado.</p>";
        return;
    }
    
    foreach ($artistas as $artista) {
        $nome = htmlspecialchars($artista['artista_nome'], ENT_QUOTES, 'UTF-8');
        $cidade = htmlspecialchars($artista['artista_cidade'], ENT_QUOTES, 'UTF-8');
        $imagem = $artista['artista_image'] ? htmlspecialchars($artista['artista_image'], ENT_QUOTES, 'UTF-8') : 'Componentes/icones/icone.png';
        
        echo "<div class='grid-card'>
            <div class='title-card'>
                <h3>$nome</h3>
            </div>
            <img src='$imagem' alt='$nome' class='image-music-card'>
            <div class='autor-card'>
                <h4>$cidade</h4>
            </div>
        </div>";
        echo "\n";
    }
}
?>