<?php
// Buffer de saída: as páginas antigas do admin chamam header('Location')
// depois de já terem impresso HTML; com o buffer isso continua funcionando
// mesmo em hospedagens com output_buffering desligado.
if (!ob_get_level()) {
    ob_start();
}
include_once __DIR__ . "/php/app.php";
$tituloPagina = $tituloPagina ?? '';
// Páginas novas definem $paginaId e seguem a CSP estrita (só scripts do
// próprio site/nonce). As antigas do admin usam onclick="..." no HTML.
enviarCabecalhosSeguranca(!isset($paginaId));
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#0a0d12">
    <title><?= $tituloPagina ? e($tituloPagina) . ' | ' : '' ?>Ressonance</title>
    <base href="<?= e(getBasePath()) ?>">
    <link rel="icon" type="image/png" href="Componentes/icones/icone.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= asset('Componentes/configuracoes/Styles/styleVariables.css') ?>">
    <link rel="stylesheet" href="<?= asset('Componentes/configuracoes/Styles/styleGeral.css') ?>">
    <link rel="stylesheet" href="<?= asset('Componentes/configuracoes/Styles/stylePrincipal.css') ?>">
    <link rel="stylesheet" href="<?= asset('Componentes/configuracoes/Styles/styleMain.css') ?>">
    <link rel="stylesheet" href="<?= asset('Componentes/configuracoes/Styles/styleArtistas.css') ?>">
    <link rel="stylesheet" href="<?= asset('Componentes/configuracoes/Styles/styleForms.css') ?>">
    <link rel="stylesheet" href="<?= asset('Componentes/configuracoes/Styles/styleLogin.css') ?>">
    <link rel="stylesheet" href="<?= asset('Componentes/configuracoes/Styles/stylePropaganda.css') ?>">
    <link rel="stylesheet" href="<?= asset('Componentes/configuracoes/Styles/styleApp.css') ?>">
</head>
<body>
