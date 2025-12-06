-- Tabla 'marcas': Catálogo de fabricantes de piezas.
CREATE TABLE marcas (
    -- Identificador único de la marca.
    id INT AUTO_INCREMENT PRIMARY KEY,
    
    -- Nombre de la marca (ej. Brembo), debe ser único.
    nombre VARCHAR(100) UNIQUE NOT NULL,
    
    -- Nombre del archivo de imagen del logo (opcional).
    imagen VARCHAR(255) DEFAULT NULL
);