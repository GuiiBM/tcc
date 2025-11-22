<?php
include "Componentes/páginas/php/DBConection.php";

echo "<h2>Teste de Usuários no Banco</h2>";

// Contar usuários
$result = mysqli_query($conexao, "SELECT COUNT(*) as total FROM usuarios");
$total = mysqli_fetch_assoc($result)['total'];
echo "<p>Total de usuários: $total</p>";

// Listar primeiros 10 usuários
$result = mysqli_query($conexao, "SELECT usuario_id, usuario_nome, usuario_email FROM usuarios LIMIT 10");
echo "<h3>Primeiros 10 usuários:</h3>";
while ($row = mysqli_fetch_assoc($result)) {
    echo "<p>ID: {$row['usuario_id']} - Nome: {$row['usuario_nome']} - Email: {$row['usuario_email']}</p>";
}

// Contar artistas
$result = mysqli_query($conexao, "SELECT COUNT(*) as total FROM artista");
$total = mysqli_fetch_assoc($result)['total'];
echo "<p>Total de artistas: $total</p>";

// Listar primeiros 10 artistas
$result = mysqli_query($conexao, "SELECT artista_id, artista_nome FROM artista LIMIT 10");
echo "<h3>Primeiros 10 artistas:</h3>";
while ($row = mysqli_fetch_assoc($result)) {
    echo "<p>ID: {$row['artista_id']} - Nome: {$row['artista_nome']}</p>";
}
?>