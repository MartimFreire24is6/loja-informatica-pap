USE loja_informatica;

-- 1. Tabela de Categorias
CREATE TABLE IF NOT EXISTS categorias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE
);

-- 2. Tabela de Produtos
CREATE TABLE IF NOT EXISTS produtos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    categoria_id INT NOT NULL,
    nome VARCHAR(150) NOT NULL,
    marca VARCHAR(80) NOT NULL,
    descricao TEXT,
    preco DECIMAL(10, 2) NOT NULL,
    stock INT NOT NULL DEFAULT 0,
    imagem VARCHAR(255) DEFAULT 'sem-imagem.jpg',
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE CASCADE
);

-- 3. Tabela de Utilizadores (para login de admin e clientes)
CREATE TABLE IF NOT EXISTS utilizadores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'cliente') DEFAULT 'cliente',
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Inserir categorias iniciais
INSERT INTO categorias (nome, slug) VALUES
('Computadores & Portáteis', 'computadores'),
('Componentes', 'componentes'),
('Periféricos', 'perifericos'),
('Monitores', 'monitores');

-- Inserir produtos de teste para a loja
INSERT INTO produtos (categoria_id, nome, marca, descricao, preco, stock, imagem) VALUES
(1, 'Portátil ASUS ROG Strix G16', 'ASUS', 'Intel Core i7-13650HX, 16GB RAM DDR5, SSD 1TB, NVIDIA RTX 4060 8GB, Ecrã 16" 165Hz.', 1499.90, 5, 'https://images.unsplash.com/photo-1603302576837-37561b2e2302?w=600'),
(2, 'Placa Gráfica MSI GeForce RTX 4070 Super', 'MSI', '12GB GDDR6X, Ventus 2X OC, Ray Tracing, DLSS 3.', 649.99, 8, 'https://images.unsplash.com/photo-1591488320449-011701bb6704?w=600'),
(3, 'Rato Gaming Logitech G Pro X Superlight 2', 'Logitech', 'Sensor HERO 2 32K DPI, Wireless Lightspeed, 60g, Preto.', 159.90, 15, 'https://images.unsplash.com/photo-1527814050087-3793815479db?w=600'),
(3, 'Teclado Mecânico Keychron K2 Pro', 'Keychron', 'Wireless Bluetooth/Cabo, Switches Red, Retroiluminação RGB, Layout PT.', 119.00, 10, 'https://images.unsplash.com/photo-1595225476474-87563907a212?w=600'),
(4, 'Monitor LG UltraGear 27" QHD 180Hz', 'LG', 'Painel IPS 2560x1440, 1ms GtG, HDR10, FreeSync / G-Sync Compatible.', 299.90, 6, 'https://images.unsplash.com/photo-1527443224154-c4a3942d3acf?w=600');