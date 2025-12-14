<?php
// session_start(): Inicia o reanuda la sesión existente.
// Es CRUCIAL llamarla antes de intentar destruirla, porque el servidor necesita saber
// QUÉ sesión (identificada por la cookie PHPSESSID) es la que se va a destruir.
session_start();

// session_destroy(): Destruye toda la información asociada con la sesión actual.
// Esto borra los datos en el servidor (variables $_SESSION), desconectando efectivamente al usuario.
session_destroy();

// header(): Envía un encabezado HTTP crudo al navegador.
// "Location: ...": Indica al navegador que debe redirigir al usuario a una nueva URL inmediatamente.
// En este caso, lo mandamos a 'dashboard-piezas.php' (o la página pública que prefieras).
header("Location: dashboard-piezas.php");

// exit: Detiene la ejecución del script INMEDIATAMENTE.
// Es una buena práctica de seguridad después de un header("Location: ...") para asegurar
// que no se ejecute ni una sola línea más de código, lo que podría exponer datos o causar errores.
exit; 
?>

