<?php
// Inicia sesión
session_start();
// Carga conexión BD
require_once "conexion.php";

// Verificar si el usuario está logueado
if (!isset($_SESSION['usuario_id'])) {
    // Retorna error JSON si no está logueado
    echo json_encode(['status' => 'error', 'message' => 'Debes iniciar sesión para usar la lista de deseos.']);
    exit;
}

$usuario_id = $_SESSION['usuario_id'];

// Acción: Agregar o Eliminar (verifica POST)
if (isset($_POST['action']) && isset($_POST['pieza_id'])) {
    // Sanitiza ID a entero
    $pieza_id = intval($_POST['pieza_id']);
    // Obtiene acción
    $action = $_POST['action'];

    if ($action === 'add') {
        // Verificar si ya existe en wishlist
        $check = $conexion->prepare("SELECT id FROM wishlist WHERE usuario_id = ? AND pieza_id = ?");
        $check->bind_param("ii", $usuario_id, $pieza_id);
        $check->execute();
        $result = $check->get_result();
        
        // Si no existe, agregar
        if ($result->num_rows == 0) {
            $stmt = $conexion->prepare("INSERT INTO wishlist (usuario_id, pieza_id) VALUES (?, ?)");
            $stmt->bind_param("ii", $usuario_id, $pieza_id);
            if ($stmt->execute()) {
                // Éxito agregar
                echo json_encode(['status' => 'success', 'message' => 'Pieza agregada a tu lista de deseos.']);
            } else {
                // Error agregar
                echo json_encode(['status' => 'error', 'message' => 'Error al agregar a la lista de deseos.']);
            }
            $stmt->close();
        } else {
            // Ya existía
            echo json_encode(['status' => 'info', 'message' => 'Esta pieza ya está en tu lista de deseos.']);
        }
        $check->close();
    } elseif ($action === 'remove') {
        // Eliminar de wishlist
        $stmt = $conexion->prepare("DELETE FROM wishlist WHERE usuario_id = ? AND pieza_id = ?");
        $stmt->bind_param("ii", $usuario_id, $pieza_id);
        if ($stmt->execute()) {
            // Éxito eliminar
            echo json_encode(['status' => 'success', 'message' => 'Pieza eliminada de tu lista de deseos.']);
        } else {
            // Error eliminar
            echo json_encode(['status' => 'error', 'message' => 'Error al eliminar de la lista de deseos.']);
        }
        $stmt->close();
    }
} else {
    // Solicitud inválida (faltan parámetros)
    echo json_encode(['status' => 'error', 'message' => 'Solicitud inválida.']);
}
?>
