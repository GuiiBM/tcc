let timeout, paisTimeout;
const artistaInput = document.getElementById('artista_nome');
const paisInput = document.getElementById('artista_pais');
const suggestions = document.getElementById('artistaSuggestions');
const paisSuggestions = document.getElementById('paisSuggestions');
let artistaExistente = false;

if (artistaInput) {
    artistaInput.addEventListener('input', function() {
        clearTimeout(timeout);
        const query = this.value.trim();
        artistaExistente = false;
        
        if (query.length < 2) {
            suggestions.style.display = 'none';
            showNewArtistFields(false);
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
                            item.innerHTML = `<strong>${artista.artista_nome}</strong><br><small>${artista.artista_pais}</small>`;
                            item.onclick = () => selectArtist(artista);
                            suggestions.appendChild(item);
                        });
                        suggestions.style.display = 'block';
                        showNewArtistFields(false);
                    } else {
                        suggestions.style.display = 'none';
                        showNewArtistFields(true);
                    }
                })
                .catch(error => {
                    console.error('Erro na busca:', error);
                    suggestions.style.display = 'none';
                    showNewArtistFields(true);
                });
        }, 300);
    });
    
    artistaInput.addEventListener('blur', function() {
        setTimeout(() => {
            if (!artistaExistente && this.value.trim().length > 0) {
                showNewArtistFields(true);
            }
        }, 200);
    });
}

if (paisInput) {
    paisInput.addEventListener('input', function() {
        clearTimeout(paisTimeout);
        const query = this.value.trim();
        
        if (query.length < 2) {
            paisSuggestions.style.display = 'none';
            return;
        }
        
        paisTimeout = setTimeout(() => {
            fetch(`Componentes/páginas/buscarPaises.php?q=${encodeURIComponent(query)}`)
                .then(response => response.json())
                .then(data => {
                    paisSuggestions.innerHTML = '';
                    if (data.length > 0) {
                        data.forEach(pais => {
                            const item = document.createElement('div');
                            item.className = 'suggestion-item';
                            item.innerHTML = `<strong>${pais.artista_pais}</strong><br><small>Artistas: ${pais.artistas}</small>`;
                            item.onclick = () => selectPais(pais.artista_pais);
                            paisSuggestions.appendChild(item);
                        });
                        paisSuggestions.style.display = 'block';
                    } else {
                        paisSuggestions.style.display = 'none';
                    }
                })
                .catch(error => {
                    console.error('Erro na busca de países:', error);
                    paisSuggestions.style.display = 'none';
                });
        }, 300);
    });
}

function selectArtist(artista) {
    artistaInput.value = artista.artista_nome;
    paisInput.value = artista.artista_pais;
    if (document.getElementById('artista_id')) {
        document.getElementById('artista_id').value = artista.artista_id;
    }
    artistaExistente = true;
    suggestions.style.display = 'none';
    showNewArtistFields(false);
}

function selectPais(pais) {
    paisInput.value = pais;
    paisSuggestions.style.display = 'none';
    showArtistsByCountry(pais);
}

function showArtistsByCountry(pais) {
    fetch(`Componentes/páginas/buscarArtistasPorPais.php?pais=${encodeURIComponent(pais)}`)
        .then(response => response.json())
        .then(data => {
            const artistList = document.getElementById('artistList');
            const modal = document.getElementById('artistasPorPais');
            
            artistList.innerHTML = '';
            
            if (data.length > 0) {
                data.forEach(artista => {
                    const item = document.createElement('div');
                    item.className = 'artist-option';
                    item.innerHTML = `<strong>${artista.artista_nome}</strong>`;
                    item.onclick = () => selectExistingArtist(artista);
                    artistList.appendChild(item);
                });
            }
            
            modal.style.display = 'block';
        })
        .catch(error => {
            console.error('Erro ao buscar artistas:', error);
        });
}

function selectExistingArtist(artista) {
    artistaInput.value = artista.artista_nome;
    document.getElementById('artista_id').value = artista.artista_id;
    artistaExistente = true;
    closeArtistModal();
    showNewArtistFields(false);
}

function addNewArtist() {
    artistaInput.value = '';
    document.getElementById('artista_id').value = '';
    artistaExistente = false;
    closeArtistModal();
    showNewArtistFields(true);
}

function closeArtistModal() {
    document.getElementById('artistasPorPais').style.display = 'none';
}

function showNewArtistFields(show) {
    const newArtistFields = document.getElementById('newArtistFields');
    if (newArtistFields) {
        newArtistFields.style.display = show ? 'block' : 'none';
        const inputs = newArtistFields.querySelectorAll('input, textarea');
        inputs.forEach(input => {
            input.required = show;
        });
    }
}

document.addEventListener('click', function(e) {
    if (!e.target.closest('.form-col')) {
        if (suggestions) {
            suggestions.style.display = 'none';
        }
        if (paisSuggestions) {
            paisSuggestions.style.display = 'none';
        }
    }
});