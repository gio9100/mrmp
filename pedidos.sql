-- Tabla 'pedidos': Información general de las transacciones de compra.
CREATE TABLE IF NOT EXISTS pedidos (
    -- Identificador único del pedido.
    id INT AUTO_INCREMENT PRIMARY KEY,
    
    -- Usuario que realizó la compra.
    usuario_id INT NOT NULL,
    
    -- Fecha y hora de creación del pedido.
    fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    -- Información de envío guardada estáticamente para este pedido.
    direccion VARCHAR(255) DEFAULT NULL,
    ciudad VARCHAR(100) DEFAULT NULL,
    codigo_postal VARCHAR(10) DEFAULT NULL,
    telefono VARCHAR(20) DEFAULT NULL,
    
    -- Método de pago y detalles de envío.
    metodo_pago VARCHAR(50) DEFAULT NULL,
    paqueteria VARCHAR(50) DEFAULT NULL,
    fecha_entrega_estimada DATE DEFAULT NULL,
    
    -- Monto total de la orden.
    total DECIMAL(10,2) DEFAULT 0.00,
    
    -- Estado actual del proceso (pendiente, enviado, etc.).
    estado VARCHAR(50) DEFAULT 'pendiente',
    
    -- Si se elimina el usuario, se eliminan sus pedidos.
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
);
