let musicaAtualId = null;

function setMusicaAtual(musicaId) {
    musicaAtualId = musicaId;
    carregarCurtidas(musicaId);
}

function curtirMusica(tipo) {
    if (!musicaAtualId) {
        alert('Selecione uma música primeiro');
        return;
    }
    
    fetch('Componentes/páginas/php/processar_curtida.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            musica_id: musicaAtualId,
            tipo: tipo
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const likeCount = document.getElementById('likeCount');
            const dislikeCount = document.getElementById('dislikeCount');
            if (likeCount) likeCount.textContent = data.curtidas;
            if (dislikeCount) dislikeCount.textContent = data.descurtidas;
        } else {
            console.error('Erro:', data.error);
        }
    })
    .catch(error => {
        console.error('Erro na requisição:', error);
    });
}

function carregarCurtidas(musicaId) {
    fetch('Componentes/páginas/php/processar_curtida.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            musica_id: musicaId,
            acao: 'carregar'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const likeCount = document.getElementById('likeCount');
            const dislikeCount = document.getElementById('dislikeCount');
            if (likeCount) likeCount.textContent = data.curtidas || 0;
            if (dislikeCount) dislikeCount.textContent = data.descurtidas || 0;
        }
    })
    .catch(error => {
        console.error('Erro ao carregar curtidas:', error);
    });
}
