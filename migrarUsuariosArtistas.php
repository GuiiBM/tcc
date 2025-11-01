<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

include "Componentes/páginas/php/DBConection.php";
include "Componentes/páginas/php/verificarPerfilCompleto.php";
include "Componentes/páginas/head.php";
?>

<body>
<div style='max-width: 900px; margin: 50px auto; padding: 30px; background: linear-gradient(145deg, rgba(22, 27, 34, 0.95), rgba(13, 17, 23, 0.9)); border-radius: 20px; color: #f0f6fc; border: 2px solid rgba(255, 215, 0, 0.3); box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4);'>
<h2 style='color: #ffd700; text-align: center; margin-bottom: 30px; text-shadow: 0 0 10px rgba(255, 215, 0, 0.5); font-size: 2rem;'>🎵 Migração de Usuários para Artistas</h2>

<?php

// Verificar todos os usuários
echo "<div style='background: rgba(0, 217, 255, 0.1); padding: 12px 20px; margin: 10px 0; border-radius: 10px; border-left: 4px solid #00d9ff;'><p style='margin: 0; color: #00d9ff; font-weight: 600;'>🔍 Verificando todos os usuários cadastrados...</p></div>";
$sql_usuarios = "SELECT usuario_id, usuario_nome, usuario_cidade, usuario_foto, usuario_senha, artista_id FROM usuarios";
$result_usuarios = mysqli_query($conexao, $sql_usuarios);

$usuarios_migrados = 0;
$senhas_criadas = 0;

if (mysqli_num_rows($result_usuarios) > 0) {
    while ($usuario = mysqli_fetch_assoc($result_usuarios)) {
        $usuario_atualizado = false;
        
        // Verificar se usuário não tem perfil de artista
        if (!$usuario['artista_id']) {
            echo "<div style='background: rgba(255, 255, 255, 0.05); padding: 8px 15px; margin: 3px 0; border-radius: 6px;'><p style='margin: 0; color: #f0f6fc; font-size: 0.9rem;'>🎨 Criando perfil de artista para: " . $usuario['usuario_nome'] . "</p></div>";
            
            $foto_artista = $usuario['usuario_foto'] ?: 'Componentes/icones/icone.png';
            $cidade_artista = $usuario['usuario_cidade'] ?: '';
            
            $stmt_artista = mysqli_prepare($conexao, "INSERT INTO artista (artista_nome, artista_cidade, artista_image) VALUES (?, ?, ?)");
            mysqli_stmt_bind_param($stmt_artista, "sss", $usuario['usuario_nome'], $cidade_artista, $foto_artista);
            
            if (mysqli_stmt_execute($stmt_artista)) {
                $artista_id = mysqli_insert_id($conexao);
                
                // Vincular usuário ao artista
                $stmt_update = mysqli_prepare($conexao, "UPDATE usuarios SET artista_id = ? WHERE usuario_id = ?");
                mysqli_stmt_bind_param($stmt_update, "ii", $artista_id, $usuario['usuario_id']);
                
                if (mysqli_stmt_execute($stmt_update)) {
                    echo "<div style='background: rgba(76, 175, 80, 0.1); padding: 10px 15px; margin: 5px 0; border-radius: 8px; border-left: 4px solid #4CAF50;'><p style='margin: 0; color: #4CAF50;'>✓ Perfil de artista criado para: <strong>" . $usuario['usuario_nome'] . "</strong></p></div>";
                    $usuarios_migrados++;
                    $usuario_atualizado = true;
                }
            }
        }
        
        // Verificar se usuário não tem senha (usuário do Google)
        if (empty($usuario['usuario_senha'])) {
            echo "<div style='background: rgba(255, 255, 255, 0.05); padding: 8px 15px; margin: 3px 0; border-radius: 6px;'><p style='margin: 0; color: #f0f6fc; font-size: 0.9rem;'>🔐 Gerando senha temporária para: " . $usuario['usuario_nome'] . "</p></div>";
            
            $senha_temporaria = 'temp_' . substr(md5($usuario['usuario_id'] . time()), 0, 8);
            $senha_hash = password_hash($senha_temporaria, PASSWORD_DEFAULT);
            
            $stmt_senha = mysqli_prepare($conexao, "UPDATE usuarios SET usuario_senha = ? WHERE usuario_id = ?");
            mysqli_stmt_bind_param($stmt_senha, "si", $senha_hash, $usuario['usuario_id']);
            
            if (mysqli_stmt_execute($stmt_senha)) {
                echo "<div style='background: rgba(255, 193, 7, 0.1); padding: 10px 15px; margin: 5px 0; border-radius: 8px; border-left: 4px solid #FFC107;'><p style='margin: 0; color: #FFC107;'>✓ Senha temporária criada para: <strong>" . $usuario['usuario_nome'] . "</strong> (Senha: <code style='background: rgba(255,255,255,0.1); padding: 2px 6px; border-radius: 4px;'>$senha_temporaria</code>)</p></div>";
                $senhas_criadas++;
                $usuario_atualizado = true;
            }
        }
        
        if (!$usuario_atualizado) {
            echo "<div style='background: rgba(139, 148, 158, 0.1); padding: 8px 15px; margin: 3px 0; border-radius: 6px; border-left: 3px solid #8b949e;'><p style='margin: 0; color: #8b949e; font-size: 0.9rem;'>- " . $usuario['usuario_nome'] . " já está completo</p></div>";
        }
    }
} else {
    echo "<p>Nenhum usuário encontrado.</p>";
}

