-- Tabla 'piezas_imagenes': Imágenes adicionales para la galería de cada producto.
CREATE TABLE IF NOT EXISTS piezas_imagenes (
    -- ID único de la imagen extra.
    id INT AUTO_INCREMENT PRIMARY KEY,
    
    -- Producto al que pertenece la imagen.
    pieza_id INT NOT NULL,
    
    -- Nombre del archivo de imagen.
    imagen VARCHAR(255) NOT NULL,
    
    -- Si se borra el producto, se eliminan sus imágenes extra.
    FOREIGN KEY (pieza_id) REFERENCES piezas(id) ON DELETE CASCADE
);