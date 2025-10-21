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
            include "Componentes/páginas/php/funcoesMusicas.php";
            $musicas = buscarMusicas($conexao);
            exibirMusicas($musicas);
            ?>
        </div>
    </div>
    <script src="Componentes/configuracoes/JS/botton.js"></script>
</section>