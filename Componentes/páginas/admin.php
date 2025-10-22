<main class="main">
        <section class="principal">
            <div class="principal-content">
                <h1 style="color: var(--neon-white); text-align: center; margin-bottom: 40px; text-shadow: 0 0 10px rgba(255, 255, 255, 0.3);">Painel Administrativo</h1>
            </div>
            
            <div style="text-align: center; margin-bottom: 30px;">
                <button class="btn-neon" onclick="showForm('musica')" id="btnMusica">Cadastrar Música</button>
                <button class="btn-neon" onclick="showForm('artista')" id="btnArtista" style="margin-left: 20px;">Cadastrar Artista</button>
            </div>
            
            <script>
            function showForm(tipo) {
                console.log('Chamando showForm com:', tipo);
                const formMusica = document.getElementById('formMusica');
                const formArtista = document.getElementById('formArtista');
                const musicasSection = document.getElementById('musicasSection');
                const artistasSection = document.getElementById('artistasSection');
                
                if (tipo === 'artista') {
                    formMusica.style.display = 'none';
                    formArtista.style.display = 'block';
                    musicasSection.style.display = 'none';
                    artistasSection.style.display = 'block';
                } else {
                    formMusica.style.display = 'block';
                    formArtista.style.display = 'none';
                    musicasSection.style.display = 'block';
                    artistasSection.style.display = 'none';
                }
            }
            </script>
            
            <div id="formMusica">
                <?php include "Componentes/páginas/formMusica.php"; ?>
            </div>
            
            <div id="formArtista" style="display: none;">
                <?php include "Componentes/páginas/formArtista.php"; ?>
            </div>
            
            <div style="margin-top: 50px; margin-bottom: 150px;">
                <div id="musicasSection">
                    <h2 style="color: var(--neon-white); text-align: center; margin-bottom: 30px; text-shadow: 0 0 10px rgba(255, 255, 255, 0.3);">Músicas Cadastradas</h2>
                    <div class="grid-container" id="cardContainer">
                        <?php
                        include "Componentes/páginas/php/funcoesMusicas.php";
                        $musicas = buscarMusicas($conexao);
                        if (count($musicas) > 0) {
                            exibirMusicas($musicas);
                        } else {
                            echo "<p style='color: var(--text-secondary); text-align: center; font-style: italic;'>Nenhuma música cadastrada ainda.</p>";
                        }
                        ?>
                    </div>
                </div>
                
                <div id="artistasSection" style="display: none;">
                    <h2 style="color: var(--neon-white); text-align: center; margin-bottom: 30px; text-shadow: 0 0 10px rgba(255, 255, 255, 0.3);">Artistas Cadastrados</h2>
                    <div class="grid-container" id="artistaContainer">
                        <?php
                        if (file_exists("Componentes/páginas/php/funcoesArtistas.php")) {
                            include "Componentes/páginas/php/funcoesArtistas.php";
                            $artistas = buscarArtistas($conexao);
                            if (count($artistas) > 0) {
                                exibirArtistas($artistas);
                            } else {
                                echo "<p style='color: var(--text-secondary); text-align: center; font-style: italic;'>Nenhum artista cadastrado ainda.</p>";
                            }
                        } else {
                            echo "<p style='color: var(--text-secondary); text-align: center; font-style: italic;'>Erro ao carregar artistas.</p>";
                        }
                        ?>
                    </div>
                </div>
            </div>
        </section>
    </div>
</main>

