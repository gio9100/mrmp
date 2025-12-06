-- Tabla 'admins': Almacena las credenciales de los administradores del sistema.
CREATE TABLE admins (
    -- Identificador único del administrador, autoincremental.
    id INT AUTO_INCREMENT PRIMARY KEY,
    
    -- Nombre completo del administrador.
    nombre VARCHAR(100) NOT NULL,
    
    -- Correo electrónico único para el inicio de sesión.
    correo VARCHAR(150) NOT NULL UNIQUE,
    
    -- Contraseña encriptada para seguridad (no texto plano).
    contrasena_hash VARCHAR(255) NOT NULL,
    
    -- Fecha y hora de registro del administrador.
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
