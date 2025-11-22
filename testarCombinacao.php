<?php
include "Componentes/páginas/php/DBConection.php";
include "Componentes/páginas/php/funcoesDuplicados.php";

echo "<h2>Teste de Combinação</h2>";

// Teste 1: Usuário com Artista
echo "<h3>Teste 1: Usuário 1 + Artista A1</h3>";
$resultado = combinarUsuarios($conexao, "1", "A1");
echo $resultado ? "✅ Sucesso" : "❌ Erro";

// Teste 2: Artista com Usuário  
echo "<h3>Teste 2: Artista A2 + Usuário 2</h3>";
$resultado = combinarUsuarios($conexao, "A2", "2");
echo $resultado ? "✅ Sucesso" : "❌ Erro";

// Verificar resultado
echo "<h3>Verificação:</h3>";
$result = mysqli_query($conexao, "SELECT usuario_id, usuario_nome, artista_id FROM usuarios LIMIT 5");
while ($row = mysqli_fetch_assoc($result)) {
    echo "<p>Usuário {$row['usuario_id']}: {$row['usuario_nome']} - Artista: {$row['artista_id']}</p>";
}
?>