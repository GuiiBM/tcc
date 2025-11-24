<main class="main">
        <section class="principal">
            <div class="principal-content">
                <h1 style="color: var(--neon-white); text-align: center; margin-bottom: 40px; text-shadow: 0 0 10px rgba(255, 255, 255, 0.3);">Painel Administrativo</h1>
            </div>
            
            <div style="text-align: center; margin-bottom: 30px;">
                <button class="btn-neon" onclick="showForm('musica')" id="btnMusica" style="margin-bottom: 15px;">Cadastrar Música</button>
                <button class="btn-neon" onclick="showForm('artista')" id="btnArtista" style="margin-left: 30px; margin-bottom: 15px;">Cadastrar Artista</button>
                <a href="migrarUsuariosArtistas.php" class="btn-neon" style="margin-left: 30px; margin-bottom: 15px; text-decoration: none; display: inline-block;">Migrar Usuários</a>
                <a href="gerenciarUsuarios.php" class="btn-neon" style="margin-left: 30px; margin-bottom: 15px; text-decoration: none; display: inline-block;">Gerenciar Usuários</a>
                <a href="iniciarBanco.php" class="btn-neon" style="margin-left: 30px; margin-bottom: 15px; text-decoration: none; display: inline-block;">Configurar BD</a>
                <a href="configurarDescricoes.php" class="btn-neon" style="margin-left: 30px; margin-bottom: 15px; text-decoration: none; display: inline-block;">Configurar Descrições</a>
                <a href="gerenciarPropagandas.php" class="btn-neon" style="margin-left: 30px; margin-bottom: 15px; text-decoration: none; display: inline-block;">Gerenciar Propagandas</a>
                <button class="btn-neon" onclick="toggleNgrok()" id="btnNgrok" style="margin-left: 30px; margin-bottom: 15px;">🌐 Ngrok</button>
            </div>
            
            <!-- Status do Ngrok -->
            <div id="ngrokStatus" style="text-align: center; margin-bottom: 20px; display: none;">
                <div id="statusMessage" style="padding: 10px; border-radius: 5px; margin: 10px auto; max-width: 600px;"></div>
                <div id="ngrokUrl" style="margin-top: 10px;"></div>
            </div>
            
            <script>
            function showForm(tipo) {
                console.log('Chamando showForm com:', tipo);
                const formMusica = document.getElementById('formMusica');
                const formArtista = document.getElementById('formArtista');
                const musicasSection = document.getElementById('musicasSection');
                const artistasSection = document.getElementById('artistasSection');
                const btnMusica = document.getElementById('btnMusica');
                const btnArtista = document.getElementById('btnArtista');
                
                btnMusica.classList.remove('btn-active');
                btnArtista.classList.remove('btn-active');
                
                if (tipo === 'artista') {
                    formMusica.style.display = 'none';
                    formArtista.style.display = 'block';
                    musicasSection.style.display = 'none';
                    btnMusica.style.opacity = '0.6';
                    btnArtista.style.opacity = '1';
                    btnArtista.classList.add('btn-active');
                } else {
                    formMusica.style.display = 'block';
                    formArtista.style.display = 'none';
                    musicasSection.style.display = 'block';
                    btnMusica.style.opacity = '1';
                    btnArtista.style.opacity = '0.6';
                    btnMusica.classList.add('btn-active');
                }
            }
            
            // Inicializar
            document.addEventListener('DOMContentLoaded', function() {
                showForm('musica');
                checkNgrokStatus();
            });
            
            // Funções do Ngrok
            let ngrokRunning = false;
            
            function checkNgrokStatus() {
                fetch('ngrok_final.php?action=status')
                    .then(response => response.json())
                    .then(data => {
                        ngrokRunning = data.running;
                        updateNgrokButton();
                    })
                    .catch(error => console.error('Erro ao verificar status:', error));
            }
            
            function updateNgrokButton() {
                const btn = document.getElementById('btnNgrok');
                if (ngrokRunning) {
                    btn.innerHTML = '🔴 Parar Ngrok';
                    btn.style.backgroundColor = '#dc3232';
                } else {
                    btn.innerHTML = '🌐 Iniciar Ngrok';
                    btn.style.backgroundColor = '';
                }
            }
            
            function toggleNgrok() {
                const btn = document.getElementById('btnNgrok');
                const statusDiv = document.getElementById('ngrokStatus');
                const messageDiv = document.getElementById('statusMessage');
                const urlDiv = document.getElementById('ngrokUrl');
                
                btn.disabled = true;
                btn.innerHTML = '⏳ Processando...';
                statusDiv.style.display = 'block';
                
                const action = ngrokRunning ? 'stop' : 'start';
                
                fetch('ngrok_final.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=' + action
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        messageDiv.innerHTML = '<span style="color: #4CAF50;">✓ ' + data.message + '</span>';
                        if (data.url) {
                            urlDiv.innerHTML = '<strong>URL Pública:</strong> <a href="' + data.url + '" target="_blank" style="color: #00bcd4;">' + data.url + '</a> <button onclick="copyUrl(\'' + data.url + '\')" style="margin-left: 10px; padding: 5px 10px; background: #007cba; color: white; border: none; border-radius: 3px; cursor: pointer;">📋 Copiar</button>';
                        } else {
                            urlDiv.innerHTML = '';
                        }
                        ngrokRunning = !ngrokRunning;
                    } else {
                        messageDiv.innerHTML = '<span style="color: #f44336;">✗ ' + data.message + '</span>';
                    }
                    
                    btn.disabled = false;
                    updateNgrokButton();
                })
                .catch(error => {
                    messageDiv.innerHTML = '<span style="color: #f44336;">✗ Erro de conexão</span>';
                    btn.disabled = false;
                    updateNgrokButton();
                    console.error('Erro:', error);
                });
            }
            
            function copyUrl(url) {
                navigator.clipboard.writeText(url).then(function() {
                    alert('URL copiada para a área de transferência!');
                }).catch(function() {
                    // Fallback para navegadores mais antigos
                    const textArea = document.createElement('textarea');
                    textArea.value = url;
                    document.body.appendChild(textArea);
                    textArea.select();
                    document.execCommand('copy');
                    document.body.removeChild(textArea);
                    alert('URL copiada!');
                });
            }
            </script>
            
            <div id="formMusica">
                <?php include "Componentes/páginas/formMusica.php"; ?>
            </div>
            
            <div id="formArtista" style="display: none;">
                <?php include "Componentes/páginas/formArtista.php"; ?>
                
            </div>            
        </section>
    </div>
</main>
