<?php 
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
include "Componentes/páginas/php/verificar_login.php";
redirecionarSeNaoAdmin();

include "Componentes/páginas/php/DBConection.php";
include "Componentes/páginas/php/funcoesDuplicados.php";
include "Componentes/páginas/head.php";

// Processar combinação de usuários
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['combinar_usuarios'])) {
    $usuario_principal = $_POST['usuario_principal'];
    $usuario_secundario = $_POST['usuario_secundario'];
    
    if ($usuario_principal && $usuario_secundario && $usuario_principal !== $usuario_secundario) {
        if (combinarUsuarios($conexao, $usuario_principal, $usuario_secundario)) {
            $mensagem = "✅ Itens combinados com sucesso!";
        } else {
            $erro = "❌ Erro ao combinar itens. Tente novamente.";
        }
    } else {
        $erro = "❌ Selecione dois itens diferentes para combinar.";
    }
}

// Processar vinculação artista-usuário
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['vincular_artista'])) {
    $usuario_id = intval($_POST['usuario_id']);
    $artista_id = intval($_POST['artista_id']);
    
    if ($usuario_id && $artista_id) {
        $result = mysqli_query($conexao, "UPDATE usuarios SET artista_id = $artista_id WHERE usuario_id = $usuario_id");
        
        if ($result) {
            $mensagem = "✅ Artista vinculado ao usuário com sucesso!";
        } else {
            $erro = "❌ Erro ao vincular: " . mysqli_error($conexao);
        }
    } else {
        $erro = "❌ Selecione um usuário e um artista para vincular.";
    }
}

$threshold = isset($_GET['threshold']) ? intval($_GET['threshold']) : 0;

// Buscar todos os usuários
function buscarTodosUsuarios($conexao) {
    $sql = "SELECT usuario_id, usuario_nome, usuario_email, usuario_cidade, usuario_data_criacao 
            FROM usuarios 
            ORDER BY usuario_nome";
    return mysqli_query($conexao, $sql);
}

// Buscar usuários sem artista
function buscarUsuariosSemArtista($conexao) {
    $sql = "SELECT usuario_id, usuario_nome, usuario_email FROM usuarios WHERE artista_id IS NULL OR artista_id = 0 ORDER BY usuario_nome";
    return mysqli_query($conexao, $sql);
}

// Buscar artistas sem usuário
function buscarArtistasSemUsuario($conexao) {
    $sql = "SELECT a.artista_id, a.artista_nome, a.artista_cidade 
            FROM artista a 
            WHERE a.artista_id NOT IN (SELECT COALESCE(artista_id, 0) FROM usuarios WHERE artista_id IS NOT NULL AND artista_id > 0) 
            ORDER BY a.artista_nome";
    return mysqli_query($conexao, $sql);
}

// Buscar pares similares artista-usuário
function buscarParesSimilares($conexao, $threshold = 0) {
    $sql = "SELECT u.usuario_id, u.usuario_nome, u.usuario_email,
                   a.artista_id, a.artista_nome, a.artista_cidade
            FROM usuarios u 
            CROSS JOIN artista a
            WHERE (u.artista_id IS NULL OR u.artista_id = 0)
            AND a.artista_id NOT IN (SELECT COALESCE(artista_id, 0) FROM usuarios WHERE artista_id IS NOT NULL AND artista_id > 0)";
    
    $result = mysqli_query($conexao, $sql);
    $pares = [];
    
    while ($row = mysqli_fetch_assoc($result)) {
        $similaridade = calcularSimilaridade($row['usuario_nome'], $row['artista_nome']);
        if ($similaridade >= $threshold) {
            $row['similaridade'] = $similaridade;
            $pares[] = $row;
        }
    }
    
    return $pares;
}
?>

<body>
<div style='max-width: 1200px; margin: 50px auto; padding: 30px; background: linear-gradient(145deg, rgba(22, 27, 34, 0.95), rgba(13, 17, 23, 0.9)); border-radius: 20px; color: #f0f6fc; border: 2px solid rgba(255, 215, 0, 0.3);'>

