document.addEventListener('DOMContentLoaded', function() {
    let timeout, cidadeTimeout;
    const artistaInput = document.getElementById('artista_nome');
    const cidadeInput = document.getElementById('artista_cidade');
    const suggestions = document.getElementById('artistaSuggestions');
    const cidadeSuggestions = document.getElementById('cidadeSuggestions');
    let artistaExistente = false;

    if (artistaInput && suggestions) {
        artistaInput.addEventListener('input', function() {
            clearTimeout(timeout);
            const query = this.value.trim();
            artistaExistente = false;
            
            if (query.length < 2) {
                suggestions.style.display = 'none';
                return;
            }
            
            timeout = setTimeout(() => {
                fetch(`Componentes/páginas/buscarArtistas.php?q=${encodeURIComponent(query)}`)
                    .then(response => response.json())
                    .then(data => {
                        suggestions.innerHTML = '';
                        if (data.length > 0) {
                            data.forEach(artista => {
                                const item = document.createElement('div');
                                item.className = 'suggestion-item';
                                item.innerHTML = `<strong>${artista.artista_nome}</strong><br><small>${artista.artista_cidade}</small>`;
                                item.onclick = () => {
                                    artistaInput.value = artista.artista_nome;
                                    if (cidadeInput) cidadeInput.value = artista.artista_cidade;
                                    if (document.getElementById('artista_id')) {
                                        document.getElementById('artista_id').value = artista.artista_id;
                                    }
                                    artistaExistente = true;
                                    suggestions.style.display = 'none';
                                };
                                suggestions.appendChild(item);
                            });
                            suggestions.style.display = 'block';
                        } else {
                            suggestions.style.display = 'none';
                        }
                    })
                    .catch(error => {
                        console.error('Erro na busca de artistas:', error);
                        suggestions.style.display = 'none';
                    });
            }, 300);
        });
    }

    if (cidadeInput && cidadeSuggestions) {
        cidadeInput.addEventListener('input', function() {
            clearTimeout(cidadeTimeout);
            const query = this.value.trim();
            
            if (query.length < 2) {
                cidadeSuggestions.style.display = 'none';
                return;
            }
            
            cidadeTimeout = setTimeout(() => {
                fetch(`Componentes/páginas/buscarCidades.php?q=${encodeURIComponent(query)}`)
                    .then(response => response.json())
                    .then(data => {
                        cidadeSuggestions.innerHTML = '';
                        if (data.length > 0) {
                            data.forEach(cidade => {
                                const item = document.createElement('div');
                                item.className = 'suggestion-item';
                                item.innerHTML = `<strong>${cidade.artista_cidade}</strong><br><small>Artistas: ${cidade.artistas}</small>`;
                                item.onclick = () => {
                                    cidadeInput.value = cidade.artista_cidade;
                                    cidadeSuggestions.style.display = 'none';
                                    showArtistsByCity(cidade.artista_cidade);
                                };
                                cidadeSuggestions.appendChild(item);
                            });
                            cidadeSuggestions.style.display = 'block';
                        } else {
                            cidadeSuggestions.style.display = 'none';
                        }
                    })
                    .catch(error => {
                        console.error('Erro na busca de cidades:', error);
                        cidadeSuggestions.style.display = 'none';
                    });
            }, 300);
        });
    }

    // Função para mostrar artistas por cidade
    window.showArtistsByCity = function(cidade) {
        fetch(`Componentes/páginas/buscarArtistasPorCidade.php?cidade=${encodeURIComponent(cidade)}`)
            .then(response => response.json())
            .then(data => {
                const artistList = document.getElementById('artistList');
                const modal = document.getElementById('artistasPorCidade');
                
                if (artistList && modal) {
                    artistList.innerHTML = '';
                    
                    if (data.length > 0) {
                        data.forEach(artista => {
                            const item = document.createElement('div');
                            item.className = 'artist-option';
                            item.style.padding = '10px';
                            item.style.cursor = 'pointer';
                            item.style.borderBottom = '1px solid #eee';
                            item.innerHTML = `<strong>${artista.artista_nome}</strong>`;
                            item.onclick = () => {
                                if (artistaInput) artistaInput.value = artista.artista_nome;
                                if (cidadeInput) cidadeInput.value = artista.artista_cidade;
                                if (document.getElementById('artista_id')) {
                                    document.getElementById('artista_id').value = artista.artista_id;
                                }
                                modal.style.display = 'none';
                            };
                            artistList.appendChild(item);
                        });
                    } else {
                        artistList.innerHTML = '<p style="padding: 10px;">Nenhum artista encontrado nesta cidade.</p>';
                    }
                    
                    modal.style.display = 'block';
                }
            })
            .catch(error => {
                console.error('Erro ao buscar artistas por cidade:', error);
            });
    };
    
    // Função para fechar modal
    window.closeArtistModal = function() {
        const modal = document.getElementById('artistasPorCidade');
        if (modal) modal.style.display = 'none';
    };
    
    // Função para adicionar novo artista
    window.addNewArtist = function() {
        if (artistaInput) artistaInput.value = '';
        if (document.getElementById('artista_id')) document.getElementById('artista_id').value = '';
        const modal = document.getElementById('artistasPorCidade');
        if (modal) modal.style.display = 'none';
        const newFields = document.getElementById('newArtistFields');
        if (newFields) newFields.style.display = 'block';
    };

    // Fechar sugestões ao clicar fora
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.form-col')) {
            if (suggestions) suggestions.style.display = 'none';
            if (cidadeSuggestions) cidadeSuggestions.style.display = 'none';
        }
    });
});