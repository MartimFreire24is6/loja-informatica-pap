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
            total DECIMAL(10,2) NOT NULL,
            criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        )
    ");

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

garantirTabelasEncomendas($pdo);
?>