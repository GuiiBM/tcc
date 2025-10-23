<section class="principal">
    <div class="principal-content">
        <h1>Recomendações</h1>
    </div>
    
    <?php include_once "Componentes/páginas/php/funcoesMusicas.php"; ?>
    
    <!-- Músicas com Menos Curtidas -->
    <div class="scroll-container">
        <h2>Descubra Novas Músicas</h2>
        <div class="scroll-controls">
            <button class="scroll-btn" data-direction="left" data-container="menosCurtidasContainer">‹</button>
            <button class="scroll-btn" data-direction="right" data-container="menosCurtidasContainer">›</button>
        </div>
        <div class="grid-container" id="menosCurtidasContainer">
            <?php
            if (isset($conexao)) {
                try {
                    $musicasMenosCurtidas = buscarMusicasMenosCurtidas($conexao, 5);
                    if (!empty($musicasMenosCurtidas)) {
                        exibirMusicasRecomendadas($musicasMenosCurtidas);
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
    </div>
    
    <!-- Últimos Artistas -->
    <div class="scroll-container">
        <h2>Novos Artistas</h2>
        <div class="scroll-controls">
            <button class="scroll-btn" data-direction="left" data-container="artistasContainer">‹</button>
            <button class="scroll-btn" data-direction="right" data-container="artistasContainer">›</button>
        </div>
        <div class="grid-container" id="artistasContainer">
            <?php
            if (isset($conexao)) {
                try {
                    $artistas = buscarUltimosArtistas($conexao, 5);
                    if (!empty($artistas)) {
                        exibirArtistasRecomendados($artistas);
                    } else {
                        echo "<p>Nenhum artista encontrado.</p>";
                    }
                } catch (Exception $e) {
                    echo "<p>Erro ao carregar artistas.</p>";
                }
            } else {
                echo "<p>Erro de conexão com o banco de dados.</p>";
            }
            ?>
        </div>
    </div>

    <!-- Últimas Músicas -->
    <div class="scroll-container">
        <h2>Últimas Músicas Adicionadas</h2>
        <div class="scroll-controls">
            <button class="scroll-btn" data-direction="left" data-container="ultimasMusicasContainer">‹</button>
            <button class="scroll-btn" data-direction="right" data-container="ultimasMusicasContainer">›</button>
        </div>
        <div class="grid-container" id="ultimasMusicasContainer">
            <?php
            if (isset($conexao)) {
                try {
                    $ultimasMusicas = buscarUltimasMusicas($conexao, 5);
                    if (!empty($ultimasMusicas)) {
                        exibirMusicasRecomendadas($ultimasMusicas);
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
    </div>
    
    <!-- Músicas Disponíveis -->
    <div class="scroll-container">
        <h2>Músicas Disponíveis</h2>
        <div class="scroll-controls">
            <button class="scroll-btn" data-direction="left" data-container="cardContainer">‹</button>
            <button class="scroll-btn" data-direction="right" data-container="cardContainer">›</button>
        </div>
        <div class="grid-container" id="cardContainer">
            <?php
            if (isset($conexao)) {
                try {
                    $musicas = buscarMusicas($conexao);
                    if (!empty($musicas)) {
                        exibirMusicas($musicas);
                    } else {
                        echo "<p>Nenhuma música cadastrada.</p>";
                    }
                } catch (Exception $e) {
                    echo "<p>Erro ao carregar músicas.</p>";
                }
            } else {
                echo "<p>Erro de conexão com o banco de dados.</p>";
            }
            ?>
        </div>
    </div>
    <script src="Componentes/configuracoes/JS/botton.js"></script>
</section>