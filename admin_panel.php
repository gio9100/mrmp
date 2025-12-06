<?php
// Iniciar sesión para gestionar el acceso
session_start();

// Conexión a la base de datos
require_once "conexion.php";

// Clave maestra para registrar nuevos administradores
$claveCreador = "CBTIS52";

// Obtener y limpiar mensajes de sesión
$mensaje = $_SESSION['mensaje'] ?? '';
unset($_SESSION['mensaje']);

// --- LOGOUT ---
if(isset($_GET['logout'])){
    session_destroy(); // Destruir sesión
    header("Location: admin_panel.php"); // Redirigir al inicio
    exit;
}

// --- REGISTRO DE ADMIN (Procesar Formulario) ---
if(isset($_POST['crear_admin'])){
    $nombre = trim($_POST['nombre']);
    $correo = trim($_POST['correo']);
    $contrasena = $_POST['contrasena'];
    $clave = $_POST['clave_creador'];

    // Validar clave maestra
    if($clave !== $claveCreador){
        $_SESSION['mensaje'] = "❌ Clave incorrecta.";
    } 
    elseif($nombre && $correo && $contrasena){
        // Encriptar contraseña
        $hash = password_hash($contrasena, PASSWORD_DEFAULT);
        
        // Insertar nuevo admin
        $stmt = $conexion->prepare("INSERT INTO admins(nombre, correo, contrasena_hash) VALUES(?,?,?)");
        $stmt->bind_param("sss", $nombre, $correo, $hash);
        $stmt->execute();
        $stmt->close();
        
        $_SESSION['mensaje'] = "✅ Admin registrado.";
    }
    header("Location: admin_panel.php");
    exit;
}

// --- LOGIN (Procesar Formulario) ---
if(isset($_POST['login_admin'])){
    $correo = trim($_POST['correo']);
    $contrasena = $_POST['contrasena'];

    // Buscar admin por correo
    $stmt = $conexion->prepare("SELECT * FROM admins WHERE correo=?");
    $stmt->bind_param("s", $correo);
    $stmt->execute();
    $res = $stmt->get_result();

    if($res && $res->num_rows == 1){
        $admin = $res->fetch_assoc();
        // Verificar contraseña
        if(password_verify($contrasena, $admin['contrasena_hash'])){
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_nombre'] = $admin['nombre'];
            header("Location: admin_panel.php");
            exit;
        }
    }
    $_SESSION['mensaje'] = "❌ Datos incorrectos.";
    header("Location: admin_panel.php");
    exit;
}

// --- ACCIONES DEL PANEL (Solo si está logueado) ---
if(isset($_SESSION['admin_id'])){

    // 1. Agregar Marca
    if(isset($_POST['nueva_marca'])){
        $nombre_marca = trim($_POST['nombre_marca']);
        $imagen_marca = '';
        
        // Procesar imagen si se subió
        if(isset($_FILES['imagen_marca']) && $_FILES['imagen_marca']['error']==0){
            $imagen_marca = time().'_'.basename($_FILES['imagen_marca']['name']);
            move_uploaded_file($_FILES['imagen_marca']['tmp_name'], "uploads/".$imagen_marca);
        }
        
        if($nombre_marca){
            $stmt = $conexion->prepare("INSERT INTO marcas(nombre, imagen) VALUES(?, ?)");
            $stmt->bind_param("ss", $nombre_marca, $imagen_marca);
            $stmt->execute();
            $_SESSION['mensaje'] = "✅ Marca agregada.";
        }
        header("Location: admin_panel.php");
        exit;
    }

    // 2. Eliminar Marca
    if(isset($_GET['eliminar_marca'])){
        $id = intval($_GET['eliminar_marca']);
        $conexion->query("DELETE FROM marcas WHERE id=$id");
        $_SESSION['mensaje'] = "✅ Marca eliminada.";
        header("Location: admin_panel.php");
        exit;
    }
    
    // 3. Agregar Pieza
    if(isset($_POST['agregar_pieza'])){
        $imagen = '';
        if(isset($_FILES['imagen']) && $_FILES['imagen']['error']==0){
            $imagen = time().'_'.basename($_FILES['imagen']['name']);
            move_uploaded_file($_FILES['imagen']['tmp_name'], "uploads/".$imagen);
        }

        // Insertar pieza
        $stmt = $conexion->prepare("INSERT INTO piezas(nombre, descripcion, precio, cantidad, marca_id, imagen) VALUES(?,?,?,?,?,?)");
        $stmt->bind_param("ssdiis", $_POST['nombre'], $_POST['descripcion'], $_POST['precio'], $_POST['cantidad'], $_POST['marca_id'], $imagen);
        $stmt->execute();
        $pieza_id = $stmt->insert_id;
        $stmt->close();

        // Procesar galería de imágenes
        if(isset($_FILES['galeria'])){
            $total = count($_FILES['galeria']['name']);
            for($i=0; $i<$total; $i++){
                if($_FILES['galeria']['error'][$i] == 0){
                    $img_gal = time().'_'.$i.'_'.basename($_FILES['galeria']['name'][$i]);
                    move_uploaded_file($_FILES['galeria']['tmp_name'][$i], "uploads/".$img_gal);
                    $conexion->query("INSERT INTO piezas_imagenes(pieza_id, imagen) VALUES($pieza_id, '$img_gal')");
                }
            }
        }
        $_SESSION['mensaje'] = "✅ Pieza agregada.";
        header("Location: admin_panel.php");
        exit;
    }

    // 4. Eliminar Pieza
    if(isset($_GET['eliminar_pieza'])){
        $id = intval($_GET['eliminar_pieza']);
        $conexion->query("DELETE FROM piezas WHERE id=$id");
        $_SESSION['mensaje'] = "✅ Pieza eliminada.";
        header("Location: admin_panel.php");
        exit;
    }
}
?>

