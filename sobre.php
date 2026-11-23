<?php
session_start();
require_once 'db.php';

$nome = trim($_POST['nome'] ?? '');
$email = trim($_POST['email'] ?? '');
$assunto = trim($_POST['assunto'] ?? '');
$mensagem = trim($_POST['mensagem'] ?? '');

$erros = [];
$enviado = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($nome === '') {
        $erros[] = 'Indica o teu nome.';
    }
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erros[] = 'Indica um endereço de email válido.';
    }
    if ($assunto === '') {
        $erros[] = 'Indica o assunto da mensagem.';
    }
    if ($mensagem === '') {
        $erros[] = 'Escreve a tua mensagem antes de enviar.';
    }

    if (empty($erros)) {
        garantirTabelaMensagens($pdo);
        $stmtMsg = $pdo->prepare("
            INSERT INTO mensagens (nome, email, assunto, mensagem)
            VALUES (?, ?, ?, ?)
        ");
        $stmtMsg->execute([$nome, $email, $assunto, $mensagem]);

        $enviado = true;
        $nome = '';
        $email = '';
        $assunto = '';
        $mensagem = '';
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sobre Nós e Contactos — TechStore PAP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/estilo.css" rel="stylesheet">
</head>
<body class="bg-light d-flex flex-column min-vh-100">

    <?php require 'includes/navbar.php'; ?>

    <main class="container mb-5 flex-grow-1">
        <div class="bg-white p-4 p-md-5 rounded shadow-sm border-0 mb-4">
            <span class="badge bg-primary mb-2">Projeto de Aptidão Profissional</span>
            <h1 class="h2 fw-bold mb-3">Sobre a TechStore PAP</h1>
            <p class="text-muted mb-0">
                A <strong>TechStore PAP</strong> é uma plataforma de comércio eletrónico dedicada a computadores,
                componentes de hardware, monitores e periféricos. O projeto foi desenvolvido com PHP e MariaDB,
                integrando gestão de catálogo, controlo de stock transacional, carrinho de compras e área de administração.
            </p>
        </div>

        <div class="row g-4 mb-5">
            <div class="col-md-4">
                <div class="card h-100 shadow-sm border-0">
                    <div class="card-body">
                        <h2 class="h5 fw-bold">🚚 Envios Rápidos</h2>
                        <p class="text-muted small mb-0">
                            Expedição em 24–48 horas úteis para encomendas de artigos em stock, com confirmação imediata.
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100 shadow-sm border-0">
                    <div class="card-body">
                        <h2 class="h5 fw-bold">🛡️ Garantia e Qualidade</h2>
                        <p class="text-muted small mb-0">
                            Equipamentos selecionados com verificação de disponibilidade em tempo real antes da finalização.
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100 shadow-sm border-0">
                    <div class="card-body">
                        <h2 class="h5 fw-bold">💬 Apoio Técnico</h2>
                        <p class="text-muted small mb-0">
                            Esclarecimento de dúvidas sobre compatibilidade de componentes, periféricos e encomendas.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-7">
                <div class="card shadow-sm border-0">
                    <div class="card-body p-4">
                        <h2 class="h4 fw-bold mb-3">Enviar Mensagem</h2>

                        <?php if ($enviado): ?>
                            <div class="alert alert-success">
                                Mensagem enviada com sucesso! Entraremos em contacto em breve.
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($erros)): ?>
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    <?php foreach ($erros as $erro): ?>
                                        <li><?= htmlspecialchars($erro) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <form method="POST" class="row g-3">
                            <div class="col-md-6">
                                <label for="nome" class="form-label">Nome</label>
                                <input type="text" id="nome" name="nome" value="<?= htmlspecialchars($nome) ?>" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" id="email" name="email" value="<?= htmlspecialchars($email) ?>" class="form-control" required>
                            </div>
                            <div class="col-12">
                                <label for="assunto" class="form-label">Assunto</label>
                                <input type="text" id="assunto" name="assunto" value="<?= htmlspecialchars($assunto) ?>" class="form-control" required>
                            </div>
                            <div class="col-12">
                                <label for="mensagem" class="form-label">Mensagem</label>
                                <textarea id="mensagem" name="mensagem" rows="4" class="form-control" required><?= htmlspecialchars($mensagem) ?></textarea>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">Enviar Mensagem</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body p-4">
                        <h2 class="h4 fw-bold mb-3">Contactos</h2>
                        <p class="text-muted small mb-4">
                            Canais de atendimento da loja para questões comerciais ou suporte técnico.
                        </p>
                        <ul class="list-unstyled mb-0">
                            <li class="mb-3">
                                <strong>Email:</strong><br>
                                <span class="text-muted">suporte@techstore-pap.pt</span>
                            </li>
                            <li class="mb-3">
                                <strong>Horário de Atendimento:</strong><br>
                                <span class="text-muted">Segunda a Sexta, 09:00 – 18:00</span>
                            </li>
                            <li>
                                <strong>Localização:</strong><br>
                                <span class="text-muted">Portugal</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php require 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>