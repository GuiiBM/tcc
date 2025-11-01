<?php
function verificarPerfilCompleto($usuario_id, $conexao) {
    $stmt = mysqli_prepare($conexao, "SELECT u.*, a.artista_id FROM usuarios u LEFT JOIN artista a ON u.artista_id = a.artista_id WHERE u.usuario_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $usuario_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $usuario = mysqli_fetch_assoc($result);
    
    if (!$usuario) {
        return false;
    }
    
    $campos_obrigatorios = ['usuario_idade', 'usuario_cidade'];
    $perfil_incompleto = false;
    
    foreach ($campos_obrigatorios as $campo) {
        if (empty($usuario[$campo])) {
            $perfil_incompleto = true;
            break;
        }
    }
    
    // Verificar se tem perfil de artista
    if (!$usuario['artista_id']) {
        $perfil_incompleto = true;
    }
    
    // Verificar se usuário do Google tem senha vazia
    if (empty($usuario['usuario_senha'])) {
        $perfil_incompleto = true;
    }
    
    return !$perfil_incompleto;
}

function completarPerfilAutomatico($usuario_id, $conexao) {
    // Buscar dados do usuário
    $stmt = mysqli_prepare($conexao, "SELECT * FROM usuarios WHERE usuario_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $usuario_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $usuario = mysqli_fetch_assoc($result);
    
    if (!$usuario) {
        return false;
    }
    
    $atualizado = false;
    
    // Se não tem perfil de artista, criar um
    if (!$usuario['artista_id']) {
        $foto_artista = $usuario['usuario_foto'] ?: 'Componentes/icones/icone.png';
        $cidade_artista = $usuario['usuario_cidade'] ?: '';
        
        $stmt_artista = mysqli_prepare($conexao, "INSERT INTO artista (artista_nome, artista_cidade, artista_image) VALUES (?, ?, ?)");
        mysqli_stmt_bind_param($stmt_artista, "sss", $usuario['usuario_nome'], $cidade_artista, $foto_artista);
        
        if (mysqli_stmt_execute($stmt_artista)) {
            $artista_id = mysqli_insert_id($conexao);
            
            // Vincular usuário ao artista
            $stmt_update = mysqli_prepare($conexao, "UPDATE usuarios SET artista_id = ? WHERE usuario_id = ?");
            mysqli_stmt_bind_param($stmt_update, "ii", $artista_id, $usuario_id);
            mysqli_stmt_execute($stmt_update);
            
            $atualizado = true;
        }
    }
    
    // Se usuário do Google não tem senha, gerar uma temporária
    if (empty($usuario['usuario_senha'])) {
        $senha_temporaria = 'temp_' . substr(md5($usuario_id . time()), 0, 8);
        $senha_hash = password_hash($senha_temporaria, PASSWORD_DEFAULT);
        
        $stmt_senha = mysqli_prepare($conexao, "UPDATE usuarios SET usuario_senha = ? WHERE usuario_id = ?");
        mysqli_stmt_bind_param($stmt_senha, "si", $senha_hash, $usuario_id);
        
        if (mysqli_stmt_execute($stmt_senha)) {
            // Salvar senha temporária na sessão para mostrar ao usuário
            $_SESSION['senha_temporaria'] = $senha_temporaria;
            $atualizado = true;
        }
    }
    
    return $atualizado;
}

function mostrarAlertaPerfilIncompleto() {
    if (isset($_SESSION['senha_temporaria'])) {
        echo "<div class='alert alert-info' style='position: fixed; top: 20px; right: 20px; z-index: 1000; background: #0969da; color: white; padding: 15px; border-radius: 8px; max-width: 300px;'>";
        echo "<strong>Senha temporária criada!</strong><br>";
        echo "Sua senha temporária é: <strong>" . $_SESSION['senha_temporaria'] . "</strong><br>";
        echo "<small>Recomendamos alterar sua senha nas configurações.</small>";
        echo "<button onclick='this.parentElement.style.display=\"none\"' style='float: right; background: none; border: none; color: white; font-size: 18px; cursor: pointer;'>&times;</button>";
        echo "</div>";
        unset($_SESSION['senha_temporaria']);
    }
}
?>