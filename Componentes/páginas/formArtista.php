<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cadastrar_artista'])) {
    $artista_nome = mysqli_real_escape_string($conexao, $_POST['artista_nome']);
    $artista_pais = mysqli_real_escape_string($conexao, $_POST['artista_pais']);
    $artista_bio = mysqli_real_escape_string($conexao, $_POST['artista_bio']);
    $artista_foto = mysqli_real_escape_string($conexao, $_POST['artista_foto']);
    
    $sql_artista = "INSERT INTO artista (artista_nome, artista_pais, artista_bio, artista_foto) 
                    VALUES ('$artista_nome', '$artista_pais', '$artista_bio', '$artista_foto')";
    
    if (mysqli_query($conexao, $sql_artista)) {
        echo "<div class='alert alert-success'>Artista cadastrado com sucesso!</div>";
    } else {
        echo "<div class='alert alert-error'>Erro ao cadastrar artista: " . mysqli_error($conexao) . "</div>";
    }
}
?>

<div class="form-container">
    <h2 class="form-title">Cadastrar Novo Artista</h2>
    <form method="POST">
        <div class="form-row">
            <div class="form-col">
                <label for="artista_nome" class="form-label">Nome do Artista</label>
                <input type="text" class="form-control" id="artista_nome" name="artista_nome" placeholder="Digite o nome do artista" required>
            </div>
            <div class="form-col">
                <label for="artista_pais" class="form-label">País de Origem</label>
                <input type="text" class="form-control" id="artista_pais" name="artista_pais" placeholder="Digite o país de origem" required>
            </div>
        </div>
        <div class="form-col-full">
            <label for="artista_foto" class="form-label">URL da Foto</label>
            <input type="url" class="form-control" id="artista_foto" name="artista_foto" placeholder="https://exemplo.com/foto.jpg" required>
        </div>
        <div class="form-col-full">
            <label for="artista_bio" class="form-label">Biografia</label>
            <textarea class="form-control" id="artista_bio" name="artista_bio" rows="4" placeholder="Digite uma breve biografia do artista..." required></textarea>
        </div>
        <div class="form-col-full" style="text-align: center; margin-top: 30px;">
            <button type="submit" name="cadastrar_artista" class="btn-neon">Cadastrar Artista</button>
        </div>
    </form>
</div>