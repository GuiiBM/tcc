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
    background: linear-gradient(145deg, rgba(22, 27, 34, 0.95), rgba(13, 17, 23, 0.9));
    border-radius: 20px;
    border: 1px solid rgba(255, 215, 0, 0.2);
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.4);
    backdrop-filter: blur(15px);
}

.config-container h2 {
    color: var(--neon-gold);
    text-align: center;
    margin-bottom: 30px;
    font-size: 2rem;
    text-shadow: 0 0 15px rgba(255, 215, 0, 0.5);
}

.config-steps {
    margin: 30px 0;
}

.config-step {
    display: flex;
    align-items: center;
    margin: 15px 0;
    padding: 15px 20px;
    background: rgba(0, 217, 255, 0.1);
    border: 1px solid rgba(0, 217, 255, 0.3);
    border-radius: 10px;
    transition: all 0.3s ease;
}

.config-step.success {
    background: rgba(0, 255, 127, 0.1);
    border-color: rgba(0, 255, 127, 0.3);
}

.step-icon {
    color: var(--neon-green);
    font-size: 1.5rem;
    font-weight: bold;
    margin-right: 15px;
    text-shadow: 0 0 10px rgba(0, 255, 127, 0.5);
}

.step-text {
    color: var(--neon-white);
    font-size: 1.1rem;
}

.config-success {
    text-align: center;
    margin-top: 30px;
    padding: 25px;
    background: rgba(255, 215, 0, 0.1);
    border: 1px solid rgba(255, 215, 0, 0.3);
    border-radius: 15px;
}

.config-success h3 {
    color: var(--neon-gold);
    margin-bottom: 15px;
    text-shadow: 0 0 15px rgba(255, 215, 0, 0.5);
}

.config-success p {
    color: var(--text-secondary);
    margin-bottom: 20px;
}
</style>