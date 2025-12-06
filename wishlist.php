<?php
// session_start(): Inicia o reanuda la sesión.
session_start();

// require_once: Incluye la conexión a la base de datos.
require_once "conexion.php";

// Verificación de logueo.
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
    <title>Lista de Deseos - MRMP</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="main.css" rel="stylesheet">
    <link href="perfil.css" rel="stylesheet">
</head>
<body class="bg-white">

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white sticky-top shadow-sm">
        <div class="container">
            <a class="navbar-brand" href="pagina-principal.php">
                <img src="img/mrmp logo.png" alt="MRMP" height="50">
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
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">Mi Cuenta</a>
                            <ul class="dropdown-menu dropdown-menu-end border-0 shadow">
                                <li><a class="dropdown-item" href="perfil.php">Mis Datos</a></li>
                                <li><a class="dropdown-item" href="mis_pedidos.php">Mis Pedidos</a></li>
                                <li><a class="dropdown-item active" href="wishlist.php">Lista de Deseos</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="pagina-principal.php?logout=1">Salir</a></li>
                            </ul>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <div class="container my-5">
        <h2 class="mb-4 text-dark fw-bold"><i class="fas fa-heart me-2 text-danger"></i>Mi Lista de Deseos</h2>
        
        <div class="card border-0 shadow-sm bg-white">
            <div class="card-body p-4">
                <?php
                $sql_wish = "SELECT w.id as wish_id, p.* 
                             FROM wishlist w 
                             JOIN piezas p ON w.pieza_id = p.id 
                             WHERE w.usuario_id = ? 
                             ORDER BY w.fecha_agregado DESC";
                $stmt_wish = $conexion->prepare($sql_wish);
                $stmt_wish->bind_param("i", $user_id);
                $stmt_wish->execute();
                $res_wish = $stmt_wish->get_result();
                ?>
                
                <div class="row g-3">
                    <?php if ($res_wish->num_rows > 0): ?>
                        <?php while ($w = $res_wish->fetch_assoc()): ?>
                            <div class="col-md-6 col-lg-4">
                                <div class="card h-100 border shadow-sm bg-white">
                                    <div class="card-body d-flex align-items-center"> 
                                        <!-- Imagen pequeña -->
                                        <div class="flex-shrink-0 me-3">
                                            <?php if (!empty($w['imagen'])): ?>
                                                <img src="uploads/<?= htmlspecialchars($w['imagen']) ?>" alt="Pieza" class="rounded" style="width: 70px; height: 70px; object-fit: cover;">
                                            <?php else: ?>
                                                <div class="bg-light rounded d-flex align-items-center justify-content-center" style="width: 70px; height: 70px;">
                                                    <i class="fas fa-cogs text-muted"></i>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <!-- Info -->
                                        <div class="flex-grow-1">
                                            <h6 class="mb-1 fw-bold text-dark"><?= htmlspecialchars($w['nombre']) ?></h6>
                                            <div class="text-primary fw-bold mb-2">$<?= number_format($w['precio'], 2) ?></div>
                                            
                                            <div class="btn-group btn-group-sm">
                                                <a href="dashboard-piezas.php?agregar=<?= $w['id'] ?>" class="btn btn-outline-primary" title="Agregar al carrito">
                                                    Agregar
                                                </a>
                                                <button onclick="eliminarWishlist(<?= $w['wish_id'] ?>)" class="btn btn-outline-danger" title="Eliminar">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="col-12 text-center py-5">
                            <i class="fas fa-heart-broken fa-4x text-muted opacity-25 mb-3"></i>
                            <h4 class="text-muted">Tu lista de deseos está vacía.</h4>
                            <p class="text-secondary">Guarda aquí lo que quieras comprar después.</p>
                            <a href="dashboard-piezas.php" class="btn btn-primary rounded-pill px-4">Explorar Piezas</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Función JS para eliminar items de Wishlist.
        function eliminarWishlist(id) {
            if(confirm('¿Quitar de la lista de deseos?')) {
                fetch('wishlist_action.php?action=remove&id=' + id)
                .then(res => res.json())
                .then(data => {
                    if(data.success) location.reload(); 
                    else alert('Error al eliminar');
                });
            }
        }
    </script>
</body>
</html>
