<?php
include "Componentes/páginas/php/DBConection.php";
include "Componentes/páginas/php/funcoesDuplicados.php";

echo "<h2>Teste do Sistema</h2>";

// Teste 1: Combinar dois usuários
echo "<h3>1. Combinando usuários 1 e 2:</h3>";
$resultado = combinarUsuarios($conexao, "1", "2");
echo $resultado ? "✅ Sucesso" : "❌ Erro";

// Teste 2: Vincular usuário com artista
echo "<h3>2. Vinculando usuário 3 com artista 1:</h3>";
$resultado = mysqli_query($conexao, "UPDATE usuarios SET artista_id = 1 WHERE usuario_id = 3");
echo $resultado ? "✅ Sucesso" : "❌ Erro: " . mysqli_error($conexao);

// Teste 3: Combinar usuário com artista
echo "<h3>3. Combinando usuário 4 com artista A1:</h3>";
$resultado = combinarUsuarios($conexao, "4", "A1");
echo $resultado ? "✅ Sucesso" : "❌ Erro";

// Verificar resultados
echo "<h3>Verificação Final:</h3>";
$result = mysqli_query($conexao, "SELECT usuario_id, usuario_nome, artista_id FROM usuarios LIMIT 10");
while ($row = mysqli_fetch_assoc($result)) {
    echo "<p>Usuário {$row['usuario_id']}: {$row['usuario_nome']} - Artista: " . ($row['artista_id'] ?: 'Nenhum') . "</p>";
}

echo "<h3>Artistas:</h3>";
$result = mysqli_query($conexao, "SELECT artista_id, artista_nome FROM artista LIMIT 10");
while ($row = mysqli_fetch_assoc($result)) {
    echo "<p>Artista {$row['artista_id']}: {$row['artista_nome']}</p>";
}
?>