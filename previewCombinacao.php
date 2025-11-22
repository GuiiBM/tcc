<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

include "Componentes/páginas/php/verificar_login.php";
redirecionarSeNaoAdmin();

include "Componentes/páginas/php/DBConection.php";
include "Componentes/páginas/php/funcoesDuplicados.php";

if (!isset($_GET['principal']) || !isset($_GET['secundario'])) {
    header('Location: gerenciarUsuarios.php');
    exit;
}

$usuario_principal = $_GET['principal'];
$usuario_secundario = $_GET['secundario'];

// Buscar dados (usuários ou artistas)
function buscarDados($conexao, $id) {
    if (strpos($id, 'A') === 0) {
        // É artista
        $artista_id = intval(substr($id, 1));
        $sql = "SELECT artista_id as usuario_id, artista_nome as usuario_nome, 'N/A' as usuario_email, artista_cidade as usuario_cidade, NOW() as usuario_data_criacao, 'artista' as usuario_tipo, artista_nome FROM artista WHERE artista_id = ?";
        $stmt = mysqli_prepare($conexao, $sql);
        mysqli_stmt_bind_param($stmt, "i", $artista_id);
    } else {
        // É usuário
        $sql = "SELECT u.*, a.artista_nome FROM usuarios u LEFT JOIN artista a ON u.artista_id = a.artista_id WHERE u.usuario_id = ?";
        $stmt = mysqli_prepare($conexao, $sql);
        mysqli_stmt_bind_param($stmt, "i", $id);
    }
    mysqli_stmt_execute($stmt);
    return mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
}

$dados_principal = buscarDados($conexao, $usuario_principal);
$dados_secundario = buscarDados($conexao, $usuario_secundario);

if (!$dados_principal || !$dados_secundario) {
    header('Location: gerenciarUsuarios.php');
    exit;
}

$curtidas_principal = strpos($usuario_principal, 'A') === 0 ? 0 : contarCurtidas($conexao, $usuario_principal);
$curtidas_secundario = strpos($usuario_secundario, 'A') === 0 ? 0 : contarCurtidas($conexao, $usuario_secundario);

include "Componentes/páginas/head.php";
?>

<body>
<div style='max-width: 1000px; margin: 50px auto; padding: 30px; background: linear-gradient(145deg, rgba(22, 27, 34, 0.95), rgba(13, 17, 23, 0.9)); border-radius: 20px; color: #f0f6fc; border: 2px solid rgba(255, 215, 0, 0.3);'>

<h2 style='color: #ffd700; text-align: center; margin-bottom: 30px; text-shadow: 0 0 10px rgba(255, 215, 0, 0.5);'>🔍 Preview da Combinação</h2>

<div style='background: rgba(255, 193, 7, 0.1); padding: 20px; margin-bottom: 30px; border-radius: 10px; border-left: 4px solid #FFC107; text-align: center;'>
    <h3 style='color: #FFC107; margin: 0 0 10px 0;'>⚠️ Confirmação Necessária</h3>
    <p style='margin: 0; color: #f0f6fc;'>Você está prestes a combinar dois usuários. Esta ação é <strong>irreversível</strong>!</p>
</div>

