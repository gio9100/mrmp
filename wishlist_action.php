<?php
session_start();
require_once "conexion.php";

// Verificar si el usuario está logueado
if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Debes iniciar sesión para usar la lista de deseos.']);
    exit;
}

$usuario_id = $_SESSION['usuario_id'];

// Acción: Agregar o Eliminar
if (isset($_POST['action']) && isset($_POST['pieza_id'])) {
    $pieza_id = intval($_POST['pieza_id']);
    $action = $_POST['action'];

    if ($action === 'add') {
        // Verificar si ya existe
        $check = $conexion->prepare("SELECT id FROM wishlist WHERE usuario_id = ? AND pieza_id = ?");
        $check->bind_param("ii", $usuario_id, $pieza_id);
        $check->execute();
        $result = $check->get_result();
        
        if ($result->num_rows == 0) {
            $stmt = $conexion->prepare("INSERT INTO wishlist (usuario_id, pieza_id) VALUES (?, ?)");
            $stmt->bind_param("ii", $usuario_id, $pieza_id);
            if ($stmt->execute()) {
                echo json_encode(['status' => 'success', 'message' => 'Pieza agregada a tu lista de deseos.']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Error al agregar a la lista de deseos.']);
            }
            $stmt->close();
        } else {
            echo json_encode(['status' => 'info', 'message' => 'Esta pieza ya está en tu lista de deseos.']);
        }
        $check->close();
    } elseif ($action === 'remove') {
        $stmt = $conexion->prepare("DELETE FROM wishlist WHERE usuario_id = ? AND pieza_id = ?");
        $stmt->bind_param("ii", $usuario_id, $pieza_id);
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Pieza eliminada de tu lista de deseos.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error al eliminar de la lista de deseos.']);
        }
        $stmt->close();
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Solicitud inválida.']);
}
?>
