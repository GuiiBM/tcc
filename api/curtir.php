<?php
// Curtir / "não gostei" / remover avaliação de uma música.
// GET ?musica_id=N -> estado do usuário + contadores
// POST {musica_id, acao: curtir|descurtir|remover}
include __DIR__ . '/../Componentes/paginas/php/app.php';

function estadoCurtida($conexao, $musicaId) {
    $contagem = consultarUm($conexao, "SELECT
        SUM(tipo_curtida = 'curtida') AS curtidas,
        SUM(tipo_curtida = 'descurtida') AS descurtidas
        FROM curtidas WHERE musica_id = ?", "i", [$musicaId]);
    $meu = null;
    if (usuarioLogado()) {
        $linha = consultarUm($conexao, "SELECT tipo_curtida FROM curtidas WHERE musica_id = ? AND usuario_id = ?", "ii", [$musicaId, $_SESSION['usuario_id']]);
        $meu = $linha['tipo_curtida'] ?? null;
    }
    return [
        'success' => true,
        'curtida' => $meu === 'curtida',
        'descurtida' => $meu === 'descurtida',
        'curtidas' => (int) ($contagem['curtidas'] ?? 0),
        'descurtidas' => (int) ($contagem['descurtidas'] ?? 0),
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    jsonResposta(estadoCurtida($conexao, (int) ($_GET['musica_id'] ?? 0)));
}

exigirPostApi();
exigirLoginApi();
$dados = dadosRequisicao();
$musicaId = (int) ($dados['musica_id'] ?? 0);
$acao = $dados['acao'] ?? '';

if (!consultarUm($conexao, "SELECT musica_id FROM musica WHERE musica_id = ?", "i", [$musicaId])) {
    jsonErro('Música não encontrada', 404);
}

if ($acao === 'remover') {
    executar($conexao, "DELETE FROM curtidas WHERE musica_id = ? AND usuario_id = ?", "ii", [$musicaId, $_SESSION['usuario_id']]);
} elseif ($acao === 'curtir' || $acao === 'descurtir') {
    $tipo = $acao === 'curtir' ? 'curtida' : 'descurtida';
    executar($conexao, "INSERT INTO curtidas (musica_id, usuario_id, tipo_curtida) VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE tipo_curtida = VALUES(tipo_curtida), data_curtida = CURRENT_TIMESTAMP", "iis", [$musicaId, $_SESSION['usuario_id'], $tipo]);
} else {
    jsonErro('Ação inválida');
}

jsonResposta(estadoCurtida($conexao, $musicaId));
