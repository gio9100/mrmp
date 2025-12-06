-- Tabla 'usuarios': Registro de clientes del sistema.
CREATE TABLE `usuarios` (
  -- ID único del usuario.
  `id` int(11) NOT NULL AUTO_INCREMENT,
  
  -- Nombre completo.
  `nombre` varchar(100) NOT NULL,
  
  -- Correo electrónico único para login.
  `correo` varchar(100) NOT NULL,
  
  -- Contraseña segura (Hash).
  `contrasena_hash` varchar(255) NOT NULL,
  
  -- Teléfono de contacto (opcional).
  `telefono` varchar(20) DEFAULT NULL,
  
  -- Nombre de archivo de la foto de perfil.
  `imagen_perfil` varchar(255) DEFAULT 'default.png',
  
  -- Estado de verificación del usuario.
  `verificado` tinyint(1) DEFAULT 0,
  
  -- Fecha de registro.
  `fecha_creacion` datetime DEFAULT current_timestamp(),
  
  -- Token para recuperación de contraseña.
  `reset_token` varchar(100) DEFAULT NULL,
  `token_expirada` datetime DEFAULT NULL,
  
  PRIMARY KEY (`id`),
  UNIQUE KEY `correo` (`correo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
