<?php
include_once "Componentes/páginas/php/DBConection.php";

echo "<h2>Teste do Sistema de Visualizações</h2>";

// Verificar se a tabela existe
$result = mysqli_query($conexao, "SHOW TABLES LIKE 'visualizacoes'");
if (mysqli_num_rows($result) > 0) {
    echo "<p style='color: green;'>✓ Tabela 'visualizacoes' existe!</p>";
    
    // Mostrar estrutura da tabela
    $result = mysqli_query($conexao, "DESCRIBE visualizacoes");
    echo "<h3>Estrutura da tabela:</h3>";
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Chave</th><th>Padrão</th><th>Extra</th></tr>";
    while ($row = mysqli_fetch_assoc($result)) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "<td>" . $row['Default'] . "</td>";
        echo "<td>" . $row['Extra'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Contar visualizações
    $result = mysqli_query($conexao, "SELECT COUNT(*) as total FROM visualizacoes");
    $row = mysqli_fetch_assoc($result);
    echo "<p>Total de visualizações registradas: " . $row['total'] . "</p>";
    
} else {
    echo "<p style='color: red;'>✗ Tabela 'visualizacoes' não existe!</p>";
    echo "<p>Execute o arquivo banco.php para criar a tabela.</p>";
}

// Testar uma visualização
echo "<h3>Teste de Visualização</h3>";
echo "<button onclick='testarVisualizacao()'>Testar Visualização (Música ID: 1)</button>";
echo "<div id='resultado'></div>";

echo "<script>
function testarVisualizacao() {
    fetch('Componentes/páginas/php/processar_visualizacao.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            musica_id: 1
        })
    })
    .then(response => response.json())
    .then(data => {
        document.getElementById('resultado').innerHTML = '<p>Resultado: ' + data.message + '</p>';
        if (data.success) {
            location.reload(); // Recarregar para ver o novo total
        }
    })
    .catch(error => {
        document.getElementById('resultado').innerHTML = '<p>Erro: ' + error + '</p>';
    });
}
</script>";
?>