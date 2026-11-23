<?php
session_start();
require_once 'db.php';
require_once 'includes/helpers.php';

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
    <link href="assets/css/estilo.css" rel="stylesheet">
</head>
<body class="bg-light">

    <?php require 'includes/navbar.php'; ?>

    <main class="flex-grow-1">
        <div class="container py-4">
            <section class="ts-hero mb-4">
            <div class="row align-items-center g-4">
                <div class="col-lg-8">
                    <span class="badge rounded-pill ts-hero-badge mb-3 px-3 py-2">
                        Hardware, Periféricos &amp; Componentes • Projeto PAP
                    </span>
                    <h1 class="display-6 fw-bold mb-3">
                        A tua loja de informática feita para performance e gestão simples.
                    </h1>
                    <p class="lead text-light opacity-75 mb-4">
                        Explora o catálogo atualizado em tempo real, valida disponibilidade de stock no carrinho e simula pagamentos com comprovativo imediato.
                    </p>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="catalogo.php" class="btn btn-primary btn-lg px-4">Explorar Catálogo</a>
                        <a href="carrinho.php" class="btn btn-outline-light btn-lg px-4">Ver Carrinho</a>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="bg-white bg-opacity-10 border border-white border-opacity-25 rounded-4 p-4">
                        <div class="small text-uppercase mb-2 opacity-75">Destaques do Sistema</div>
                        <ul class="list-unstyled mb-0 small d-grid gap-2">
                            <li>✔ Validação de stock em transação segura</li>
                            <li>✔ Checkout com MB WAY, Multibanco, Cartão e IBAN</li>
                            <li>✔ Painel administrativo completo 100% web</li>
                        </ul>
                    </div>
                </div>
            </div>
            </section>

            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="card h-100 border-0">
                        <div class="card-body">
                            <div class="fw-semibold mb-1">Stock em Tempo Real</div>
                            <p class="text-muted small mb-0">Cada compra confirma a disponibilidade e atualiza as unidades restantes automaticamente.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 border-0">
                        <div class="card-body">
                            <div class="fw-semibold mb-1">Checkout Simulado Realista</div>
                            <p class="text-muted small mb-0">Escolha do método de pagamento com geração de referência e recibo pronto a imprimir.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 border-0">
                        <div class="card-body">
                            <div class="fw-semibold mb-1">Gestão Centralizada</div>
                            <p class="text-muted small mb-0">Categorias, produtos com upload local, encomendas e mensagens geridos no painel admin.</p>
                        </div>
                    </div>
                </div>
            </div>

    <!-- Categorias -->
    <section class="mb-5">
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
    <section id="destaques" class="mb-5">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="h4 fw-bold mb-0">Destaques em Stock</h2>
            <a href="catalogo.php" class="btn btn-outline-dark btn-sm">Ir para o Catálogo</a>
        </div>

        <div class="row row-cols-1 row-cols-md-3 g-4">
            <?php foreach ($destaques as $prod): ?>
                <?php $estadoStock = obterEstadoStockProduto((int)$prod['stock']); ?>
                <div class="col">
                    <div class="card produto-card h-100 shadow-sm border-0 position-relative">
                        <?php if (!empty($prod['imagem'])): ?>
                            <div class="produto-img-wrap">
                                <img src="<?= htmlspecialchars($prod['imagem']) ?>" alt="<?= htmlspecialchars($prod['nome']) ?>">
                            </div>
                        <?php endif; ?>
                        <div class="card-body d-flex flex-column">
                            <div class="mb-2">
                                <span class="badge bg-secondary"><?= htmlspecialchars($prod['categoria_nome']) ?></span>
                                <span class="badge bg-info text-dark"><?= htmlspecialchars($prod['marca']) ?></span>
                                <span class="badge rounded-pill <?= htmlspecialchars($estadoStock['classe']) ?> tabular-nums"><?= htmlspecialchars($estadoStock['texto']) ?></span>
                            </div>
                            <h5 class="card-title">
                                <a href="produto.php?id=<?= (int)$prod['id'] ?>" class="text-decoration-none text-dark stretched-link">
                                    <?= htmlspecialchars($prod['nome']) ?>
                                </a>
                            </h5>
                            <p class="card-text text-muted small flex-grow-1"><?= htmlspecialchars($prod['descricao']) ?></p>
                            <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top">
                                <span class="fs-5 fw-bold text-success preco-destaque tabular-nums"><?= number_format((float)$prod['preco'], 2, ',', ' ') ?> €</span>
                            </div>
                            <form method="POST" action="carrinho.php" class="mt-3 position-relative" style="z-index: 2;">
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
        </div>
    </main>

    <?php require 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>