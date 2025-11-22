<?php
function calcularSimilaridade($str1, $str2) {
    $str1 = strtolower(trim($str1));
    $str2 = strtolower(trim($str2));
    
    // Similaridade exata
    if ($str1 === $str2) return 100;
    
    // Similaridade usando similar_text
    similar_text($str1, $str2, $percent);
    return $percent;
}

function buscarUsuariosSimilares($conexao, $threshold = 0) {
    // Buscar todos os usuários e artistas
    $sql = "SELECT usuario_id, usuario_nome, usuario_email, usuario_cidade, usuario_data_criacao, 'usuario' as tipo FROM usuarios 
            UNION ALL 
            SELECT CONCAT('A', artista_id) as usuario_id, artista_nome as usuario_nome, 'N/A' as usuario_email, 
                   artista_cidade as usuario_cidade, NOW() as usuario_data_criacao, 'artista' as tipo 
            FROM artista 
            ORDER BY usuario_nome";
    $result = mysqli_query($conexao, $sql);
    
    $usuarios = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $usuarios[] = $row;
    }
    
    $duplicados = [];
    
    for ($i = 0; $i < count($usuarios); $i++) {
        for ($j = $i + 1; $j < count($usuarios); $j++) {
            $usuario1 = $usuarios[$i];
            $usuario2 = $usuarios[$j];
            
            $similaridadeNome = calcularSimilaridade($usuario1['usuario_nome'], $usuario2['usuario_nome']);
            $emailsIguais = strtolower($usuario1['usuario_email']) === strtolower($usuario2['usuario_email']);
            $nomesIguais = strtolower(trim($usuario1['usuario_nome'])) === strtolower(trim($usuario2['usuario_nome']));
            $cidadesSimilares = calcularSimilaridade($usuario1['usuario_cidade'], $usuario2['usuario_cidade']);
            
            $motivos = [];
            if ($emailsIguais) $motivos[] = 'Email idêntico';
            if ($nomesIguais) $motivos[] = 'Nome idêntico';
            if ($similaridadeNome >= $threshold && !$nomesIguais) $motivos[] = "Nome {$similaridadeNome}% similar";
            if ($cidadesSimilares >= 90) $motivos[] = "Cidade {$cidadesSimilares}% similar";
            
            if ($similaridadeNome >= $threshold || $emailsIguais || $nomesIguais || $cidadesSimilares >= $threshold) {
                $duplicados[] = [
                    'usuario1' => $usuario1,
                    'usuario2' => $usuario2,
                    'similaridade_nome' => $similaridadeNome,
                    'similaridade_cidade' => $cidadesSimilares,
                    'emails_iguais' => $emailsIguais,
                    'nomes_iguais' => $nomesIguais,
                    'motivo' => implode(' + ', $motivos)
                ];
            }
        }
    }
    
    return $duplicados;
}

function buscarTodosUsuariosDetalhado($conexao) {
    $sql = "SELECT u.usuario_id, u.usuario_nome, u.usuario_email, 
                   COALESCE(u.usuario_cidade, '') as usuario_cidade, 
                   u.usuario_data_criacao, 
                   COALESCE(u.usuario_tipo, 'usuario') as usuario_tipo,
                   COALESCE(a.artista_nome, 'Não vinculado') as artista_nome, 
                   (SELECT COUNT(*) FROM curtidas c WHERE c.usuario_id = u.usuario_id) as total_curtidas,
                   'usuario' as tipo_registro
            FROM usuarios u 
            LEFT JOIN artista a ON u.artista_id = a.artista_id
            
            UNION ALL
            
            SELECT CONCAT('A', a.artista_id) as usuario_id, a.artista_nome as usuario_nome, 
                   'N/A' as usuario_email, 
                   COALESCE(a.artista_cidade, '') as usuario_cidade, 
                   NOW() as usuario_data_criacao, 
                   'artista' as usuario_tipo,
                   a.artista_nome, 
                   0 as total_curtidas,
                   'artista' as tipo_registro
            FROM artista a 
            WHERE a.artista_id NOT IN (SELECT COALESCE(artista_id, 0) FROM usuarios WHERE artista_id IS NOT NULL AND artista_id > 0)
            
            ORDER BY usuario_nome";
    return mysqli_query($conexao, $sql);
}

function contarCurtidas($conexao, $usuario_id) {
    $sql = "SELECT COUNT(*) as total FROM curtidas WHERE usuario_id = ?";
    $stmt = mysqli_prepare($conexao, $sql);
    mysqli_stmt_bind_param($stmt, "i", $usuario_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    return $row['total'];
}

function combinarUsuarios($conexao, $usuario_principal, $usuario_secundario) {
    $principal_eh_artista = strpos($usuario_principal, 'A') === 0;
    $secundario_eh_artista = strpos($usuario_secundario, 'A') === 0;
    
    if ($principal_eh_artista && $secundario_eh_artista) {
        // Artista + Artista
        $artista_principal = intval(substr($usuario_principal, 1));
        $artista_secundario = intval(substr($usuario_secundario, 1));
        
        mysqli_query($conexao, "UPDATE usuarios SET artista_id = $artista_principal WHERE artista_id = $artista_secundario");
        mysqli_query($conexao, "DELETE FROM artista WHERE artista_id = $artista_secundario");
        
    } else if (!$principal_eh_artista && !$secundario_eh_artista) {
        // Usuário + Usuário
        $usuario_principal = intval($usuario_principal);
        $usuario_secundario = intval($usuario_secundario);
        
        mysqli_query($conexao, "UPDATE curtidas SET usuario_id = $usuario_principal WHERE usuario_id = $usuario_secundario");
        
        $result = mysqli_query($conexao, "SELECT artista_id FROM usuarios WHERE usuario_id = $usuario_principal");
        $principal_data = mysqli_fetch_assoc($result);
        
        if (!$principal_data['artista_id']) {
            $result2 = mysqli_query($conexao, "SELECT artista_id FROM usuarios WHERE usuario_id = $usuario_secundario");
            $secundario_data = mysqli_fetch_assoc($result2);
            if ($secundario_data['artista_id']) {
                mysqli_query($conexao, "UPDATE usuarios SET artista_id = {$secundario_data['artista_id']} WHERE usuario_id = $usuario_principal");
            }
        }
        
        mysqli_query($conexao, "DELETE FROM usuarios WHERE usuario_id = $usuario_secundario");
        
    } else if ($principal_eh_artista && !$secundario_eh_artista) {
        // Artista + Usuário
        $artista_id = intval(substr($usuario_principal, 1));
        $usuario_id = intval($usuario_secundario);
        
        mysqli_query($conexao, "UPDATE usuarios SET artista_id = $artista_id WHERE usuario_id = $usuario_id");
        
    } else {
        // Usuário + Artista
        $usuario_id = intval($usuario_principal);
        $artista_id = intval(substr($usuario_secundario, 1));
        
        mysqli_query($conexao, "UPDATE usuarios SET artista_id = $artista_id WHERE usuario_id = $usuario_id");
        mysqli_query($conexao, "DELETE FROM artista WHERE artista_id = $artista_id");
    }
    
    return true;
}
?>