<?php
session_start();
require_once 'db.php';

$totalItensCarrinho = array_sum($_SESSION['carrinho'] ?? []);

$categorias = $pdo->query("SELECT * FROM categorias ORDER BY nome ASC")->fetchAll();
$destaques  = $pdo->query("
    SELECT p.*, c.nome AS categoria_nome
    FROM produtos p
    JOIN categorias c ON p.categoria_id = c.id
    WHERE p.stock > 0
    ORDER BY p.id DESC
    LIMIT 3
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TechStore PAP — Computadores, Hardware e Periféricos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php">🖥️ TechStore PAP</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menuNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="menuNav">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item"><a class="nav-link active" href="index.php">Início</a></li>
                    <li class="nav-item"><a class="nav-link" href="catalogo.php">Catálogo</a></li>
                </ul>
                <div class="d-flex gap-2">
                    <a href="carrinho.php" class="btn btn-warning btn-sm fw-bold">
                        🛒 Carrinho (<?= (int)$totalItensCarrinho ?>)
                    </a>
                    <a href="admin.php" class="btn btn-outline-light btn-sm">Área de Administração</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Banner -->
    <section class="bg-dark text-white py-5 mb-5">
        <div class="container py-3">
            <div class="row align-items-center g-4">
                <div class="col-lg-8">
                    <span class="badge bg-primary mb-2">Hardware &amp; Setup</span>
                    <h1 class="display-5 fw-bold mb-3">Equipamento de informática pensado para performance</h1>
                    <p class="lead text-light opacity-75 mb-4">
                        Portáteis, placas gráficas, monitores e periféricos com controlo de stock em tempo real e checkout direto.
                    </p>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="catalogo.php" class="btn btn-primary btn-lg px-4">Explorar Catálogo</a>
                        <a href="#destaques" class="btn btn-outline-light btn-lg px-4">Ver Destaques</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Categorias -->
    <section class="container mb-5">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="h4 fw-bold mb-0">Categorias</h2>
            <a href="catalogo.php" class="text-decoration-none small">Ver tudo →</a>
        </div>
        <div class="row row-cols-2 row-cols-md-4 g-3">
            <?php foreach ($categorias as $cat): ?>
                <div class="col">
                    <a href="catalogo.php?categoria=<?= (int)$cat['id'] ?>" class="card text-decoration-none shadow-sm h-100 border-0">
                        <div class="card-body d-flex align-items-center justify-content-between">
                            <span class="fw-bold text-dark"><?= htmlspecialchars($cat['nome']) ?></span>
                            <span class="text-primary">→</span>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Produtos em Destaque -->
    <section id="destaques" class="container mb-5">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="h4 fw-bold mb-0">Destaques em Stock</h2>
            <a href="catalogo.php" class="btn btn-outline-dark btn-sm">Ir para o Catálogo</a>
        </div>

        <div class="row row-cols-1 row-cols-md-3 g-4">
            <?php foreach ($destaques as $prod): ?>
                <div class="col">
                    <div class="card h-100 shadow-sm border-0">
                        <?php if (!empty($prod['imagem'])): ?>
                            <img src="<?= htmlspecialchars($prod['imagem']) ?>" class="card-img-top" alt="<?= htmlspecialchars($prod['nome']) ?>" style="height: 200px; object-fit: cover;">
                        <?php endif; ?>
                        <div class="card-body d-flex flex-column">
                            <div class="mb-2">
                                <span class="badge bg-secondary"><?= htmlspecialchars($prod['categoria_nome']) ?></span>
                                <span class="badge bg-info text-dark"><?= htmlspecialchars($prod['marca']) ?></span>
                            </div>
                            <h5 class="card-title"><?= htmlspecialchars($prod['nome']) ?></h5>
                            <p class="card-text text-muted small flex-grow-1"><?= htmlspecialchars($prod['descricao']) ?></p>
                            <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top">
                                <span class="fs-5 fw-bold text-success"><?= number_format((float)$prod['preco'], 2, ',', ' ') ?> €</span>
                                <small class="text-muted">Stock: <?= (int)$prod['stock'] ?> un.</small>
                            </div>
                            <form method="POST" action="carrinho.php" class="mt-3">
                                <input type="hidden" name="acao" value="adicionar">
                                <input type="hidden" name="id" value="<?= (int)$prod['id'] ?>">
                                <button type="submit" class="btn btn-primary btn-sm w-100">+ Adicionar ao Carrinho</button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Vantagens / Rodapé -->
    <footer class="bg-dark text-white py-4 mt-auto">
        <div class="container d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <strong>TechStore PAP</strong> — Projeto de Aptidão Profissional
            </div>
            <div class="small text-secondary">
                Catálogo dinâmico • Carrinho com transação PDO • Gestão de stock
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>