class AutoDescriptionUpdater {
    constructor() {
        this.updateInterval = 2 * 60 * 1000; // 2 minutos
        this.isUpdating = false;
        this.lastUpdate = null;
        this.descriptionsCache = {};
        this.init();
    }

    init() {
        // Primeira atualização imediata
        this.updateDescriptions();
        
        // Iniciar atualizações automáticas
        this.startAutoUpdate();
        
        // Atualizar quando a página ganhar foco
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden && !this.isUpdating) {
                this.updateDescriptions();
            }
        });
    }

    startAutoUpdate() {
        setInterval(() => {
            if (!this.isUpdating) {
                this.updateDescriptions();
            }
        }, this.updateInterval);
    }

    async updateDescriptions() {
        if (this.isUpdating) return;
        
        this.isUpdating = true;
        
        try {
            const formData = new URLSearchParams();
            formData.append('action', 'update');
            if (this.lastUpdate) {
                formData.append('lastUpdate', this.lastUpdate);
            }

            const response = await fetch('Componentes/páginas/php/updateDescriptions.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: formData
            });

            const result = await response.json();
            
            if (result.success) {
                this.lastUpdate = result.timestamp;
                
                // Só atualizar cache se houve mudanças
                if (result.descriptions && Object.keys(result.descriptions).length > 0) {
                    this.descriptionsCache = { ...this.descriptionsCache, ...result.descriptions };
                    this.updateLocalCache(result.descriptions);
                }
            }
        } catch (error) {
            // Falha silenciosa para não interromper a experiência do usuário
        } finally {
            this.isUpdating = false;
        }
    }

    updateLocalCache(descriptions) {
        // Atualizar descrições em popups abertos
        const artistDescription = document.getElementById('artistDescription');
        if (artistDescription && descriptions) {
            const currentArtist = document.getElementById('artistName')?.textContent;
            if (currentArtist && descriptions[currentArtist]) {
                artistDescription.textContent = descriptions[currentArtist];
            }
        }
    }

    // Método público para obter descrição do cache
    getDescription(artistName) {
        return this.descriptionsCache[artistName] || null;
    }
}

// Inicializar quando o DOM estiver pronto
document.addEventListener('DOMContentLoaded', () => {
    new AutoDescriptionUpdater();
});