<div style='display: grid; grid-template-columns: 1fr auto 1fr; gap: 20px; align-items: center; margin-bottom: 30px;'>
    <!-- Usuário Principal -->
    <div style='background: rgba(76, 175, 80, 0.1); padding: 20px; border-radius: 15px; border: 2px solid rgba(76, 175, 80, 0.3);'>
        <h3 style='color: #4CAF50; text-align: center; margin: 0 0 15px 0;'>✅ SERÁ MANTIDO</h3>
        <div style='text-align: center; margin-bottom: 15px;'>
            <div style='width: 80px; height: 80px; border-radius: 50%; background: linear-gradient(135deg, #4CAF50, #45a049); display: flex; align-items: center; justify-content: center; margin: 0 auto; font-size: 2rem; color: white;'>
                👤
            </div>
        </div>
        <h4 style='color: #ffd700; margin: 0 0 10px 0; text-align: center;'><?php echo htmlspecialchars($dados_principal['usuario_nome']); ?></h4>
        <p><strong>Email:</strong> <?php echo htmlspecialchars($dados_principal['usuario_email']); ?></p>
        <p><strong>Cidade:</strong> <?php echo htmlspecialchars($dados_principal['usuario_cidade'] ?: 'Não informado'); ?></p>
        <p><strong>Tipo:</strong> <?php echo ucfirst($dados_principal['usuario_tipo']); ?></p>
        <p><strong>Criado:</strong> <?php echo date('d/m/Y H:i', strtotime($dados_principal['usuario_data_criacao'])); ?></p>
        <p><strong>Curtidas:</strong> <?php echo $curtidas_principal; ?></p>
        <p><strong>Artista:</strong> <?php echo $dados_principal['artista_nome'] ?: 'Não vinculado'; ?></p>
    </div>
    
    <!-- Seta -->
    <div style='text-align: center;'>
        <div style='font-size: 3rem; color: #ffd700;'>→</div>
        <p style='color: #00d9ff; font-weight: bold; margin: 10px 0;'>COMBINAR</p>
    </div>
    
    <!-- Usuário Secundário -->
    <div style='background: rgba(244, 67, 54, 0.1); padding: 20px; border-radius: 15px; border: 2px solid rgba(244, 67, 54, 0.3);'>
        <h3 style='color: #f44336; text-align: center; margin: 0 0 15px 0;'>❌ SERÁ REMOVIDO</h3>
        <div style='text-align: center; margin-bottom: 15px;'>
            <div style='width: 80px; height: 80px; border-radius: 50%; background: linear-gradient(135deg, #f44336, #d32f2f); display: flex; align-items: center; justify-content: center; margin: 0 auto; font-size: 2rem; color: white;'>
                👤
            </div>
        </div>
        <h4 style='color: #ffd700; margin: 0 0 10px 0; text-align: center;'><?php echo htmlspecialchars($dados_secundario['usuario_nome']); ?></h4>
        <p><strong>Email:</strong> <?php echo htmlspecialchars($dados_secundario['usuario_email']); ?></p>
        <p><strong>Cidade:</strong> <?php echo htmlspecialchars($dados_secundario['usuario_cidade'] ?: 'Não informado'); ?></p>
        <p><strong>Tipo:</strong> <?php echo ucfirst($dados_secundario['usuario_tipo']); ?></p>
        <p><strong>Criado:</strong> <?php echo date('d/m/Y H:i', strtotime($dados_secundario['usuario_data_criacao'])); ?></p>
        <p><strong>Curtidas:</strong> <?php echo $curtidas_secundario; ?> (serão transferidas)</p>
        <p><strong>Artista:</strong> <?php echo $dados_secundario['artista_nome'] ?: 'Não vinculado'; ?></p>
    </div>
</div>

<!-- Resultado da Combinação -->
<div style='background: rgba(0, 0, 0, 0.3); padding: 25px; border-radius: 15px; margin-bottom: 30px;'>
    <h3 style='color: #00d9ff; text-align: center; margin: 0 0 20px 0;'>📊 Resultado da Combinação</h3>
    <div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;'>
        <div style='text-align: center; background: rgba(76, 175, 80, 0.1); padding: 15px; border-radius: 8px;'>
            <div style='color: #4CAF50; font-size: 1.5rem; font-weight: bold;'><?php echo $curtidas_principal + $curtidas_secundario; ?></div>
            <div style='color: #f0f6fc;'>Total de Curtidas</div>
        </div>
        <div style='text-align: center; background: rgba(255, 193, 7, 0.1); padding: 15px; border-radius: 8px;'>
            <div style='color: #FFC107; font-size: 1.5rem; font-weight: bold;'>1</div>
            <div style='color: #f0f6fc;'>Usuário Final</div>
        </div>
        <div style='text-align: center; background: rgba(244, 67, 54, 0.1); padding: 15px; border-radius: 8px;'>
            <div style='color: #f44336; font-size: 1.5rem; font-weight: bold;'>1</div>
            <div style='color: #f0f6fc;'>Usuário Removido</div>
        </div>
    </div>
</div>

<!-- Ações -->
<div style='text-align: center; display: flex; gap: 20px; justify-content: center;'>
    <a href="gerenciarUsuarios.php" class="btn-neon" style='text-decoration: none; background: linear-gradient(135deg, #6c757d, #5a6268);'>
        ← Cancelar
    </a>
    
    <form method="POST" action="gerenciarUsuarios.php" style='display: inline;'>
        <input type="hidden" name="usuario_principal" value="<?php echo $usuario_principal; ?>">
        <input type="hidden" name="usuario_secundario" value="<?php echo $usuario_secundario; ?>">
        <button type="submit" name="combinar_usuarios" class="btn-neon" 
                style='background: linear-gradient(135deg, #f44336, #d32f2f); padding: 15px 30px;'
                onclick="return confirm('ATENÇÃO: Esta ação é irreversível!\n\nO usuário <?php echo addslashes($dados_secundario['usuario_nome']); ?> será permanentemente removido.\n\nTem certeza que deseja continuar?')">
            🔄 Confirmar Combinação
        </button>
    </form>
</div>

</div>
</body>
</html>