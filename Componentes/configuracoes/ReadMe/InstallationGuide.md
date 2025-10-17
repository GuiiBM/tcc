# Guia de Instalação

## Pré-requisitos
- XAMPP (Apache + MySQL + PHP)
- Navegador web moderno
- Editor de código (opcional)

## Passos de Instalação

### 1. Configurar XAMPP
```bash
# Iniciar Apache e MySQL no painel do XAMPP
# Verificar se os serviços estão rodando
```

### 2. Configurar Banco de Dados
```sql
-- Criar banco de dados
CREATE DATABASE musicas;

-- Usar o banco
USE musicas;

-- As tabelas serão criadas automaticamente pelo sistema
```

### 3. Configurar Projeto
```bash
# Clonar/copiar projeto para:
C:\xampp\htdocs\tcc\

# Estrutura deve ficar:
C:\xampp\htdocs\tcc\
├── Componentes/
├── admin.php
├── index.php
└── ...
```

### 4. Inicializar Sistema
1. Abrir navegador
2. Acessar: `http://localhost/tcc/iniciarBanco.php`
3. Aguardar criação das tabelas
4. Acessar: `http://localhost/tcc/`

## URLs do Sistema
- **Página Inicial**: `http://localhost/tcc/`
- **Músicas**: `http://localhost/tcc/músicas.php`
- **Artistas**: `http://localhost/tcc/artistas.php`
- **Admin**: `http://localhost/tcc/admin.php`

## Configurações do Banco
```php
// Configurações padrão (head.php)
$host = "localhost";
$usuario = "root";
$senha = "";
$banco = "musicas";
```

## Solução de Problemas

### Erro de Conexão
- Verificar se MySQL está rodando
- Confirmar credenciais do banco
- Verificar se banco "musicas" existe

### Erro de Permissão
- Verificar permissões da pasta htdocs
- Executar XAMPP como administrador

### JavaScript não funciona
- Verificar console do navegador
- Confirmar se arquivos JS estão carregando
- Verificar caminhos dos arquivos