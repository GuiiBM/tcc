<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Ressonance</title>
    <link rel="icon" type="image/png" href="Componentes/icones/icone.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" defer></script>
    <script src="Componentes/configuracoes/JS/zoomControl.js" defer></script>
    <script src="Componentes/configuracoes/JS/musicPlayer.js" defer></script>
    <script src="Componentes/configuracoes/JS/curtidas.js" defer></script>
    <script src="Componentes/configuracoes/JS/artistaAutocomplete.js?v=3" defer></script>
    <script src="Componentes/configuracoes/JS/randomizeMusics.js" defer></script>
    <script src="Componentes/configuracoes/JS/artistsLayout.js" defer></script>
    <script src="Componentes/configuracoes/JS/tooltip.js" defer></script>
    <script src="Componentes/configuracoes/JS/artistPopup.js" defer></script>
    <script src="Componentes/configuracoes/JS/autoUpdateDescriptions.js" defer></script>
    <script src="Componentes/configuracoes/JS/enhancedInteractions.js" defer></script>
    <link rel="stylesheet" href="Componentes/configuracoes/Styles/styleGeral.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="Componentes/configuracoes/Styles/styleVariables.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="Componentes/configuracoes/Styles/styleHeader.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="Componentes/configuracoes/Styles/styleFooter.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="Componentes/configuracoes/Styles/styleAside.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="Componentes/configuracoes/Styles/stylePrincipal.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="Componentes/configuracoes/Styles/styleMain.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="Componentes/configuracoes/Styles/styleArtistas.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="Componentes/configuracoes/Styles/styleForms.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="Componentes/configuracoes/Styles/styleTooltip.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="Componentes/configuracoes/Styles/styleArtistPopup.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="Componentes/configuracoes/Styles/styleLogin.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="Componentes/configuracoes/Styles/styleEnhancements.css?v=<?php echo time(); ?>">
</head>
<body>

<?php
    if (file_exists("Componentes/páginas/php/DBConection.php")) {
        include "Componentes/páginas/php/DBConection.php";
    }
?>