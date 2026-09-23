<?php
// migracoes.php - Cria e atualiza todas as tabelas do banco automaticamente.
//
// garantirEsquema() roda a cada requisição (é só um SELECT) e, se a versão
// gravada na tabela `sistema` for menor que VERSAO_ESQUEMA, executa todas as
// migrações. Todas são idempotentes (podem rodar várias vezes sem estragar
// nada), então um banco vazio na hospedagem vira um banco completo no
// primeiro acesso, e um banco antigo importado do XAMPP é atualizado sozinho.

define('VERSAO_ESQUEMA', 4);

function garantirEsquema($conexao) {
    static $verificado = false;
    if ($verificado) {
        return;
    }
    $verificado = true;

    $versao = 0;
    try {
        $resultado = mysqli_query($conexao, "SELECT valor FROM sistema WHERE chave = 'versao_esquema'");
        if ($resultado && ($linha = mysqli_fetch_row($resultado))) {
            $versao = (int) $linha[0];
        }
    } catch (Throwable $e) {
        $versao = 0;
    }

    if ($versao < VERSAO_ESQUEMA) {
        executarMigracoes($conexao);
    }
}

// Executa um comando SQL ignorando erros esperados (coluna/índice/chave que
// já existe). Retorna true/false e registra a mensagem em $log.
function migracaoSql($conexao, $sql, $descricao, &$log) {
    try {
        $ok = mysqli_query($conexao, $sql);
        $log[] = [$ok ? 'ok' : 'erro', $descricao . ($ok ? '' : ': ' . mysqli_error($conexao))];
        return (bool) $ok;
    } catch (Throwable $e) {
        $log[] = ['erro', $descricao . ': ' . $e->getMessage()];
        return false;
    }
}

function migracaoTabelaExiste($conexao, $tabela) {
    try {
        $tabela = mysqli_real_escape_string($conexao, $tabela);
        $r = mysqli_query($conexao, "SHOW TABLES LIKE '$tabela'");
        return $r && mysqli_num_rows($r) > 0;
    } catch (Throwable $e) {
        return false;
    }
}

function migracaoColunaExiste($conexao, $tabela, $coluna) {
    try {
        $coluna = mysqli_real_escape_string($conexao, $coluna);
        $r = mysqli_query($conexao, "SHOW COLUMNS FROM `$tabela` LIKE '$coluna'");
        return $r && mysqli_num_rows($r) > 0;
    } catch (Throwable $e) {
        return false;
    }
}

function migracaoIndiceExiste($conexao, $tabela, $indice) {
    try {
        $indice = mysqli_real_escape_string($conexao, $indice);
        $r = mysqli_query($conexao, "SHOW INDEX FROM `$tabela` WHERE Key_name = '$indice'");
        return $r && mysqli_num_rows($r) > 0;
    } catch (Throwable $e) {
        return false;
    }
}

function migracaoAdicionarColuna($conexao, $tabela, $coluna, $definicao, &$log) {
    if (migracaoColunaExiste($conexao, $tabela, $coluna)) {
        $log[] = ['ok', "Coluna $tabela.$coluna já existe"];
        return;
    }
    migracaoSql($conexao, "ALTER TABLE `$tabela` ADD COLUMN `$coluna` $definicao", "Coluna $tabela.$coluna adicionada", $log);
}

function migracaoCriarTabela($conexao, $tabela, $sql, &$log) {
    if (migracaoTabelaExiste($conexao, $tabela)) {
        $log[] = ['ok', "Tabela '$tabela' já existe"];
        return;
    }
    migracaoSql($conexao, $sql, "Tabela '$tabela' criada", $log);
}