<?php 
// VISTA: Si no hay sesión iniciada, mostrar Login
if(!isset($_SESSION['admin_id'])): 
?>

    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <title>Admin MRMP - Acceso</title>
        <link rel="stylesheet" href="admin.css">
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    </head>
    <body class="login-page">

        <div class="login-wrapper">
            
            <!-- FORMULARIO LOGIN -->
            <div class="login-card" id="card-login">
                <div class="logo-taller">
                    <img src="img/logo2.png" alt="Logo MRMP">
                    <h1>Admin Login</h1>
                    <p class="subtitulo">Panel de Administración MRMP</p>
                </div>
                
                <?php if($mensaje): ?>
                    <div class="alert-box"><?= htmlspecialchars($mensaje) ?></div>
                <?php endif; ?>

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
                    <p>¿No estás registrado? <a href="#" onclick="toggleForms()">Crear cuenta admin</a></p>
                    <a href="dashboard-piezas.php" class="back-link"><i class="fas fa-arrow-left"></i> Volver a la Tienda</a>
                </div>
            </div>

            <!-- FORMULARIO REGISTRO -->
            <div class="login-card" id="card-register" style="display: none;">
                 <div class="logo-taller">
                    <img src="img/logo2.png" alt="Logo MRMP">
                    <h1>Nuevo Admin</h1>
                    <p class="subtitulo">Registro Autorizado</p>
                </div>

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
            // Alternar entre login y registro
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
// VISTA: Si hay sesión iniciada, mostrar Panel
else: 

    // Obtener piezas y marcas para el dashboard
    $piezas = $conexion->query("SELECT p.*, m.nombre as marca_nombre FROM piezas p LEFT JOIN marcas m ON p.marca_id = m.id ORDER BY p.id DESC");
    $marcas = $conexion->query("SELECT * FROM marcas ORDER BY nombre");
?>

    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <title>Panel de Administración MRMP</title>
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

            <!-- Sección Marcas -->
            <section class="formulario">
                <h2><i class="fas fa-tag"></i> Gestión de Marcas</h2>
                <form method="post" enctype="multipart/form-data">
                    <input type="text" name="nombre_marca" placeholder="Nombre de la Nueva Marca" required>
                    <input type="file" name="imagen_marca" accept="image/*">
                    <button type="submit" name="nueva_marca">Agregar Marca</button>
                </form>

                <h3>Marcas Disponibles</h3>
                <ul class="lista-marcas">
                    <?php while($m = $marcas->fetch_assoc()): ?>
                        <li class="marca-item">
                            <?php if($m['imagen']): ?>
                                <img src="uploads/<?= htmlspecialchars($m['imagen']) ?>" width="50" style="display:block; margin: 0 auto 10px;">
                            <?php endif; ?>
                            <strong><?= htmlspecialchars($m['nombre']) ?></strong>
                            <div style="margin-top: 10px;">
                                <a href="?eliminar_marca=<?= $m['id'] ?>" class="eliminar" onclick="return confirm('¿Borrar marca?')">Eliminar</a>
                            </div>
                        </li>
                    <?php endwhile; ?>
                </ul>
            </section>

            <!-- Sección Piezas -->
            <section class="formulario">
                <h2><i class="fas fa-cogs"></i> Agregar Nueva Pieza</h2>
                <form method="post" enctype="multipart/form-data">
                    <input type="text" name="nombre" placeholder="Nombre de la pieza" required>
                    <textarea name="descripcion" placeholder="Descripción detallada" rows="3" required></textarea>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <input type="number" step="0.01" name="precio" placeholder="Precio ($)" required>
                        <input type="number" name="cantidad" placeholder="Stock disponible" required>
                    </div>

                    <select name="marca_id" required>
                        <option value="">Selecciona una marca...</option>
                        <?php 
                        $marcas->data_seek(0); 
                        while($m = $marcas->fetch_assoc()): 
                        ?>
                            <option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['nombre']) ?></option>
                        <?php endwhile; ?>
                    </select>

                    <label>Imagen Principal:</label>
                    <input type="file" name="imagen">
                    
                    <label>Galería (Opcional):</label>
                    <input type="file" name="galeria[]" multiple accept="image/*">

                    <button type="submit" name="agregar_pieza" style="margin-top: 15px;">Guardar Pieza</button>
                </form>
            </section>

            <!-- Tabla Inventario -->
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
            &copy; <?= date('Y') ?> Mexican Racing Motor Parts
        </footer>
    </body>
    </html>
<?php endif; ?>