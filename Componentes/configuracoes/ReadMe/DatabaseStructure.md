# Estrutura do Banco de Dados

## Tabelas

### Tabela `artista`
```sql
CREATE TABLE artista (
    artista_id INT PRIMARY KEY AUTO_INCREMENT,
    artista_nome VARCHAR(50) NOT NULL,
    artista_pais VARCHAR(50),
    artista_image VARCHAR(255)
);
```

#### Campos:
- `artista_id`: Chave primária auto-incremento
- `artista_nome`: Nome do artista (obrigatório)
- `artista_pais`: País de origem
- `artista_image`: URL da foto do artista

### Tabela `musica`
```sql
CREATE TABLE musica (
    musica_id INT PRIMARY KEY AUTO_INCREMENT,
    musica_titulo VARCHAR(50) NOT NULL,
    musica_capa VARCHAR(255),
    musica_link VARCHAR(255),
    musica_artista INT,
    CONSTRAINT FOREIGN KEY (musica_artista) REFERENCES artista(artista_id)
);
```

#### Campos:
- `musica_id`: Chave primária auto-incremento
- `musica_titulo`: Título da música (obrigatório)
- `musica_capa`: URL da capa da música
- `musica_link`: Link do YouTube
- `musica_artista`: Chave estrangeira para artista

## Relacionamentos
- **1:N** - Um artista pode ter várias músicas
- **Integridade Referencial** - Músicas sempre vinculadas a artistas válidos

## Arquivos de Configuração
- `banco.php`: Criação das tabelas
- `head.php`: Configuração de conexão
- `iniciarBanco.php`: Inicialização do banco

## Conexão
```php
$host = "localhost";
$usuario = "root";
$senha = "";
$banco = "musicas";
$conexao = mysqli_connect($host, $usuario, $senha);
mysqli_select_db($conexao, $banco);
```