<?php
// session_start(): Inicia una nueva sesión o reanuda la existente.
// Permite que el servidor "recuerde" al usuario (si ya inició sesión) o guarde mensajes temporales.
session_start();

// require_once: Incluye el archivo de conexión a la base de datos.
// Se usa '_once' para asegurar que no se redefina la conexión si ya fue incluida antes.
require_once "conexion.php";

$mensaje = ""; // Variable para guardar mensajes de error o éxito que se mostrarán al usuario.
$exito = false; // Bandera (flag) booleana para controlar si mostramos la redirección JS.

// Array con la lista blanca (whitelist) de dominios de correo permitidos.
// Esto ayuda a reducir el SPAM limitando el registro a proveedores confiables.
$dominios_validos = [
    'gmail.com', 'outlook.com', 'outlook.es',
    'hotmail.com', 'hotmail.es', 'yahoo.com',
    'yahoo.es', 'icloud.com'
];

// $_SERVER["REQUEST_METHOD"]: Variable superglobal que indica el método de solicitud (GET, POST, etc.).
// "POST" significa que el usuario envió los datos del formulario.
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // trim(): Función que elimina espacios en blanco al inicio y al final de una cadena.
    // ?? (Null Coalescing Operator): Si $_POST["nombre"] no existe, asigna una cadena vacía "" para evitar errores.
    $nombre = trim($_POST["nombre"] ?? "");
    $correo = trim($_POST["correo"] ?? "");

    // mb_strtolower(): Convierte el string a minúsculas usando codificación multibyte (UTF-8).
    // Esto asegura que 'GMAIL.COM' sea tratado igual que 'gmail.com'.
    $correo = mb_strtolower($correo, 'UTF-8');

    // La contraseña NO se limpia con trim() ni se pasa a minúsculas, porque debe ser exacta.
    $contrasena = $_POST["contrasena"] ?? "";

    // VALIDACIÓN 1: Verificar campos vacíos.
    // ===: Operador de identidad (compara valor y tipo).
    if ($nombre === "" || $correo === "" || $contrasena === "") {
        $mensaje = "⚠️ Completa todos los campos.";
    }
    // VALIDACIÓN 2: Formato de correo.
    // filter_var(): Filtra una variable con un filtro específico.
    // FILTER_VALIDATE_EMAIL: Constante predefinida de PHP que valida la sintaxis de un email según RFC 822.
    // ! (negación): Si NO es válido...
    elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $mensaje = "⚠️ El correo no tiene un formato válido.";
    }
    else {
        // explode(): Divide un string en un array usando un delimitador ('@').
        // 'usuario@gmail.com' -> ['usuario', 'gmail.com']
        $partes_correo = explode('@', $correo);
        
        // Obtenemos la segunda parte (el dominio). Si no existe, asignamos cadena vacía.
        $dominio = $partes_correo[1] ?? "";

        // VALIDACIÓN 3: Dominio permitido.
        // in_array(): Busca si el valor '$dominio' existe dentro del array '$dominios_validos'.
        if (!in_array($dominio, $dominios_validos)) {
            // implode(): Une elementos de un array en un string separado por comas.
            // array_slice(): Tomamos solo los primeros 5 dominios para no saturar el mensaje.
            $dominios_lista = implode(', ', array_slice($dominios_validos, 0, 5));
            $mensaje = "⚠️ Solo se permiten correos de dominios como: $dominios_lista, etc.";
        }
        // VALIDACIÓN 4: Longitud de contraseña.
        // strlen(): Devuelve la longitud de un string.
        elseif (strlen($contrasena) < 6) {
            $mensaje = "⚠️ La contraseña debe tener al menos 6 caracteres.";
        }
        else {
            // password_hash(): Crea un hash de contraseña seguro usando un algoritmo fuerte de un solo sentido.
            // PASSWORD_DEFAULT: Usa el algoritmo bcrypt (actualmente estándar).
            // NUNCA se deben guardar contraseñas en texto plano en la base de datos.
            $contrasena_hash = password_hash($contrasena, PASSWORD_DEFAULT);

            // PREPARACIÓN DE LA CONSULTA SQL (Seguridad contra Inyección SQL).
            // Los signos '?' son marcadores de posición que serán reemplazados por los valores reales después.
            $sql = "INSERT INTO usuarios (nombre, correo, contrasena_hash) VALUES (?, ?, ?)";
            
            // prepare(): Prepara la sentencia SQL para su ejecución segura.
            $stmt = $conexion->prepare($sql);

            // bind_param(): Vincula las variables a los marcadores '?'.
            // "sss": Indica que los tres parámetros son Strings (cadena, cadena, cadena).
            $stmt->bind_param("sss", $nombre, $correo, $contrasena_hash);

            // execute(): Ejecuta la consulta preparada. Devuelve true si tuvo éxito, false si falló.
            if ($stmt->execute()) {
                $mensaje = "✅ Registro exitoso. Ahora inicia sesión.";
                $exito = true;

                // Bloque de JavaScript inyectado para redireccionar después de 2 segundos.
                echo "
                <script>
                    setTimeout(function() {
                        // windows.location.href: Propiedad JS que cambia la URL del navegador.
                        window.location.href = 'inicio_secion.php';
                    }, 2000); // 2000 milisegundos = 2 segundos
                </script>
                ";
            } else {
                // Si execute() falla, es probable que el correo ya exista (suponiendo restricción UNIQUE en la BD).
                $mensaje = "⚠️ Error al registrar (posiblemente el correo ya existe).";
            }

            // close(): Cierra la sentencia preparada para liberar memoria en el servidor.
            $stmt->close();
        }
    }
}
?>


