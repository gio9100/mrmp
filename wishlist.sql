-- Tabla 'wishlist': Lista de deseos de los usuarios.
CREATE TABLE IF NOT EXISTS wishlist (
    -- ID único del registro en wishlist.
    id INT AUTO_INCREMENT PRIMARY KEY,
    
    -- Usuario propietario de la lista.
    usuario_id INT NOT NULL,
    
    -- Producto agregado a la lista.
    pieza_id INT NOT NULL,
    
    -- Fecha en que se agregó.
    fecha_agregado DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    -- Eliminación en cascada: Si se borra usuario o pieza, se limpia de la lista.
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (pieza_id) REFERENCES piezas(id) ON DELETE CASCADE,
    
    -- Evita duplicar el mismo producto para el mismo usuario.
    UNIQUE KEY unique_wishlist (usuario_id, pieza_id)
);
