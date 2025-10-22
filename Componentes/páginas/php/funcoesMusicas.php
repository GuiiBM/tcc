<?php
function buscarMusicas($conexao) {
    $sql = "SELECT m.*, a.artista_nome, a.artista_cidade 
            FROM musica m 
            INNER JOIN artista a ON m.musica_artista = a.artista_id 
            ORDER BY m.musica_id DESC";
    
    $result = mysqli_query($conexao, $sql);
    $musicas = [];
    
    while ($row = mysqli_fetch_assoc($result)) {
        $musicas[] = $row;
    }
    
    return $musicas;
}

function exibirMusicas($musicas) {
    if (empty($musicas)) {
        echo "<p>Nenhuma música cadastrada.</p>";
        return;
    }
    
    foreach ($musicas as $musica) {
        echo "
        <div class='grid-card' onclick=\"playMusic('{$musica['musica_link']}', '{$musica['musica_titulo']}', '{$musica['artista_nome']}')\">
            <div class='title-card'>
                <h3>{$musica['musica_titulo']}</h3>
            </div>
            <img src='{$musica['musica_capa']}' alt='{$musica['musica_titulo']}' class='image-music-card'>
            <div class='autor-card'>
                <h4>{$musica['artista_nome']} - {$musica['artista_cidade']}</h4>
            </div>
        </div>";
    }
}
?>