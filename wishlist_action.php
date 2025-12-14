<?php
// session_start(): Inicia una "sesión" en el servidor. Esto permite guardar variables (como el ID del usuario)
// que persisten entre diferentes páginas del sitio web mientras el navegador esté abierto.
session_start();

// require_once: Incluye el contenido del archivo 'conexion.php'.
// Si el archivo no se encuentra, detiene la ejecución del script (fatal error), evitando problemas mayores.
require_once "conexion.php";

// isset(): Verifica si una variable está definida y no es NULL.
// $_SESSION['usuario_id']: Variable de sesión que guardamos cuando el usuario se loguea.
// El operador '!' niega la condición: "Si NO está seteada la sesión del usuario..."
if (!isset($_SESSION['usuario_id'])) {
    // json_encode(): Convierte un array de PHP a formato JSON.
    // Esto es necesario para que el JavaScript (que llamó a este archivo vía AJAX/Fetch) pueda entender la respuesta.
    echo json_encode(['status' => 'error', 'message' => 'Debes iniciar sesión para usar la lista de deseos.']);
    
    // exit: Detiene la ejecución del código inmediatamente. Importante para seguridad.
    exit;
}

// Asignamos el ID del usuario de la sesión a una variable local para facilitar su uso.
$usuario_id = $_SESSION['usuario_id'];

// Verificamos si se recibieron los datos necesarios por el método POST.
// $_POST['action']: La acción a realizar ('add' para agregar, 'remove' para eliminar).
// $_POST['pieza_id']: El ID del producto que se quiere manipular.
if (isset($_POST['action']) && isset($_POST['pieza_id'])) {

    // intval(): Convierte el valor a un número entero.
    // Esto es una medida de SEGURIDAD para evitar Inyección SQL o datos incorrectos.
    $pieza_id = intval($_POST['pieza_id']);
    
    // Guardamos la acción en una variable.
    $action = $_POST['action'];

    // Estructura de control: Si la acción es 'add' (agregar).
    if ($action === 'add') {
        // --- PARTE 1: VERIFICAR SI YA EXISTE ---
        
        // prepare(): Prepara una sentencia SQL para su ejecución.
        // Los signos de interrogación '?' son marcadores de posición para los valores.
        // Esto PREVIENE la Inyección SQL porque separa el código SQL de los datos.
        $check = $conexion->prepare("SELECT id FROM wishlist WHERE usuario_id = ? AND pieza_id = ?");
        
        // bind_param(): Vincula las variables a los marcadores '?'.
        // "ii" significa que ambos parámetros son Enteros (integer, integer).
        $check->bind_param("ii", $usuario_id, $pieza_id);
        
        // execute(): Ejecuta la consulta preparada en la base de datos.
        $check->execute();
        
        // get_result(): Obtiene el conjunto de resultados de la consulta ejecutada.
        $result = $check->get_result();
        
        // num_rows: Propiedad que contiene el número de filas devueltas.
        // Si es 0, significa que la pieza NO está en la wishlist, así que procedemos a agregarla.
        if ($result->num_rows == 0) {
            // Preparamos la sentencia INSERT para guardar la relación usuario-pieza.
            $stmt = $conexion->prepare("INSERT INTO wishlist (usuario_id, pieza_id) VALUES (?, ?)");
            $stmt->bind_param("ii", $usuario_id, $pieza_id);
            
            // Si execute() devuelve true, la inserción fue exitosa.
            if ($stmt->execute()) {
                // Respondemos con JSON indicando éxito.
                echo json_encode(['status' => 'success', 'message' => 'Pieza agregada a tu lista de deseos.']);
            } else {
                // Si execute() falla, respondemos con error.
                echo json_encode(['status' => 'error', 'message' => 'Error al agregar a la lista de deseos.']);
            }
            // close(): Cierra la sentencia preparada para liberar recursos.
            $stmt->close();
        } else {
            // Si num_rows > 0, la pieza ya estaba en la lista.
            echo json_encode(['status' => 'info', 'message' => 'Esta pieza ya está en tu lista de deseos.']);
        }
        // Cerramos la sentencia de verificación.
        $check->close();

    // elseif: Si la acción no fue 'add', verificamos si es 'remove' (eliminar).
    } elseif ($action === 'remove') {
        // --- PARTE 2: ELIMINAR DE LA LISTA ---
        
        // Preparamos la sentencia DELETE.
        $stmt = $conexion->prepare("DELETE FROM wishlist WHERE usuario_id = ? AND pieza_id = ?");
        $stmt->bind_param("ii", $usuario_id, $pieza_id);
        
        if ($stmt->execute()) {
             // Éxito al eliminar.
            echo json_encode(['status' => 'success', 'message' => 'Pieza eliminada de tu lista de deseos.']);
        } else {
             // Error al eliminar.
            echo json_encode(['status' => 'error', 'message' => 'Error al eliminar de la lista de deseos.']);
        }
        $stmt->close();
    }
} else {
    // Si faltan parámetros POST (action o pieza_id), devolvemos error de solicitud inválida.
    echo json_encode(['status' => 'error', 'message' => 'Solicitud inválida.']);
}
// Fin del script PHP.
?>
