<?php
session_start();
include "Componentes/páginas/php/verificar_login.php";
redirecionarSeNaoAdmin();
include "Componentes/páginas/head.php";
include "Componentes/páginas/header.php";
include "Componentes/páginas/gerenciarPropagandas.php";
?>