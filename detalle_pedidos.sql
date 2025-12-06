-- Tabla 'detalle_pedidos': Relaciona los productos específicos comprados en cada pedido.
CREATE TABLE IF NOT EXISTS detalle_pedidos (
    -- ID único para este detalle de pedido.
    id INT AUTO_INCREMENT PRIMARY KEY,
    
    -- Relación con el pedido general.
    pedido_id INT NOT NULL,
    
    -- Relación con la pieza comprada.
    pieza_id INT NOT NULL,
    
    -- Cantidad de unidades de esta pieza.
    cantidad INT NOT NULL,
    
    -- Precio unitario al momento de la compra (para historial fijo).
    precio_unitario DECIMAL(10,2) NOT NULL,
    
    -- Si se borra el pedido, se eliminan sus detalles.
    FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE CASCADE,
    
    -- Relación con el inventario de piezas.
    FOREIGN KEY (pieza_id) REFERENCES piezas(id)
);
