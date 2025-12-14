<?php
// session_start(): Inicia una sesión o reanuda la actual.
// Fundamental para acceder a $_SESSION (comprobar si hay admin logueado).
session_start();

// require_once: Incluye la conexión a la base de datos (con MySQLi).
require_once "conexion.php";

// Variable de configuración: Clave secreta (Master Key) requerida para registrar un nuevo admin.
// Esto evita que cualquiera pueda registrarse como administrador sin permiso.
$claveCreador = "CBTIS52";

// Operador de Fusión Null (??): Si $_SESSION['mensaje'] existe, lo asigna a $mensaje.
// Si no existe, asigna una cadena vacía ''.
$mensaje = $_SESSION['mensaje'] ?? '';

// unset(): Elimina la variable de la sesión para que el mensaje no aparezca
// de nuevo si el usuario recarga la página. (Mensajes "Flash").
unset($_SESSION['mensaje']);

// --- LÓGICA DE CERRAR SESIÓN (LOGOUT) ---
// Verificamos si en la URL viene el parámetro 'logout' (ej: admin_panel.php?logout).
if(isset($_GET['logout'])){
    // session_destroy(): Borra todos los datos de sesión en el servidor.
    session_destroy(); 
    
    // header(): Redirige al script mismo, pero ahora sin sesión iniciada (cargará el login).
    header("Location: admin_panel.php"); 
    exit; // Detiene la ejecución.
}

// --- LÓGICA DE REGISTRO DE NUEVO ADMIN ---
// Verificamos si se envió el formulario con el botón 'crear_admin'.
if(isset($_POST['crear_admin'])){
    // trim(): Elimina espacios en blanco.
    $nombre = trim($_POST['nombre']);
    $correo = trim($_POST['correo']);
    $contrasena = $_POST['contrasena']; // Contraseña tal cual la escribió.
    $clave = $_POST['clave_creador']; // Clave maestra ingresada.

    // 1. Validar la clave maestra.
    if($clave !== $claveCreador){
        $_SESSION['mensaje'] = "❌ Clave incorrecta. No tienes permiso para crear admins.";
    } 
    // 2. Validar que los campos no estén vacíos.
    elseif($nombre && $correo && $contrasena){
        // password_hash(): Crea un hash seguro de la contraseña.
        // PASSWORD_DEFAULT: Usa el algoritmo bcrypt, estándar actual de seguridad.
        $hash = password_hash($contrasena, PASSWORD_DEFAULT);
        
        // INSERT: Preparar la consulta para guardar el nuevo admin.
        $stmt = $conexion->prepare("INSERT INTO admins(nombre, correo, contrasena_hash) VALUES(?,?,?)");
        
        // bind_param: "sss" significa String, String, String.
        $stmt->bind_param("sss", $nombre, $correo, $hash);
        
        $stmt->execute(); // Ejecutar.
        $stmt->close(); // Cerrar sentencia.
        
        $_SESSION['mensaje'] = "✅ Admin registrado correctamente.";
    }
    // Redirigir para limpiar el formulario y evitar reenvíos.
    header("Location: admin_panel.php");
    exit;
}

// --- LÓGICA DE INICIO DE SESIÓN (LOGIN) ---
if(isset($_POST['login_admin'])){
    $correo = trim($_POST['correo']);
    $contrasena = $_POST['contrasena'];

    // Consultamos la BD buscando el correo.
    $stmt = $conexion->prepare("SELECT * FROM admins WHERE correo=?");
    $stmt->bind_param("s", $correo);
    $stmt->execute();
    
    // get_result(): Obtiene el conjunto de resultados.
    $res = $stmt->get_result();

    // Si encontramos exactamente 1 usuario con ese correo.
    if($res && $res->num_rows == 1){
        // fetch_assoc: Convertimos la fila en un array asociativo.
        $admin = $res->fetch_assoc();
        
        // password_verify(): Compara la contraseña escrita (plana) con el hash guardado en la BD.
        if(password_verify($contrasena, $admin['contrasena_hash'])){
            // LOGIN EXITOSO: Guardamos datos en sesión.
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_nombre'] = $admin['nombre'];
            
            header("Location: admin_panel.php");
            exit;
        }
    }
    // Si falla correo o contraseña.
    $_SESSION['mensaje'] = "❌ Datos incorrectos.";
    header("Location: admin_panel.php");
    exit;
}

