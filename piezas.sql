-- Tabla 'piezas': Inventario principal de productos a la venta.
CREATE TABLE piezas (
    -- ID único del producto.
    id INT AUTO_INCREMENT PRIMARY KEY,
    
    -- Nombre o título de la pieza.
    nombre VARCHAR(100) NOT NULL,
    
    -- Descripción detallada del producto.
    descripcion TEXT,
    
    -- Precio de venta al público.
    precio DECIMAL(10,2) NOT NULL,
    
    -- Cantidad disponible en stock.
    cantidad INT NOT NULL,
    
    -- Marca asociada al producto.
    marca_id INT,
    
    -- Imagen principal del producto.
    imagen VARCHAR(255),
    
    -- Fecha de alta en el sistema.
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Si se borra la marca, el producto permanece pero sin marca asignada (NULL).
    FOREIGN KEY (marca_id) REFERENCES marcas(id) ON DELETE SET NULL
);