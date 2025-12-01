<?php
// Gestión de Estados de Pedidos
// Este archivo maneja los cambios de estado de pedidos desde el admin panel

session_start();
require_once "conexion.php";
require_once "enviar_correo.php";

if(!isset($_SESSION['admin_id'])){
    header("Location: admin_panel.php");
    exit;
}

if(isset($_POST['actualizar_estado_pedido'])){
    $pedido_id = intval($_POST['pedido_id']);
    $nuevo_estado = trim($_POST['estado']);
    $paqueteria = isset($_POST['paqueteria']) ? trim($_POST['paqueteria']) : null;
    
    // Obtener datos del pedido y usuario
    $sql_pedido = "SELECT p.*, u.correo, u.nombre FROM pedidos p 
                   JOIN usuarios u ON p.usuario_id = u.id 
                   WHERE p.id = ?";
    $stmt = $conexion->prepare($sql_pedido);
    $stmt->bind_param("i", $pedido_id);
    $stmt->execute();
    $pedido = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if(!$pedido){
        $_SESSION['mensaje'] = "❌ Pedido no encontrado.";
        header("Location: gestionar_pedidos.php");
        exit;
    }
    
    // Actualizar estado
    if($paqueteria && $nuevo_estado === 'enviado'){
        $stmt = $conexion->prepare("UPDATE pedidos SET estado = ?, paqueteria = ? WHERE id = ?");
        $stmt->bind_param("ssi", $nuevo_estado, $paqueteria, $pedido_id);
    } else {
        $stmt = $conexion->prepare("UPDATE pedidos SET estado = ? WHERE id = ?");
        $stmt->bind_param("si", $nuevo_estado, $pedido_id);
    }
    $stmt->execute();
    $stmt->close();
    
    // Enviar notificación por email según el estado
    if($nuevo_estado === 'confirmado'){
        $asunto = "Pedido #$pedido_id Confirmado - MRMP";
        $cuerpo = "Hola {$pedido['nombre']},\n\n";
        $cuerpo .= "Tu pedido #{$pedido_id} ha sido confirmado y está siendo preparado.\n\n";
        $cuerpo .= "Total: $".number_format($pedido['total'], 2)."\n";
        $cuerpo .= "Dirección de envío: {$pedido['direccion']}, {$pedido['ciudad']}\n\n";
        $cuerpo .= "Te notificaremos cuando sea enviado.\n\n";
        $cuerpo .= "Gracias por tu compra,\nMexican Racing Motor Parts";
        enviarCorreo($pedido['correo'], $asunto, $cuerpo);
    } 
    elseif($nuevo_estado === 'enviado'){
        $asunto = "Pedido #$pedido_id Enviado - MRMP";
        $cuerpo = "Hola {$pedido['nombre']},\n\n";
        $cuerpo .= "¡Buenas noticias! Tu pedido #{$pedido_id} ha sido enviado.\n\n";
        $cuerpo .= "Paquetería: {$paqueteria}\n";
        $cuerpo .= "Total: $".number_format($pedido['total'], 2)."\n";
        $cuerpo .= "Dirección de envío: {$pedido['direccion']}, {$pedido['ciudad']}\n\n";
        $cuerpo .= "Recibirás tu pedido pronto.\n\n";
        $cuerpo .= "Gracias por tu compra,\nMexican Racing Motor Parts";
        enviarCorreo($pedido['correo'], $asunto, $cuerpo);
    }
    elseif($nuevo_estado === 'cancelado'){
        $asunto = "Pedido #$pedido_id Cancelado - MRMP";
        $cuerpo = "Hola {$pedido['nombre']},\n\n";
        $cuerpo .= "Lamentamos informarte que tu pedido #{$pedido_id} ha sido cancelado.\n\n";
        $cuerpo .= "Si tienes alguna pregunta, por favor contáctanos.\n\n";
        $cuerpo .= "Mexican Racing Motor Parts";
        enviarCorreo($pedido['correo'], $asunto, $cuerpo);
    }
    
    $_SESSION['mensaje'] = "✅ Estado del pedido actualizado correctamente.";
    header("Location: gestionar_pedidos.php");
    exit;
}

// Si no hay POST, redirigir
header("Location: gestionar_pedidos.php");
exit;
?>