<h2 style='color: #ffd700; text-align: center; margin-bottom: 30px; text-shadow: 0 0 10px rgba(255, 215, 0, 0.5);'>👥 Gerenciar Usuários Duplicados</h2>

<?php if (isset($mensagem)): ?>
<div style='background: rgba(76, 175, 80, 0.1); padding: 15px; margin: 20px 0; border-radius: 10px; border-left: 4px solid #4CAF50; color: #4CAF50;'>
    <?php echo $mensagem; ?>
</div>
<?php endif; ?>

<?php if (isset($erro)): ?>
<div style='background: rgba(244, 67, 54, 0.1); padding: 15px; margin: 20px 0; border-radius: 10px; border-left: 4px solid #f44336; color: #f44336;'>
    <?php echo $erro; ?>
</div>
<?php endif; ?>

<div style='display: flex; gap: 20px; margin-bottom: 30px; flex-wrap: wrap; justify-content: center;'>
    <button onclick="mostrarSecao('duplicados')" id="btnDuplicados" class="btn-neon">Usuários Duplicados</button>
    <button onclick="mostrarSecao('todos')" id="btnTodos" class="btn-neon">Todos os Usuários</button>
    <button onclick="mostrarSecao('vincular')" id="btnVincular" class="btn-neon">Vincular Artista-Usuário</button>
    <button onclick="mostrarSecao('manual')" id="btnManual" class="btn-neon">Combinação Manual</button>
    <button onclick="mostrarSecao('configuracao')" id="btnConfiguracao" class="btn-neon">Configurações</button>
    <a href="admin.php" class="btn-neon" style="text-decoration: none;">Voltar ao Admin</a>
</div>

<!-- Seção de Usuários Duplicados -->
<div id="secaoDuplicados">
    <h3 style='color: #00d9ff; margin-bottom: 20px;'>🔍 Usuários Potencialmente Duplicados</h3>
    
    <?php
    $duplicados = buscarUsuariosSimilares($conexao, $threshold);
    if (count($duplicados) > 0):
    ?>
    <div style='background: rgba(0, 0, 0, 0.3); padding: 20px; border-radius: 15px;'>
        <div style='text-align: center; margin-bottom: 20px;'>
            <p style='color: #00d9ff;'>Encontrados <strong><?php echo count($duplicados); ?></strong> pares de usuários similares (threshold: <?php echo $threshold; ?>%)</p>
            <p style='color: #8b949e; font-size: 0.9rem;'>Detectando: nomes idênticos, emails idênticos, nomes similares e cidades similares</p>
        </div>
        <?php foreach ($duplicados as $dup): ?>
        <div style='background: rgba(255, 255, 255, 0.05); padding: 15px; margin: 10px 0; border-radius: 10px; border-left: 4px solid #ff6b6b;'>
            <div style='text-align: center; margin-bottom: 10px;'>
                <span style='background: rgba(255, 107, 107, 0.2); color: #ff6b6b; padding: 5px 15px; border-radius: 20px; font-size: 0.9rem;'>
                    <?php echo $dup['motivo']; ?>
                </span>
            </div>
            <div style='display: grid; grid-template-columns: 1fr 1fr; gap: 20px;'>
                <div>
                    <h4 style='color: #ffd700; margin: 0 0 10px 0;'>Usuário 1</h4>
                    <p><strong>Nome:</strong> <?php echo htmlspecialchars($dup['usuario1']['usuario_nome']); ?></p>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($dup['usuario1']['usuario_email']); ?></p>
                    <p><strong>Cidade:</strong> <?php echo htmlspecialchars($dup['usuario1']['usuario_cidade']); ?></p>
                    <p><strong>Criado:</strong> <?php echo date('d/m/Y H:i', strtotime($dup['usuario1']['usuario_data_criacao'])); ?></p>
                    <p><strong>Curtidas:</strong> <?php echo contarCurtidas($conexao, $dup['usuario1']['usuario_id']); ?></p>
                </div>
                <div>
                    <h4 style='color: #ffd700; margin: 0 0 10px 0;'>Usuário 2</h4>
                    <p><strong>Nome:</strong> <?php echo htmlspecialchars($dup['usuario2']['usuario_nome']); ?></p>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($dup['usuario2']['usuario_email']); ?></p>
                    <p><strong>Cidade:</strong> <?php echo htmlspecialchars($dup['usuario2']['usuario_cidade']); ?></p>
                    <p><strong>Criado:</strong> <?php echo date('d/m/Y H:i', strtotime($dup['usuario2']['usuario_data_criacao'])); ?></p>
                    <p><strong>Curtidas:</strong> <?php echo contarCurtidas($conexao, $dup['usuario2']['usuario_id']); ?></p>
                </div>
            </div>
            <div style='text-align: center; margin-top: 15px;'>
                <a href="previewCombinacao.php?principal=<?php echo $dup['usuario1']['usuario_id']; ?>&secundario=<?php echo $dup['usuario2']['usuario_id']; ?>" 
                   class="btn-neon" style='background: linear-gradient(135deg, #4CAF50, #45a049); text-decoration: none; display: inline-block; margin-right: 10px;'>
                    🔍 Manter Usuário 1
                </a>
                <a href="previewCombinacao.php?principal=<?php echo $dup['usuario2']['usuario_id']; ?>&secundario=<?php echo $dup['usuario1']['usuario_id']; ?>" 
                   class="btn-neon" style='background: linear-gradient(135deg, #2196F3, #1976D2); text-decoration: none; display: inline-block;'>
                    🔍 Manter Usuário 2
                </a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div style='background: rgba(76, 175, 80, 0.1); padding: 20px; border-radius: 10px; text-align: center; color: #4CAF50;'>
        ✅ Nenhum usuário duplicado encontrado automaticamente!
    </div>
    <?php endif; ?>