// --- ACCIONES DEL PANEL DE ADMINISTRACIÓN ---
// Todo este bloque solo se ejecuta SI hay un admin logueado.
if(isset($_SESSION['admin_id'])){

    // 1. Lógica para AGREGAR MARCA
    if(isset($_POST['nueva_marca'])){
        $nombre_marca = trim($_POST['nombre_marca']);
        $imagen_marca = '';
        
        // $_FILES: Manejo de subida de archivos.
        // Verificamos si se envió 'imagen_marca' y si el código de error es 0 (subida exitosa).
        if(isset($_FILES['imagen_marca']) && $_FILES['imagen_marca']['error']==0){
            // time(): Agregamos el timestamp al nombre para hacerlo único y evitar sobrescrituras.
            // basename(): Obtiene el nombre del archivo limpio, sin rutas de carpetas.
            $imagen_marca = time().'_'.basename($_FILES['imagen_marca']['name']);
            
            // move_uploaded_file(): Mueve el archivo de la carpeta temporal a nuestra carpeta 'uploads/'.
            move_uploaded_file($_FILES['imagen_marca']['tmp_name'], "uploads/".$imagen_marca);
        }
        
        if($nombre_marca){
            // INSERT: Guardamos nombre de marca y nombre de archivo de imagen.
            $stmt = $conexion->prepare("INSERT INTO marcas(nombre, imagen) VALUES(?, ?)");
            $stmt->bind_param("ss", $nombre_marca, $imagen_marca);
            $stmt->execute();
            $_SESSION['mensaje'] = "✅ Marca agregada.";
        }
        header("Location: admin_panel.php");
        exit;
    }

    // 2. Lógica para ELIMINAR MARCA
    // Usamos GET porque se activa desde un enlace, no un formulario.
    if(isset($_GET['eliminar_marca'])){
        // intval: Seguridad para asegurar que el ID sea numérico.
        $id = intval($_GET['eliminar_marca']);
        
        // DELETE: Borramos la marca (Nota: Deberíamos borrar las piezas asociadas o manejar la FK).
        $conexion->query("DELETE FROM marcas WHERE id=$id");
        
        $_SESSION['mensaje'] = "✅ Marca eliminada.";
        header("Location: admin_panel.php");
        exit;
    }
    
    // 3. Lógica para AGREGAR PIEZA (PRODUCTO)
    if(isset($_POST['agregar_pieza'])){
        $imagen = '';
        // Manejo de imagen principal.
        if(isset($_FILES['imagen']) && $_FILES['imagen']['error']==0){
            $imagen = time().'_'.basename($_FILES['imagen']['name']);
            move_uploaded_file($_FILES['imagen']['tmp_name'], "uploads/".$imagen);
        }

        // INSERT: Guardar datos de la pieza.
        // "ssdiis" -> string, string, double(precio), integer(cantidad), integer(marca_id), string(imagen).
        $stmt = $conexion->prepare("INSERT INTO piezas(nombre, descripcion, precio, cantidad, marca_id, imagen) VALUES(?,?,?,?,?,?)");
        $stmt->bind_param("ssdiis", $_POST['nombre'], $_POST['descripcion'], $_POST['precio'], $_POST['cantidad'], $_POST['marca_id'], $imagen);
        $stmt->execute();
        
        // insert_id: Obtenemos el ID de la pieza recién creada (necesario para la galería).
        $pieza_id = $stmt->insert_id;
        $stmt->close();

        // Procesar GALERÍA DE IMÁGENES (Múltiples archivos).
        if(isset($_FILES['galeria'])){
            // count(): Contamos cuántos archivos se enviaron.
            $total = count($_FILES['galeria']['name']);
            
            // Iteramos sobre cada archivo subido.
            for($i=0; $i<$total; $i++){
                if($_FILES['galeria']['error'][$i] == 0){
                    $img_gal = time().'_'.$i.'_'.basename($_FILES['galeria']['name'][$i]);
                    move_uploaded_file($_FILES['galeria']['tmp_name'][$i], "uploads/".$img_gal);
                    
                    // INSERT simple para la tabla 'piezas_imagenes'.
                    $conexion->query("INSERT INTO piezas_imagenes(pieza_id, imagen) VALUES($pieza_id, '$img_gal')");
                }
            }
        }
        $_SESSION['mensaje'] = "✅ Pieza agregada.";
        header("Location: admin_panel.php");
        exit;
    }

    // 4. Lógica para ELIMINAR PIEZA
    if(isset($_GET['eliminar_pieza'])){
        $id = intval($_GET['eliminar_pieza']);
        // DELETE: Borramos la pieza (Las imágenes asociadas deberían borrarse por ON DELETE CASCADE en BD).
        $conexion->query("DELETE FROM piezas WHERE id=$id");
        $_SESSION['mensaje'] = "✅ Pieza eliminada.";
        header("Location: admin_panel.php");
        exit;
    }
}
?>

