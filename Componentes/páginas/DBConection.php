<?php
    $host="localhost";
    $usuario="root";
    $senha="";
    $banco="musicas";
    $conexao = mysqli_connect($host, $usuario, $senha) or die ("Não foi possivel fazer a conexão com o servidor");
    mysqli_select_db($conexao,$banco) or die ("Não foi possível fazer a conexão com o banco de dados");
?>