<?php
// session_start: Inicia o reanuda la sesión existente. 
// Es necesario hacer esto antes de poder destruirla para acceder a su ID.
session_start();

// session_destroy: Destruye toda la información registrada de una sesión.
// Esto elimina los archivos de sesión en el servidor y cierra efectivamente la sesión del usuario.
session_destroy();

// header: Envía un encabezado HTTP sin procesar.
// "Location: ...": Redirige al navegador a la página especificada (dashboard-piezas.php).
header("Location: dashboard-piezas.php");

// exit: Termina la ejecución del script inmediatamente.
// Buena práctica después de una redirección para asegurar que no se ejecute código adicional.
exit; 
?>
