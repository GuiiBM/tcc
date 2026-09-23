<?php
function calcularSimilaridade($str1, $str2) {
    $str1 = strtolower(trim($str1));
    $str2 = strtolower(trim($str2));

    // Campos vazios não são "similares" entre si
    if ($str1 === '' || $str2 === '') return 0;

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

// Executa um comando preparado com parâmetros inteiros.
function sqlInteiros($conexao, $sql, ...$valores) {
    $stmt = mysqli_prepare($conexao, $sql);
    if (!$stmt) {
        return false;
    }
    if ($valores) {
        mysqli_stmt_bind_param($stmt, str_repeat('i', count($valores)), ...$valores);
    }
    return mysqli_stmt_execute($stmt) ? $stmt : false;
}

// Combina dois cadastros duplicados. IDs de artista vêm com prefixo "A".
// Tudo numa transação: ou a combinação acontece por inteiro, ou nada muda.
function combinarUsuarios($conexao, $usuario_principal, $usuario_secundario) {
    $principal_eh_artista = strpos($usuario_principal, 'A') === 0;
    $secundario_eh_artista = strpos($usuario_secundario, 'A') === 0;
    $ok = true;
    mysqli_begin_transaction($conexao);

    if ($principal_eh_artista && $secundario_eh_artista) {
        // Artista + Artista: migrar músicas e álbuns do secundário antes de removê-lo
        // (o artista secundário tem ON DELETE CASCADE em músicas e álbuns)
        $artista_principal = intval(substr($usuario_principal, 1));
        $artista_secundario = intval(substr($usuario_secundario, 1));
        $ok = sqlInteiros($conexao, "UPDATE musica SET musica_artista = ? WHERE musica_artista = ?", $artista_principal, $artista_secundario)
            && sqlInteiros($conexao, "UPDATE album SET album_artista = ? WHERE album_artista = ?", $artista_principal, $artista_secundario)
            && sqlInteiros($conexao, "UPDATE IGNORE seguidores SET artista_id = ? WHERE artista_id = ?", $artista_principal, $artista_secundario)
            && sqlInteiros($conexao, "UPDATE usuarios SET artista_id = ? WHERE artista_id = ? AND NOT EXISTS (SELECT 1 FROM (SELECT artista_id FROM usuarios WHERE artista_id = ?) x)", $artista_principal, $artista_secundario, $artista_principal)
            && sqlInteiros($conexao, "DELETE FROM artista WHERE artista_id = ?", $artista_secundario);

    } else if (!$principal_eh_artista && !$secundario_eh_artista) {
        // Usuário + Usuário
        $usuario_principal = intval($usuario_principal);
        $usuario_secundario = intval($usuario_secundario);

        // Remove curtidas do secundário que colidiriam com a chave única (musica_id, usuario_id)
        // do principal antes de reatribuir o restante
        $ok = sqlInteiros($conexao, "DELETE c2 FROM curtidas c2 INNER JOIN curtidas c1 ON c1.musica_id = c2.musica_id AND c1.usuario_id = ? WHERE c2.usuario_id = ?", $usuario_principal, $usuario_secundario)
            && sqlInteiros($conexao, "UPDATE curtidas SET usuario_id = ? WHERE usuario_id = ?", $usuario_principal, $usuario_secundario)
            && sqlInteiros($conexao, "UPDATE playlist SET usuario_id = ? WHERE usuario_id = ?", $usuario_principal, $usuario_secundario)
            && sqlInteiros($conexao, "UPDATE historico SET usuario_id = ? WHERE usuario_id = ?", $usuario_principal, $usuario_secundario)
            && sqlInteiros($conexao, "UPDATE IGNORE seguidores SET usuario_id = ? WHERE usuario_id = ?", $usuario_principal, $usuario_secundario);

        if ($ok) {
            $stmt = sqlInteiros($conexao, "SELECT artista_id FROM usuarios WHERE usuario_id = ?", $usuario_principal);
            $principal_data = $stmt ? mysqli_fetch_assoc(mysqli_stmt_get_result($stmt)) : null;
            $stmt = sqlInteiros($conexao, "SELECT artista_id FROM usuarios WHERE usuario_id = ?", $usuario_secundario);
            $secundario_data = $stmt ? mysqli_fetch_assoc(mysqli_stmt_get_result($stmt)) : null;
            // Apaga o secundário primeiro (libera o artista_id único) e depois
            // passa o artista dele para o principal, se o principal não tiver.
            $ok = sqlInteiros($conexao, "DELETE FROM usuarios WHERE usuario_id = ?", $usuario_secundario);
            if ($ok && empty($principal_data['artista_id']) && !empty($secundario_data['artista_id'])) {
                $ok = sqlInteiros($conexao, "UPDATE usuarios SET artista_id = ? WHERE usuario_id = ?", (int) $secundario_data['artista_id'], $usuario_principal);
            }
        }

    } else {
        // Artista + Usuário (em qualquer ordem): vincula o usuário ao artista, relação 1-para-1
        $artista_id = intval(substr($principal_eh_artista ? $usuario_principal : $usuario_secundario, 1));
        $usuario_id = intval($principal_eh_artista ? $usuario_secundario : $usuario_principal);
        $ok = sqlInteiros($conexao, "UPDATE usuarios SET artista_id = NULL WHERE artista_id = ? AND usuario_id <> ?", $artista_id, $usuario_id)
            && sqlInteiros($conexao, "UPDATE usuarios SET artista_id = ? WHERE usuario_id = ?", $artista_id, $usuario_id);
    }

    if (!$ok) {
        error_log('Combinar usuários: ' . mysqli_error($conexao));
        mysqli_rollback($conexao);
        return false;
    }
    mysqli_commit($conexao);
    return true;
}
?>