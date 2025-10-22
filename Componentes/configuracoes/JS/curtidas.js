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
            document.getElementById('likeCount').textContent = data.curtidas;
            document.getElementById('dislikeCount').textContent = data.descurtidas;
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
            document.getElementById('likeCount').textContent = data.curtidas || 0;
            document.getElementById('dislikeCount').textContent = data.descurtidas || 0;
        }
    })
    .catch(error => {
        console.error('Erro ao carregar curtidas:', error);
    });
}

// Adicionar estilos CSS para os botões de curtida
const style = document.createElement('style');
style.textContent = `
    .like-controls {
        gap: 15px;
    }
    
    .like-btn, .dislike-btn {
        background: transparent;
        border: 2px solid #ffd700;
        border-radius: 50px;
        padding: 8px 15px;
        color: #ffd700;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 5px;
        font-size: 14px;
        font-weight: 600;
    }
    
    .like-btn:hover {
        background: #ffd700;
        color: #0d1117;
        transform: scale(1.05);
    }
    
    .dislike-btn {
        border-color: #ff6b6b;
        color: #ff6b6b;
    }
    
    .dislike-btn:hover {
        background: #ff6b6b;
        color: #ffffff;
        transform: scale(1.05);
    }
    
    .like-btn svg, .dislike-btn svg {
        width: 18px;
        height: 18px;
    }
`;
document.head.appendChild(style);