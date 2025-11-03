<main class="main page-artistas">
        <section class="principal">
    <div class="principal-content">
        <h1 style="text-align:center;">Todos os Artistas</h1>
    </div>
        <div class="grid-container" id="cardContainer">
            <?php
            $stmt = mysqli_prepare($conexao, "SELECT artista_nome, artista_cidade, artista_image, artista_descricao FROM artista ORDER BY artista_nome");
            
            if ($stmt && mysqli_stmt_execute($stmt)) {
                $result = mysqli_stmt_get_result($stmt);
                while ($artista = mysqli_fetch_assoc($result)) {
                    $nome = htmlspecialchars($artista['artista_nome'], ENT_QUOTES, 'UTF-8');
                    $imagem = $artista['artista_image'] ? htmlspecialchars($artista['artista_image'], ENT_QUOTES, 'UTF-8') : 'https://via.placeholder.com/300x280?text=Sem+Foto';
                    $descricao = htmlspecialchars($artista['artista_descricao'] ?: 'Descrição não disponível', ENT_QUOTES, 'UTF-8');
                    
                    echo "<div class='grid-card'>";
                    echo "<div class='title-card'>";
                    echo "<h2>$nome</h2>";
                    echo "</div>";
                    echo "<img src='$imagem' alt='$nome' class='image-music-card'>";
                    echo "<div class='artist-description'>";
                    echo "<p>$descricao</p>";
                    echo "</div>";
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
function validateDescription(textarea) {
    const words = textarea.value.trim().split(/\s+/).filter(word => word.length > 0);
    if (words.length < 8) {
        textarea.setCustomValidity('A descrição deve ter pelo menos 8 palavras');
    } else {
        textarea.setCustomValidity('');
    }
}

document.getElementById('artistForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const description = document.getElementById('artistDescription');
    const words = description.value.trim().split(/\s+/).filter(word => word.length > 0);
    
    if (words.length < 8) {
        alert('A descrição deve ter pelo menos 8 palavras');
        return;
    }
    
    const formData = new FormData(this);
    
    fetch('Componentes/páginas/php/adicionarArtista.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Artista adicionado com sucesso!');
            location.reload();
        } else {
            alert('Erro: ' + data.message);
        }
    })
    .catch(error => {
        alert('Erro ao adicionar artista.');
    });
});
</script>
<?php endif; ?>

<script src="Componentes/configuracoes/JS/artistasGrid.js"></script>