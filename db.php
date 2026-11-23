<?php
$host = 'localhost';
$dbname = 'loja_informatica';
$user = 'admin_loja';
$pass = 'pap2026';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    // Configura o PDO para mostrar erros caso algo falhe na query
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erro na ligação à base de dados: " . $e->getMessage());
}

function garantirTabelasEncomendas(PDO $pdo): void
{
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS encomendas (
            id INT AUTO_INCREMENT PRIMARY KEY,
            referencia VARCHAR(40) NOT NULL UNIQUE,
            cliente_nome VARCHAR(120) NULL,
            cliente_email VARCHAR(160) NULL,
            cliente_morada VARCHAR(255) NULL,
            total DECIMAL(10,2) NOT NULL,
            estado VARCHAR(30) NOT NULL DEFAULT 'Pendente',
            criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        )
    ");

    foreach ([
        'cliente_nome' => 'VARCHAR(120) NULL',
        'cliente_email' => 'VARCHAR(160) NULL',
        'cliente_morada' => 'VARCHAR(255) NULL',
    ] as $nomeColuna => $definicaoColuna) {
        $stmtColuna = $pdo->prepare("SHOW COLUMNS FROM encomendas LIKE ?");
        $stmtColuna->execute([$nomeColuna]);
        if (!$stmtColuna->fetch()) {
            $pdo->exec("ALTER TABLE encomendas ADD COLUMN {$nomeColuna} {$definicaoColuna}");
        }
    }

    $colunaEstado = $pdo->query("SHOW COLUMNS FROM encomendas LIKE 'estado'")->fetch();
    if (!$colunaEstado) {
        $pdo->exec("ALTER TABLE encomendas ADD COLUMN estado VARCHAR(30) NOT NULL DEFAULT 'Pendente'");
    }

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS itens_encomenda (
            id INT AUTO_INCREMENT PRIMARY KEY,
            encomenda_id INT NOT NULL,
            produto_id INT NULL,
            nome_produto VARCHAR(150) NOT NULL,
            marca VARCHAR(100) NOT NULL,
            preco_unitario DECIMAL(10,2) NOT NULL,
            quantidade INT NOT NULL,
            subtotal DECIMAL(10,2) NOT NULL,
            CONSTRAINT fk_item_encomenda FOREIGN KEY (encomenda_id) REFERENCES encomendas(id) ON DELETE CASCADE,
            CONSTRAINT fk_item_produto FOREIGN KEY (produto_id) REFERENCES produtos(id) ON DELETE SET NULL
        )
    ");
}

function garantirTabelaMensagens(PDO $pdo): void
{
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS mensagens (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nome VARCHAR(120) NOT NULL,
            email VARCHAR(160) NOT NULL,
            assunto VARCHAR(180) NOT NULL,
            mensagem TEXT NOT NULL,
            lida TINYINT(1) NOT NULL DEFAULT 0,
            criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        )
    ");
}

garantirTabelasEncomendas($pdo);
?>