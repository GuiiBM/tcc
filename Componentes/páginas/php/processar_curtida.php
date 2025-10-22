<?php
include 'DBConection.php';
include 'funcoesMusicas.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (isset($input['acao']) && $input['acao'] === 'carregar' && isset($input['musica_id'])) {
        $musica_id = intval($input['musica_id']);
        $curtidas = contarCurtidas($conexao, $musica_id);
        $descurtidas = contarDescurtidas($conexao, $musica_id);
        
        echo json_encode([
            'success' => true,
            'curtidas' => $curtidas,
            'descurtidas' => $descurtidas
        ]);
    } elseif (isset($input['musica_id']) && isset($input['tipo'])) {
        $musica_id = intval($input['musica_id']);
        $tipo = $input['tipo'];
        
        if ($tipo === 'curtida' || $tipo === 'descurtida') {
            $sucesso = curtirMusica($conexao, $musica_id, $tipo);
            
            if ($sucesso) {
                $curtidas = contarCurtidas($conexao, $musica_id);
                $descurtidas = contarDescurtidas($conexao, $musica_id);
                
                echo json_encode([
                    'success' => true,
                    'curtidas' => $curtidas,
                    'descurtidas' => $descurtidas
                ]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Erro ao processar curtida']);
            }
        } else {
            echo json_encode(['success' => false, 'error' => 'Tipo inválido']);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Dados incompletos']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Método não permitido']);
}
?>