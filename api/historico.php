<?php
// Registra no histórico uma faixa ouvida por completo (o player só chama
// depois de 90% da duração realmente tocada).
// POST {tipo: musica|episodio, id}   |   POST {acao: 'limpar'}
include __DIR__ . '/../Componentes/paginas/php/app.php';
exigirPostApi();
exigirLoginApi();

$dados = dadosRequisicao();
$usuarioId = (int) $_SESSION['usuario_id'];

if (($dados['acao'] ?? '') === 'limpar') {
    executar($conexao, "DELETE FROM historico WHERE usuario_id = ?", "i", [$usuarioId]);
    jsonResposta(['success' => true, 'message' => 'Histórico apagado']);
}

$id = (int) ($dados['id'] ?? 0);
$tipo = $dados['tipo'] ?? 'musica';

if ($tipo === 'episodio') {
    if (!consultarUm($conexao, "SELECT episodio_id FROM episodio WHERE episodio_id = ?", "i", [$id])) {
        jsonErro('Episódio não encontrado', 404);
    }
    $coluna = 'episodio_id';
} else {
    if (!consultarUm($conexao, "SELECT musica_id FROM musica WHERE musica_id = ?", "i", [$id])) {
        jsonErro('Música não encontrada', 404);
    }
    $coluna = 'musica_id';
}

// Evita registros duplicados se o evento disparar duas vezes seguidas.
$duplicado = consultarUm($conexao, "SELECT historico_id FROM historico WHERE usuario_id = ? AND $coluna = ? AND data_reproducao > DATE_SUB(NOW(), INTERVAL 20 SECOND)", "ii", [$usuarioId, $id]);
if (!$duplicado) {
    executar($conexao, "INSERT INTO historico (usuario_id, $coluna) VALUES (?, ?)", "ii", [$usuarioId, $id]);
}
jsonResposta(['success' => true]);
