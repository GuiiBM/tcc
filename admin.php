<?php 
// Detectar se está no ngrok e adicionar cabeçalho apenas se necessário
if (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'ngrok') !== false) {
    header('ngrok-skip-browser-warning: true');
}

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
include "Componentes/páginas/php/verificar_login.php";
redirecionarSeNaoAdmin();

include "Componentes/páginas/php/DBConection.php";
include "Componentes/páginas/head.php";
include "Componentes/páginas/header.php";
include "Componentes/páginas/admin.php";
?>