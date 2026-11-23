<?php
$paginaAtual = basename($_SERVER['PHP_SELF'] ?? '');
$totalItensCarrinho = 0;

if (!empty($_SESSION['carrinho']) && is_array($_SESSION['carrinho'])) {
    foreach ($_SESSION['carrinho'] as $quantidade) {
        $totalItensCarrinho += max(0, (int)$quantidade);
    }
}
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top shadow-sm d-print-none">
    <div class="container">
        <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="index.php">
            <span>⚡ TechStore PAP</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarTechStore" aria-controls="navbarTechStore" aria-expanded="false" aria-label="Alternar navegação">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarTechStore">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link <?= $paginaAtual === 'index.php' ? 'active' : '' ?>" href="index.php">Início</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $paginaAtual === 'catalogo.php' ? 'active' : '' ?>" href="catalogo.php">Catálogo</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $paginaAtual === 'sobre.php' ? 'active' : '' ?>" href="sobre.php">Contactos &amp; Sobre</a>
                </li>
            </ul>
            <div class="d-flex align-items-center gap-2">
                <a href="carrinho.php" class="btn btn-outline-light btn-sm position-relative">
                    🛒 Carrinho
                    <span class="badge bg-primary ms-1 tabular-nums"><?= (int)$totalItensCarrinho ?></span>
                </a>
                <a href="admin.php" class="btn <?= $paginaAtual === 'admin.php' ? 'btn-warning text-dark' : 'btn-outline-warning' ?> btn-sm">
                    Admin
                </a>
                <?php if (!empty($_SESSION['admin_logado'])): ?>
                    <a href="logout.php" class="btn btn-outline-danger btn-sm">Sair</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>
