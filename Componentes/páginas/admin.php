<main class="main">
        <section class="principal">
            <div class="principal-content">
                <h1 class="admin-title">Painel Administrativo</h1>
            </div>

            <div class="admin-toolbar">
                <button class="btn-neon" onclick="showForm('musica')" id="btnMusica">Cadastrar Música</button>
                <button class="btn-neon" onclick="showForm('artista')" id="btnArtista">Cadastrar Artista</button>
                <a href="migrarUsuariosArtistas.php" class="btn-neon">Migrar Usuários</a>
                <a href="gerenciarUsuarios.php" class="btn-neon">Gerenciar Usuários</a>
                <a href="iniciarBanco.php" class="btn-neon">Configurar BD</a>
                <a href="configurarDescricoes.php" class="btn-neon">Configurar Descrições</a>
                <a href="gerenciarPropagandas.php" class="btn-neon">Gerenciar Propagandas</a>
            </div>

            <script>
            function showForm(tipo) {
                console.log('Chamando showForm com:', tipo);
                const formMusica = document.getElementById('formMusica');
                const formArtista = document.getElementById('formArtista');
                const btnMusica = document.getElementById('btnMusica');
                const btnArtista = document.getElementById('btnArtista');

                btnMusica.classList.remove('btn-active', 'btn-neon-inactive');
                btnArtista.classList.remove('btn-active', 'btn-neon-inactive');

                if (tipo === 'artista') {
                    formMusica.style.display = 'none';
                    formArtista.style.display = 'block';
                    btnMusica.classList.add('btn-neon-inactive');
                    btnArtista.classList.add('btn-active');
                } else {
                    formMusica.style.display = 'block';
                    formArtista.style.display = 'none';
                    btnArtista.classList.add('btn-neon-inactive');
                    btnMusica.classList.add('btn-active');
                }
            }
            
            // Inicializar
            document.addEventListener('DOMContentLoaded', function() {
                showForm('musica');
            });
            </script>
            
            <div id="formMusica">
                <?php include "Componentes/páginas/formMusica.php"; ?>
            </div>
            
            <div id="formArtista" style="display: none;">
                <?php include "Componentes/páginas/formArtista.php"; ?>
                
            </div>
        </section>
</main>
