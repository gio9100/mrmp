<?php
require_once "conexion.php";

function executeSQLFile($conexion, $filename) {
    if (!file_exists($filename)) {
        echo "❌ Archivo no encontrado: $filename<br>";
        return;
    }
    
    $sql = file_get_contents($filename);
    if ($conexion->multi_query($sql)) {
        do {
            if ($result = $conexion->store_result()) {
                $result->free();
            }
        } while ($conexion->more_results() && $conexion->next_result());
        echo "✅ Ejecutado correctamente: $filename<br>";
    } else {
        echo "❌ Error ejecutando $filename: " . $conexion->error . "<br>";
    }
}

echo "<h2>Iniciando actualización de base de datos...</h2>";

executeSQLFile($conexion, "wishlist.sql");
executeSQLFile($conexion, "pedidos.sql");
executeSQLFile($conexion, "detalle_pedidos.sql");

echo "<h2>Actualización completada.</h2>";
?>
