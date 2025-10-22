function showForm(tipo) {
    const formMusica = document.getElementById('formMusica');
    const formArtista = document.getElementById('formArtista');
    const btnMusica = document.getElementById('btnMusica');
    const btnArtista = document.getElementById('btnArtista');
    const musicasSection = document.getElementById('musicasSection');
    const artistasSection = document.getElementById('artistasSection');
    
    if (!formMusica || !formArtista) return;
    
    if (tipo === 'musica') {
        formMusica.style.display = 'block';
        formArtista.style.display = 'none';
        musicasSection.style.display = 'block';
        artistasSection.style.display = 'none';
        btnMusica.style.opacity = '1';
        btnArtista.style.opacity = '0.6';
    } else {
        formMusica.style.display = 'none';
        formArtista.style.display = 'block';
        musicasSection.style.display = 'none';
        artistasSection.style.display = 'block';
        btnMusica.style.opacity = '0.6';
        btnArtista.style.opacity = '1';
    }
}

document.addEventListener('DOMContentLoaded', function() {
    showForm('musica');
});