<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
include "Componentes/páginas/php/verificar_login.php";
redirecionarSeNaoLogado();
include "Componentes/páginas/head.php";
include "Componentes/páginas/header.php";
include_once "Componentes/páginas/php/funcoesMusicas.php";

$meuArtistaId = null;
if ($_SESSION['usuario_tipo'] !== 'admin') {
    $stmt = mysqli_prepare($conexao, "SELECT artista_id FROM usuarios WHERE usuario_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $_SESSION['usuario_id']);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if ($row = mysqli_fetch_assoc($result)) {
        $meuArtistaId = $row['artista_id'];
    }
}
$minhasMusicas = $meuArtistaId ? buscarMusicasPorArtista($conexao, $meuArtistaId) : [];
?>
<main class="main">
    <section class="principal">
        <?php if ($meuArtistaId): ?>
        <div class="admin-toolbar">
            <button type="button" class="btn-neon" id="tabCadastrar">Cadastrar Música</button>
            <button type="button" class="btn-neon" id="tabMinhas">Minhas Músicas (<?= count($minhasMusicas) ?>)</button>
        </div>
        <?php endif; ?>

        <div id="painelCadastrar">
            <?php include "Componentes/páginas/formMusica.php"; ?>
        </div>

        <?php if ($meuArtistaId): ?>
        <div id="painelMinhas" hidden>
            <div class="my-songs-grid">
                <?php
                if (!empty($minhasMusicas)) {
                    exibirMusicas($minhasMusicas);
                } else {
                    echo "<p class='my-songs-empty'>Você ainda não cadastrou nenhuma música.</p>";
                }
                ?>
            </div>
        </div>
        <script>
        (function() {
            const btnCadastrar = document.getElementById('tabCadastrar');
            const btnMinhas = document.getElementById('tabMinhas');
            const painelCadastrar = document.getElementById('painelCadastrar');
            const painelMinhas = document.getElementById('painelMinhas');

            function mostrar(ativo) {
                const cadastrarAtivo = ativo === 'cadastrar';
                painelCadastrar.hidden = !cadastrarAtivo;
                painelMinhas.hidden = cadastrarAtivo;
                btnCadastrar.classList.toggle('btn-active', cadastrarAtivo);
                btnCadastrar.classList.toggle('btn-neon-inactive', !cadastrarAtivo);
                btnMinhas.classList.toggle('btn-active', !cadastrarAtivo);
                btnMinhas.classList.toggle('btn-neon-inactive', cadastrarAtivo);
            }

            btnCadastrar.addEventListener('click', function() { mostrar('cadastrar'); });
            btnMinhas.addEventListener('click', function() { mostrar('minhas'); });
            mostrar('cadastrar');
        })();
        </script>
        <?php endif; ?>
    </section>
</main>
<?php include "Componentes/páginas/footer.php"; ?>
