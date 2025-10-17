<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $artista_nome = mysqli_real_escape_string($conexao, $_POST['artista_nome']);
    $artista_pais = mysqli_real_escape_string($conexao, $_POST['artista_pais']);
    $musica_titulo = mysqli_real_escape_string($conexao, $_POST['musica_titulo']);
    $musica_capa = mysqli_real_escape_string($conexao, $_POST['musica_capa']);
    $musica_link = mysqli_real_escape_string($conexao, $_POST['musica_link']);
    
    $check_artista = "SELECT artista_id FROM artista WHERE artista_nome = '$artista_nome'";
    $result = mysqli_query($conexao, $check_artista);
    
    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $artista_id = $row['artista_id'];
    } else {
        $artista_image = mysqli_real_escape_string($conexao, $_POST['artista_image']);
        $sql_artista = "INSERT INTO artista (artista_nome, artista_pais, artista_image) VALUES ('$artista_nome', '$artista_pais', '$artista_image')";
        mysqli_query($conexao, $sql_artista);
        $artista_id = mysqli_insert_id($conexao);
    }
    
    $sql_musica = "INSERT INTO musica (musica_titulo, musica_capa, musica_link, musica_artista) 
                   VALUES ('$musica_titulo', '$musica_capa', '$musica_link', '$artista_id')";
    mysqli_query($conexao, $sql_musica);
    
    echo "<div class='alert alert-success'>Música cadastrada com sucesso!</div>";
}
?>

<div class="form-container">
    <h2 class="form-title">Cadastrar Nova Música</h2>
    <form method="POST">
        <div class="form-row">
            <div class="form-col" style="position: relative;">
                <label for="artista_nome" class="form-label">Nome do Artista</label>
                <input type="text" class="form-control" id="artista_nome" name="artista_nome" placeholder="Digite o nome do artista" required autocomplete="off">
                <input type="hidden" id="artista_id" name="artista_id">
                <div id="artistaSuggestions" class="artist-suggestions"></div>
            </div>
            <div class="form-col" style="position: relative;">
                <label for="artista_pais" class="form-label">País do Artista</label>
                <input type="text" class="form-control" id="artista_pais" name="artista_pais" placeholder="Digite o país de origem" required autocomplete="off">
                <div id="paisSuggestions" class="artist-suggestions"></div>
                <div id="artistasPorPais" class="artist-selection-modal" style="display: none;">
                    <div class="modal-header-custom">
                        <h5>Selecione o Artista</h5>
                        <button type="button" class="close-modal" onclick="closeArtistModal()">&times;</button>
                    </div>
                    <div class="artist-list" id="artistList"></div>
                    <div class="add-new-artist">
                        <button type="button" class="btn-add-new" onclick="addNewArtist()">+ Adicionar Novo Artista</button>
                    </div>
                </div>
            </div>
        </div>
        
        <div id="newArtistFields" style="display: none;">
            <div class="form-col-full">
                <label for="artista_image" class="form-label">URL da Foto do Artista</label>
                <input type="url" class="form-control" id="artista_image" name="artista_image" placeholder="https://exemplo.com/foto.jpg">
            </div>
        </div>
        <div class="form-col-full">
            <label for="musica_titulo" class="form-label">Título da Música</label>
            <input type="text" class="form-control" id="musica_titulo" name="musica_titulo" placeholder="Digite o título da música" required>
        </div>
        <div class="form-col-full">
            <label for="musica_capa" class="form-label">URL da Capa</label>
            <input type="url" class="form-control" id="musica_capa" name="musica_capa" placeholder="https://exemplo.com/capa.jpg" required>
        </div>
        <div class="form-col-full">
            <label for="musica_link" class="form-label">Link do Áudio (YouTube)</label>
            <input type="url" class="form-control" id="musica_link" name="musica_link" placeholder="https://youtube.com/watch?v=..." required>
        </div>
        <div class="form-col-full" style="text-align: center; margin-top: 30px;">
            <button type="submit" class="btn-neon">Cadastrar Música</button>
        </div>
    </form>
</div>