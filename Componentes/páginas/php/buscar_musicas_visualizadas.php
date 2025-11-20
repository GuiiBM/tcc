<?php
include_once "funcoesMusicas.php";
include_once "DBConection.php";

if (isset($conexao)) {
    $musicasMaisVisualizadas = buscarMusicasMaisVisualizadas($conexao);
    if (!empty($musicasMaisVisualizadas)) {
        exibirMusicasRecomendadas($musicasMaisVisualizadas);
    } else {
        echo "<p>Nenhuma música encontrada.</p>";
    }
}
?>