<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registro - Performance Zone MX</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="registro.css">
    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body>

<!-- <form>: Formulario para crear una cuenta nueva. -->
<!-- novalidate: Desactiva validación del navegador. -->
<form method="post" class="formulario" novalidate>
    
    <div class="logo-taller">
        <img src="img/nuevologo.jpeg" alt="Logo Taller">
        <h1>Performance Zone MX</h1>
        <p class="subtitulo">Crea tu cuenta</p>
    </div>

    <section class="seccion-informacion">
        <label>Nombre Completo</label>
        <!-- htmlspecialchars(): Previene XSS al mostrar valores previos. -->
        <input type="text" name="nombre" placeholder="Ej: Jesus Mendez" value="<?= htmlspecialchars($_POST['nombre'] ?? '') ?>" required>

        <label>Correo Electrónico</label>
        <input type="email" name="correo" placeholder="ejemplo@gmail.com" value="<?= htmlspecialchars($_POST['correo'] ?? '') ?>" required>

        <label>Contraseña</label>
        <input type="password" name="contrasena" placeholder="Mínimo 6 caracteres" required minlength="6">
    </section>

    <section class="seccion-botones">
        <button type="submit">Crear Cuenta</button>
        <p>¿Ya tienes cuenta? <a href="inicio_secion.php">Inicia sesión</a></p>
    </section>
</form>

<!-- Modal de Mensaje -->
<?php if($mensaje): ?>
<div class="modal-mensaje <?= $exito ? 'exito' : 'error' ?>">
    <div class="modal-contenido">

        <h2><?= $exito ? "🔧 Registro Completado" : "❌ Error" ?></h2>
        <p><?= htmlspecialchars($mensaje) ?></p>

        <?php if($exito): ?>
            <p style="font-style: italic; margin-top: 15px;">
                Serás redirigido automáticamente en 2 segundos...
            </p>
        <?php else: ?>
            <!-- onclick: Cierra el modal al hacer clic. -->
            <button onclick="cerrarModal()">Cerrar</button>
        <?php endif; ?>
    </div>
</div>

<script>
// Función para ocultar el modal.
function cerrarModal() { 
    document.querySelector('.modal-mensaje').style.display='none'; 
}
</script>

<?php endif; ?>

</body>
</html>
