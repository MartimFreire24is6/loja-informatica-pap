<?php
session_start();
if (empty($_SESSION['admin_logado'])) {
    header('Location: login.php');
    exit;
}
require_once 'db.php';
garantirTabelaMensagens($pdo);

$mensagem = '';
$tipoAlerta = 'success';
$estadosValidos = ['Pendente', 'Em preparação', 'Enviada', 'Concluída'];

// Criar nova categoria
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'criar_categoria') {
    $nomeCat = $_POST['nome_categoria'] ?? '';
    $nomeCat = is_string($nomeCat) ? trim($nomeCat) : '';
    if ($nomeCat !== '') {
        $stmtCat = $pdo->prepare("SELECT id FROM categorias WHERE nome = ? LIMIT 1");
        $stmtCat->execute([$nomeCat]);

        if ($stmtCat->fetch()) {
            $mensagem = "A categoria «{$nomeCat}» já existe.";
            $tipoAlerta = 'info';
        } else {
            $nomeParaSlug = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $nomeCat);
            $slugBase = strtolower($nomeParaSlug !== false ? $nomeParaSlug : $nomeCat);
            $slugBase = trim((string)preg_replace('/[^a-z0-9]+/', '-', $slugBase), '-');
            if ($slugBase === '') {
                $slugBase = 'categoria';
            }

            $stmtSlug = $pdo->prepare("SELECT id FROM categorias WHERE slug = ? LIMIT 1");
            $slug = substr($slugBase, 0, 100);
            $sufixo = 1;
            while (true) {
                $stmtSlug->execute([$slug]);
                if (!$stmtSlug->fetch()) {
                    break;
                }

                $parteSufixo = '-' . $sufixo++;
                $slug = substr($slugBase, 0, 100 - strlen($parteSufixo)) . $parteSufixo;
            }

            $stmtCat = $pdo->prepare("INSERT INTO categorias (nome, slug) VALUES (?, ?)");
            $stmtCat->execute([$nomeCat, $slug]);
            $mensagem = "Categoria «{$nomeCat}» guardada!";
            $tipoAlerta = 'success';
        }
    } else {
        $mensagem = "Indica um nome para a categoria.";
        $tipoAlerta = 'danger';
    }
}

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

// 2b. Edição completa de produto (UPDATE completo)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'editar_completo') {
    $id           = (int)($_POST['id'] ?? 0);
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

    if ($id > 0 && $categoria_id > 0 && $nome !== '' && $marca !== '' && $preco > 0 && $stock >= 0) {
        $stmt = $pdo->prepare("
            UPDATE produtos
            SET categoria_id = ?, nome = ?, marca = ?, descricao = ?, preco = ?, stock = ?, imagem = ?
            WHERE id = ?
        ");
        $stmt->execute([$categoria_id, $nome, $marca, $descricao, $preco, $stock, $imagem, $id]);
        $mensagem = "Produto «{$nome}» (#{$id}) atualizado com sucesso!";
        $tipoAlerta = 'success';
    } else {
        $mensagem = "Verifica os campos obrigatórios para editar o produto.";
        $tipoAlerta = 'danger';
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

// Atualizar estado de encomenda
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'estado_encomenda') {
    $encId = (int)($_POST['encomenda_id'] ?? 0);
    $novoEstado = $_POST['estado'] ?? 'Pendente';
    $novoEstado = is_string($novoEstado) ? trim($novoEstado) : '';

    if ($encId > 0 && in_array($novoEstado, $estadosValidos, true)) {
        $stmtEst = $pdo->prepare("UPDATE encomendas SET estado = ? WHERE id = ?");
        $stmtEst->execute([$novoEstado, $encId]);
        $mensagem = "Estado da encomenda #{$encId} atualizado para «{$novoEstado}».";
        $tipoAlerta = 'success';
    } else {
        $mensagem = "Não foi possível atualizar o estado da encomenda.";
        $tipoAlerta = 'danger';
    }
}

// Marcar mensagem como lida ou por ler
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'alternar_lida') {
    $msgId = (int)($_POST['mensagem_id'] ?? 0);
    if ($msgId > 0) {
        $stmt = $pdo->prepare("UPDATE mensagens SET lida = IF(lida = 1, 0, 1) WHERE id = ?");
        $stmt->execute([$msgId]);
        $mensagem = "Estado de leitura da mensagem #{$msgId} atualizado.";
        $tipoAlerta = 'info';
    }
}

