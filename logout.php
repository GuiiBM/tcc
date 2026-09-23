<?php
require_once __DIR__ . '/Componentes/paginas/php/seguranca.php';
iniciarSessaoSegura();

// Destruir todas as variáveis de sessão
$_SESSION = array();

// Destruir a sessão
session_destroy();

// Redirecionar para a página inicial
header('Location: index.php');
exit;
?>