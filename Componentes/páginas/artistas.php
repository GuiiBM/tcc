<main class="main page-artistas">
        <section class="principal">
    <div class="principal-content">
        <h1 style="text-align:center;">Todos os Artistas</h1>
    </div>

    <?php if (isset($_SESSION['usuario_tipo']) && $_SESSION['usuario_tipo'] === 'admin'): ?>
    <div class="admin-add-artist">
        <button type="button" class="btn-neon" id="toggleArtistForm">+ Adicionar Novo Artista</button>

        <form id="artistForm" class="form-container admin-add-artist-form" enctype="multipart/form-data" hidden>
            <h2 class="form-title">Adicionar Novo Artista</h2>
            <div class="form-row">
                <div class="form-col">
                    <label for="newArtistName" class="form-label">Nome do Artista</label>
                    <input type="text" class="form-control" id="newArtistName" name="artistName" placeholder="Digite o nome do artista" required>
                </div>
                <div class="form-col">
                    <label for="newArtistCity" class="form-label">Cidade</label>
                    <input type="text" class="form-control" id="newArtistCity" name="artistCity" placeholder="Digite a cidade de origem" required>
                </div>
            </div>
            <div class="form-col-full">
                <label for="newArtistLink" class="form-label">Página do Artista (opcional)</label>
                <input type="url" class="form-control" id="newArtistLink" name="artistLink" placeholder="https://...">
            </div>
            <div class="form-col-full">
                <label for="newArtistImage" class="form-label">Foto do Artista</label>
                <input type="file" class="form-control" id="newArtistImage" name="artistImage" accept="image/*" required>
            </div>
            <div class="form-col-full">
                <label for="newArtistDescription" class="form-label">Descrição (mínimo 8 palavras)</label>
                <textarea class="form-control" id="newArtistDescription" name="artistDescription" rows="4" oninput="validateDescription(this)" required></textarea>
            </div>
            <div class="form-col-full" style="text-align: center; margin-top: 20px;">
                <button type="submit" class="btn-neon">Cadastrar Artista</button>
            </div>
        </form>
    </div>
    <?php endif; ?>

        <div class="grid-container" id="cardContainer">
            <?php
            $stmt = mysqli_prepare($conexao, "SELECT artista_id, artista_nome, artista_cidade, artista_image, artista_descricao FROM artista ORDER BY artista_nome");

            if ($stmt && mysqli_stmt_execute($stmt)) {
                $result = mysqli_stmt_get_result($stmt);
                while ($artista = mysqli_fetch_assoc($result)) {
                    $id = (int) $artista['artista_id'];
                    $nome = htmlspecialchars($artista['artista_nome'], ENT_QUOTES, 'UTF-8');
                    $imagem = $artista['artista_image'] ? htmlspecialchars($artista['artista_image'], ENT_QUOTES, 'UTF-8') : 'https://via.placeholder.com/300x280?text=Sem+Foto';
                    $descricao = htmlspecialchars($artista['artista_descricao'] ?: 'Descrição não disponível', ENT_QUOTES, 'UTF-8');

                    echo "<div class='grid-card' data-artist-id='$id'>";
                    echo "<div class='title-card'>";
                    echo "<h2>$nome</h2>";
                    echo "</div>";
                    echo "<img src='$imagem' alt='$nome' class='image-music-card' onerror='this.onerror=null;this.src=\"Componentes/icones/icone.png\"'>";
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

const toggleArtistFormBtn = document.getElementById('toggleArtistForm');
const artistForm = document.getElementById('artistForm');
toggleArtistFormBtn.addEventListener('click', function() {
    const isHidden = artistForm.hidden;
    artistForm.hidden = !isHidden;
    toggleArtistFormBtn.textContent = isHidden ? 'Cancelar' : '+ Adicionar Novo Artista';
});

artistForm.addEventListener('submit', function(e) {
    e.preventDefault();
    
    const description = document.getElementById('newArtistDescription');
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