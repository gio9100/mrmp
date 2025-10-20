<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtener y normalizar
    $correo = isset($_POST['correo']) ? trim($_POST['correo']) : '';
    $correo = mb_strtolower($correo, 'UTF-8');

    // Validación básica con filter_var
    if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        die('Correo inválido.');
    }

    // Validación que sea exactamente dominio gmail.com
    if (!preg_match('/^[A-Za-z0-9._%+-]+@gmail\.com$/', $correo)) {
        die('Solo se permiten correos @gmail.com');
    }

    // Si llega aquí, el correo tiene formato válido y es @gmail.com
    // Aquí puedes continuar (guardar en DB, enviar email, etc.)
    echo 'Correo aceptado: ' . htmlspecialchars($correo);
}
?>