// Apagar mensagem
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'apagar_mensagem') {
    $msgId = (int)($_POST['mensagem_id'] ?? 0);
    if ($msgId > 0) {
        $stmt = $pdo->prepare("DELETE FROM mensagens WHERE id = ?");
        $stmt->execute([$msgId]);
        $mensagem = "Mensagem #{$msgId} removida.";
        $tipoAlerta = 'warning';
    }
}

// Buscar categorias e produtos para listar na página
$categorias = $pdo->query("SELECT * FROM categorias ORDER BY nome ASC")->fetchAll();
$produtos   = $pdo->query("SELECT p.*, c.nome AS categoria_nome FROM produtos p JOIN categorias c ON p.categoria_id = c.id ORDER BY p.id DESC")->fetchAll();
$produtoEditar = null;
$editarId = (int)($_GET['editar_id'] ?? 0);
if ($editarId > 0) {
    $stmtEdit = $pdo->prepare("SELECT * FROM produtos WHERE id = ?");
    $stmtEdit->execute([$editarId]);
    $produtoEditar = $stmtEdit->fetch() ?: null;
}

// Buscar encomendas e respetivos itens
$encomendas = $pdo->query("SELECT * FROM encomendas ORDER BY id DESC")->fetchAll();
$itensPorEncomenda = [];
if (!empty($encomendas)) {
    $stmtItens = $pdo->query("SELECT * FROM itens_encomenda ORDER BY id ASC");
    foreach ($stmtItens->fetchAll() as $it) {
        $itensPorEncomenda[(int)$it['encomenda_id']][] = $it;
    }
}

