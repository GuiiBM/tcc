<?php 
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    include "Componentes/páginas/head.php";
    include "Componentes/páginas/php/banco.php";
?>