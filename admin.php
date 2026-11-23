<?php
require_once 'db.php';

$mensagem = '';
$tipoAlerta = 'success';

// 1. Adicionar novo produto (CREATE)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'adicionar') {
    $categoria_id = (int)($_POST['categoria_id'] ?? 0);
    $nome         = trim($_POST['nome'] ?? '');
    $marca        = trim($_POST['marca'] ?? '');
    $descricao    = trim($_POST['descricao'] ?? '');
    $preco        = (float)str_replace(',', '.', $_POST['preco'] ?? 0);
    $stock        = (int)($_POST['stock'] ?? 0);
    $imagem       = trim($_POST['imagem'] ?? '');

    if ($imagem === '') {
        $imagem = 'https://images.unsplash.com/photo-1518770660439-4636190af475?w=600';
    }

    if ($categoria_id > 0 && $nome !== '' && $marca !== '' && $preco > 0) {
        $stmt = $pdo->prepare("INSERT INTO produtos (categoria_id, nome, marca, descricao, preco, stock, imagem) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$categoria_id, $nome, $marca, $descricao, $preco, $stock, $imagem]);
        $mensagem = "Produto «{$nome}» adicionado com sucesso!";
    } else {
        $mensagem = "Preenche os campos obrigatórios (categoria, nome, marca e preço válido).";
        $tipoAlerta = 'danger';
    }
}

// 2. Atualizar preço e stock (UPDATE)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'atualizar') {
    $id    = (int)($_POST['id'] ?? 0);
    $preco = (float)str_replace(',', '.', $_POST['preco'] ?? 0);
    $stock = (int)($_POST['stock'] ?? 0);

    if ($id > 0 && $preco >= 0 && $stock >= 0) {
        $stmt = $pdo->prepare("UPDATE produtos SET preco = ?, stock = ? WHERE id = ?");
        $stmt->execute([$preco, $stock, $id]);
        $mensagem = "Stock e preço do produto #{$id} atualizados!";
    }
}

// 3. Apagar produto (DELETE)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'apagar') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
        $stmt = $pdo->prepare("DELETE FROM produtos WHERE id = ?");
        $stmt->execute([$id]);
        $mensagem = "Produto removido do catálogo.";
        $tipoAlerta = 'warning';
    }
}

// Buscar categorias e produtos para listar na página
$categorias = $pdo->query("SELECT * FROM categorias ORDER BY nome ASC")->fetchAll();
$produtos   = $pdo->query("SELECT p.*, c.nome AS categoria_nome FROM produtos p JOIN categorias c ON p.categoria_id = c.id ORDER BY p.id DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administração - TechStore PAP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

    <nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php">🖥️ TechStore PAP</a>
            <div class="d-flex">
                <a href="index.php" class="btn btn-outline-light btn-sm">← Voltar à Loja</a>
            </div>
        </div>
    </nav>

    <div class="container mb-5">
        <h2 class="fw-bold mb-3">Painel de Administração de Produtos</h2>

        <?php if ($mensagem !== ''): ?>
            <div class="alert alert-<?= $tipoAlerta ?> alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($mensagem) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Formulário para Adicionar Produto -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-primary text-white fw-bold">
                + Adicionar Novo Produto
            </div>
            <div class="card-body">
                <form method="POST" class="row g-3">
                    <input type="hidden" name="acao" value="adicionar">

                    <div class="col-md-4">
                        <label class="form-label">Nome do Produto *</label>
                        <input type="text" name="nome" class="form-control" placeholder="Ex: SSD Samsung 990 Pro 1TB" required>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Marca *</label>
                        <input type="text" name="marca" class="form-control" placeholder="Ex: Samsung" required>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Categoria *</label>
                        <select name="categoria_id" class="form-select" required>
                            <?php foreach ($categorias as $cat): ?>
                                <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['nome']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">Preço (€) *</label>
                        <input type="number" step="0.01" min="0" name="preco" class="form-control" placeholder="129.90" required>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">Stock Inicial *</label>
                        <input type="number" min="0" name="stock" class="form-control" value="5" required>
                    </div>

                    <div class="col-md-5">
                        <label class="form-label">URL da Imagem (opcional)</label>
                        <input type="url" name="imagem" class="form-control" placeholder="https://...">
                    </div>

                    <div class="col-md-5">
                        <label class="form-label">Descrição / Especificações</label>
                        <input type="text" name="descricao" class="form-control" placeholder="NVMe PCIe 4.0, Leitura 7450 MB/s...">
                    </div>

                    <div class="col-12">
                        <button type="submit" class="btn btn-success">Guardar Produto na Base de Dados</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Tabela de Gestão de Stock e Produtos -->
        <div class="card shadow-sm">
            <div class="card-header bg-dark text-white fw-bold">
                Catálogo Atual (<?= count($produtos) ?> produtos)
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Produto</th>
                            <th>Categoria</th>
                            <th style="width: 140px;">Preço (€)</th>
                            <th style="width: 110px;">Stock</th>
                            <th class="text-end">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($produtos as $prod): ?>
                            <tr>
                                <td><?= $prod['id'] ?></td>
                                <td>
                                    <div class="fw-bold"><?= htmlspecialchars($prod['nome']) ?></div>
                                    <small class="text-muted"><?= htmlspecialchars($prod['marca']) ?></small>
                                </td>
                                <td><span class="badge bg-secondary"><?= htmlspecialchars($prod['categoria_nome']) ?></span></td>
                                <td>
                                    <form method="POST" class="d-flex gap-1 align-items-center">
                                        <input type="hidden" name="acao" value="atualizar">
                                        <input type="hidden" name="id" value="<?= $prod['id'] ?>">
                                        <input type="number" step="0.01" min="0" name="preco" value="<?= $prod['preco'] ?>" class="form-control form-control-sm">
                                </td>
                                <td>
                                        <input type="number" min="0" name="stock" value="<?= $prod['stock'] ?>" class="form-control form-control-sm">
                                </td>
                                <td class="text-end">
                                        <button type="submit" class="btn btn-primary btn-sm">Atualizar</button>
                                    </form>
                                    <form method="POST" class="d-inline" onsubmit="return confirm('Tens a certeza que queres apagar este produto?');">
                                        <input type="hidden" name="acao" value="apagar">
                                        <input type="hidden" name="id" value="<?= $prod['id'] ?>">
                                        <button type="submit" class="btn btn-danger btn-sm">Apagar</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>