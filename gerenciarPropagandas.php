<?php
require_once __DIR__ . '/Componentes/paginas/php/seguranca.php';
iniciarSessaoSegura();
include "Componentes/paginas/php/verificar_login.php";
redirecionarSeNaoAdmin();
include "Componentes/paginas/head.php";
include "Componentes/paginas/header.php";
include "Componentes/paginas/gerenciarPropagandas.php";
include "Componentes/paginas/footer.php";
?>