// Buscar mensagens recebidas
$mensagensRecebidas = [];
$mensagensNaoLidas = 0;
$mensagensRecebidas = $pdo->query("SELECT * FROM mensagens ORDER BY id DESC")->fetchAll();
foreach ($mensagensRecebidas as $m) {
    if ((int)$m['lida'] === 0) {
        $mensagensNaoLidas++;
    }
}
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
                <a href="logout.php" class="btn btn-outline-danger btn-sm ms-2">Sair</a>
            </div>
        </div>
    </nav>

    <div class="container mb-5">
        <h2 class="fw-bold mb-3">Painel de Administração</h2>

        <?php if ($mensagem !== ''): ?>
            <div class="alert alert-<?= $tipoAlerta ?> alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($mensagem) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <ul class="nav nav-tabs mb-4" id="adminTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active fw-semibold" id="tab-produtos" data-bs-toggle="tab" data-bs-target="#pane-produtos" type="button" role="tab" aria-controls="pane-produtos" aria-selected="true">
                    🖥️ Produtos (<?= count($produtos) ?>)
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-semibold" id="tab-categorias" data-bs-toggle="tab" data-bs-target="#pane-categorias" type="button" role="tab" aria-controls="pane-categorias" aria-selected="false">
                    🏷️ Categorias (<?= count($categorias) ?>)
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-semibold" id="tab-encomendas" data-bs-toggle="tab" data-bs-target="#pane-encomendas" type="button" role="tab" aria-controls="pane-encomendas" aria-selected="false">
                    📦 Encomendas (<?= count($encomendas) ?>)
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-semibold" id="tab-mensagens" data-bs-toggle="tab" data-bs-target="#pane-mensagens" type="button" role="tab" aria-controls="pane-mensagens" aria-selected="false">
                    ✉️ Mensagens (<?= count($mensagensRecebidas) ?>)
                    <?php if ($mensagensNaoLidas > 0): ?>
                        <span class="badge bg-warning text-dark ms-1"><?= (int)$mensagensNaoLidas ?></span>
                    <?php endif; ?>
                </button>
            </li>
        </ul>

        <div class="tab-content" id="adminTabsContent">
            <div class="tab-pane fade show active" id="pane-produtos" role="tabpanel" aria-labelledby="tab-produtos" tabindex="0">
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

        <?php if ($produtoEditar): ?>
            <div class="card shadow-sm mb-4 border-warning">
                <div class="card-header bg-warning text-dark fw-bold d-flex justify-content-between align-items-center">
                    <span>✏️ A Editar Produto #<?= (int)$produtoEditar['id'] ?></span>
                    <a href="admin.php" class="btn btn-sm btn-outline-dark">Cancelar</a>
                </div>
                <div class="card-body">
                    <form method="POST" class="row g-3">
                        <input type="hidden" name="acao" value="editar_completo">
                        <input type="hidden" name="id" value="<?= (int)$produtoEditar['id'] ?>">

                        <div class="col-md-4">
                            <label class="form-label">Nome do Produto *</label>
                            <input type="text" name="nome" class="form-control" value="<?= htmlspecialchars($produtoEditar['nome']) ?>" required>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Marca *</label>
                            <input type="text" name="marca" class="form-control" value="<?= htmlspecialchars($produtoEditar['marca']) ?>" required>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Categoria *</label>
                            <select name="categoria_id" class="form-select" required>
                                <?php foreach ($categorias as $cat): ?>
                                    <option value="<?= (int)$cat['id'] ?>" <?= (int)$cat['id'] === (int)$produtoEditar['categoria_id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($cat['nome']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-2">
                            <label class="form-label">Preço (€) *</label>
                            <input type="number" step="0.01" min="0" name="preco" class="form-control" value="<?= htmlspecialchars((string)$produtoEditar['preco']) ?>" required>
                        </div>

                        <div class="col-md-2">
                            <label class="form-label">Stock *</label>
                            <input type="number" min="0" name="stock" class="form-control" value="<?= (int)$produtoEditar['stock'] ?>" required>
                        </div>

                        <div class="col-md-5">
                            <label class="form-label">URL da Imagem</label>
                            <input type="url" name="imagem" class="form-control" value="<?= htmlspecialchars((string)$produtoEditar['imagem']) ?>">
                        </div>

                        <div class="col-md-5">
                            <label class="form-label">Descrição / Especificações</label>
                            <input type="text" name="descricao" class="form-control" value="<?= htmlspecialchars((string)$produtoEditar['descricao']) ?>">
                        </div>

                        <div class="col-12">
                            <button type="submit" class="btn btn-warning fw-bold">Guardar Alterações do Produto</button>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>

        <!-- Tabela de Gestão de Stock e Produtos -->
        <div class="card shadow-sm mb-4">
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
                                    <a href="admin.php?editar_id=<?= (int)$prod['id'] ?>" class="btn btn-warning btn-sm">Editar</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

            </div>

            <div class="tab-pane fade" id="pane-categorias" role="tabpanel" aria-labelledby="tab-categorias" tabindex="0">
                <!-- Gestão de Categorias -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-secondary text-white fw-bold">
                        Gerir Categorias
                    </div>
                    <div class="card-body">
                        <form method="POST" class="row g-2 align-items-end mb-3">
                            <input type="hidden" name="acao" value="criar_categoria">
                            <div class="col-md-8">
                                <label for="nome_categoria" class="form-label">Nova categoria</label>
                                <input type="text" id="nome_categoria" name="nome_categoria" class="form-control" maxlength="100" required>
                            </div>
                            <div class="col-md-4">
                                <button type="submit" class="btn btn-success">Guardar Categoria</button>
                            </div>
                        </form>
                        <?php if (empty($categorias)): ?>
                            <p class="text-muted mb-0">Ainda não existem categorias.</p>
                        <?php else: ?>
                            <ul class="list-inline mb-0">
                                <?php foreach ($categorias as $cat): ?>
                                    <li class="list-inline-item"><span class="badge bg-light text-dark border"><?= htmlspecialchars($cat['nome']) ?></span></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="pane-encomendas" role="tabpanel" aria-labelledby="tab-encomendas" tabindex="0">
        <!-- Encomendas Realizadas -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-dark text-white fw-bold">
                📦 Encomendas Realizadas (<?= count($encomendas) ?>)
            </div>
            <div class="card-body">
                <?php if (empty($encomendas)): ?>
                    <p class="text-muted mb-0">Ainda não existem encomendas registadas.</p>
                <?php else: ?>
                    <div class="accordion" id="accordionEncomendas">
                        <?php foreach ($encomendas as $idx => $enc): ?>
                            <?php $linhas = $itensPorEncomenda[(int)$enc['id']] ?? []; ?>
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button <?= $idx > 0 ? 'collapsed' : '' ?>" type="button"
                                            data-bs-toggle="collapse" data-bs-target="#enc<?= (int)$enc['id'] ?>">
                                        <span class="fw-bold me-2"><?= htmlspecialchars($enc['referencia']) ?></span>
                                        <span class="text-muted small me-3">
                                            <?= htmlspecialchars(date('d/m/Y H:i', strtotime($enc['criado_em']))) ?>
                                        </span>
                                        <span class="badge bg-secondary ms-auto me-2">
                                            <?= htmlspecialchars($enc['estado']) ?>
                                        </span>
                                        <span class="badge bg-success ms-auto me-2">
                                            <?= number_format((float)$enc['total'], 2, ',', ' ') ?> €
                                        </span>
                                    </button>
                                </h2>
                                <div id="enc<?= (int)$enc['id'] ?>" class="accordion-collapse collapse <?= $idx === 0 ? 'show' : '' ?>"
                                     data-bs-parent="#accordionEncomendas">
                                    <div class="accordion-body">
                                        <form method="POST" class="row g-2 align-items-end mb-3">
                                            <input type="hidden" name="acao" value="estado_encomenda">
                                            <input type="hidden" name="encomenda_id" value="<?= (int)$enc['id'] ?>">
                                            <div class="col-sm-6 col-md-4">
                                                <label for="estado<?= (int)$enc['id'] ?>" class="form-label">Estado da encomenda</label>
                                                <select id="estado<?= (int)$enc['id'] ?>" name="estado" class="form-select">
                                                    <?php foreach ($estadosValidos as $estado): ?>
                                                        <option value="<?= htmlspecialchars($estado) ?>" <?= $enc['estado'] === $estado ? 'selected' : '' ?>>
                                                            <?= htmlspecialchars($estado) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="col-sm-6 col-md-3">
                                                <button type="submit" class="btn btn-primary">Atualizar Estado</button>
                                            </div>
                                        </form>
                                        <div class="table-responsive">
                                            <table class="table table-sm align-middle mb-0">
                                                <thead>
                                                    <tr>
                                                        <th>Produto</th>
                                                        <th>Marca</th>
                                                        <th class="text-center">Qtd.</th>
                                                        <th class="text-end">Preço Unit.</th>
                                                        <th class="text-end">Subtotal</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($linhas as $l): ?>
                                                        <tr>
                                                            <td><?= htmlspecialchars($l['nome_produto']) ?></td>
                                                            <td><?= htmlspecialchars($l['marca']) ?></td>
                                                            <td class="text-center"><?= (int)$l['quantidade'] ?></td>
                                                            <td class="text-end"><?= number_format((float)$l['preco_unitario'], 2, ',', ' ') ?> €</td>
                                                            <td class="text-end fw-semibold"><?= number_format((float)$l['subtotal'], 2, ',', ' ') ?> €</td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
            </div>

            <div class="tab-pane fade" id="pane-mensagens" role="tabpanel" aria-labelledby="tab-mensagens" tabindex="0">
        <!-- Mensagens Recebidas -->
        <div class="card shadow-sm">
            <div class="card-header bg-dark text-white fw-bold d-flex justify-content-between align-items-center">
                <span>✉️ Mensagens Recebidas (<?= count($mensagensRecebidas) ?>)</span>
                <?php if ($mensagensNaoLidas > 0): ?>
                    <span class="badge bg-warning text-dark"><?= (int)$mensagensNaoLidas ?> por ler</span>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <?php if (empty($mensagensRecebidas)): ?>
                    <p class="text-muted mb-0">Ainda não foram recebidas mensagens através do formulário de contacto.</p>
                <?php else: ?>
                    <div class="d-flex flex-column gap-3">
                        <?php foreach ($mensagensRecebidas as $msg): ?>
                            <?php $estaLida = (int)$msg['lida'] === 1; ?>
                            <div class="border rounded p-3 <?= $estaLida ? 'bg-light' : 'bg-white border-primary' ?>">
                                <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-2">
                                    <div>
                                        <span class="badge <?= $estaLida ? 'bg-secondary' : 'bg-primary' ?> me-2">
                                            <?= $estaLida ? 'Lida' : 'Nova' ?>
                                        </span>
                                        <strong><?= htmlspecialchars($msg['assunto']) ?></strong>
                                        <div class="small text-muted mt-1">
                                            De <strong><?= htmlspecialchars($msg['nome']) ?></strong>
                                            (<?= htmlspecialchars($msg['email']) ?>) •
                                            <?= htmlspecialchars(date('d/m/Y H:i', strtotime($msg['criado_em']))) ?>
                                        </div>
                                    </div>
                                    <div class="d-flex gap-1">
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="acao" value="alternar_lida">
                                            <input type="hidden" name="mensagem_id" value="<?= (int)$msg['id'] ?>">
                                            <button type="submit" class="btn btn-outline-primary btn-sm">
                                                <?= $estaLida ? 'Marcar por ler' : 'Marcar como lida' ?>
                                            </button>
                                        </form>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Apagar esta mensagem?');">
                                            <input type="hidden" name="acao" value="apagar_mensagem">
                                            <input type="hidden" name="mensagem_id" value="<?= (int)$msg['id'] ?>">
                                            <button type="submit" class="btn btn-outline-danger btn-sm">Apagar</button>
                                        </form>
                                    </div>
                                </div>
                                <p class="mb-0"><?= nl2br(htmlspecialchars($msg['mensagem'])) ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>