echo "<div style='text-align: center; margin-top: 30px; padding: 25px; background: linear-gradient(145deg, rgba(13, 17, 23, 0.9), rgba(22, 27, 34, 0.8)); border-radius: 16px; border: 1px solid rgba(0, 217, 255, 0.2);'>";
echo "<h3 style='color: #ffd700; font-size: 1.5rem; margin-bottom: 20px; text-shadow: 0 0 8px rgba(255, 215, 0, 0.4);'>✅ Migração Concluída!</h3>";
echo "<div style='display: flex; justify-content: center; gap: 30px; margin: 20px 0; flex-wrap: wrap;'>";
echo "<div style='background: rgba(76, 175, 80, 0.1); padding: 15px 20px; border-radius: 12px; border: 1px solid rgba(76, 175, 80, 0.3);'>";
echo "<p style='margin: 0; color: #4CAF50; font-weight: bold;'>👥 Usuários migrados: $usuarios_migrados</p>";
echo "</div>";
echo "<div style='background: rgba(255, 193, 7, 0.1); padding: 15px 20px; border-radius: 12px; border: 1px solid rgba(255, 193, 7, 0.3);'>";
echo "<p style='margin: 0; color: #FFC107; font-weight: bold;'>🔐 Senhas criadas: $senhas_criadas</p>";
echo "</div>";
echo "</div>";
echo "<div style='margin-top: 25px;'>";
echo "<a href='index.php' style='background: linear-gradient(135deg, #ffd700, #ffed4e); color: #0d1117; padding: 14px 28px; border-radius: 12px; text-decoration: none; font-weight: bold; margin-right: 15px; display: inline-block; transition: all 0.3s ease; box-shadow: 0 4px 16px rgba(255, 215, 0, 0.3);'>🏠 Ir para Início</a>";
echo "<a href='admin.php' style='background: linear-gradient(135deg, #0969da, #1f6feb); color: white; padding: 14px 28px; border-radius: 12px; text-decoration: none; font-weight: bold; display: inline-block; transition: all 0.3s ease; box-shadow: 0 4px 16px rgba(9, 105, 218, 0.3);'>⚙️ Ir para Admin</a>";
echo "</div>";
echo "</div>";
echo "</div>";
echo "</body></html>";
?>