</div>

<!-- Seção de Todos os Usuários -->
<div id="secaoTodos" style="display: none;">
    <h3 style='color: #00d9ff; margin-bottom: 20px;'>👥 Todos os Usuários Cadastrados</h3>
    
    <div style='background: rgba(0, 0, 0, 0.3); padding: 20px; border-radius: 15px;'>
        <?php
        $todosUsuarios = buscarTodosUsuariosDetalhado($conexao);
        if (mysqli_num_rows($todosUsuarios) > 0):
        ?>
        <div style='display: grid; gap: 15px;'>
            <?php while ($usuario = mysqli_fetch_assoc($todosUsuarios)): ?>
            <div style='background: rgba(255, 255, 255, 0.05); padding: 15px; border-radius: 10px; display: grid; grid-template-columns: 1fr auto; align-items: center;'>
                <div>
                    <h4 style='color: #ffd700; margin: 0 0 8px 0;'><?php echo htmlspecialchars($usuario['usuario_nome']); ?></h4>
                    <div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px; font-size: 0.9rem;'>
                        <p style='margin: 0;'><strong>Email:</strong> <?php echo htmlspecialchars($usuario['usuario_email']); ?></p>
                        <p style='margin: 0;'><strong>Cidade:</strong> <?php echo htmlspecialchars($usuario['usuario_cidade'] ?: 'Não informado'); ?></p>
                        <p style='margin: 0;'><strong>Criado:</strong> <?php echo date('d/m/Y', strtotime($usuario['usuario_data_criacao'])); ?></p>
                        <p style='margin: 0;'><strong>Curtidas:</strong> <?php echo $usuario['total_curtidas']; ?></p>
                        <p style='margin: 0;'><strong>Tipo:</strong> <?php echo ucfirst($usuario['usuario_tipo']); ?></p>
                        <p style='margin: 0;'><strong>Artista:</strong> <?php echo htmlspecialchars($usuario['artista_nome'] ?: 'Não vinculado'); ?></p>
                    </div>
                </div>
                <div style='text-align: center;'>
                    <button onclick="selecionarUsuario('<?php echo $usuario['usuario_id']; ?>', '<?php echo addslashes($usuario['usuario_nome']); ?>')" 
                            class="btn-neon" style='background: linear-gradient(135deg, #00d9ff, #0099cc); padding: 8px 15px; font-size: 0.8rem;'>
                        🎯 Selecionar
                    </button>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
        <?php else: ?>
        <p style='text-align: center; color: #8b949e;'>Nenhum usuário encontrado.</p>
        <?php endif; ?>
    </div>
