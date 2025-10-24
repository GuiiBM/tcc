<?php 
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    include "Componentes/páginas/head.php";
    include "Componentes/páginas/header.php";
    include "Componentes/páginas/main.php";
    include "Componentes/páginas/footer.php";
?>

<?php
// Verificar se veio dados via POST para tocar música
$musicData = null;
if ($_POST && isset($_POST['audio'], $_POST['titulo'], $_POST['artista'], $_POST['id'])) {
    $musicData = [
        'audio' => htmlspecialchars($_POST['audio']),
        'titulo' => htmlspecialchars($_POST['titulo']),
        'artista' => htmlspecialchars($_POST['artista']),
        'id' => (int)$_POST['id']
    ];
}
?>

<script>
// Tocar música se veio via POST
document.addEventListener('DOMContentLoaded', function() {
    <?php if ($musicData): ?>
    setTimeout(() => {
        if (window.musicPlayer) {
            playMusic('<?php echo $musicData['audio']; ?>', '<?php echo $musicData['titulo']; ?>', '<?php echo $musicData['artista']; ?>', <?php echo $musicData['id']; ?>);
        }
    }, 500);
    <?php endif; ?>
});
</script>