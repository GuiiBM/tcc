<?php
function buscarMusicas($conexao) {
    $stmt = mysqli_prepare($conexao, "SELECT m.musica_id, m.musica_titulo, m.musica_capa, m.musica_link, m.musica_data_adicao, a.artista_nome, a.artista_cidade FROM musica m INNER JOIN artista a ON m.musica_artista = a.artista_id ORDER BY m.musica_id DESC");
    
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

function buscarUltimosArtistas($conexao, $limite = null) {
    $limiteSql = $limite ? "LIMIT ?" : "";
    $stmt = mysqli_prepare($conexao, "SELECT a.artista_id, a.artista_nome, a.artista_cidade, a.artista_image FROM artista a ORDER BY a.artista_id DESC $limiteSql");
    
    if (!$stmt) {
        return [];
    }
    
    if ($limite) {
        mysqli_stmt_bind_param($stmt, "i", $limite);
    }
    
    if (!mysqli_stmt_execute($stmt)) {
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

function buscarUltimasMusicas($conexao, $limite = null) {
    $limiteSql = $limite ? "LIMIT ?" : "";
    $stmt = mysqli_prepare($conexao, "SELECT m.musica_id, m.musica_titulo, m.musica_capa, m.musica_link, m.musica_data_adicao, a.artista_nome, a.artista_cidade FROM musica m INNER JOIN artista a ON m.musica_artista = a.artista_id ORDER BY m.musica_data_adicao DESC $limiteSql");
    
    if (!$stmt) {
        return [];
    }
    
    if ($limite) {
        mysqli_stmt_bind_param($stmt, "i", $limite);
    }
    
    if (!mysqli_stmt_execute($stmt)) {
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

function buscarMusicasMenosCurtidas($conexao, $limite = null) {
    $limiteSql = $limite ? "LIMIT ?" : "";
    $stmt = mysqli_prepare($conexao, "
        SELECT 
            m.musica_id, 
            m.musica_titulo, 
            m.musica_capa, 
            m.musica_link, 
            m.musica_data_adicao, 
            a.artista_nome, 
            a.artista_cidade,
            COALESCE(likes.total_likes, 0) as total_likes,
            COALESCE(dislikes.total_dislikes, 0) as total_dislikes
        FROM musica m 
        INNER JOIN artista a ON m.musica_artista = a.artista_id 
        LEFT JOIN (
            SELECT musica_id, COUNT(*) as total_likes 
            FROM curtidas 
            WHERE tipo_curtida = 'curtida' 
            GROUP BY musica_id
        ) likes ON m.musica_id = likes.musica_id
        LEFT JOIN (
            SELECT musica_id, COUNT(*) as total_dislikes 
            FROM curtidas 
            WHERE tipo_curtida = 'descurtida' 
            GROUP BY musica_id
        ) dislikes ON m.musica_id = dislikes.musica_id
        ORDER BY 
            (COALESCE(likes.total_likes, 0) + COALESCE(dislikes.total_dislikes, 0)) ASC,
            (COALESCE(likes.total_likes, 0) - COALESCE(dislikes.total_dislikes, 0)) DESC
        $limiteSql
    ");
    
    if (!$stmt) {
        return [];
    }
    
    if ($limite) {
        mysqli_stmt_bind_param($stmt, "i", $limite);
    }
    
    if (!mysqli_stmt_execute($stmt)) {
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

function buscarMusicasOrdemPersonalizada($conexao, $limite = null) {
    $limiteSql = $limite ? "LIMIT ?" : "";
    $stmt = mysqli_prepare($conexao, "
        SELECT 
            m.musica_id, 
            m.musica_titulo, 
            m.musica_capa, 
            m.musica_link, 
            m.musica_data_adicao, 
            a.artista_nome, 
            a.artista_cidade,
            COALESCE(likes.total_likes, 0) as total_likes,
            COALESCE(dislikes.total_dislikes, 0) as total_dislikes
        FROM musica m 
        INNER JOIN artista a ON m.musica_artista = a.artista_id 
        LEFT JOIN (
            SELECT musica_id, COUNT(*) as total_likes 
            FROM curtidas 
            WHERE tipo_curtida = 'curtida' 
            GROUP BY musica_id
        ) likes ON m.musica_id = likes.musica_id
        LEFT JOIN (
            SELECT musica_id, COUNT(*) as total_dislikes 
            FROM curtidas 
            WHERE tipo_curtida = 'descurtida' 
            GROUP BY musica_id
        ) dislikes ON m.musica_id = dislikes.musica_id
        ORDER BY 
            (COALESCE(likes.total_likes, 0) + COALESCE(dislikes.total_dislikes, 0)) ASC,
            (COALESCE(likes.total_likes, 0) - COALESCE(dislikes.total_dislikes, 0)) DESC
        $limiteSql
    ");
    
    if (!$stmt) {
        return [];
    }
    
    if ($limite) {
        mysqli_stmt_bind_param($stmt, "i", $limite);
    }
    
    if (!mysqli_stmt_execute($stmt)) {
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

function curtirMusica($conexao, $musica_id, $tipo) {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['usuario_id'])) {
        return false;
    }
    
    $usuario_id = $_SESSION['usuario_id'];
    
    // Verificar se já existe curtida
    $stmt = mysqli_prepare($conexao, "SELECT curtida_id FROM curtidas WHERE musica_id = ? AND usuario_id = ?");
    mysqli_stmt_bind_param($stmt, "ii", $musica_id, $usuario_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) > 0) {
        // Atualizar curtida existente
        $stmt = mysqli_prepare($conexao, "UPDATE curtidas SET tipo_curtida = ?, data_curtida = CURRENT_TIMESTAMP WHERE musica_id = ? AND usuario_id = ?");
        mysqli_stmt_bind_param($stmt, "sii", $tipo, $musica_id, $usuario_id);
    } else {
        // Inserir nova curtida
        $stmt = mysqli_prepare($conexao, "INSERT INTO curtidas (musica_id, usuario_id, tipo_curtida) VALUES (?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "iis", $musica_id, $usuario_id, $tipo);
    }
    
    return mysqli_stmt_execute($stmt);
}

function contarCurtidas($conexao, $musica_id) {
    $stmt = mysqli_prepare($conexao, "SELECT COUNT(*) as curtidas FROM curtidas WHERE musica_id = ? AND tipo_curtida = 'curtida'");
    mysqli_stmt_bind_param($stmt, "i", $musica_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    return $row['curtidas'];
}

function contarDescurtidas($conexao, $musica_id) {
    $stmt = mysqli_prepare($conexao, "SELECT COUNT(*) as descurtidas FROM curtidas WHERE musica_id = ? AND tipo_curtida = 'descurtida'");
    mysqli_stmt_bind_param($stmt, "i", $musica_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    return $row['descurtidas'];
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
        
        echo "<div class='grid-card' onclick=\"playMusic('$link', '$titulo', '$artista', {$musica['musica_id']})\">
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

function exibirArtistasRecomendados($artistas) {
    if (empty($artistas)) {
        echo "<p>Nenhum artista encontrado.</p>";
        return;
    }
    
    foreach ($artistas as $artista) {
        $nome = htmlspecialchars($artista['artista_nome'], ENT_QUOTES, 'UTF-8');
        $cidade = htmlspecialchars($artista['artista_cidade'], ENT_QUOTES, 'UTF-8');
        $imagem = htmlspecialchars($artista['artista_image'], ENT_QUOTES, 'UTF-8');
        
        echo "<div class='grid-card'>
            <div class='title-card'>
                <h3>$nome</h3>
            </div>
            <img src='$imagem' alt='$nome' class='image-music-card' onerror='this.src=\"Componentes/icones/icone.png\"'>
            <div class='autor-card'>
                <h4>$cidade</h4>
            </div>
        </div>";
    }
}

function exibirMusicasRecomendadas($musicas) {
    if (empty($musicas)) {
        echo "<p>Nenhuma música encontrada.</p>";
        return;
    }
    
    foreach ($musicas as $musica) {
        $titulo = htmlspecialchars($musica['musica_titulo'], ENT_QUOTES, 'UTF-8');
        $artista = htmlspecialchars($musica['artista_nome'], ENT_QUOTES, 'UTF-8');
        $cidade = htmlspecialchars($musica['artista_cidade'], ENT_QUOTES, 'UTF-8');
        $capa = htmlspecialchars($musica['musica_capa'], ENT_QUOTES, 'UTF-8');
        $link = htmlspecialchars($musica['musica_link'], ENT_QUOTES, 'UTF-8');
        
        echo "<div class='grid-card' onclick=\"playMusic('$link', '$titulo', '$artista', {$musica['musica_id']})\">
            <div class='title-card'>
                <h3>$titulo</h3>
            </div>
            <img src='$capa' alt='$titulo' class='image-music-card'>
            <div class='autor-card'>
                <h4>$artista <br> $cidade</h4>
            </div>
        </div>";
    }
}
?>