</div>

<!-- Seção de Vinculação Artista-Usuário -->
<div id="secaoVincular" style="display: none;">
    <h3 style='color: #00d9ff; margin-bottom: 20px;'>🔗 Vincular Artistas e Usuários</h3>
    
    <!-- Pares Similares -->
    <div style='background: rgba(0, 0, 0, 0.3); padding: 20px; border-radius: 15px; margin-bottom: 30px;'>
        <h4 style='color: #ffd700; margin-bottom: 15px;'>🎯 Pares Similares Detectados</h4>
        <?php
        $paresSimilares = buscarParesSimilares($conexao, $threshold);
        if (count($paresSimilares) > 0):
        ?>
        <div style='display: grid; gap: 15px;'>
            <?php foreach ($paresSimilares as $par): ?>
            <div style='background: rgba(255, 255, 255, 0.05); padding: 15px; border-radius: 10px; display: grid; grid-template-columns: 1fr auto 1fr auto; align-items: center; gap: 15px;'>
                <div>
                    <h5 style='color: #4CAF50; margin: 0 0 5px 0;'>Usuário</h5>
                    <p style='margin: 0; font-weight: bold;'><?php echo htmlspecialchars($par['usuario_nome']); ?></p>
                    <p style='margin: 0; font-size: 0.9rem; color: #8b949e;'><?php echo htmlspecialchars($par['usuario_email']); ?></p>
                </div>
                <div style='text-align: center; color: #ffd700;'>
                    <div style='font-size: 1.2rem;'>↔️</div>
                    <div style='font-size: 0.8rem;'><?php echo round($par['similaridade']); ?>%</div>
                </div>
                <div>
                    <h5 style='color: #2196F3; margin: 0 0 5px 0;'>Artista</h5>
                    <p style='margin: 0; font-weight: bold;'><?php echo htmlspecialchars($par['artista_nome']); ?></p>
                    <p style='margin: 0; font-size: 0.9rem; color: #8b949e;'><?php echo htmlspecialchars($par['artista_cidade'] ?: 'Sem cidade'); ?></p>
                </div>
                <div>
                    <form method="POST" style='display: inline;'>
                        <input type="hidden" name="usuario_id" value="<?php echo $par['usuario_id']; ?>">
                        <input type="hidden" name="artista_id" value="<?php echo $par['artista_id']; ?>">
                        <button type="submit" name="vincular_artista" class="btn-neon" style='background: linear-gradient(135deg, #4CAF50, #45a049); padding: 8px 15px; font-size: 0.8rem;'>
                            🔗 Vincular
                        </button>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <p style='text-align: center; color: #8b949e;'>Nenhum par similar encontrado com o threshold atual.</p>
        <?php endif; ?>
    </div>
    
    <!-- Vinculação Manual -->
    <div style='background: rgba(0, 0, 0, 0.3); padding: 20px; border-radius: 15px;'>
        <h4 style='color: #ffd700; margin-bottom: 15px;'>🔧 Vinculação Manual</h4>
        <form method="POST" style='display: grid; grid-template-columns: 1fr 1fr auto; gap: 20px; align-items: end;'>
            <div>
                <label style='color: #ffd700; font-weight: bold; display: block; margin-bottom: 10px;'>Usuário sem Artista:</label>
                <select name="usuario_id" required style='width: 100%; padding: 12px; border-radius: 8px; border: 2px solid rgba(255, 215, 0, 0.3); background: rgba(0, 0, 0, 0.5); color: #f0f6fc;'>
                    <option value="">Selecione um usuário</option>
                    <?php
                    $usuariosSemArtista = buscarUsuariosSemArtista($conexao);
                    while ($usuario = mysqli_fetch_assoc($usuariosSemArtista)):
                    ?>
                    <option value="<?php echo $usuario['usuario_id']; ?>">
                        <?php echo htmlspecialchars($usuario['usuario_nome']) . ' (' . htmlspecialchars($usuario['usuario_email']) . ')'; ?>
                    </option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div>
                <label style='color: #ffd700; font-weight: bold; display: block; margin-bottom: 10px;'>Artista sem Usuário:</label>
                <select name="artista_id" required style='width: 100%; padding: 12px; border-radius: 8px; border: 2px solid rgba(255, 215, 0, 0.3); background: rgba(0, 0, 0, 0.5); color: #f0f6fc;'>
                    <option value="">Selecione um artista</option>
                    <?php
                    $artistasSemUsuario = buscarArtistasSemUsuario($conexao);
                    while ($artista = mysqli_fetch_assoc($artistasSemUsuario)):
                    ?>
                    <option value="<?php echo $artista['artista_id']; ?>">
                        <?php echo htmlspecialchars($artista['artista_nome']) . ' (' . htmlspecialchars($artista['artista_cidade'] ?: 'Sem cidade') . ')'; ?>
                    </option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div>
                <button type="submit" name="vincular_artista" class="btn-neon" style='background: linear-gradient(135deg, #ff6b6b, #ee5a52); padding: 15px 25px;'>
                    🔗 Vincular
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Seção de Combinação Manual -->
<div id="secaoManual" style="display: none;">
    <h3 style='color: #00d9ff; margin-bottom: 20px;'>🔧 Combinação Manual de Usuários</h3>
    
    <form method="POST" style='background: rgba(0, 0, 0, 0.3); padding: 25px; border-radius: 15px;'>
        <div style='display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 20px;'>
            <div>
                <label style='color: #ffd700; font-weight: bold; display: block; margin-bottom: 10px;'>Principal (será mantido/vinculado):</label>
                <select name="usuario_principal" required style='width: 100%; padding: 12px; border-radius: 8px; border: 2px solid rgba(255, 215, 0, 0.3); background: rgba(0, 0, 0, 0.5); color: #f0f6fc;'>
                    <option value="">Selecione o principal</option>
                    <?php
                    $todosItens = buscarTodosUsuariosDetalhado($conexao);
                    while ($item = mysqli_fetch_assoc($todosItens)):
                    ?>
                    <option value="<?php echo $item['usuario_id']; ?>">
                        <?php echo htmlspecialchars($item['usuario_nome']) . ' (' . ($item['tipo_registro'] === 'usuario' ? htmlspecialchars($item['usuario_email']) : 'Artista') . ')'; ?>
                    </option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div>
                <label style='color: #ffd700; font-weight: bold; display: block; margin-bottom: 10px;'>Secundário (será combinado):</label>
                <select name="usuario_secundario" required style='width: 100%; padding: 12px; border-radius: 8px; border: 2px solid rgba(255, 215, 0, 0.3); background: rgba(0, 0, 0, 0.5); color: #f0f6fc;'>
                    <option value="">Selecione o secundário</option>
                    <?php
                    $todosItens = buscarTodosUsuariosDetalhado($conexao);
                    while ($item = mysqli_fetch_assoc($todosItens)):
                    ?>
                    <option value="<?php echo $item['usuario_id']; ?>">
                        <?php echo htmlspecialchars($item['usuario_nome']) . ' (' . ($item['tipo_registro'] === 'usuario' ? htmlspecialchars($item['usuario_email']) : 'Artista') . ')'; ?>
                    </option>
                    <?php endwhile; ?>
                </select>
            </div>
        </div>
        
        <div style='text-align: center;'>
            <button type="button" onclick="previewCombinacao()" class="btn-neon" style='background: linear-gradient(135deg, #00d9ff, #0099cc); padding: 15px 30px; margin-right: 15px;'>
                🔍 Preview da Combinação
            </button>
            <button type="submit" name="combinar_usuarios" class="btn-neon" style='background: linear-gradient(135deg, #ff6b6b, #ee5a52); padding: 15px 30px;'>
                🔄 Combinar Diretamente
            </button>
        </div>
    </form>
    
    <div style='background: rgba(255, 193, 7, 0.1); padding: 20px; margin-top: 20px; border-radius: 10px; border-left: 4px solid #FFC107;'>
        <h4 style='color: #FFC107; margin-top: 0;'>⚠️ Atenção:</h4>
        <ul style='color: #f0f6fc; margin: 0;'>
            <li><strong>Usuário + Usuário:</strong> Transfere curtidas, preserva perfil principal</li>
            <li><strong>Artista + Artista:</strong> Atualiza referências, remove duplicado</li>
            <li><strong>Usuário + Artista:</strong> Vincula perfis, cria conexão</li>
            <li><strong>Artista + Usuário:</strong> Vincula usuário ao artista</li>
        </ul>
    </div>
