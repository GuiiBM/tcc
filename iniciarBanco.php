<?php 
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    include "Componentes/páginas/head.php";
    include "Componentes/páginas/header.php";
    include "Componentes/páginas/php/banco.php";
    include "Componentes/páginas/footer.php";
?>