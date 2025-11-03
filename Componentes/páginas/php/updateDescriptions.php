<?php
include "DBConection.php";

header('Content-Type: application/json');

try {
    if (isset($_POST['action']) && $_POST['action'] === 'update') {
        
        // Verificar timestamp da última atualização
        $lastUpdate = isset($_POST['lastUpdate']) ? $_POST['lastUpdate'] : null;
        
        // Copiar descrições dos usuários para artistas (apenas se necessário)
        $sql_update = "UPDATE artista a 
                       JOIN usuarios u ON a.artista_id = u.artista_id 
                       SET a.artista_descricao = u.usuario_descricao 
                       WHERE u.usuario_descricao IS NOT NULL AND u.usuario_descricao != '' 
                       AND (a.artista_descricao IS NULL OR a.artista_descricao != u.usuario_descricao)";
        
        $result1 = mysqli_query($conexao, $sql_update);
        $updated1 = mysqli_affected_rows($conexao);
        
        // Adicionar descrições padrão para artistas sem descrição
        $sql_default = "UPDATE artista 
                        SET artista_descricao = CONCAT('Artista talentoso de ', COALESCE(artista_cidade, 'localização não informada'), '. Explore suas músicas e descubra seu estilo único.') 
                        WHERE artista_descricao IS NULL OR artista_descricao = ''";
        
        $result2 = mysqli_query($conexao, $sql_default);
        $updated2 = mysqli_affected_rows($conexao);
        
        $totalUpdated = $updated1 + $updated2;
        
        // Só retornar descrições se houve atualizações ou é primeira execução
        $descriptions = [];
        if ($totalUpdated > 0 || !$lastUpdate) {
            $sql_select = "SELECT artista_nome, artista_descricao FROM artista WHERE artista_descricao IS NOT NULL";
            $result = mysqli_query($conexao, $sql_select);
            
            while ($row = mysqli_fetch_assoc($result)) {
                $descriptions[$row['artista_nome']] = $row['artista_descricao'];
            }
        }
        
        echo json_encode([
            'success' => true,
            'descriptions' => $descriptions,
            'updated' => $totalUpdated,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        
    } else {
        echo json_encode(['success' => false, 'message' => 'Ação inválida']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erro: ' . $e->getMessage()]);
}
?>