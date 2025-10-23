<?php
include "Componentes/páginas/php/DBConection.php";

$artistName = "Eduardo Crigo";

// Testar busca do artista
$artistStmt = mysqli_prepare($conexao, "SELECT artista_nome, artista_cidade, artista_image FROM artista WHERE artista_nome = ?");
mysqli_stmt_bind_param($artistStmt, "s", $artistName);
mysqli_stmt_execute($artistStmt);
$artistResult = mysqli_stmt_get_result($artistStmt);
$artist = mysqli_fetch_assoc($artistResult);

echo "<h3>Teste do Artista: $artistName</h3>";
if ($artist) {
    echo "<p>Artista encontrado:</p>";
    echo "<pre>" . print_r($artist, true) . "</pre>";
} else {
    echo "<p>Artista NÃO encontrado</p>";
}

// Testar busca de músicas
$songsStmt = mysqli_prepare($conexao, "SELECT musica_id, musica_titulo, musica_audio, musica_image FROM musica WHERE artista_nome = ?");
mysqli_stmt_bind_param($songsStmt, "s", $artistName);
mysqli_stmt_execute($songsStmt);
$songsResult = mysqli_stmt_get_result($songsStmt);

echo "<h3>Músicas do artista:</h3>";
$songs = [];
while ($song = mysqli_fetch_assoc($songsResult)) {
    $songs[] = $song;
}

if (!empty($songs)) {
    echo "<pre>" . print_r($songs, true) . "</pre>";
} else {
    echo "<p>Nenhuma música encontrada</p>";
}
?>