<?php
session_start();
require_once "conexion.php";

if(isset($_GET['logout'])){
    session_destroy();
    header("Location: dashboard-piezas.php");
    exit;
}

if(isset($_SESSION['usuario_id']) && !isset($_SESSION['carrito'])) $_SESSION['carrito'] = [];

// Agregar pieza al carrito
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
                $_SESSION['mensaje'] = "⚠️ No hay stock suficiente.";
            }
        }
    }
    header("Location: dashboard-piezas.php");
    exit;
}

// Filtros y búsqueda
$busqueda = trim($_GET['buscar'] ?? '');
$marca_id = intval($_GET['marca'] ?? 0);

// Marcas
$marcas_res = $conexion->query("SELECT * FROM marcas ORDER BY nombre");
$marcas = [];
while($m = $marcas_res->fetch_assoc()){
    $marcas[$m['id']] = $m['nombre'];
}

// Piezas
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
if(count($params)>0) $stmt->bind_param($tipos,...$params);
$stmt->execute();
$res = $stmt->get_result();
$piezas = $res->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$mensaje = $_SESSION['mensaje'] ?? '';
unset($_SESSION['mensaje']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard MRMP</title>
<link rel="stylesheet" href="dashboard.css">
</head>

<script>
// Espera a que toda la página haya cargado
document.addEventListener('DOMContentLoaded', () => {

  // Busca todos los enlaces con la clase "ver-desc"
  document.querySelectorAll('.ver-desc').forEach(link => {

    // Al hacer clic en cualquiera de esos enlaces
    link.addEventListener('click', e => {
      e.preventDefault(); // Evita que el enlace cambie la URL

      // Obtiene el valor del "href" (por ejemplo "#desc-5") y le quita el "#"
      const targetId = link.getAttribute('href').substring(1);

      // Busca el modal correspondiente con ese ID
      const modal = document.getElementById(targetId);

      // Si el modal existe, lo muestra
      if (modal) {
        modal.style.display = 'flex'; // Cambia el display para hacerlo visible
      }
    });
  });

  // Busca todos los botones o enlaces para cerrar el modal (la "X")
  document.querySelectorAll('.modal-close').forEach(btn => {

    // Cuando se hace clic en cerrar
    btn.addEventListener('click', e => {
      e.preventDefault(); // Evita que el enlace haga algo raro

      // Busca el contenedor del modal y lo oculta
      btn.closest('.modal-desc').style.display = 'none';
    });
  });

  // Permite cerrar el modal si se hace clic fuera del cuadro de contenido
  document.querySelectorAll('.modal-desc').forEach(modal => {
    modal.addEventListener('click', e => {

      // Si se hace clic directamente sobre el fondo oscuro (no el contenido)
      if (e.target === modal) {
        modal.style.display = 'none'; // Cierra el modal
      }
    });
  });
});
</script>

<body>

<header>
  <div class="logo">
    <img src="img/mrmp-logo.png" alt="MRMP logo">
    <p>Mexican Racing Motor Parts</p>
  </div>
  <div class="usuario">
    <?php if(isset($_SESSION['usuario_id'])): ?>
      <span class="saludo">Hola, <?= htmlspecialchars($_SESSION['usuario_nombre']) ?></span>
      <a href="perfil.php">Perfil</a>
      <a href="carrito.php">Carrito (<?= array_sum($_SESSION['carrito'] ?? []) ?>)</a>
      <a href="dashboard-piezas.php?logout=1">Cerrar sesión</a>
      <a href="blog.php">Blog</a>
    <?php else: ?>
      <a href="inicio_secion.php">Iniciar sesión</a>
      <a href="register.php">Crear cuenta</a>
      <a href="blog.php">Blog</a>
    <?php endif; ?>
  </div>
</header>

<main>
<?php if($mensaje): ?>
<div class="modal-mensaje"><?= htmlspecialchars($mensaje) ?></div>
<?php endif; ?>

<div class="marcas-menu">
  <?php foreach($marcas as $id=>$nombre): ?>
    <form method="get">
      <input type="hidden" name="marca" value="<?= $id ?>">
      <button type="submit"><?= htmlspecialchars($nombre) ?></button>
    </form>
  <?php endforeach; ?>
  <form method="get"><button type="submit">Todas</button></form>
</div>

<form class="buscar-form" method="get">
  <input type="text" name="buscar" placeholder="Buscar piezas..." value="<?= htmlspecialchars($busqueda) ?>">
  <button type="submit">Buscar</button>
</form>

<div class="piezas">
<?php if(count($piezas) === 0): ?>
  <p>No se encontraron piezas.</p>
<?php else: ?>
  <?php foreach($piezas as $p): ?>
  <div class="pieza">
    <?php if(!empty($p['imagen'])): ?>
    <div class="imagen-container">
      <img src="uploads/<?= htmlspecialchars($p['imagen']) ?>" alt="<?= htmlspecialchars($p['nombre']) ?>">
    </div>
    <?php endif; ?>
    <h3><?= htmlspecialchars($p['nombre']) ?></h3>
    <p class="precio">Precio: $<?= number_format($p['precio'],2) ?></p>
    <p class="stock">Stock: <?= intval($p['cantidad']) ?></p>
    <a href="#desc-<?= $p['id'] ?>" class="ver-desc">Ver descripción</a>

    <?php if(isset($_SESSION['usuario_id'])): ?>
    <form method="get">
      <input type="hidden" name="agregar" value="<?= intval($p['id']) ?>">
      <button type="submit">Agregar al carrito</button>
    </form>
    <?php else: ?>
    <p1 class="login-aviso">Inicia sesión para agregar al carrito</p1>
    <?php endif; ?>

    <div class="modal-desc" id="desc-<?= $p['id'] ?>">
      <div class="modal-desc-content">
        <h3><?= htmlspecialchars($p['nombre']) ?></h3>
        <p><?= nl2br(htmlspecialchars($p['descripcion'])) ?></p>
        <a href="#!" class="modal-close">×</a>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
<?php endif; ?>
</div>

</main>
<footer>
  <div class="footer-redes">
    <a href="https://www.facebook.com/profile.php?id=61583404693123" target="_blank" class="facebook">
      <i class="fab fa-facebook-f"></i>
      <img src="https://upload.wikimedia.org/wikipedia/commons/1/1b/Facebook_icon.svg" alt="Facebook">
      </a>
    </a>
    <a href="" target="_blank" class="instagram">
      <i class="fab fa-instagram-f"></i>
      <img src="https://upload.wikimedia.org/wikipedia/commons/a/a5/Instagram_icon.png" alt="Instagram">
    </a>
    </div>
      <p>© <?= date('Y') ?> <span>Mexican Racing Motor Parts</span></p>
</footer>
</body>
</html>
