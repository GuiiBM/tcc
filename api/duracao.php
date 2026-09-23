<?php
// Grava a duração de uma faixa, medida pelo navegador ao carregar o áudio.
// Só preenche durações que ainda estão vazias (não sobrescreve).
include __DIR__ . '/../Componentes/paginas/php/app.php';
exigirPostApi();

$dados = dadosRequisicao();
$id = (int) ($dados['id'] ?? 0);
$duracao = (int) ($dados['duracao'] ?? 0);

if ($duracao < 1 || $duracao > 6 * 3600) {
    jsonErro('Duração inválida');
}

if (($dados['tipo'] ?? '') === 'episodio') {
    executar($conexao, "UPDATE episodio SET episodio_duracao = ? WHERE episodio_id = ? AND (episodio_duracao IS NULL OR episodio_duracao = 0)", "ii", [$duracao, $id]);
} else {
    executar($conexao, "UPDATE musica SET musica_duracao = ? WHERE musica_id = ? AND (musica_duracao IS NULL OR musica_duracao = 0)", "ii", [$duracao, $id]);
}
jsonResposta(['success' => true]);
