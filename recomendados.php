<?php
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    include "Componentes/páginas/head.php";
    include "Componentes/páginas/header.php";
    include "Componentes/páginas/php/DBConection.php";
    include "Componentes/páginas/php/funcoesMusicas.php";
?>

<main class="main page-artistas">
        <section class="principal">
    <div class="principal-content">
        <h1 style="text-align:center;">Recomendados - Ouça</h1>
    </div>
        <div class="grid-container" id="cardContainer">
            <?php
            if (isset($conexao)) {
                try {
                    $musicasMaisVisualizadas = buscarMusicasMaisVisualizadas($conexao);
                    if (!empty($musicasMaisVisualizadas)) {
                        foreach ($musicasMaisVisualizadas as $musica) {
                            $titulo = htmlspecialchars($musica['musica_titulo'], ENT_QUOTES, 'UTF-8');
                            $artista = htmlspecialchars($musica['artista_nome'], ENT_QUOTES, 'UTF-8');
                            $cidade = htmlspecialchars($musica['artista_cidade'], ENT_QUOTES, 'UTF-8');
                            $capa = htmlspecialchars($musica['musica_capa'], ENT_QUOTES, 'UTF-8');
                            $link = htmlspecialchars($musica['musica_link'], ENT_QUOTES, 'UTF-8');
                            $visualizacoes = isset($musica['total_visualizacoes']) ? $musica['total_visualizacoes'] : 0;
                            
                            echo "<div class='grid-card' onclick=\"redirectToPlay('$link', '$titulo', '$artista', {$musica['musica_id']})\">";
                            echo "<div class='title-card'>";
                            echo "<h2>$titulo</h2>";
                            echo "</div>";
                            echo "<img src='$capa' alt='$titulo' class='image-music-card'>";
                            echo "<div class='autor-card'>";
                            if ($visualizacoes > 0) {
                                echo "<small class='card-views'>👁 $visualizacoes visualizações</small>";
                            }
                            echo "<h4>$artista - $cidade</h4>";
                            echo "</div>";
                            echo "</div>";
                        }
                    } else {
                        echo "<p>Nenhuma música encontrada.</p>";
                    }
                } catch (Exception $e) {
                    echo "<p>Erro ao carregar músicas.</p>";
                }
            } else {
                echo "<p>Erro de conexão com o banco de dados.</p>";
            }
            ?>
        </div>
</section>
</main>


<script>
function redirectToPlay(audio, titulo, artista, musicaId) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'index.php';
    
    const fields = {
        'audio': audio,
        'titulo': titulo,
        'artista': artista,
        'id': musicaId
    };
    
    for (const [key, value] of Object.entries(fields)) {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = key;
        input.value = value;
        form.appendChild(input);
    }
    
    document.body.appendChild(form);
    form.submit();
}
</script>

<?php include "Componentes/páginas/footer.php"; ?>