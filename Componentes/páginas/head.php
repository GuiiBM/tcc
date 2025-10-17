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
    <link rel="stylesheet" href="Componentes/configuracoes/Styles/variables.css">
    <link rel="stylesheet" href="Componentes/configuracoes/Styles/styleHeader.css">
    <link rel="stylesheet" href="Componentes/configuracoes/Styles/styleFooter.css">
    <link rel="stylesheet" href="Componentes/configuracoes/Styles/styleAside.css">
    <link rel="stylesheet" href="Componentes/configuracoes/Styles/stylePrincipal.css">
    <link rel="stylesheet" href="Componentes/configuracoes/Styles/styleMain.css">
    <link rel="stylesheet" href="Componentes/configuracoes/Styles/styleGeral.css">
    <link rel="stylesheet" href="Componentes/configuracoes/Styles/styleArtistas.css">
</head>
<body>

<?php
    $host="localhost:33060";
    $usuario="root";
    $senha="";
    $banco="musicas";
    $conexao = mysqli_connect($host, $usuario, $senha) or die ("Não foi possivel fazer a conexão com o servidor");
    mysqli_select_db($conexao,$banco) or die ("Não foi possível fazer a conexão com o banco de dados");
?>