<?php 
// VISTA: Lógica de visualización condicional (Login vs Dashboard).
// Si NO (!) está seteada la sesión de admin, mostramos el formulario de login/registro.
if(!isset($_SESSION['admin_id'])): 
?>

    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <title>Admin MRMP - Acceso</title>
        <link rel="stylesheet" href="admin.css">
        <!-- FontAwesome para iconos -->
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    </head>
    <body class="login-page">

        <div class="login-wrapper">
            
            <!-- TARJETA LOGIN -->
            <div class="login-card" id="card-login">
                <div class="logo-taller">
            <img src="img/nuevologo.jpeg" alt="Logo Taller">
            <h1>Performance Zone MX</h1>
            <p class="subtitulo">Panel de Administración</p>
        </div>
                
                <!-- Mostrar mensaje flash si existe -->
                <?php if($mensaje): ?>
                    <!-- htmlspecialchars: Seguridad contra XSS al mostrar texto variable -->
                    <div class="alert-box"><?= htmlspecialchars($mensaje) ?></div>
                <?php endif; ?>

                <!-- Formulario Login: Envía datos por POST a la misma página -->
                <form method="post">
                    <div class="input-group">
                        <i class="fas fa-envelope"></i>
                        <input type="email" name="correo" placeholder="Correo Electrónico" required>
                    </div>
                    <div class="input-group">
                        <i class="fas fa-lock"></i>
                        <input type="password" name="contrasena" placeholder="Contraseña" required>
                    </div>
                    
                    <button type="submit" name="login_admin" class="btn-login">Iniciar Sesión</button>
                </form>
                
                <div class="login-footer">
                    <!-- onclick="toggleForms()": Llama a función JS para cambiar de vista -->
                    <p>¿No estás registrado? <a href="#" onclick="toggleForms()">Crear cuenta admin</a></p>
                    <a href="dashboard-piezas.php" class="back-link"><i class="fas fa-arrow-left"></i> Volver a la Tienda</a>
                </div>
            </div>

            <!-- TARJETA REGISTRO (Oculta por defecto: style="display: none;") -->
            <div class="login-card" id="card-register" style="display: none;">
                 <div class="logo-taller">
                    <img src="img/logo2.png" alt="Logo MRMP">
                    <h1>Nuevo Admin</h1>
                    <p class="subtitulo">Registro Autorizado</p>
                </div>

                <!-- Formulario Registro -->
                <form method="post">
                    <div class="input-group">
                        <i class="fas fa-user"></i>
                        <input type="text" name="nombre" placeholder="Nombre Completo" required>
                    </div>
                    <div class="input-group">
                        <i class="fas fa-envelope"></i>
                        <input type="email" name="correo" placeholder="Correo Electrónico" required>
                    </div>
                    <div class="input-group">
                        <i class="fas fa-lock"></i>
                        <input type="password" name="contrasena" placeholder="Contraseña" required>
                    </div>
                    <!-- Campo para la Clave Maestra -->
                    <div class="input-group">
                        <i class="fas fa-key"></i>
                        <input type="password" name="clave_creador" placeholder="Clave Maestra" required>
                    </div>
                    
                    <button type="submit" name="crear_admin" class="btn-login">Registrar Admin</button>
                </form>

                <div class="login-footer">
                    <p>¿Ya tienes cuenta? <a href="#" onclick="toggleForms()">Iniciar sesión</a></p>
                </div>
            </div>
        </div>

        <script>
            // Función JS para alternar la visibilidad entre formularios Login y Registro.
            function toggleForms() {
                const login = document.getElementById('card-login');
                const register = document.getElementById('card-register');
                
                if (login.style.display === 'none') {
                    login.style.display = 'block';
                    register.style.display = 'none';
                } else {
                    login.style.display = 'none';
                    register.style.display = 'block';
                }
            }
        </script>
    </body>
    </html>