// Executa todas as migrações e devolve o log [['ok'|'erro', mensagem], ...].
// Importa os municípios e estados de Componentes/dados/*.csv (dados abertos
// do IBGE compilados por github.com/kelvins/municipios-brasileiros, MIT).
function importarCidades($conexao) {
    include_once __DIR__ . '/geo.php';
    $pasta = dirname(__DIR__, 2) . '/dados/';
    $linhas = [];
    $siglas = [];
    if (($f = @fopen($pasta . 'estados.csv', 'r'))) {
        fgetcsv($f);
        while (($c = fgetcsv($f)) !== false) {
            if (count($c) < 5) continue;
            $codigo = (int) preg_replace('/\D/', '', $c[0]);
            $siglas[$codigo] = $c[1];
            $linhas[] = [$codigo, $c[2], $c[1], 'estado', 0, (float) $c[3], (float) $c[4]];
        }
        fclose($f);
    }
    if (($f = @fopen($pasta . 'municipios.csv', 'r'))) {
        fgetcsv($f);
        while (($c = fgetcsv($f)) !== false) {
            if (count($c) < 6) continue;
            $linhas[] = [(int) $c[0], $c[1], $siglas[(int) $c[5]] ?? '', 'cidade', (int) $c[4], (float) $c[2], (float) $c[3]];
        }
        fclose($f);
    }
    $total = 0;
    foreach (array_chunk($linhas, 400) as $lote) {
        $valores = [];
        foreach ($lote as [$id, $nome, $uf, $tipo, $capital, $lat, $lon]) {
            $valores[] = sprintf("(%d, '%s', '%s', '%s', '%s', %d, %F, %F)", $id,
                mysqli_real_escape_string($conexao, $nome), mysqli_real_escape_string($conexao, normalizarTexto($nome)),
                mysqli_real_escape_string($conexao, $uf), $tipo, $capital, $lat, $lon);
        }
        if (mysqli_query($conexao, "INSERT IGNORE INTO cidade (cidade_id, cidade_nome, cidade_busca, uf, cidade_tipo, capital, latitude, longitude) VALUES " . implode(',', $valores))) {
            $total += mysqli_affected_rows($conexao);
        }
    }
    return $total;
}

