<?php
session_start();

if (isset($_GET['limpar_recibo'])) {
    unset($_SESSION['ultima_encomenda']);
    header('Location: carrinho.php');
    exit;
}

$ultimaEncomenda = $_SESSION['ultima_encomenda'] ?? null;

require_once 'db.php';

if (!isset($_SESSION['carrinho']) || !is_array($_SESSION['carrinho'])) {
    $_SESSION['carrinho'] = [];
}

$mensagem = '';
$tipoAlerta = 'info';

// Adicionar produto ao carrinho
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'adicionar') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
        $stmt = $pdo->prepare("SELECT id, nome, preco, stock FROM produtos WHERE id = ?");
        $stmt->execute([$id]);
        $prod = $stmt->fetch();

        if ($prod && (int)$prod['stock'] > 0) {
            $qtdAtual = $_SESSION['carrinho'][$id] ?? 0;
            if ($qtdAtual + 1 <= (int)$prod['stock']) {
                $_SESSION['carrinho'][$id] = $qtdAtual + 1;
                $mensagem = "«{$prod['nome']}» adicionado ao carrinho.";
                $tipoAlerta = 'success';
            } else {
                $mensagem = "Só existem {$prod['stock']} unidade(s) em stock de «{$prod['nome']}».";
                $tipoAlerta = 'warning';
            }
        } else {
            $mensagem = "Produto indisponível ou sem stock.";
            $tipoAlerta = 'danger';
        }
    }
}

// Atualizar quantidade
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'atualizar_qtd') {
    $id  = (int)($_POST['id'] ?? 0);
    $qtd = (int)($_POST['qtd'] ?? 0);

    if ($id > 0) {
        if ($qtd <= 0) {
            unset($_SESSION['carrinho'][$id]);
        } else {
            $stmt = $pdo->prepare("SELECT stock, nome FROM produtos WHERE id = ?");
            $stmt->execute([$id]);
            $prod = $stmt->fetch();
            if ($prod) {
                $qtdFinal = min($qtd, (int)$prod['stock']);
                if ($qtdFinal > 0) {
                    $_SESSION['carrinho'][$id] = $qtdFinal;
                    if ($qtd > (int)$prod['stock']) {
                        $mensagem = "Quantidade ajustada ao stock disponível ({$prod['stock']} un.) para «{$prod['nome']}».";
                        $tipoAlerta = 'warning';
                    }
                } else {
                    unset($_SESSION['carrinho'][$id]);
                }
            }
        }
    }
}

// Remover item
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'remover') {
    $id = (int)($_POST['id'] ?? 0);
    unset($_SESSION['carrinho'][$id]);
}

// Finalizar compra (desconta stock, grava encomenda e itens com transação PDO)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'finalizar') {
    if (empty($_SESSION['carrinho'])) {
        $mensagem = "O carrinho está vazio.";
        $tipoAlerta = 'warning';
    } else {
        try {
            $pdo->beginTransaction();
            $itensResumo = [];
            $totalPago = 0.0;

            foreach ($_SESSION['carrinho'] as $prodId => $qtd) {
                $prodId = (int)$prodId;
                $qtd    = (int)$qtd;

                $stmt = $pdo->prepare("SELECT id, nome, marca, preco, stock FROM produtos WHERE id = ? FOR UPDATE");
                $stmt->execute([$prodId]);
                $prod = $stmt->fetch();

                if (!$prod || (int)$prod['stock'] < $qtd) {
                    throw new Exception("Stock insuficiente para «" . ($prod['nome'] ?? "Produto #{$prodId}") . "».");
                }

                $preco = (float)$prod['preco'];
                $subtotal = $preco * $qtd;
                $totalPago += $subtotal;
                $itensResumo[] = [
                    'produto_id' => (int)$prod['id'],
                    'nome' => $prod['nome'],
                    'marca' => $prod['marca'],
                    'preco' => $preco,
                    'qtd' => $qtd,
                    'subtotal' => $subtotal,
                ];

                $upd = $pdo->prepare("UPDATE produtos SET stock = stock - ? WHERE id = ?");
                $upd->execute([$qtd, $prodId]);
            }

            $referencia = 'PAP-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));

            $stmtEnc = $pdo->prepare("INSERT INTO encomendas (referencia, total) VALUES (?, ?)");
            $stmtEnc->execute([$referencia, $totalPago]);
            $encomendaId = (int)$pdo->lastInsertId();

            $stmtItem = $pdo->prepare("
                INSERT INTO itens_encomenda (encomenda_id, produto_id, nome_produto, marca, preco_unitario, quantidade, subtotal)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");

            foreach ($itensResumo as $linha) {
                $stmtItem->execute([
                    $encomendaId,
                    $linha['produto_id'],
                    $linha['nome'],
                    $linha['marca'],
                    $linha['preco'],
                    $linha['qtd'],
                    $linha['subtotal'],
                ]);
            }

            $pdo->commit();
            $_SESSION['ultima_encomenda'] = [
                'referencia' => $referencia,
                'data' => date('d/m/Y H:i'),
                'itens' => $itensResumo,
                'total' => $totalPago,
            ];
            unset($_SESSION['carrinho']);
            header('Location: carrinho.php?sucesso=1');
            exit;
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $mensagem = "Não foi possível concluir a compra: " . $e->getMessage();
            $tipoAlerta = 'danger';
        }
    }
}

// Carregar detalhes dos itens atualmente no carrinho
$itensCarrinho = [];
$totalGeral = 0.0;

