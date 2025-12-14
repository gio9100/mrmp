<?php
// session_start(): Inicia el mecanismo de sesiones para acceder a $_SESSION.
session_start();

// require_once: Carga la configuración de la base de datos y la función de correos.
// Es importante cargarlos 'una sola vez' para evitar duplicidad de código o errores de redifusión.
require_once "conexion.php";
require_once "enviar_correo.php";

// VERIFICACIÓN DE SEGURIDAD (AUTORIZACIÓN)
// Verificamos si la variable de sesión 'admin_id' NO está definida.
// Esto asegura que SOLO los administradores logueados puedan ejecutar este script.
if(!isset($_SESSION['admin_id'])){
    // header("Location: ..."): Redirige al login de administradores si no tiene permiso.
    header("Location: admin_panel.php");
    exit; // Detiene el script para proteger el resto del código.
}

// isset($_POST['actualizar_estado_pedido']): Verifica si el usuario envió el formulario específico
// presionando el botón con name "actualizar_estado_pedido".
if(isset($_POST['actualizar_estado_pedido'])){
    // intval(): Fuerza que el ID sea un número entero. (Seguridad contra inyección).
    $pedido_id = intval($_POST['pedido_id']);
    
    // trim(): Limpia espacios accidentales del estado seleccionado.
    $nuevo_estado = trim($_POST['estado']);
    
    // Operador Ternario (Condición ? Verdadero : Falso):
    // Si existe $_POST['paqueteria'], lo limpiamos con trim(), si no, asignamos NULL.
    $paqueteria = isset($_POST['paqueteria']) ? trim($_POST['paqueteria']) : null;
    
    // CONSULTA PARA OBTENER INFORMACIÓN DEL PEDIDO Y USUARIO
    // Usamos JOIN para mezclar datos de 'pedidos' y 'usuarios' en una sola consulta.
    $sql_pedido = "SELECT p.*, u.correo, u.nombre FROM pedidos p 
                   JOIN usuarios u ON p.usuario_id = u.id 
                   WHERE p.id = ?";
    
    // prepare(): Prepara la consulta SQL segurizando los datos.
    $stmt = $conexion->prepare($sql_pedido);
    $stmt->bind_param("i", $pedido_id); // "i" = entero.
    $stmt->execute();
    
    // fetch_assoc(): Obtiene el resultado como un array asociativo.
    $pedido = $stmt->get_result()->fetch_assoc();
    $stmt->close(); // Cerramos esta sentencia para liberar recursos antes de la siguiente.
    
    // Si fetch_assoc retorna false o null, el pedido no existe.
    if(!$pedido){
        $_SESSION['mensaje'] = "❌ Pedido no encontrado.";
        header("Location: gestionar_pedidos.php");
        exit;
    }
    
    // LOGICA DE ACTUALIZACIÓN EN BASE DE DATOS
    // Si hay paquetería definida y el estado es 'enviado', actualizamos ambos campos.
    if($paqueteria && $nuevo_estado === 'enviado'){
        $stmt = $conexion->prepare("UPDATE pedidos SET estado = ?, paqueteria = ? WHERE id = ?");
        $stmt->bind_param("ssi", $nuevo_estado, $paqueteria, $pedido_id);
    } else {
        // Si no, solo actualizamos el estado.
        $stmt = $conexion->prepare("UPDATE pedidos SET estado = ? WHERE id = ?");
        $stmt->bind_param("si", $nuevo_estado, $pedido_id);
    }
    // Ejecutamos la actualización.
    $stmt->execute();
    $stmt->close();
    
    // LOGICA DE NOTIFICACIONES POR CORREO ELECTRÓNICO
    // Dependiendo del nuevo estado, personalizamos el mensaje.
    if($nuevo_estado === 'confirmado'){
        $asunto = "Pedido #$pedido_id Confirmado - Performance Zone MX";
        
        // Construcción del cuerpo del correo (Concatenación con .=).
        $cuerpo = "Hola {$pedido['nombre']},\n\n";
        $cuerpo .= "Tu pedido #{$pedido_id} ha sido confirmado y está siendo preparado.\n\n";
        // number_format(): Formatea el número a 2 decimales (ej. 100.00).
        $cuerpo .= "Total: $".number_format($pedido['total'], 2)."\n";
        $cuerpo .= "Dirección de envío: {$pedido['direccion']}, {$pedido['ciudad']}\n\n";
        $cuerpo .= "Te notificaremos cuando sea enviado.\n\n";
        $cuerpo .= "Gracias por tu compra,\nPerformance Zone MX";
        
        // enviaCorreo(): Función auxiliar definida en 'enviar_correo.php'.
        enviarCorreo($pedido['correo'], $asunto, $cuerpo);
    } 
    elseif($nuevo_estado === 'enviado'){
        $asunto = "Pedido #$pedido_id Enviado - Performance Zone MX";
        $cuerpo = "Hola {$pedido['nombre']},\n\n";
        $cuerpo .= "¡Buenas noticias! Tu pedido #{$pedido_id} ha sido enviado.\n\n";
        $cuerpo .= "Paquetería: {$paqueteria}\n";
        $cuerpo .= "Total: $".number_format($pedido['total'], 2)."\n";
        $cuerpo .= "Dirección de envío: {$pedido['direccion']}, {$pedido['ciudad']}\n\n";
        $cuerpo .= "Recibirás tu pedido pronto.\n\n";
        $cuerpo .= "Gracias por tu compra,\nPerformance Zone MX";
        
        enviarCorreo($pedido['correo'], $asunto, $cuerpo);
    }
    elseif($nuevo_estado === 'cancelado'){
        $asunto = "Pedido #$pedido_id Cancelado - Performance Zone MX";
        $cuerpo = "Hola {$pedido['nombre']},\n\n";
        $cuerpo .= "Lamentamos informarte que tu pedido #{$pedido_id} ha sido cancelado.\n\n";
        $cuerpo .= "Si tienes alguna pregunta, por favor contáctanos.\n\n";
        $cuerpo .= "Performance Zone MX";
        
        enviarCorreo($pedido['correo'], $asunto, $cuerpo);
    }
    
    // Guardamos mensaje de éxito en sesión para mostrarlo en la página de gestión.
    $_SESSION['mensaje'] = "✅ Estado del pedido actualizado correctamente.";
    
    // Redireccionamos a la página de gestión.
    header("Location: gestionar_pedidos.php");
    exit;
}

// Si alguien intenta entrar directamente a este archivo (GET) sin enviar formulario, lo sacamos.
header("Location: gestionar_pedidos.php");
exit;
?>
