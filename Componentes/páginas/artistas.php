<main class="main page-artistas">
        <section class="principal">
    <div class="principal-content">
        <h1 style="text-align:center;">Todos os Artistas</h1>
        <?php if (isset($_SESSION['usuario_tipo']) && $_SESSION['usuario_tipo'] === 'admin'): ?>
        <div class="admin-controls" style="text-align: center; margin: 20px 0; padding: 20px; background: linear-gradient(145deg, rgba(22, 27, 34, 0.95), rgba(13, 17, 23, 0.9)); border: 2px solid rgba(255, 215, 0, 0.3); border-radius: 16px;">
            <input type="text" id="artistName" placeholder="Nome do Artista" style="margin-right: 10px; padding: 12px 16px; border: 2px solid rgba(255, 215, 0, 0.2); border-radius: 12px; background: rgba(13, 17, 23, 0.9); color: #f0f6fc; font-size: 14px;">
            <input type="text" id="artistCity" placeholder="Cidade" style="margin-right: 10px; padding: 12px 16px; border: 2px solid rgba(255, 215, 0, 0.2); border-radius: 12px; background: rgba(13, 17, 23, 0.9); color: #f0f6fc; font-size: 14px;">
            <button onclick="addArtist()" style="padding: 12px 20px; background: linear-gradient(135deg, #ffd700, #ffed4e, #ffd700); border: none; border-radius: 12px; cursor: pointer; color: #0d1117; font-weight: 700; font-size: 14px;">Adicionar Artista</button>
        </div>
        <?php endif; ?>
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

<?php if (isset($_SESSION['usuario_tipo']) && $_SESSION['usuario_tipo'] === 'admin'): ?>
<script>
function addArtist() {
    const name = document.getElementById('artistName').value;
    const city = document.getElementById('artistCity').value;
    
    if (!name || !city) {
        alert('Por favor, preencha nome e cidade do artista.');
        return;
    }
    
    // Aqui você pode adicionar a lógica para enviar os dados via AJAX
    console.log('Adicionar artista:', name, city);
    alert('Funcionalidade de adicionar artista em desenvolvimento.');
}
</script>
<?php endif; ?>