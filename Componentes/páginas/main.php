<main class="main">
    <div class="content-wrapper">
        <?php 
        if (file_exists("Componentes/páginas/aside.php")) {
            include "Componentes/páginas/aside.php";
        }
        if (file_exists("Componentes/páginas/principal.php")) {
            include "Componentes/páginas/principal.php";
        }
        ?>
    </div>
</main>