if (!empty($_SESSION['carrinho'])) {
    $ids = array_keys($_SESSION['carrinho']);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare("SELECT p.*, c.nome AS categoria_nome FROM produtos p JOIN categorias c ON p.categoria_id = c.id WHERE p.id IN ($placeholders)");
    $stmt->execute(array_map('intval', $ids));
    $rows = $stmt->fetchAll();

    foreach ($rows as $row) {
        $id = (int)$row['id'];
        $qtd = (int)($_SESSION['carrinho'][$id] ?? 0);
        if ($qtd > 0) {
            $subtotal = $qtd * (float)$row['preco'];
            $totalGeral += $subtotal;
            $row['qtd'] = $qtd;
            $row['subtotal'] = $subtotal;
            $itensCarrinho[] = $row;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carrinho - TechStore PAP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php">🖥️ TechStore PAP</a>
            <div class="d-flex gap-2">
                <a href="index.php" class="btn btn-outline-light btn-sm">← Continuar a Comprar</a>
                <a href="admin.php" class="btn btn-outline-secondary btn-sm text-white">Área de Administração</a>
            </div>
        </div>
    </nav>

    <div class="container mb-5">
        <h2 class="fw-bold mb-3">Carrinho de Compras</h2>

        <?php if ($mensagem !== ''): ?>
            <div class="alert alert-<?= $tipoAlerta ?> alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($mensagem) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (empty($itensCarrinho) && $ultimaEncomenda): ?>
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body p-4">
                    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
                        <div>
                            <span class="badge bg-success mb-2">Encomenda Concluída</span>
                            <h2 class="h4 fw-bold mb-1">Resumo da Encomenda</h2>
                            <div class="text-muted small">
                                Referência: <strong><?= htmlspecialchars($ultimaEncomenda['referencia']) ?></strong> •
                                Data: <strong><?= htmlspecialchars($ultimaEncomenda['data']) ?></strong>
                            </div>
                        </div>
                        <a href="carrinho.php?limpar_recibo=1" class="btn btn-outline-secondary btn-sm">Fechar Resumo</a>
                    </div>

                    <div class="table-responsive">
                        <table class="table align-middle mb-3">
                            <thead class="table-light">
                                <tr>
                                    <th>Produto</th>
                                    <th class="text-center">Qtd.</th>
                                    <th class="text-end">Preço Unit.</th>
                                    <th class="text-end">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($ultimaEncomenda['itens'] as $linha): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-semibold"><?= htmlspecialchars($linha['nome']) ?></div>
                                            <small class="text-muted"><?= htmlspecialchars($linha['marca']) ?></small>
                                        </td>
                                        <td class="text-center"><?= (int)$linha['qtd'] ?></td>
                                        <td class="text-end"><?= number_format((float)$linha['preco'], 2, ',', ' ') ?> €</td>
                                        <td class="text-end fw-semibold"><?= number_format((float)$linha['subtotal'], 2, ',', ' ') ?> €</td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="3" class="text-end fs-6">Total Pago:</th>
                                    <th class="text-end fs-5 text-success">
                                        <?= number_format((float)$ultimaEncomenda['total'], 2, ',', ' ') ?> €
                                    </th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <div class="d-flex flex-wrap gap-2">
                        <a href="catalogo.php" class="btn btn-primary">Continuar a Comprar</a>
                        <a href="index.php" class="btn btn-outline-dark">Voltar ao Início</a>
                    </div>
                </div>
            </div>
        <?php elseif (empty($itensCarrinho)): ?>
            <div class="card shadow-sm p-4 text-center">
                <p class="text-muted mb-3">O teu carrinho está vazio de momento.</p>
                <div>
                    <a href="index.php" class="btn btn-primary">Explorar Catálogo</a>
                </div>
            </div>
        <?php else: ?>
            <div class="card shadow-sm mb-4">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Produto</th>
                                <th>Preço Unit.</th>
                                <th style="width: 170px;">Quantidade</th>
                                <th>Subtotal</th>
                                <th class="text-end">Remover</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($itensCarrinho as $item): ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold"><?= htmlspecialchars($item['nome']) ?></div>
                                        <small class="text-muted"><?= htmlspecialchars($item['marca']) ?> • <?= htmlspecialchars($item['categoria_nome']) ?> (Stock: <?= (int)$item['stock'] ?>)</small>
                                    </td>
                                    <td><?= number_format((float)$item['preco'], 2, ',', ' ') ?> €</td>
                                    <td>
                                        <form method="POST" class="d-flex gap-1">
                                            <input type="hidden" name="acao" value="atualizar_qtd">
                                            <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
                                            <input type="number" name="qtd" min="0" max="<?= (int)$item['stock'] ?>" value="<?= (int)$item['qtd'] ?>" class="form-control form-control-sm">
                                            <button type="submit" class="btn btn-outline-primary btn-sm">OK</button>
                                        </form>
                                    </td>
                                    <td class="fw-bold"><?= number_format((float)$item['subtotal'], 2, ',', ' ') ?> €</td>
                                    <td class="text-end">
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="acao" value="remover">
                                            <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
                                            <button type="submit" class="btn btn-outline-danger btn-sm">Remover</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="d-flex flex-wrap justify-content-between align-items-center bg-white p-4 rounded shadow-sm gap-3">
                <div>
                    <span class="text-muted">Total da Encomenda:</span>
                    <span class="fs-4 fw-bold text-success ms-2"><?= number_format($totalGeral, 2, ',', ' ') ?> €</span>
                </div>
                <form method="POST">
                    <input type="hidden" name="acao" value="finalizar">
                    <button type="submit" class="btn btn-success btn-lg">Finalizar Compra</button>
                </form>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>