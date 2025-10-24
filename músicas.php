<?php 
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
include "Componentes/páginas/php/verificar_login.php";
redirecionarSeNaoLogado();
include "Componentes/páginas/head.php";
include "Componentes/páginas/header.php";
include "Componentes/páginas/formMusica.php";
?>