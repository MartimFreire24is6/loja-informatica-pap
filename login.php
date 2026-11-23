<?php
session_start();
require_once 'db.php';

// Se já tem sessão iniciada como admin, vai direto para admin.php
if (!empty($_SESSION['admin_logado'])) {
    header('Location: admin.php');
    exit;
}

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['password'] ?? '';

    if ($email === '' || $senha === '') {
        $erro = 'Preenche o email e a palavra-passe.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM utilizadores WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        // Verifica hash da password (ou compatibilidade caso exista texto simples num registo inicial)
        $senhaValida = false;
        if ($user) {
            $hashGuardado = (string)($user['password_hash'] ?? $user['password'] ?? '');
            if ($hashGuardado !== '') {
                $senhaValida = password_verify($senha, $hashGuardado) || hash_equals($hashGuardado, $senha);
            }
        }

        if ($user && $senhaValida) {
            session_regenerate_id(true);
            $_SESSION['admin_logado'] = true;
            $_SESSION['admin_nome']   = $user['nome'] ?? $user['email'];
            $_SESSION['admin_email']  = $user['email'];
            header('Location: admin.php');
            exit;
        } else {
            $erro = 'Credenciais inválidas.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin - TechStore PAP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-5">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php">🖥️ TechStore PAP</a>
            <a href="index.php" class="btn btn-outline-light btn-sm">← Voltar à Loja</a>
        </div>
    </nav>

    <div class="container" style="max-width: 440px;">
        <div class="card shadow-sm">
            <div class="card-header bg-dark text-white fw-bold">
                Área Reservada — Administração
            </div>
            <div class="card-body p-4">
                <?php if ($erro !== ''): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div>
                <?php endif; ?>

                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" required autofocus>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Palavra-passe</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Entrar</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>