<?php
// Uso (no computador, com XAMPP e ffmpeg instalados):
//   php ferramentas/calcularDuracoes.php            -> preenche durações vazias
//   php ferramentas/calcularDuracoes.php --compacta -> também gera a versão
//      "Economia de dados" (MP3 64 kbps) das músicas que não têm
// No InfinityFree não há ffmpeg: rode aqui antes de exportar o banco. Sem
// isso o site preenche as durações sozinho quando cada música é tocada.
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Somente pela linha de comando.');
}
chdir(__DIR__ . '/..');
$_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__);
include 'Componentes/paginas/php/DBConection.php';

$gerarCompacta = in_array('--compacta', $argv, true);
$ffprobe = trim((string) shell_exec('command -v ffprobe'));
$ffmpeg = trim((string) shell_exec('command -v ffmpeg'));
if (!$ffprobe) {
    exit("ffprobe não encontrado. Instale o ffmpeg.\n");
}

$musicas = mysqli_fetch_all(mysqli_query($conexao, "SELECT musica_id, musica_titulo, musica_link, musica_duracao, musica_link_baixa FROM musica"), MYSQLI_ASSOC);
foreach ($musicas as $m) {
    $arquivo = $m['musica_link'];
    if (!is_file($arquivo)) {
        echo "! arquivo não encontrado: {$m['musica_titulo']}\n";
        continue;
    }
    if (!$m['musica_duracao']) {
        $segundos = (int) round((float) shell_exec(escapeshellcmd($ffprobe) . ' -v error -show_entries format=duration -of csv=p=0 ' . escapeshellarg($arquivo)));
        if ($segundos > 0) {
            $stmt = mysqli_prepare($conexao, "UPDATE musica SET musica_duracao = ? WHERE musica_id = ?");
            mysqli_stmt_bind_param($stmt, "ii", $segundos, $m['musica_id']);
            mysqli_stmt_execute($stmt);
            echo "✓ {$m['musica_titulo']}: {$segundos}s\n";
        }
    }
    if ($gerarCompacta && $ffmpeg && !$m['musica_link_baixa']) {
        $destino = 'Componentes/Armazenamento/audios/' . pathinfo($arquivo, PATHINFO_FILENAME) . '_64k.mp3';
        exec(escapeshellcmd($ffmpeg) . ' -y -v error -i ' . escapeshellarg($arquivo) . ' -vn -ac 2 -b:a 64k ' . escapeshellarg($destino), $saida, $codigo);
        if ($codigo === 0 && is_file($destino)) {
            $stmt = mysqli_prepare($conexao, "UPDATE musica SET musica_link_baixa = ? WHERE musica_id = ?");
            mysqli_stmt_bind_param($stmt, "si", $destino, $m['musica_id']);
            mysqli_stmt_execute($stmt);
            echo "✓ versão compacta: {$m['musica_titulo']}\n";
        }
    }
}
echo "Concluído.\n";
