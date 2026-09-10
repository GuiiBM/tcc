<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

include "Componentes/páginas/php/verificar_login.php";
redirecionarSeNaoAdmin();
include "Componentes/páginas/php/DBConection.php";
include_once "Componentes/páginas/php/verificarPerfilCompleto.php";
include "Componentes/páginas/head.php";
include "Componentes/páginas/header.php";
?>

<div style='max-width: 900px; margin: 50px auto; padding: 30px; background: var(--bg-surface); border-radius: 20px; color: var(--text-primary); border: 2px solid var(--border-accent); box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4);'>
<h2 style='color: var(--accent); text-align: center; margin-bottom: 30px; font-size: 2rem;'>🎵 Migração de Usuários para Artistas</h2>

<?php

// Verificar todos os usuários
echo "<div style='background: var(--accent-info-soft); padding: 12px 20px; margin: 10px 0; border-radius: 10px; border-left: 4px solid var(--accent-info);'><p style='margin: 0; color: var(--accent-info); font-weight: 600;'>🔍 Verificando todos os usuários cadastrados...</p></div>";
$sql_usuarios = "SELECT usuario_id, usuario_nome, usuario_cidade, usuario_foto, usuario_senha, artista_id FROM usuarios";
$result_usuarios = mysqli_query($conexao, $sql_usuarios);

$usuarios_migrados = 0;
$senhas_criadas = 0;

if (mysqli_num_rows($result_usuarios) > 0) {
    while ($usuario = mysqli_fetch_assoc($result_usuarios)) {
        $usuario_atualizado = false;
        
        // Verificar se usuário não tem perfil de artista
        if (!$usuario['artista_id']) {
            echo "<div style='background: rgba(255, 255, 255, 0.05); padding: 8px 15px; margin: 3px 0; border-radius: 6px;'><p style='margin: 0; color: var(--text-primary); font-size: 0.9rem;'>🎨 Criando perfil de artista para: " . htmlspecialchars($usuario['usuario_nome']) . "</p></div>";
            
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
                    echo "<div style='background: var(--success-soft); padding: 10px 15px; margin: 5px 0; border-radius: 8px; border-left: 4px solid var(--success);'><p style='margin: 0; color: var(--success);'>✓ Perfil de artista criado para: <strong>" . htmlspecialchars($usuario['usuario_nome']) . "</strong></p></div>";
                    $usuarios_migrados++;
                    $usuario_atualizado = true;
                }
            }
        }
        
        // Verificar se usuário não tem senha (usuário do Google)
        if (empty($usuario['usuario_senha'])) {
            echo "<div style='background: rgba(255, 255, 255, 0.05); padding: 8px 15px; margin: 3px 0; border-radius: 6px;'><p style='margin: 0; color: var(--text-primary); font-size: 0.9rem;'>🔐 Gerando senha temporária para: " . htmlspecialchars($usuario['usuario_nome']) . "</p></div>";
            
            $senha_temporaria = 'temp_' . substr(md5($usuario['usuario_id'] . time()), 0, 8);
            $senha_hash = password_hash($senha_temporaria, PASSWORD_DEFAULT);
            
            $stmt_senha = mysqli_prepare($conexao, "UPDATE usuarios SET usuario_senha = ? WHERE usuario_id = ?");
            mysqli_stmt_bind_param($stmt_senha, "si", $senha_hash, $usuario['usuario_id']);
            
            if (mysqli_stmt_execute($stmt_senha)) {
                echo "<div style='background: var(--warning-soft); padding: 10px 15px; margin: 5px 0; border-radius: 8px; border-left: 4px solid var(--warning);'><p style='margin: 0; color: var(--warning);'>✓ Senha temporária criada para: <strong>" . htmlspecialchars($usuario['usuario_nome']) . "</strong> (o usuário deve usar 'Esqueci minha senha' ou entrar via Google)</p></div>";
                $senhas_criadas++;
                $usuario_atualizado = true;
            }
        }
        
        if (!$usuario_atualizado) {
            echo "<div style='background: rgba(139, 148, 158, 0.1); padding: 8px 15px; margin: 3px 0; border-radius: 6px; border-left: 3px solid var(--text-secondary);'><p style='margin: 0; color: var(--text-secondary); font-size: 0.9rem;'>- " . htmlspecialchars($usuario['usuario_nome']) . " já está completo</p></div>";
        }
    }
} else {
    echo "<p>Nenhum usuário encontrado.</p>";
}

echo "<div style='text-align: center; margin-top: 30px; padding: 25px; background: var(--bg-surface-alt); border-radius: 16px; border: 1px solid rgba(0, 217, 255, 0.2);'>";
echo "<h3 style='color: var(--accent); font-size: 1.5rem; margin-bottom: 20px;'>✅ Migração Concluída!</h3>";
echo "<div style='display: flex; justify-content: center; gap: 30px; margin: 20px 0; flex-wrap: wrap;'>";
echo "<div style='background: var(--success-soft); padding: 15px 20px; border-radius: 12px; border: 1px solid rgba(63, 185, 80, 0.35);'>";
echo "<p style='margin: 0; color: var(--success); font-weight: bold;'>👥 Usuários migrados: $usuarios_migrados</p>";
echo "</div>";
echo "<div style='background: var(--warning-soft); padding: 15px 20px; border-radius: 12px; border: 1px solid rgba(210, 153, 34, 0.35);'>";
echo "<p style='margin: 0; color: var(--warning); font-weight: bold;'>🔐 Senhas criadas: $senhas_criadas</p>";
echo "</div>";
echo "</div>";
echo "<div style='margin-top: 25px;'>";
echo "<a href='index.php' style='background: var(--accent); color: var(--bg-canvas); padding: 14px 28px; border-radius: 12px; text-decoration: none; font-weight: bold; margin-right: 15px; display: inline-block; transition: all 0.3s ease; box-shadow: 0 4px 16px var(--border-accent);'>🏠 Ir para Início</a>";
echo "<a href='admin.php' style='background: var(--accent-info); color: white; padding: 14px 28px; border-radius: 12px; text-decoration: none; font-weight: bold; display: inline-block; transition: background 0.2s ease;'>⚙️ Ir para Admin</a>";
echo "</div>";
echo "</div>";
echo "</div>";
echo "</body></html>";
?>