<main class="main page-artistas">
        <section class="principal">
    <div class="principal-content">
        <h1 style="text-align:center;">Todos os Artistas</h1>
    </div>
        <div class="grid-container" id="cardContainer">
            <?php
            $sql = "SELECT * FROM artista";
            $result = mysqli_query($conexao, $sql);
            
            while ($artista = mysqli_fetch_assoc($result)) {
                echo "<div class='grid-card'>";
                echo "<div class='title-card'>";
                echo "<h2>" . $artista['artista_nome'] . "</h2>";
                echo "</div>";
                
                $imagem = $artista['artista_image'] ? $artista['artista_image'] : 'https://via.placeholder.com/300x280?text=Sem+Foto';
                echo "<img src='" . $imagem . "' alt='" . $artista['artista_nome'] . "' class='image-music-card'>";
                echo "</div>";
            }
            ?>
        </div>
</section>
</main>