<?php
session_start();
require_once "conexion.php";

// Logout
if(isset($_GET['logout'])){
    session_destroy();
    header("Location: dashboard.php");
    exit;
}

// Inicializar carrito si está logueado
if(isset($_SESSION['usuario_id']) && !isset($_SESSION['carrito'])) $_SESSION['carrito'] = [];

// Agregar pieza al carrito (solo usuarios logueados)
if(isset($_GET['agregar']) && is_numeric($_GET['agregar'])){
    if(!isset($_SESSION['usuario_id'])){
        $_SESSION['mensaje'] = "⚠️ Debes iniciar sesión para agregar al carrito.";
    } else {
        $id_pieza = intval($_GET['agregar']);
        $stmt = $conexion->prepare("SELECT cantidad FROM piezas WHERE id=?");
        $stmt->bind_param("i",$id_pieza);
        $stmt->execute();
        $res = $stmt->get_result();
        if($res && $res->num_rows==1){
            $pieza = $res->fetch_assoc();
            if(($pieza['cantidad'] - ($_SESSION['carrito'][$id_pieza] ?? 0)) > 0){
                $_SESSION['carrito'][$id_pieza] = ($_SESSION['carrito'][$id_pieza] ?? 0) + 1;
                $_SESSION['mensaje'] = "✅ Pieza agregada al carrito.";
            } else {
                $_SESSION['mensaje'] = "⚠️ No hay stock suficiente, te avisaremos cuando tengamos más.";
            }
        }
    }
    header("Location: dashboard-piezas.php");
    exit;
}

// Buscador y filtro
$busqueda = trim($_GET['buscar'] ?? '');
$marca_id = intval($_GET['marca'] ?? 0);

// Obtener marcas
$marcas_res = $conexion->query("SELECT * FROM marcas ORDER BY nombre");
$marcas = [];
while($m = $marcas_res->fetch_assoc()){
    $marcas[$m['id']] = $m['nombre'];
}

// Obtener piezas filtradas
$sql = "SELECT p.*, m.nombre as marca_nombre FROM piezas p LEFT JOIN marcas m ON p.marca_id = m.id";
$condiciones = [];
$params = [];
$tipos = "";

if($busqueda !== ''){
    $condiciones[] = "(p.nombre LIKE ? OR p.descripcion LIKE ?)";
    $params[] = "%$busqueda%";
    $params[] = "%$busqueda%";
    $tipos .= "ss";
}
if($marca_id>0){
    $condiciones[] = "p.marca_id=?";
    $params[] = $marca_id;
    $tipos .= "i";
}

if(count($condiciones)>0){
    $sql .= " WHERE ".implode(" AND ", $condiciones);
}

$sql .= " ORDER BY p.id DESC";

