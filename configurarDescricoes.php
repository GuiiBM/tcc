<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
include "Componentes/páginas/php/verificar_login.php";
redirecionarSeNaoAdmin();
include "Componentes/páginas/php/DBConection.php";
include "Componentes/páginas/head.php";
include "Componentes/páginas/header.php";

// Capturar saída em buffer
ob_start();

// Adicionar coluna se não existir
$sql_check = "SHOW COLUMNS FROM artista LIKE 'artista_descricao'";
$result = mysqli_query($conexao, $sql_check);
$coluna_criada = false;
if (mysqli_num_rows($result) == 0) {
    mysqli_query($conexao, "ALTER TABLE artista ADD COLUMN artista_descricao TEXT");
    $coluna_criada = true;
}

// Copiar descrições dos usuários para artistas
$sql_update = "UPDATE artista a 
               JOIN usuarios u ON a.artista_id = u.artista_id 
               SET a.artista_descricao = u.usuario_descricao 
               WHERE u.usuario_descricao IS NOT NULL AND u.usuario_descricao != ''";

$result = mysqli_query($conexao, $sql_update);
$affected = mysqli_affected_rows($conexao);

// Adicionar descrições padrão para artistas sem descrição
$sql_default = "UPDATE artista 
                SET artista_descricao = CONCAT('Artista talentoso de ', COALESCE(artista_cidade, 'localização não informada'), '. Explore suas músicas e descubra seu estilo único.') 
                WHERE artista_descricao IS NULL OR artista_descricao = ''";

$result2 = mysqli_query($conexao, $sql_default);
$affected2 = mysqli_affected_rows($conexao);

ob_end_clean();
?>

<main class="main">
    <section class="principal">
        <div class="config-container">
            <h2>Configurando Sistema de Descrições</h2>
            
            <div class="config-steps">
                <?php if ($coluna_criada): ?>
                <div class="config-step success">
                    <span class="step-icon">✓</span>
                    <span class="step-text">Coluna artista_descricao criada!</span>
                </div>
                <?php endif; ?>
                
                <div class="config-step success">
                    <span class="step-icon">✓</span>
                    <span class="step-text"><?php echo $affected; ?> descrições copiadas dos usuários!</span>
                </div>
                
                <div class="config-step success">
                    <span class="step-icon">✓</span>
                    <span class="step-text"><?php echo $affected2; ?> descrições padrão adicionadas!</span>
                </div>
            </div>
            
            <div class="config-success">
                <h3>Sistema configurado com sucesso!</h3>
                <p>As descrições dos artistas agora aparecerão nos popups.</p>
                <a href="admin.php" class="btn-neon">Voltar ao Painel</a>
            </div>

        </div>
    </section>
</main>

<style>
.config-container {
    max-width: 800px;
    margin: 50px auto;
    padding: 30px;
    background: var(--bg-surface);
    border-radius: var(--radius-xl);
    border: 1px solid var(--border-subtle);
    box-shadow: var(--shadow-lg);
}

.config-container h2 {
    color: var(--text-primary);
    text-align: center;
    margin-bottom: 30px;
    font-size: 1.8rem;
    font-weight: 600;
}

.config-steps {
    margin: 30px 0;
}

.config-step {
    display: flex;
    align-items: center;
    margin: 15px 0;
    padding: 15px 20px;
    background: var(--bg-surface-alt);
    border: 1px solid var(--border-default);
    border-radius: var(--radius-md);
    transition: background var(--transition-base);
}

.config-step.success {
    background: var(--success-soft);
    border-color: rgba(63, 185, 80, 0.3);
}

.step-icon {
    color: var(--success);
    font-size: 1.4rem;
    font-weight: bold;
    margin-right: 15px;
}

.step-text {
    color: var(--text-primary);
    font-size: 1.05rem;
}

.config-success {
    text-align: center;
    margin-top: 30px;
    padding: 25px;
    background: var(--accent-soft);
    border: 1px solid var(--border-accent);
    border-radius: var(--radius-lg);
}

.config-success h3 {
    color: var(--text-primary);
    margin-bottom: 15px;
    font-weight: 600;
}

.config-success p {
    color: var(--text-secondary);
    margin-bottom: 20px;
}
</style>