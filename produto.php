<?php
session_start();
require_once 'db.php';
require_once 'includes/helpers.php';
$id = (int)($_GET['id'] ?? 0);

$produto = null;
if ($id > 0) {
    $stmt = $pdo->prepare("
        SELECT p.*, c.nome AS categoria_nome
        FROM produtos p
        JOIN categorias c ON p.categoria_id = c.id
        WHERE p.id = ?
        LIMIT 1
    ");
    $stmt->execute([$id]);
    $produto = $stmt->fetch();
}
$estadoStockProduto = $produto ? obterEstadoStockProduto((int)$produto['stock']) : null;

// Suporte para adicionar quantidade específica vinda do produto.php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'adicionar_qtd') {
    $prodId = (int)($_POST['id'] ?? 0);
    $qtd    = max(1, (int)($_POST['qtd'] ?? 1));

    if ($prodId > 0) {
        $stmt = $pdo->prepare("SELECT id, nome, stock FROM produtos WHERE id = ? LIMIT 1");
        $stmt->execute([$prodId]);
        $item = $stmt->fetch();

        if ($item && (int)$item['stock'] > 0) {
            if (!isset($_SESSION['carrinho']) || !is_array($_SESSION['carrinho'])) {
                $_SESSION['carrinho'] = [];
            }
            $atual = (int)($_SESSION['carrinho'][$prodId] ?? 0);
            $novaQtd = min((int)$item['stock'], $atual + $qtd);
            $_SESSION['carrinho'][$prodId] = $novaQtd;
            header('Location: carrinho.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $produto ? htmlspecialchars($produto['nome']) . ' — TechStore PAP' : 'Produto não encontrado — TechStore PAP' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/estilo.css" rel="stylesheet">
</head>
<body class="bg-light">

    <?php require 'includes/navbar.php'; ?>

    <main class="container mb-5 flex-grow-1">
        <a href="catalogo.php" class="btn btn-outline-secondary btn-sm mb-3">← Voltar ao Catálogo</a>

        <?php if (!$produto): ?>
            <div class="card shadow-sm p-4 text-center">
                <h2 class="h4 fw-bold mb-2">Produto não encontrado</h2>
                <p class="text-muted mb-3">O artigo que procuras não existe ou já foi removido.</p>
                <div><a href="catalogo.php" class="btn btn-primary">Ver Catálogo</a></div>
            </div>
        <?php else: ?>
            <div class="card shadow-sm border-0">
                <div class="row g-0">
                    <div class="col-md-5 produto-detalhe-img-wrap d-flex align-items-center justify-content-center p-3">
                        <?php if (!empty($produto['imagem'])): ?>
                            <img src="<?= htmlspecialchars($produto['imagem']) ?>"
                                 alt="<?= htmlspecialchars($produto['nome']) ?>"
                                 class="img-fluid rounded produto-imagem-detalhe">
                        <?php else: ?>
                            <div class="text-muted py-5">Sem imagem disponível</div>
                        <?php endif; ?>
                    </div>

                    <div class="col-md-7">
                        <div class="card-body p-4 p-lg-5">
                            <div class="mb-2">
                                <span class="badge bg-secondary"><?= htmlspecialchars($produto['categoria_nome']) ?></span>
                                <span class="badge bg-info text-dark"><?= htmlspecialchars($produto['marca']) ?></span>
                            </div>

                            <h1 class="h3 fw-bold mb-3"><?= htmlspecialchars($produto['nome']) ?></h1>

                            <div class="fs-3 fw-bold text-success preco-destaque tabular-nums mb-2">
                                <?= number_format((float)$produto['preco'], 2, ',', ' ') ?> €
                            </div>

                            <p class="mb-4">
                                <span class="badge rounded-pill <?= htmlspecialchars($estadoStockProduto['classe']) ?> tabular-nums">
                                    <?= htmlspecialchars($estadoStockProduto['texto']) ?>
                                </span>
                            </p>

                            <h2 class="h6 fw-bold text-uppercase text-muted">Descrição e Especificações</h2>
                            <p class="text-secondary mb-4">
                                <?= nl2br(htmlspecialchars($produto['descricao'] ?: 'Sem descrição adicional.')) ?>
                            </p>

                            <?php if ((int)$produto['stock'] > 0): ?>
                                <form method="POST" class="row g-2 align-items-end">
                                    <input type="hidden" name="acao" value="adicionar_qtd">
                                    <input type="hidden" name="id" value="<?= (int)$produto['id'] ?>">

                                    <div class="col-4 col-sm-3">
                                        <label for="qtd" class="form-label small fw-bold">Quantidade</label>
                                        <input type="number" id="qtd" name="qtd" value="1" min="1"
                                               max="<?= (int)$produto['stock'] ?>" class="form-control tabular-nums">
                                    </div>

                                    <div class="col-8 col-sm-6">
                                        <button type="submit" class="btn btn-primary w-100">
                                            + Adicionar ao Carrinho
                                        </button>
                                    </div>
                                </form>
                            <?php else: ?>
                                <button type="button" class="btn btn-secondary" disabled>Indisponível de momento</button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </main>

    <?php require 'includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>