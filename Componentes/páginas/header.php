<header class="navbar navbar-expand-lg py-3">
    <div class="container-fluid px-3">
        <a class="navbar-brand d-flex align-items-center" href="#">
            <img src="Componentes/icones/icone2.png" alt="Ícone" width="48" height="48" class="me-2">
            <span class="brand-name">Ressonance</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent" aria-controls="navbarContent" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse justify-content-between" id="navbarContent">
            <ul class="navbar-nav mx-auto mb-2 mb-lg-0" style="text-align: center;">
                <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
                <li class="nav-item"><a class="nav-link" href="artistas.php">Artistas</a></li>
                <li class="nav-item"><a class="nav-link" href="#">Álbuns</a></li>
            </ul>
            <div class="d-flex align-items-center" style="white-space: nowrap;">
                <?php 
                if (session_status() == PHP_SESSION_NONE) {
                    session_start();
                }
                
                if (isset($_SESSION['usuario_id'])): ?>
                    <span class="navbar-text me-3" style="color: #ffd700; font-size: 14px; white-space: nowrap; text-align: right; display: inline-block;">Olá, <?= htmlspecialchars($_SESSION['usuario_nome']) ?>!</span>
                    <?php if (isset($_SESSION['usuario_tipo']) && $_SESSION['usuario_tipo'] === 'admin'): ?>
                        <a href="admin.php" class="btn btn-outline-warning me-2" style="white-space: nowrap;">Admin</a>
                    <?php else: ?>
                        <a href="músicas.php" class="btn btn-outline-warning me-2" style="white-space: nowrap;">Músicas</a>
                    <?php endif; ?>
                    <a href="logout.php" class="btn btn-outline-danger" style="white-space: nowrap;">Sair</a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-outline-light me-2" style="white-space: nowrap;">Login</a>
                    <a href="login.php" class="btn btn-warning" style="white-space: nowrap;">Sign-UP</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</header>