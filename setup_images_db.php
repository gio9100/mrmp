<?php
require_once "conexion.php";

$sql = "CREATE TABLE IF NOT EXISTS piezas_imagenes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pieza_id INT NOT NULL,
    imagen VARCHAR(255) NOT NULL,
    FOREIGN KEY (pieza_id) REFERENCES piezas(id) ON DELETE CASCADE
)";

if ($conexion->query($sql) === TRUE) {
    echo "Tabla 'piezas_imagenes' creada o ya existe correctamente.";
} else {
    echo "Error creando tabla: " . $conexion->error;
}
?>
