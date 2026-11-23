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
?>