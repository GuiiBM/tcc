<main class="main page-artistas">
        <section class="principal">
    <div class="principal-content">
        <h1 style="text-align:center;">Todos os Artistas</h1>
    </div>
        <div class="grid-container" id="cardContainer">
            <?php
            $stmt = mysqli_prepare($conexao, "SELECT artista_nome, artista_cidade, artista_image FROM artista ORDER BY artista_nome");
            
            if ($stmt && mysqli_stmt_execute($stmt)) {
                $result = mysqli_stmt_get_result($stmt);
                while ($artista = mysqli_fetch_assoc($result)) {
                    $nome = htmlspecialchars($artista['artista_nome'], ENT_QUOTES, 'UTF-8');
                    $imagem = $artista['artista_image'] ? htmlspecialchars($artista['artista_image'], ENT_QUOTES, 'UTF-8') : 'https://via.placeholder.com/300x280?text=Sem+Foto';
                    
                    echo "<div class='grid-card'>";
                    echo "<div class='title-card'>";
                    echo "<h2>$nome</h2>";
                    echo "</div>";
                    echo "<img src='$imagem' alt='$nome' class='image-music-card'>";
                    echo "</div>";
                }
                mysqli_stmt_close($stmt);
            } else {
                echo "<p>Erro ao carregar artistas.</p>";
            }
            ?>
        </div>
</section>
</main>