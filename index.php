<?php
require_once 'db.php';

// Buscar todas as categorias para o menu de filtros
$stmtCat = $pdo->query("SELECT * FROM categorias ORDER BY nome ASC");
$categorias = $stmtCat->fetchAll();

// Verificar se o utilizador filtrou por alguma categoria na URL (ex: index.php?categoria=perifericos)
$categoriaSlug = $_GET['categoria'] ?? '';

if ($categoriaSlug !== '') {
    $sql = "SELECT p.*, c.nome AS categoria_nome 
            FROM produtos p 
            JOIN categorias c ON p.categoria_id = c.id 
            WHERE c.slug = :slug 
            ORDER BY p.id DESC";
    $stmtProd = $pdo->prepare($sql);
    $stmtProd->execute(['slug' => $categoriaSlug]);
} else {
    $sql = "SELECT p.*, c.nome AS categoria_nome 
            FROM produtos p 
            JOIN categorias c ON p.categoria_id = c.id 
            ORDER BY p.id DESC";
    $stmtProd = $pdo->query($sql);
}

$produtos = $stmtProd->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TechStore PAP - Computadores e Periféricos</title>
    <!-- Bootstrap 5 via CDN para um visual moderno e responsivo -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .card-img-top {
            height: 200px;
            object-fit: cover;
        }
    </style>
</head>
<body class="bg-light">

    <!-- Barra de Navegação -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php">🖥️ TechStore PAP</a>
            <div class="d-flex">
                <a href="admin.php" class="btn btn-outline-light btn-sm">Área de Administração</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <!-- Cabeçalho -->
        <div class="p-4 mb-4 bg-white rounded-3 shadow-sm">
            <h1 class="display-6 fw-bold">Hardware & Periféricos</h1>
            <p class="text-muted mb-0">Catálogo dinâmico ligado à base de dados MariaDB.</p>
        </div>

        <!-- Filtros por Categoria -->
        <div class="mb-4 d-flex flex-wrap gap-2">
            <a href="index.php" class="btn <?= $categoriaSlug === '' ? 'btn-primary' : 'btn-outline-primary' ?>">
                Todos os Produtos
            </a>
            <?php foreach ($categorias as $cat): ?>
                <a href="index.php?categoria=<?= htmlspecialchars($cat['slug']) ?>" 
                   class="btn <?= $categoriaSlug === $cat['slug'] ? 'btn-primary' : 'btn-outline-primary' ?>">
                    <?= htmlspecialchars($cat['nome']) ?>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- Grelha de Produtos -->
        <div class="row row-cols-1 row-cols-md-3 g-4 mb-5">
            <?php if (count($produtos) === 0): ?>
                <div class="col-12">
                    <div class="alert alert-warning">Nenhum produto encontrado nesta categoria.</div>
                </div>
            <?php else: ?>
                <?php foreach ($produtos as $prod): ?>
                    <div class="col">
                        <div class="card h-100 shadow-sm">
                            <img src="<?= htmlspecialchars($prod['imagem']) ?>" class="card-img-top" alt="<?= htmlspecialchars($prod['nome']) ?>">
                            <div class="card-body d-flex flex-column">
                                <div class="mb-2">
                                    <span class="badge bg-secondary"><?= htmlspecialchars($prod['categoria_nome']) ?></span>
                                    <span class="badge bg-info text-dark"><?= htmlspecialchars($prod['marca']) ?></span>
                                </div>
                                <h5 class="card-title"><?= htmlspecialchars($prod['nome']) ?></h5>
                                <p class="card-text text-muted small flex-grow-1"><?= htmlspecialchars($prod['descricao']) ?></p>
                                <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top">
                                    <span class="fs-5 fw-bold text-success"><?= number_format($prod['preco'], 2, ',', ' ') ?> €</span>
                                    <span class="small <?= $prod['stock'] > 0 ? 'text-muted' : 'text-danger fw-bold' ?>">
                                        <?= $prod['stock'] > 0 ? "Stock: {$prod['stock']} un." : 'Esgotado' ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

</body>
</html>