<section class="principal">
    <div class="principal-content">
        <h1>Conteúdo Principal</h1>
        <p>Esta é a área principal do site.</p>
        <!-- Conteúdo adicional aqui -->
    </div>
    <div class="scroll-container">
        <div class="scroll-controls">
            <button class="scroll-btn scroll-btn-left" onclick="scrollToLeft()">‹</button>
            <button class="scroll-btn scroll-btn-right" onclick="scrollRight()">›</button>
        </div>
        <div class="grid-container" id="cardContainer">

        <div class="grid-card">
            <div class="title-card">
                <h2>Título do card</h2>
            </div>
            <img src="https://i.ytimg.com/vi/yWjCY_CHQeY/maxresdefault.jpg" alt="" class="image-music-card">
            <div class="autor-card">
                <h4>Artista</h4>
            </div>
        </div>

        <div class="grid-card">
            <div class="title-card">
                <h2>Título do card</h2>
            </div>
            <img src="https://i.ytimg.com/vi/yWjCY_CHQeY/maxresdefault.jpg" alt="" class="image-music-card">
            <div class="autor-card">
                <h4>Artista</h4>
            </div>
        </div>

        <div class="grid-card">
            <div class="title-card">
                <h2>Título do card</h2>
            </div>
            <img src="https://i.ytimg.com/vi/yWjCY_CHQeY/maxresdefault.jpg" alt="" class="image-music-card">
            <div class="autor-card">
                <h4>Artista</h4>
            </div>
        </div>

        <div class="grid-card">
            <div class="title-card">
                <h2>Título do card</h2>
            </div>
            <img src="https://i.ytimg.com/vi/yWjCY_CHQeY/maxresdefault.jpg" alt="" class="image-music-card">
            <div class="autor-card">
                <h4>Artista</h4>
            </div>
        </div>

        <div class="grid-card">
            <div class="title-card">
                <h2>Título do card</h2>
            </div>
            <img src="https://i.ytimg.com/vi/yWjCY_CHQeY/maxresdefault.jpg" alt="" class="image-music-card">
            <div class="autor-card">
                <h4>Artista</h4>
            </div>
        </div>

        <div class="grid-card">
            <div class="title-card">
                <h2>Título do card</h2>
            </div>
            <img src="https://i.ytimg.com/vi/yWjCY_CHQeY/maxresdefault.jpg" alt="" class="image-music-card">
            <div class="autor-card">
                <h4>Artista</h4>
            </div>
        </div>
    </div>

    <script>
    function scrollToLeft() {
        document.getElementById('cardContainer').scrollBy({
            left: -300,
            behavior: 'smooth'
        });
    }
    
    function scrollRight() {
        document.getElementById('cardContainer').scrollBy({
            left: 300,
            behavior: 'smooth'
        });
    }
    </script>
</section>