</div>

<!-- Seção de Configurações -->
<div id="secaoConfiguracao" style="display: none;">
    <h3 style='color: #00d9ff; margin-bottom: 20px;'>⚙️ Configurações de Detecção</h3>
    
    <div style='background: rgba(0, 0, 0, 0.3); padding: 25px; border-radius: 15px;'>
        <form method="GET" style='text-align: center;'>
            <div style='margin-bottom: 20px;'>
                <label style='color: #ffd700; font-weight: bold; display: block; margin-bottom: 10px;'>Threshold de Similaridade (%):</label>
                <input type="range" name="threshold" min="0" max="100" value="<?php echo $threshold; ?>" 
                       style='width: 300px; margin: 10px;' oninput="updateThresholdValue(this.value)">
                <div style='color: #00d9ff; font-size: 1.2rem; margin-top: 10px;'>
                    <span id="thresholdValue"><?php echo $threshold; ?></span>%
                </div>
                <p style='color: #8b949e; font-size: 0.9rem; margin-top: 10px;'>
                    0% = mostra todos os pares | 100% = apenas duplicatas exatas<br>
                    Valores baixos mostram mais candidatos para análise manual
                </p>
            </div>
            
            <button type="submit" class="btn-neon" style='padding: 12px 25px;'>
                🔄 Atualizar Detecção
            </button>
        </form>
        
        <div style='margin-top: 30px; padding: 20px; background: rgba(255, 193, 7, 0.1); border-radius: 10px; border-left: 4px solid #FFC107;'>
            <h4 style='color: #FFC107; margin-top: 0;'>📊 Estatísticas do Sistema:</h4>
            <?php
            $total_usuarios = mysqli_fetch_assoc(mysqli_query($conexao, "SELECT COUNT(*) as total FROM usuarios"))['total'];
            $total_duplicados = count(buscarUsuariosSimilares($conexao, $threshold));
            $total_curtidas = mysqli_fetch_assoc(mysqli_query($conexao, "SELECT COUNT(*) as total FROM curtidas"))['total'];
            ?>
            <div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-top: 15px;'>
                <div style='text-align: center; background: rgba(0, 0, 0, 0.3); padding: 15px; border-radius: 8px;'>
                    <div style='color: #00d9ff; font-size: 2rem; font-weight: bold;'><?php echo $total_usuarios; ?></div>
                    <div style='color: #f0f6fc;'>Total de Usuários</div>
                </div>
                <div style='text-align: center; background: rgba(0, 0, 0, 0.3); padding: 15px; border-radius: 8px;'>
                    <div style='color: #ff6b6b; font-size: 2rem; font-weight: bold;'><?php echo $total_duplicados; ?></div>
                    <div style='color: #f0f6fc;'>Duplicados Encontrados</div>
                </div>
                <div style='text-align: center; background: rgba(0, 0, 0, 0.3); padding: 15px; border-radius: 8px;'>
                    <div style='color: #4CAF50; font-size: 2rem; font-weight: bold;'><?php echo $total_curtidas; ?></div>
                    <div style='color: #f0f6fc;'>Total de Curtidas</div>
                </div>
            </div>
        </div>
    </div>