function executarMigracoes($conexao) {
    $log = [];
    $motor = 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4';

    migracaoCriarTabela($conexao, 'sistema', "CREATE TABLE sistema (
        chave VARCHAR(50) PRIMARY KEY,
        valor VARCHAR(255) NOT NULL
    ) $motor", $log);

    // ===== Tabelas originais do projeto =====
    migracaoCriarTabela($conexao, 'artista', "CREATE TABLE artista (
        artista_id INT PRIMARY KEY AUTO_INCREMENT,
        artista_nome VARCHAR(100) NOT NULL,
        artista_cidade VARCHAR(100),
        artista_image VARCHAR(255),
        artista_descricao TEXT,
        artista_link VARCHAR(255)
    ) $motor", $log);
    migracaoAdicionarColuna($conexao, 'artista', 'artista_descricao', 'TEXT', $log);
    migracaoAdicionarColuna($conexao, 'artista', 'artista_link', 'VARCHAR(255)', $log);
    migracaoAdicionarColuna($conexao, 'artista', 'artista_capa', 'VARCHAR(255) NULL', $log);

    migracaoCriarTabela($conexao, 'musica', "CREATE TABLE musica (
        musica_id INT PRIMARY KEY AUTO_INCREMENT,
        musica_titulo VARCHAR(100) NOT NULL,
        musica_capa VARCHAR(255),
        musica_link VARCHAR(255),
        musica_artista INT,
        musica_data_adicao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_musica_artista FOREIGN KEY (musica_artista) REFERENCES artista(artista_id) ON DELETE CASCADE
    ) $motor", $log);
    migracaoAdicionarColuna($conexao, 'musica', 'musica_data_adicao', 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP', $log);

    migracaoCriarTabela($conexao, 'usuarios', "CREATE TABLE usuarios (
        usuario_id INT PRIMARY KEY AUTO_INCREMENT,
        usuario_email VARCHAR(255) UNIQUE NOT NULL,
        usuario_senha VARCHAR(255) NOT NULL,
        usuario_nome VARCHAR(100) NOT NULL,
        usuario_idade INT,
        usuario_cidade VARCHAR(100),
        usuario_descricao TEXT,
        usuario_foto VARCHAR(255),
        usuario_tipo ENUM('admin', 'usuario') DEFAULT 'usuario',
        usuario_data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        artista_id INT UNIQUE,
        CONSTRAINT fk_usuario_artista FOREIGN KEY (artista_id) REFERENCES artista(artista_id) ON DELETE SET NULL
    ) $motor", $log);
    if (!migracaoColunaExiste($conexao, 'usuarios', 'artista_id')) {
        migracaoSql($conexao, "ALTER TABLE usuarios ADD COLUMN artista_id INT, ADD CONSTRAINT fk_usuario_artista FOREIGN KEY (artista_id) REFERENCES artista(artista_id) ON DELETE SET NULL", "Coluna usuarios.artista_id adicionada", $log);
    }
    migracaoAdicionarColuna($conexao, 'usuarios', 'usuario_qualidade', "ENUM('auto', 'alta', 'baixa') NOT NULL DEFAULT 'auto'", $log);

    migracaoCriarTabela($conexao, 'curtidas', "CREATE TABLE curtidas (
        curtida_id INT PRIMARY KEY AUTO_INCREMENT,
        musica_id INT NOT NULL,
        usuario_id INT NOT NULL,
        tipo_curtida ENUM('curtida', 'descurtida') NOT NULL,
        data_curtida TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_curtida_musica FOREIGN KEY (musica_id) REFERENCES musica(musica_id) ON DELETE CASCADE,
        CONSTRAINT fk_curtida_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(usuario_id) ON DELETE CASCADE,
        UNIQUE KEY unique_user_music (musica_id, usuario_id)
    ) $motor", $log);

    migracaoCriarTabela($conexao, 'visualizacoes', "CREATE TABLE visualizacoes (
        visualizacao_id INT PRIMARY KEY AUTO_INCREMENT,
        musica_id INT NOT NULL,
        ip_usuario VARCHAR(45) NOT NULL,
        data_visualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_visualizacao_musica FOREIGN KEY (musica_id) REFERENCES musica(musica_id) ON DELETE CASCADE
    ) $motor", $log);

    migracaoCriarTabela($conexao, 'propagandas', "CREATE TABLE propagandas (
        propaganda_id INT PRIMARY KEY AUTO_INCREMENT,
        propaganda_nome VARCHAR(255) NOT NULL,
        propaganda_ordem INT NOT NULL DEFAULT 0,
        propaganda_ativa BOOLEAN DEFAULT TRUE,
        data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) $motor", $log);

    // ===== Álbuns =====
    migracaoCriarTabela($conexao, 'album', "CREATE TABLE album (
        album_id INT PRIMARY KEY AUTO_INCREMENT,
        album_titulo VARCHAR(150) NOT NULL,
        album_capa VARCHAR(255),
        album_artista INT NOT NULL,
        album_tipo ENUM('album', 'ep', 'single') NOT NULL DEFAULT 'album',
        album_ano SMALLINT NULL,
        album_data TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_album_artista FOREIGN KEY (album_artista) REFERENCES artista(artista_id) ON DELETE CASCADE
    ) $motor", $log);

    // ===== Novas colunas de música =====
    migracaoAdicionarColuna($conexao, 'musica', 'musica_duracao', 'INT NULL', $log);
    migracaoAdicionarColuna($conexao, 'musica', 'musica_faixa', 'INT NULL', $log);
    migracaoAdicionarColuna($conexao, 'musica', 'musica_letra', 'MEDIUMTEXT NULL', $log);
    migracaoAdicionarColuna($conexao, 'musica', 'musica_link_baixa', 'VARCHAR(255) NULL', $log);
    if (!migracaoColunaExiste($conexao, 'musica', 'album_id')) {
        migracaoSql($conexao, "ALTER TABLE musica ADD COLUMN album_id INT NULL, ADD CONSTRAINT fk_musica_album FOREIGN KEY (album_id) REFERENCES album(album_id) ON DELETE SET NULL", "Coluna musica.album_id adicionada", $log);
    }

    // ===== Categorias (gêneros e humores) =====
    migracaoCriarTabela($conexao, 'categoria', "CREATE TABLE categoria (
        categoria_id INT PRIMARY KEY AUTO_INCREMENT,
        categoria_nome VARCHAR(60) NOT NULL UNIQUE,
        categoria_tipo ENUM('genero', 'humor') NOT NULL DEFAULT 'genero',
        categoria_cor VARCHAR(7) NOT NULL DEFAULT '#d4af37',
        categoria_ordem INT NOT NULL DEFAULT 0
    ) $motor", $log);

    migracaoCriarTabela($conexao, 'musica_categoria', "CREATE TABLE musica_categoria (
        musica_id INT NOT NULL,
        categoria_id INT NOT NULL,
        PRIMARY KEY (musica_id, categoria_id),
        CONSTRAINT fk_mc_musica FOREIGN KEY (musica_id) REFERENCES musica(musica_id) ON DELETE CASCADE,
        CONSTRAINT fk_mc_categoria FOREIGN KEY (categoria_id) REFERENCES categoria(categoria_id) ON DELETE CASCADE
    ) $motor", $log);

    $categoriasPadrao = [
        ['Pop', 'genero', '#e8115b'], ['Rock', 'genero', '#b02897'], ['MPB', 'genero', '#148a08'],
        ['Sertanejo', 'genero', '#ba5d07'], ['Funk', 'genero', '#8c1932'], ['Hip-Hop', 'genero', '#bc5900'],
        ['Eletrônica', 'genero', '#0d73ec'], ['Samba e Pagode', 'genero', '#e91429'], ['Forró', 'genero', '#d84000'],
        ['Gospel', 'genero', '#7358ff'], ['Indie', 'genero', '#608108'], ['Jazz', 'genero', '#1e3264'],
        ['Reggae', 'genero', '#27856a'], ['Clássica', 'genero', '#777777'],
        ['Foco', 'humor', '#503750'], ['Treino', 'humor', '#777777'], ['Relax', 'humor', '#477d95'],
        ['Festa', 'humor', '#af2896'], ['Romântica', 'humor', '#dc148c'], ['Dormir', 'humor', '#1e3264'],
        ['Viagem', 'humor', '#0d72ea'], ['Bem-estar', 'humor', '#509bf5'],
    ];
    $r = mysqli_query($conexao, "SELECT COUNT(*) FROM categoria");
    if ($r && (int) mysqli_fetch_row($r)[0] === 0) {
        $stmt = mysqli_prepare($conexao, "INSERT INTO categoria (categoria_nome, categoria_tipo, categoria_cor, categoria_ordem) VALUES (?, ?, ?, ?)");
        foreach ($categoriasPadrao as $ordem => $categoria) {
            mysqli_stmt_bind_param($stmt, "sssi", $categoria[0], $categoria[1], $categoria[2], $ordem);
            mysqli_stmt_execute($stmt);
        }
        $log[] = ['ok', 'Categorias padrão cadastradas'];
    }

    // ===== Playlists =====
    migracaoCriarTabela($conexao, 'playlist', "CREATE TABLE playlist (
        playlist_id INT PRIMARY KEY AUTO_INCREMENT,
        usuario_id INT NOT NULL,
        playlist_nome VARCHAR(100) NOT NULL,
        playlist_descricao TEXT NULL,
        playlist_capa VARCHAR(255) NULL,
        playlist_publica TINYINT(1) NOT NULL DEFAULT 1,
        playlist_criada TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_playlist_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(usuario_id) ON DELETE CASCADE
    ) $motor", $log);

    migracaoCriarTabela($conexao, 'playlist_musica', "CREATE TABLE playlist_musica (
        playlist_id INT NOT NULL,
        musica_id INT NOT NULL,
        posicao INT NOT NULL DEFAULT 0,
        adicionada TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (playlist_id, musica_id),
        CONSTRAINT fk_pm_playlist FOREIGN KEY (playlist_id) REFERENCES playlist(playlist_id) ON DELETE CASCADE,
        CONSTRAINT fk_pm_musica FOREIGN KEY (musica_id) REFERENCES musica(musica_id) ON DELETE CASCADE
    ) $motor", $log);

    // ===== Seguidores de artistas =====
    migracaoCriarTabela($conexao, 'seguidores', "CREATE TABLE seguidores (
        usuario_id INT NOT NULL,
        artista_id INT NOT NULL,
        data_seguiu TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (usuario_id, artista_id),
        CONSTRAINT fk_seg_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(usuario_id) ON DELETE CASCADE,
        CONSTRAINT fk_seg_artista FOREIGN KEY (artista_id) REFERENCES artista(artista_id) ON DELETE CASCADE
    ) $motor", $log);

    // ===== Podcasts =====
    migracaoCriarTabela($conexao, 'podcast', "CREATE TABLE podcast (
        podcast_id INT PRIMARY KEY AUTO_INCREMENT,
        podcast_titulo VARCHAR(150) NOT NULL,
        podcast_autor VARCHAR(100) NOT NULL,
        podcast_descricao TEXT NULL,
        podcast_capa VARCHAR(255) NULL,
        usuario_id INT NULL,
        podcast_data TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_podcast_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(usuario_id) ON DELETE SET NULL
    ) $motor", $log);

    migracaoCriarTabela($conexao, 'episodio', "CREATE TABLE episodio (
        episodio_id INT PRIMARY KEY AUTO_INCREMENT,
        podcast_id INT NOT NULL,
        episodio_titulo VARCHAR(200) NOT NULL,
        episodio_descricao TEXT NULL,
        episodio_audio VARCHAR(500) NOT NULL,
        episodio_duracao INT NULL,
        episodio_data TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_episodio_podcast FOREIGN KEY (podcast_id) REFERENCES podcast(podcast_id) ON DELETE CASCADE
    ) $motor", $log);

    // ===== Histórico de reprodução (faixas ouvidas por completo) =====
    migracaoCriarTabela($conexao, 'historico', "CREATE TABLE historico (
        historico_id INT PRIMARY KEY AUTO_INCREMENT,
        usuario_id INT NOT NULL,
        musica_id INT NULL,
        episodio_id INT NULL,
        data_reproducao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_historico_usuario_data (usuario_id, data_reproducao),
        CONSTRAINT fk_hist_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(usuario_id) ON DELETE CASCADE,
        CONSTRAINT fk_hist_musica FOREIGN KEY (musica_id) REFERENCES musica(musica_id) ON DELETE CASCADE,
        CONSTRAINT fk_hist_episodio FOREIGN KEY (episodio_id) REFERENCES episodio(episodio_id) ON DELETE CASCADE
    ) $motor", $log);

    if (!migracaoIndiceExiste($conexao, 'visualizacoes', 'idx_visualizacao_data')) {
        migracaoSql($conexao, "ALTER TABLE visualizacoes ADD INDEX idx_visualizacao_data (musica_id, data_visualizacao)", "Índice de visualizações criado", $log);
    }

    // ===== v4: localização (recomendações locais) =====
    migracaoCriarTabela($conexao, 'cidade', "CREATE TABLE cidade (
        cidade_id INT PRIMARY KEY,
        cidade_nome VARCHAR(100) NOT NULL,
        cidade_busca VARCHAR(100) NOT NULL,
        uf CHAR(2) NOT NULL,
        cidade_tipo ENUM('cidade', 'estado') NOT NULL DEFAULT 'cidade',
        capital TINYINT(1) NOT NULL DEFAULT 0,
        latitude DECIMAL(9,6) NOT NULL,
        longitude DECIMAL(9,6) NOT NULL,
        INDEX idx_cidade_busca (cidade_busca)
    ) $motor", $log);
    $r = mysqli_query($conexao, "SELECT COUNT(*) FROM cidade");
    if ($r && (int) mysqli_fetch_row($r)[0] === 0) {
        $total = importarCidades($conexao);
        $log[] = [$total > 5000 ? 'ok' : 'erro', "Municípios e estados importados: $total"];
    }
    foreach (['artista' => 'artista', 'usuarios' => 'usuario'] as $tabela => $prefixo) {
        migracaoAdicionarColuna($conexao, $tabela, "{$prefixo}_lat", 'DECIMAL(9,6) NULL', $log);
        migracaoAdicionarColuna($conexao, $tabela, "{$prefixo}_lon", 'DECIMAL(9,6) NULL', $log);
        migracaoAdicionarColuna($conexao, $tabela, "{$prefixo}_geo_cidade", 'VARCHAR(100) NULL', $log);
    }

    // ===== v4: segurança =====
    // Limite de tentativas (login, cadastro, troca de senha).
    migracaoCriarTabela($conexao, 'tentativa', "CREATE TABLE tentativa (
        tentativa_id INT PRIMARY KEY AUTO_INCREMENT,
        chave VARCHAR(60) NOT NULL,
        criada TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_tentativa (chave, criada)
    ) $motor", $log);
    // LGPD: reproduções guardavam o IP puro; passam a guardar só um hash.
    $segredo = segredoApp();
    $stmt = mysqli_prepare($conexao, "UPDATE visualizacoes SET ip_usuario = CONCAT('h:', LEFT(SHA2(CONCAT(?, ip_usuario), 256), 38)) WHERE ip_usuario NOT LIKE 'h:%'");
    mysqli_stmt_bind_param($stmt, "s", $segredo);
    if (mysqli_stmt_execute($stmt)) {
        $log[] = ['ok', 'IPs das reproduções anonimizados: ' . mysqli_stmt_affected_rows($stmt)];
    }

    $houveErro = false;
    foreach ($log as $item) {
        if ($item[0] === 'erro') {
            $houveErro = true;
            error_log('Migração: ' . $item[1]);
        }
    }

    // Só grava a versão nova se tudo deu certo; senão tenta de novo na próxima
    // requisição (e o erro fica no log do PHP).
    if (!$houveErro) {
        $versao = (string) VERSAO_ESQUEMA;
        $stmt = mysqli_prepare($conexao, "REPLACE INTO sistema (chave, valor) VALUES ('versao_esquema', ?)");
        mysqli_stmt_bind_param($stmt, "s", $versao);
        mysqli_stmt_execute($stmt);
    }

    return $log;
}
?>
