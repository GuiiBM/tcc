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
<div style='max-width: 1000px; margin: 50px auto; padding: 30px; background: var(--bg-surface); border-radius: 20px; color: var(--text-primary); border: 2px solid var(--border-accent);'>

<h2 style='color: var(--accent); text-align: center; margin-bottom: 30px;'>🔍 Preview da Combinação</h2>

<div style='background: var(--warning-soft); padding: 20px; margin-bottom: 30px; border-radius: 10px; border-left: 4px solid var(--warning); text-align: center;'>
    <h3 style='color: var(--warning); margin: 0 0 10px 0;'>⚠️ Confirmação Necessária</h3>
    <p style='margin: 0; color: var(--text-primary);'>Você está prestes a combinar dois usuários. Esta ação é <strong>irreversível</strong>!</p>
</div>

<div style='display: grid; grid-template-columns: 1fr auto 1fr; gap: 20px; align-items: center; margin-bottom: 30px;'>
    <!-- Usuário Principal -->
    <div style='background: var(--success-soft); padding: 20px; border-radius: 15px; border: 2px solid rgba(63, 185, 80, 0.35);'>
        <h3 style='color: var(--success); text-align: center; margin: 0 0 15px 0;'>✅ SERÁ MANTIDO</h3>
        <div style='text-align: center; margin-bottom: 15px;'>
            <div style='width: 80px; height: 80px; border-radius: 50%; background: linear-gradient(135deg, var(--success), #45a049); display: flex; align-items: center; justify-content: center; margin: 0 auto; font-size: 2rem; color: white;'>
                👤
            </div>
        </div>
        <h4 style='color: var(--accent); margin: 0 0 10px 0; text-align: center;'><?php echo htmlspecialchars($dados_principal['usuario_nome']); ?></h4>
        <p><strong>Email:</strong> <?php echo htmlspecialchars($dados_principal['usuario_email']); ?></p>
        <p><strong>Cidade:</strong> <?php echo htmlspecialchars($dados_principal['usuario_cidade'] ?: 'Não informado'); ?></p>
        <p><strong>Tipo:</strong> <?php echo ucfirst($dados_principal['usuario_tipo']); ?></p>
        <p><strong>Criado:</strong> <?php echo date('d/m/Y H:i', strtotime($dados_principal['usuario_data_criacao'])); ?></p>
        <p><strong>Curtidas:</strong> <?php echo $curtidas_principal; ?></p>
        <p><strong>Artista:</strong> <?php echo htmlspecialchars($dados_principal['artista_nome'] ?: 'Não vinculado'); ?></p>
    </div>
    
    <!-- Seta -->
    <div style='text-align: center;'>
        <div style='font-size: 3rem; color: var(--accent);'>→</div>
        <p style='color: var(--accent-info); font-weight: bold; margin: 10px 0;'>COMBINAR</p>
    </div>
    
    <!-- Usuário Secundário -->
    <div style='background: var(--danger-soft); padding: 20px; border-radius: 15px; border: 2px solid rgba(248, 81, 73, 0.35);'>
        <h3 style='color: var(--danger); text-align: center; margin: 0 0 15px 0;'>❌ SERÁ REMOVIDO</h3>
        <div style='text-align: center; margin-bottom: 15px;'>
            <div style='width: 80px; height: 80px; border-radius: 50%; background: linear-gradient(135deg, var(--danger), #d32f2f); display: flex; align-items: center; justify-content: center; margin: 0 auto; font-size: 2rem; color: white;'>
                👤
            </div>
        </div>
        <h4 style='color: var(--accent); margin: 0 0 10px 0; text-align: center;'><?php echo htmlspecialchars($dados_secundario['usuario_nome']); ?></h4>
        <p><strong>Email:</strong> <?php echo htmlspecialchars($dados_secundario['usuario_email']); ?></p>
        <p><strong>Cidade:</strong> <?php echo htmlspecialchars($dados_secundario['usuario_cidade'] ?: 'Não informado'); ?></p>
        <p><strong>Tipo:</strong> <?php echo ucfirst($dados_secundario['usuario_tipo']); ?></p>
        <p><strong>Criado:</strong> <?php echo date('d/m/Y H:i', strtotime($dados_secundario['usuario_data_criacao'])); ?></p>
        <p><strong>Curtidas:</strong> <?php echo $curtidas_secundario; ?> (serão transferidas)</p>
        <p><strong>Artista:</strong> <?php echo htmlspecialchars($dados_secundario['artista_nome'] ?: 'Não vinculado'); ?></p>
    </div>
</div>

<!-- Resultado da Combinação -->
<div style='background: rgba(0, 0, 0, 0.3); padding: 25px; border-radius: 15px; margin-bottom: 30px;'>
    <h3 style='color: var(--accent-info); text-align: center; margin: 0 0 20px 0;'>📊 Resultado da Combinação</h3>
    <div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;'>
        <div style='text-align: center; background: var(--success-soft); padding: 15px; border-radius: 8px;'>
            <div style='color: var(--success); font-size: 1.5rem; font-weight: bold;'><?php echo $curtidas_principal + $curtidas_secundario; ?></div>
            <div style='color: var(--text-primary);'>Total de Curtidas</div>
        </div>
        <div style='text-align: center; background: var(--warning-soft); padding: 15px; border-radius: 8px;'>
            <div style='color: var(--warning); font-size: 1.5rem; font-weight: bold;'>1</div>
            <div style='color: var(--text-primary);'>Usuário Final</div>
        </div>
        <div style='text-align: center; background: var(--danger-soft); padding: 15px; border-radius: 8px;'>
            <div style='color: var(--danger); font-size: 1.5rem; font-weight: bold;'>1</div>
            <div style='color: var(--text-primary);'>Usuário Removido</div>
        </div>
    </div>
</div>

<!-- Ações -->
<div style='text-align: center; display: flex; gap: 20px; justify-content: center;'>
    <a href="gerenciarUsuarios.php" class="btn-neon" style='text-decoration: none; background: var(--bg-surface-alt); color: var(--text-primary);'>
        ← Cancelar
    </a>
    
    <form method="POST" action="gerenciarUsuarios.php" style='display: inline;'>
        <input type="hidden" name="usuario_principal" value="<?php echo htmlspecialchars($usuario_principal); ?>">
        <input type="hidden" name="usuario_secundario" value="<?php echo htmlspecialchars($usuario_secundario); ?>">
        <button type="submit" name="combinar_usuarios" class="btn-neon"
                style='background: linear-gradient(135deg, var(--danger), #d32f2f); padding: 15px 30px;'
                onclick="return confirm('ATENÇÃO: Esta ação é irreversível!\n\nO usuário <?php echo htmlspecialchars(addslashes($dados_secundario['usuario_nome']), ENT_QUOTES); ?> será permanentemente removido.\n\nTem certeza que deseja continuar?')">
            🔄 Confirmar Combinação
        </button>
    </form>
</div>

</div>
</body>
</html>