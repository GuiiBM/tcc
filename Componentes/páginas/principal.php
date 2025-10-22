<section class="principal">
    <div class="principal-content">
        <h1>Músicas Disponíveis</h1>
    </div>
    <div class="scroll-container">
        <div class="scroll-controls">
            <button class="scroll-btn" data-direction="left" data-container="cardContainer">‹</button>
            <button class="scroll-btn" data-direction="right" data-container="cardContainer">›</button>
        </div>
        <div class="grid-container" id="cardContainer">
            <?php
            if (isset($conexao)) {
                include "Componentes/páginas/php/funcoesMusicas.php";
                $musicas = buscarMusicas($conexao);
                exibirMusicas($musicas);
            } else {
                echo "<p>Erro de conexão com o banco de dados.</p>";
            }
            ?>
        </div>
    </div>
    <script src="Componentes/configuracoes/JS/botton.js"></script>
</section>