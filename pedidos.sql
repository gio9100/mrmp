CREATE TABLE IF NOT EXISTS pedidos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
    direccion VARCHAR(255) DEFAULT NULL,
    ciudad VARCHAR(100) DEFAULT NULL,
    codigo_postal VARCHAR(10) DEFAULT NULL,
    telefono VARCHAR(20) DEFAULT NULL,
    metodo_pago VARCHAR(50) DEFAULT NULL,
    paqueteria VARCHAR(50) DEFAULT NULL,
    fecha_entrega_estimada DATE DEFAULT NULL,
    total DECIMAL(10,2) DEFAULT 0.00,
    estado VARCHAR(50) DEFAULT 'pendiente',
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
);
