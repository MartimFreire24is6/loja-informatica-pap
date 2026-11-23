<?php
session_start();
$totalItensCarrinho = array_sum($_SESSION['carrinho'] ?? []);

require_once 'db.php';

// Buscar todas as categorias para o menu de filtros
$stmtCat = $pdo->query("SELECT * FROM categorias ORDER BY nome ASC");
$categorias = $stmtCat->fetchAll();

$categoriaId = (int)($_GET['categoria'] ?? 0);
$pesquisa = is_string($_GET['q'] ?? null) ? trim($_GET['q']) : '';
$ordem = is_string($_GET['ordem'] ?? null) ? $_GET['ordem'] : 'recentes';
if (!in_array($ordem, ['recentes', 'preco_asc', 'preco_desc', 'nome_asc'], true)) {
    $ordem = 'recentes';
}

$where = [];
$params = [];

if ($categoriaId > 0) {
    $where[] = 'p.categoria_id = ?';
    $params[] = $categoriaId;
}

if ($pesquisa !== '') {
    $where[] = '(p.nome LIKE ? OR p.marca LIKE ? OR p.descricao LIKE ?)';
    $termo = '%' . $pesquisa . '%';
    $params[] = $termo;
    $params[] = $termo;
    $params[] = $termo;
}

$orderBy = match ($ordem) {
    'preco_asc' => 'p.preco ASC',
    'preco_desc' => 'p.preco DESC',
    'nome_asc' => 'p.nome ASC',
    default => 'p.id DESC',
};

$sql = "SELECT p.*, c.nome AS categoria_nome
        FROM produtos p
        JOIN categorias c ON p.categoria_id = c.id";
if ($where !== []) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
$sql .= " ORDER BY {$orderBy}";

$stmtProd = $pdo->prepare($sql);
$stmtProd->execute($params);
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
            <div class="navbar-nav me-auto">
                <a class="nav-link" href="index.php">Início</a>
                <a class="nav-link active" href="catalogo.php">Catálogo</a>
                <a class="nav-link" href="sobre.php">Sobre Nós</a>
            </div>
            <div class="d-flex">
                <a href="carrinho.php" class="btn btn-warning btn-sm fw-bold me-2">
                    🛒 Carrinho (<?= (int)$totalItensCarrinho ?>)
                </a>
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
            <a href="catalogo.php" class="btn <?= $categoriaId === 0 ? 'btn-primary' : 'btn-outline-primary' ?>">
                Todos os Produtos
            </a>
            <?php foreach ($categorias as $cat): ?>
                <a href="catalogo.php?categoria=<?= (int)$cat['id'] ?>"
                   class="btn <?= $categoriaId === (int)$cat['id'] ? 'btn-primary' : 'btn-outline-primary' ?>">
                    <?= htmlspecialchars($cat['nome']) ?>
                </a>
            <?php endforeach; ?>
        </div>

        <form method="GET" class="row g-2 mb-4 align-items-center">
            <?php if ($categoriaId > 0): ?>
                <input type="hidden" name="categoria" value="<?= $categoriaId ?>">
            <?php endif; ?>

            <div class="col-md-6">
                <input type="search" name="q" value="<?= htmlspecialchars($pesquisa, ENT_QUOTES, 'UTF-8') ?>"
                       class="form-control" placeholder="Pesquisar por produto, marca ou especificação...">
            </div>

            <div class="col-md-4">
                <select name="ordem" class="form-select">
                    <option value="recentes" <?= $ordem === 'recentes' ? 'selected' : '' ?>>Ordenar: Mais recentes</option>
                    <option value="preco_asc" <?= $ordem === 'preco_asc' ? 'selected' : '' ?>>Preço: menor para o maior</option>
                    <option value="preco_desc" <?= $ordem === 'preco_desc' ? 'selected' : '' ?>>Preço: maior para o menor</option>
                    <option value="nome_asc" <?= $ordem === 'nome_asc' ? 'selected' : '' ?>>Nome: A → Z</option>
                </select>
            </div>

            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-dark w-100">Filtrar</button>
                <?php if ($pesquisa !== '' || $ordem !== 'recentes'): ?>
                    <a href="catalogo.php<?= $categoriaId > 0 ? '?categoria=' . $categoriaId : '' ?>" class="btn btn-outline-secondary">Limpar</a>
                <?php endif; ?>
            </div>
        </form>

        <!-- Grelha de Produtos -->
        <div class="row row-cols-1 row-cols-md-3 g-4 mb-5">
            <?php if (count($produtos) === 0): ?>
                <div class="col-12">
                    <div class="alert alert-warning">Nenhum produto encontrado com estes filtros.</div>
                </div>
            <?php else: ?>
                <?php foreach ($produtos as $prod): ?>
                    <div class="col">
                        <div class="card h-100 shadow-sm position-relative">
                            <img src="<?= htmlspecialchars($prod['imagem']) ?>" class="card-img-top" alt="<?= htmlspecialchars($prod['nome']) ?>">
                            <div class="card-body d-flex flex-column">
                                <div class="mb-2">
                                    <span class="badge bg-secondary"><?= htmlspecialchars($prod['categoria_nome']) ?></span>
                                    <span class="badge bg-info text-dark"><?= htmlspecialchars($prod['marca']) ?></span>
                                </div>
                                <h5 class="card-title">
                                    <a href="produto.php?id=<?= (int)$prod['id'] ?>" class="text-decoration-none text-dark stretched-link">
                                        <?= htmlspecialchars($prod['nome']) ?>
                                    </a>
                                </h5>
                                <p class="card-text text-muted small flex-grow-1"><?= htmlspecialchars($prod['descricao']) ?></p>
                                <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top">
                                    <span class="fs-5 fw-bold text-success"><?= number_format($prod['preco'], 2, ',', ' ') ?> €</span>
                                    <span class="small <?= $prod['stock'] > 0 ? 'text-muted' : 'text-danger fw-bold' ?>">
                                        <?= $prod['stock'] > 0 ? "Stock: {$prod['stock']} un." : 'Esgotado' ?>
                                    </span>
                                </div>
                                <form method="POST" action="carrinho.php" class="mt-3 position-relative" style="z-index: 2;">
                                    <input type="hidden" name="acao" value="adicionar">
                                    <input type="hidden" name="id" value="<?= (int)$prod['id'] ?>">
                                    <button type="submit" class="btn btn-primary btn-sm w-100" <?= ((int)$prod['stock'] <= 0) ? 'disabled' : '' ?>>
                                        <?= ((int)$prod['stock'] > 0) ? '+ Adicionar ao Carrinho' : 'Sem Stock' ?>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

</body>
</html>
