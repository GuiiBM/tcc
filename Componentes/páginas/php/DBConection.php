<?php
    $host="localhost";
    $usuario="root";
    $senha="";
    $banco="musicas";
    $conexao = mysqli_connect($host, $usuario, $senha, $banco) or die ("Não foi possivel fazer a conexão com o servidor");
?>