$stmt = $conexion->prepare($sql);
if(count($params)>0){
    $stmt->bind_param($tipos,...$params);
}
$stmt->execute();
$res = $stmt->get_result();
$piezas = $res->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Mensaje
$mensaje = $_SESSION['mensaje'] ?? '';
unset($_SESSION['mensaje']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard MRMC</title>
<link rel="stylesheet" href="dashboard.css">
<style>
/* Botones de login, carrito y cerrar sesión */
header a {
    text-decoration:none;
    padding:8px 12px;
    border-radius:8px;
    margin-left:10px;
    background:#0040ff;
    color:#fff;
    transition:0.3s;
}
header a:hover { background:#002080; cursor:pointer; }

/* Lightbox imagen */
.pieza img{cursor:pointer; transition:0.3s; border-radius:8px;}
.pieza img:hover{opacity:0.85;}
.lightbox{display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.95); justify-content:center; align-items:center; z-index:1000;}
.lightbox:target{display:flex;}
.lightbox img{max-width:90%; max-height:90%; border-radius:10px;}

/* Modal descripción de pieza */
.modal-desc{
    display:none;
    position:fixed;
    top:0; left:0; width:100%; height:100%;
    background: rgba(0,0,0,0.85);
    backdrop-filter: blur(5px);
    justify-content:center;
    align-items:center;
    padding:20px;
    z-index:1000;
}

.modal-desc:target { display:flex; }

.modal-desc-content{
    background: rgba(30,30,30,0.95);
    padding:20px;
    border-radius:15px;
    max-width:600px;
    width:100%;
    color:#fff;
    text-align:center;
    position:relative;
}

.modal-desc-content h3{ margin-bottom:10px; color:#ffd700; }
.modal-desc-content p{ margin-bottom:15px; }

.modal-close{
    position:absolute;
    top:10px;
    right:10px;
    background:#ff0040;
    border:none;
    border-radius:50%;
    width:35px;
    height:35px;
    font-weight:bold;
    cursor:pointer;
    color:#fff;
}

/* Botón agregar carrito */
.pieza form button{
    padding:8px 12px;
    border:none;
    border-radius:8px;
    background:#00ffea;
    color:#000;
    font-weight:bold;
    cursor:pointer;
    margin-top:5px;
}

.pieza form button:hover{
    background:#00c8b0;
    color:#fff;
}

/* Mensaje flotante */
.modal-mensaje{
    position:fixed;
    top:10px;
    right:10px;
    background:#ffd700;
    color:#000;
    padding:10px 15px;
    border-radius:10px;
    z-index:1000;
    box-shadow:0 5px 15px rgba(0,0,0,0.5);
}
</style>
</head>
<body>

<header>
    <div class="logo"><img src="images/mrmp logo.png" alt="mrmp logo" width="100px"></div>
    <div>
        <?php if(isset($_SESSION['usuario_id'])): ?>
            Hola, <?= htmlspecialchars($_SESSION['usuario_nombre']) ?> | 
            <a href="perfil.php">Perfil</a>
            <a href="carrito.php">Carrito (<?= array_sum($_SESSION['carrito'] ?? []) ?>)</a>
            <a href="logout.php">Cerrar sesión</a>
        <?php else: ?>
            <a href="inicio_secion.php">Iniciar sesión</a>
            <a href="register.php">Crear cuenta</a>
        <?php endif; ?>
    </div>
</header>

<main>
<?php if($mensaje): ?>
<div class="modal-mensaje"><?= htmlspecialchars($mensaje) ?></div>
<?php endif; ?>

<!-- Marcas -->
<div class="marcas-menu">
<?php foreach($marcas as $id=>$nombre): ?>
<form method="get">
<input type="hidden" name="marca" value="<?= $id ?>">
<button type="submit"><?= htmlspecialchars($nombre) ?></button>
</form>
<?php endforeach; ?>
<form method="get"><button type="submit">Todas</button></form>
</div>

<!-- Buscador -->
<form class="buscar-form" method="get">
<input type="text" name="buscar" placeholder="Buscar piezas..." value="<?= htmlspecialchars($busqueda) ?>">
<button type="submit">Buscar</button>
</form>

<!-- Piezas -->
<div class="piezas">
<?php if(count($piezas)==0): ?>
<p>No se encontraron piezas.</p>
<?php else: ?>
<?php foreach($piezas as $p): ?>
<div class="pieza">
    <?php if(!empty($p['imagen'])): ?>
    <a href="#img-<?= $p['id'] ?>"><img src="uploads/<?= htmlspecialchars($p['imagen']) ?>" alt="<?= htmlspecialchars($p['nombre']) ?>"></a>
    <?php endif; ?>
    <h3><?= htmlspecialchars($p['nombre']) ?></h3>
    <p>Precio: $<?= number_format($p['precio'],2) ?></p>
    <p>Stock: <?= intval($p['cantidad']) ?></p>
    <a href="#desc-<?= $p['id'] ?>">Ver descripción</a>

    <!-- Agregar al carrito -->
    <?php if(isset($_SESSION['usuario_id'])): ?>
    <form method="get" style="margin-top:5px;">
        <input type="hidden" name="agregar" value="<?= intval($p['id']) ?>">
        <button type="submit">Agregar al carrito</button>
    </form>
    <?php else: ?>
    <p style="color:red; font-size:14px;">Inicia sesión para agregar al carrito</p>
    <?php endif; ?>
</div>

<!-- Lightbox imagen -->
<?php if(!empty($p['imagen'])): ?>
<div class="lightbox" id="img-<?= $p['id'] ?>">
    <a href="#"><img src="uploads/<?= htmlspecialchars($p['imagen']) ?>" alt="<?= htmlspecialchars($p['nombre']) ?>"></a>
</div>
<?php endif; ?>

<!-- Modal descripción -->
<div class="modal-desc" id="desc-<?= $p['id'] ?>">
    <div class="modal-desc-content">
        <h3><?= htmlspecialchars($p['nombre']) ?></h3>
        <p><?= nl2br(htmlspecialchars($p['descripcion'])) ?></p>
        <a href="#!" class="modal-close">X</a>
    </div>
</div>

<?php endforeach; ?>
<?php endif; ?>
</div>

</main>
<footer>
&copy; <?= date('Y') ?> MRMC
</footer>
</body>
</html>
