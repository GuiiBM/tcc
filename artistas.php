<?php
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    include "Componentes/páginas/head.php";
    include "Componentes/páginas/header.php";
    include "Componentes/páginas/artistas.php";
    include "Componentes/páginas/footer.php";
?>