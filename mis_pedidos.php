<?php
// session_start(): Inicia una nueva sesión o reanuda la existente.
// Esencial ver quien es el usuario actual ($_SESSION['usuario_id']).
session_start();

// require_once: Carga el archivo de conexión a la base de datos (conexion.php).
require_once "conexion.php";

// VALIDACIÓN DE ACCESO
// Solo usuarios logueados pueden ver sus pedidos.
if (!isset($_SESSION['usuario_id'])) {
    header("Location: inicio_secion.php");
    exit();
}

$user_id = $_SESSION['usuario_id'];
$usuario_nombre = $_SESSION['usuario_nombre'] ?? 'Usuario';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Pedidos - Performance Zone MX</title>
    
    <!-- Bootstrap CSS y FontAwesome -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="main.css" rel="stylesheet">
    <!-- Reutilizamos estilos de perfil para consistencia visual -->
    <link href="perfil.css" rel="stylesheet">
</head>
<body class="bg-white">

    <!-- NAVBAR (Barra de Navegación) -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white sticky-top shadow-sm">
        <div class="container">
            <a class="navbar-brand" href="pagina-principal.php">
                <img src="img/nuevologo.jpeg" alt="Performance Zone MX" height="40" class="d-inline-block align-text-top">
                Performance Zone MX
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navProfile">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navProfile">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item"><a class="nav-link" href="pagina-principal.php">Inicio</a></li>
                    <li class="nav-item"><a class="nav-link" href="dashboard-piezas.php">Piezas</a></li>
                </ul>
                <div class="navbar-nav">
                     <span class="nav-link text-dark fw-bold">
                        <i class="fas fa-user me-2"></i><?= htmlspecialchars($usuario_nombre) ?>
                    </span>
                     <ul class="navbar-nav">
                        <!-- Menú Dropdown -->
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">Mi Cuenta</a>
                            <ul class="dropdown-menu dropdown-menu-end border-0 shadow">
                                <li><a class="dropdown-item" href="perfil.php">Mis Datos</a></li>
                                <li><a class="dropdown-item active" href="mis_pedidos.php">Mis Pedidos</a></li>
                                <li><a class="dropdown-item" href="wishlist.php">Lista de Deseos</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="pagina-principal.php?logout=1">Salir</a></li>
                            </ul>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <!-- CONTENT -->
    <div class="container my-5">
        <h2 class="mb-4 text-dark fw-bold"><i class="fas fa-box-open me-2 text-primary"></i>Historial de Pedidos</h2>
        
        <div class="card border-0 shadow-sm bg-white">
            <div class="card-body p-4">
                <?php
                // CONSULTA PRINCIPAL
                // Obtenemos todos los pedidos hechos por este usuario, ordenados del más reciente al más antiguo.
                $sql_pedidos = "SELECT * FROM pedidos WHERE usuario_id = ? ORDER BY fecha DESC";
                $stmt_pedidos = $conexion->prepare($sql_pedidos);
                $stmt_pedidos->bind_param("i", $user_id);
                $stmt_pedidos->execute();
                $res_pedidos = $stmt_pedidos->get_result();
                ?>
                
                <!-- Si el usuario tiene pedidos... -->
                <?php if ($res_pedidos->num_rows > 0): ?>
                    <!-- .table-responsive: Permite scroll horizontal en pantallas pequeñas. -->
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Fecha</th>
                                    <th>Total</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($pedido = $res_pedidos->fetch_assoc()): ?>
                                    <tr>
                                        <td class="fw-bold">#<?= $pedido['id'] ?></td>
                                        <!-- Formato de Fecha: dd/mm/YYYY -->
                                        <td><?= date('d/m/Y', strtotime($pedido['fecha'])) ?></td>
                                        <td class="text-primary fw-bold">$<?= number_format($pedido['total'], 2) ?></td>
                                        <td>
                                            <?php
                                            // Expresión MATCH (PHP 8.0+): Alternativa moderna y limpia al switch.
                                            // Asigna una clase CSS de Bootstrap basada en el valor de $pedido['estado'].
                                            $estadoClass = match($pedido['estado']) {
                                                'pendiente' => 'bg-warning text-dark', // Amarillo
                                                'enviado' => 'bg-info text-dark',      // Cian
                                                'entregado' => 'bg-success',           // Verde
                                                'cancelado' => 'bg-danger',            // Rojo
                                                default => 'bg-secondary'              // Gris
                                            };
                                            ?>
                                            <span class="badge <?= $estadoClass ?> rounded-pill"><?= ucfirst($pedido['estado']) ?></span>
                                        </td>
                                        <td>
                                            <!-- Botón Colapsable: data-bs-target apunta al ID del div oculto (detalles+ID). -->
                                            <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#detalles<?= $pedido['id'] ?>">
                                                <i class="fas fa-eye"></i> Ver Detalles
                                            </button>
                                        </td>
                                    </tr>
                                    
                                    <!-- FILA DE DETALLES (Oculta por defecto) -->
                                    <tr>
                                        <!-- colspan="5": La celda ocupa el ancho de las 5 columnas de la tabla. -->
                                        <td colspan="5" class="p-0 border-0">
                                            <!-- .collapse: Clase de Bootstrap para ocultar contenido hasta que se active. -->
                                            <div class="collapse bg-white p-3 border-bottom" id="detalles<?= $pedido['id'] ?>">
                                                <h6 class="text-secondary mb-2"><small>Contenido del pedido:</small></h6>
                                                
                                                <ul class="list-group list-group-flush">
                                                    <?php
                                                    // SUB-CONSULTA: Detalles del pedido
                                                    // Buscamos qué piezas específicas conforman este pedido.
                                                    // JOIN con 'piezas' para obtener el nombre del producto.
                                                    $sql_det = "SELECT dp.*, p.nombre 
                                                                FROM detalle_pedidos dp 
                                                                JOIN piezas p ON dp.pieza_id = p.id 
                                                                WHERE dp.pedido_id = ?";
                                                    $stmt_det = $conexion->prepare($sql_det);
                                                    $stmt_det->bind_param("i", $pedido['id']);
                                                    $stmt_det->execute();
                                                    $detalles = $stmt_det->get_result();
                                                    
                                                    // Iteramos sobre los items del pedido
                                                    while ($d = $detalles->fetch_assoc()):
                                                    ?>
                                                        <li class="list-group-item bg-transparent d-flex justify-content-between align-items-center px-0">
                                                            <span>
                                                                <?= htmlspecialchars($d['nombre']) ?> 
                                                                <span class="badge bg-light text-dark border ms-2">x<?= $d['cantidad'] ?></span>
                                                            </span>
                                                            <span class="fw-bold">$<?= number_format($d['precio_unitario'], 2) ?></span>
                                                        </li>
                                                    <?php endwhile; ?>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <!-- MENSAJE DE ESTADO VACÍO -->
                    <div class="text-center py-5">
                        <i class="fas fa-shopping-bag fa-4x text-muted opacity-25 mb-3"></i>
                        <h4 class="text-muted">No tienes pedidos registrados.</h4>
                        <p class="text-secondary mb-4">Explora nuestro catálogo y equipa tu auto.</p>
                        <a href="dashboard-piezas.php" class="btn btn-primary px-4 rounded-pill">Ir a la Tienda</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Bootstrap Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
