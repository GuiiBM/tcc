<main class="main page-artistas">
        <section class="principal">
    <div class="principal-content">
        <h1 style="text-align:center;">Todos os Artistas</h1>
        <?php if (isset($_SESSION['usuario_tipo']) && $_SESSION['usuario_tipo'] === 'admin'): ?>
        <div class="admin-controls" style="text-align: center; margin: 20px 0; padding: 20px; background: linear-gradient(145deg, rgba(22, 27, 34, 0.95), rgba(13, 17, 23, 0.9)); border: 2px solid rgba(255, 215, 0, 0.3); border-radius: 16px;">
            <form id="artistForm" enctype="multipart/form-data" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; align-items: end;">
                <input type="text" id="artistName" name="artistName" placeholder="Nome do Artista*" required style="padding: 12px 16px; border: 2px solid rgba(255, 215, 0, 0.2); border-radius: 12px; background: rgba(13, 17, 23, 0.9); color: #f0f6fc; font-size: 14px;">
                <input type="text" id="artistCity" name="artistCity" placeholder="Cidade*" required style="padding: 12px 16px; border: 2px solid rgba(255, 215, 0, 0.2); border-radius: 12px; background: rgba(13, 17, 23, 0.9); color: #f0f6fc; font-size: 14px;">
                <textarea id="artistDescription" name="artistDescription" placeholder="Descrição* (mínimo 8 palavras)" required maxlength="512" style="padding: 12px 16px; border: 2px solid rgba(255, 215, 0, 0.2); border-radius: 12px; background: rgba(13, 17, 23, 0.9); color: #f0f6fc; font-size: 14px; resize: none; min-height: 48px; overflow: hidden;" oninput="this.style.height = 'auto'; this.style.height = this.scrollHeight + 'px'; validateDescription(this);"></textarea>
                <input type="url" id="artistLink" name="artistLink" placeholder="Link (opcional)" style="padding: 12px 16px; border: 2px solid rgba(255, 215, 0, 0.2); border-radius: 12px; background: rgba(13, 17, 23, 0.9); color: #f0f6fc; font-size: 14px;">
                <input type="file" id="artistImage" name="artistImage" accept="image/*" required style="padding: 12px 16px; border: 2px solid rgba(255, 215, 0, 0.2); border-radius: 12px; background: rgba(13, 17, 23, 0.9); color: #f0f6fc; font-size: 14px;">
                <button type="submit" style="padding: 12px 20px; background: linear-gradient(135deg, #ffd700, #ffed4e, #ffd700); border: none; border-radius: 12px; cursor: pointer; color: #0d1117; font-weight: 700; font-size: 14px;">Adicionar Artista</button>
            </form>
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