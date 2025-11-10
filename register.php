<?php

session_start();
require_once "conexion.php";

$mensaje = "";
$exito = false;

//lista de dominios 
$dominios_validos = [
    'gmail.com',
    'outlook.com',
    'outlook.es',
    'hotmail.com',
    'hotmail.es',
    'yahoo.com',
    'yahoo.es',
    'icloud.com',
  
];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    //obtener y limpiar datos del formulario
    $nombre = trim($_POST["nombre"] ?? "");
    $correo = trim($_POST["correo"] ?? "");
    $correo = mb_strtolower($correo, 'UTF-8'); 
    $contrasena = $_POST["contrasena"] ?? "";


    if ($nombre === "" || $correo === "" || $contrasena === "") {
        $mensaje = "⚠️ Completa todos los campos.";
    } 
    //formato de correo
    elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $mensaje = "⚠️ El correo no tiene un formato válido.";
    } 
    else {
        //verificar el dominio
        $partes_correo = explode('@', $correo);
        $dominio = isset($partes_correo[1]) ? $partes_correo[1] : '';
        
        //verificar si esta en la lista
        if (!in_array($dominio, $dominios_validos)) {
            $dominios_lista = implode(', ', array_slice($dominios_validos, 0, 5));
            $mensaje = "⚠️ Solo se permiten correos de dominios verificados como: " . $dominios_lista . ", etc.";
        } 
        elseif (strlen($contrasena) < 6) {
            $mensaje = "⚠️ La contraseña debe tener al menos 6 caracteres.";
        } 
        else {
            $contrasena_hash = password_hash($contrasena, PASSWORD_DEFAULT);

            //crud insertar usuarios a la db
            $sql = "INSERT INTO usuarios (nombre, correo, contrasena_hash) VALUES (?, ?, ?)";
            $stmt = $conexion->prepare($sql);
            $stmt->bind_param("sss", $nombre, $correo, $contrasena_hash);

            if ($stmt->execute()) {
                $mensaje = "✅ Registro exitoso. Ahora inicia sesión.";
                $exito = true;
            } else {
                //correo ya registrado vro
                $mensaje = ($conexion->errno === 1062)
                    ? "⚠️ El correo ya está registrado."
                    : "❌ Error: " . $conexion->error;
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Registro MRMC</title>
<link rel="stylesheet" href="registro.css">
<meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body>

<form method="post" class="formulario" novalidate>
    <div class="logo-taller">
        <img src="img/mrmp logo.png" alt="Logo MRMP">
        <h1>Registro MRMP</h1>
        <p class="subtitulo">Motor Racing Mexican Parts</p>
    </div>

    <section class="seccion-informacion">
        <label>Nombre Completo</label>
        <input type="text" 
               name="nombre" 
               id="nombre"
               placeholder="Ej: Jesus Mendez" 
               value="<?= htmlspecialchars($_POST['nombre'] ?? '') ?>"
               required>

        <label>Correo Electrónico</label>
        <input type="email" 
               id="correo" 
               name="correo" 
               placeholder="ejemplo@gmail.com"
               value="<?= htmlspecialchars($_POST['correo'] ?? '') ?>"
               required>
        <div id="mensaje-correo" class="mensaje-correo">
            ⚠️ Solo se aceptan correos de: Gmail, Outlook, Hotmail, Yahoo, iCloud, etc.
        </div>

        <label>Contraseña</label>
        <input type="password" 
               id="contrasena"
               name="contrasena" 
               placeholder="Mínimo 6 caracteres"
               required 
               minlength="6">
        <div id="mensaje-contrasena" class="mensaje-validacion"></div>
    </section>

    <section class="seccion-botones">
        <button type="submit">Crear Cuenta</button>
        <p>¿Ya tienes cuenta? <a href="inicio_secion.php">Inicia sesión</a></p>
    </section>
</form>


<?php if($mensaje): ?>
<div class="modal-mensaje <?= $exito ? 'exito' : 'error' ?>">
    <div class="modal-contenido">
        <h2><?= $exito ? "🔧 Registro Completado" : "❌ Error" ?></h2>
        <p><?= htmlspecialchars($mensaje) ?></p>
        <?php if($exito): ?>
            <button onclick="window.location.href='inicio_secion.php'">Ir al Login</button>
        <?php else: ?>
            <button onclick="cerrarModal()">Cerrar</button>
        <?php endif; ?>
    </div>
</div>
<script>

function cerrarModal() { 
    document.querySelector('.modal-mensaje').style.display='none'; 
}
</script>
<?php endif; ?>

<script>


//lista de dominios válidos
const dominiosValidos = [
    'gmail.com',
    'outlook.com',
    'outlook.es',
    'hotmail.com',
    'hotmail.es',
    'yahoo.com',
    'yahoo.es',
    'icloud.com',
];

//validación del correo
const correoInput = document.getElementById('correo');
const mensajeCorreo = document.getElementById('mensaje-correo');

correoInput.addEventListener('input'), function() {
    const val = this.value.trim().toLowerCase();
    
    if (!val) {
        correoInput.classList.remove('error', 'success');
        mensajeCorreo.style.display = 'none';
        return;
    }

}