<?php 
// VISTA: Si SÍ hay sesión iniciada (else del if(!isset...)).
// Mostramos el dashboard de administración.
else: 
    // CONSULTAS PARA LLENAR EL DASHBOARD
    // 1. Obtener todas las piezas con el nombre de su marca (JOIN).
    // ORDER BY p.id DESC: Muestra las más recientes primero.
    $piezas = $conexion->query("SELECT p.*, m.nombre as marca_nombre FROM piezas p LEFT JOIN marcas m ON p.marca_id = m.id ORDER BY p.id DESC");
    
    // 2. Obtener todas las marcas para listarlas y para el select del formulario.
    $marcas = $conexion->query("SELECT * FROM marcas ORDER BY nombre");
?>

    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <title>Panel de Administración - Performance Zone MX</title>
        <link rel="stylesheet" href="admin.css">
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    </head>
    <body>
        <header>
            <h1>Panel MRMP <small style="font-size: 0.6em; font-weight: 400;">Bienvenido, <?= htmlspecialchars($_SESSION['admin_nombre']) ?></small></h1>
            <nav>
                <a href="gestionar_pedidos.php"><i class="fas fa-clipboard-list"></i> Pedidos</a>
                <a href="dashboard-piezas.php"><i class="fas fa-store"></i> Tienda Publica</a>
                <a href="?logout" style="color: #dc3545;"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a>
            </nav>
        </header>

        <main>
            <?php if($mensaje): ?>
                <div class="modal-mensaje">
                    <div class="modal-contenido">
                        <h2>Notificación</h2>
                        <p><?= htmlspecialchars($mensaje) ?></p>
                        <button onclick="this.parentElement.parentElement.style.display='none'">Aceptar</button>
                    </div>
                </div>
            <?php endif; ?>

            <!-- SECCIÓN 1: GESTIÓN DE MARCAS -->
            <section class="formulario">
                <h2><i class="fas fa-tag"></i> Gestión de Marcas</h2>
                
                <!-- Formulario Agregar Marca -->
                <form method="post" enctype="multipart/form-data">
                    <input type="text" name="nombre_marca" placeholder="Nombre de la Nueva Marca" required>
                    <input type="file" name="imagen_marca" accept="image/*">
                    <button type="submit" name="nueva_marca">Agregar Marca</button>
                </form>

                <h3>Marcas Disponibles</h3>
                <ul class="lista-marcas">
                    <!-- while: Iteramos sobre cada fila de resultados de marcas -->
                    <?php while($m = $marcas->fetch_assoc()): ?>
                        <li class="marca-item">
                            <?php if($m['imagen']): ?>
                                <img src="uploads/<?= htmlspecialchars($m['imagen']) ?>" width="50" style="display:block; margin: 0 auto 10px;">
                            <?php endif; ?>
                            <strong><?= htmlspecialchars($m['nombre']) ?></strong>
                            <div style="margin-top: 10px;">
                                <!-- Enlace GET con confirmación JS para eliminar -->
                                <a href="?eliminar_marca=<?= $m['id'] ?>" class="eliminar" onclick="return confirm('¿Borrar marca?')">Eliminar</a>
                            </div>
                        </li>
                    <?php endwhile; ?>
                </ul>
            </section>

            <!-- SECCIÓN 2: GESTIÓN DE PIEZAS -->
            <section class="formulario">
                <h2><i class="fas fa-cogs"></i> Agregar Nueva Pieza</h2>
                
                <form method="post" enctype="multipart/form-data">
                    <input type="text" name="nombre" placeholder="Nombre de la pieza" required>
                    <textarea name="descripcion" placeholder="Descripción detallada" rows="3" required></textarea>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <input type="number" step="0.01" name="precio" placeholder="Precio ($)" required>
                        <input type="number" name="cantidad" placeholder="Stock disponible" required>
                    </div>

                    <!-- Select dinámico de Marcas -->
                    <select name="marca_id" required>
                        <option value="">Selecciona una marca...</option>
                        <?php 
                        // data_seek(0): Rebobinamos el puntero de resultados de marcas al inicio (porque ya lo recorrimos arriba en el while).
                        $marcas->data_seek(0); 
                        while($m = $marcas->fetch_assoc()): 
                        ?>
                            <option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['nombre']) ?></option>
                        <?php endwhile; ?>
                    </select>

                    <label>Imagen Principal:</label>
                    <input type="file" name="imagen">
                    
                    <label>Galería (Opcional):</label>
                    <!-- name="galeria[]": Los corchetes indican que es un array de archivos (multiple) -->
                    <input type="file" name="galeria[]" multiple accept="image/*">

                    <button type="submit" name="agregar_pieza" style="margin-top: 15px;">Guardar Pieza</button>
                </form>
            </section>

            <!-- SECCIÓN 3: TABLA DE INVENTARIO -->
            <section>
                <h3>Inventario Actual</h3>
                <div style="overflow-x: auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Imagen</th>
                                <th>Producto</th>
                                <th>Marca</th>
                                <th>Precio</th>
                                <th>Stock</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Iteramos sobre las piezas -->
                            <?php while($p = $piezas->fetch_assoc()): ?>
                                <tr>
                                    <td>#<?= $p['id'] ?></td>
                                    <td>
                                        <?php if($p['imagen']): ?>
                                            <img src="uploads/<?= htmlspecialchars($p['imagen']) ?>" width="40" style="border-radius: 4px;">
                                        <?php else: ?>
                                            <span style="color:#ccc;">Sin img</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong><?= htmlspecialchars($p['nombre']) ?></strong>
                                    </td>
                                    <td><?= htmlspecialchars($p['marca_nombre']) ?></td>
                                    <td style="color: #d32f2f;">$<?= number_format($p['precio'], 2) ?></td>
                                    <td><?= $p['cantidad'] ?> un.</td>
                                    <td>
                                        <a href="?eliminar_pieza=<?= $p['id'] ?>" class="eliminar" onclick="return confirm('¿Eliminar pieza?')">X</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
        
        <footer>
            <title>Login Administrativo - Performance Zone MX</title>
        </footer>
    </body>
    </html>
<?php endif; // Fin del if/else de auth ?>
```