</div>

</div>

<script>
function updateThresholdValue(value) {
    document.getElementById('thresholdValue').textContent = value;
}

function previewCombinacao() {
    const principal = document.querySelector('select[name="usuario_principal"]').value;
    const secundario = document.querySelector('select[name="usuario_secundario"]').value;
    
    if (!principal || !secundario) {
        alert('Por favor, selecione ambos os usuários antes de fazer o preview.');
        return;
    }
    
    if (principal === secundario) {
        alert('Por favor, selecione dois usuários diferentes.');
        return;
    }
    
    window.location.href = `previewCombinacao.php?principal=${principal}&secundario=${secundario}`;
}
function mostrarSecao(secao) {
    const duplicados = document.getElementById('secaoDuplicados');
    const todos = document.getElementById('secaoTodos');
    const vincular = document.getElementById('secaoVincular');
    const manual = document.getElementById('secaoManual');
    const configuracao = document.getElementById('secaoConfiguracao');
    const btnDuplicados = document.getElementById('btnDuplicados');
    const btnTodos = document.getElementById('btnTodos');
    const btnVincular = document.getElementById('btnVincular');
    const btnManual = document.getElementById('btnManual');
    const btnConfiguracao = document.getElementById('btnConfiguracao');
    
    // Reset buttons
    btnDuplicados.style.opacity = '0.6';
    btnTodos.style.opacity = '0.6';
    btnVincular.style.opacity = '0.6';
    btnManual.style.opacity = '0.6';
    btnConfiguracao.style.opacity = '0.6';
    
    // Hide all sections
    duplicados.style.display = 'none';
    todos.style.display = 'none';
    vincular.style.display = 'none';
    manual.style.display = 'none';
    configuracao.style.display = 'none';
    
    if (secao === 'duplicados') {
        duplicados.style.display = 'block';
        btnDuplicados.style.opacity = '1';
    } else if (secao === 'todos') {
        todos.style.display = 'block';
        btnTodos.style.opacity = '1';
    } else if (secao === 'vincular') {
        vincular.style.display = 'block';
        btnVincular.style.opacity = '1';
    } else if (secao === 'manual') {
        manual.style.display = 'block';
        btnManual.style.opacity = '1';
    } else if (secao === 'configuracao') {
        configuracao.style.display = 'block';
        btnConfiguracao.style.opacity = '1';
    }
}

function selecionarUsuario(id, nome) {
    const selects = document.querySelectorAll('select[name="usuario_principal"], select[name="usuario_secundario"]');
    let selecionado = false;
    
    selects.forEach(select => {
        if (!select.value && !selecionado) {
            select.value = id;
            selecionado = true;
            select.style.background = 'rgba(76, 175, 80, 0.2)';
            setTimeout(() => {
                select.style.background = 'rgba(0, 0, 0, 0.5)';
            }, 1000);
        }
    });
    
    if (selecionado) {
        mostrarSecao('manual');
        alert(`Usuário "${nome}" selecionado!`);
    } else {
        alert('Ambos os campos já estão preenchidos. Limpe um campo primeiro.');
    }
}

// Inicializar mostrando duplicados
document.addEventListener('DOMContentLoaded', function() {
    mostrarSecao('duplicados');
});
</script>

</body>
</html>