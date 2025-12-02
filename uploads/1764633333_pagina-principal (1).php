<?php
// Abrimos PHP para escribir código del lado del servidor
session_start();
// Iniciamos la sesión para poder acceder a las variables de sesión ($_SESSION)
?>
<!-- Cerramos el código PHP -->
<!DOCTYPE html>
<!-- Declaramos que este es un documento HTML5 -->
<html lang="es">
<!-- Abrimos la etiqueta HTML y le decimos que el idioma es español -->
<head>
<!-- Abrimos la sección head donde van los metadatos -->
    <meta charset="UTF-8">
<!-- Definimos que el charset es UTF-8 para que se vean bien los acentos -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
<!-- Hacemos que la página sea responsive (se adapte a celulares) -->
    <title>Lab-Explorer - Plataforma de Conocimiento Científico</title>
<!-- Título que aparece en la pestaña del navegador -->
    
<!-- Línea vacía -->
    <!-- Fonts -->
<!-- Comentario que indica que aquí van las fuentes -->
    <link href="https://fonts.googleapis.com" rel="preconnect">
<!-- Pre-conectamos con Google Fonts para que cargue más rápido -->
    <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin">
<!-- Pre-conectamos con el CDN de Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&family=Nunito:ital,wght@0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
<!-- Cargamos las fuentes Roboto, Poppins y Nunito de Google Fonts -->
    
<!-- Línea vacía -->
    <!-- Bootstrap Icons -->
<!-- Comentario que indica que aquí van los iconos de Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
<!-- Cargamos los iconos de Bootstrap desde un CDN -->
    
<!-- Línea vacía -->
    <!-- Bootstrap CSS -->
<!-- Comentario que indica que aquí va el CSS de Bootstrap -->
    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
<!-- Cargamos el framework Bootstrap para que todo se vea ordenado -->
    
<!-- Línea vacía -->
    <!-- Main CSS -->
<!-- Comentario que indica que aquí van nuestros archivos CSS -->
    <link href="assets/css/main.css" rel="stylesheet">
<!-- Cargamos nuestro archivo CSS principal -->
    <link rel="stylesheet" href="assets/css-admins/admin.css">
<!-- Cargamos el CSS de administración -->
    
<!-- Línea vacía -->
    <style>
/* Abrimos una etiqueta style para escribir CSS personalizado */
        /* Estilos para la página principal de bienvenida */
/* Comentario CSS para indicar que estos estilos son específicos de esta página */
        .hero-section {
/* Clase para la sección principal de bienvenida */
            background-image: url('assets/img/fondo-inicio-registro/registro-inicio.png');
/* Imagen de fondo del laboratorio (la misma del inicio de sesión) */
            background-position: center center;
/* Centramos la imagen en medio de la sección */
            background-repeat: no-repeat;
/* Evitamos que la imagen se repita */
            background-size: cover;
/* Hacemos que la imagen cubra toda la sección sin deformarse */
            background-attachment: scroll;
/* La imagen hace scroll con la página (no se queda fija) */
            min-height: 100vh;
/* Altura mínima del 100% de la pantalla */
            display: flex;
/* Usamos flexbox para centrar el contenido */
            align-items: center;
/* Centramos verticalmente */
            position: relative;
/* Posición relativa para poder poner elementos encima */
            overflow: hidden;
/* Escondemos lo que se salga */
            padding-top: 80px;
/* Espacio arriba para el header */
        }
/* Cerramos la clase hero-section */
        
/* Línea vacía */
        .hero-content {
/* Clase para el contenido del hero */
            position: relative;
/* Posición relativa para que esté encima del patrón */
            z-index: 2;
/* Nivel de apilamiento 2 (encima del fondo) */
            color: #212529;
/* Texto gris oscuro */
            text-align: center;
/* Texto centrado */
            padding: 60px 20px;
/* Espaciado interno: 60px arriba/abajo, 20px izquierda/derecha */
        }
/* Cerramos la clase hero-content */
        
/* Línea vacía */
        .hero-title {
/* Clase para el título principal del hero */
            font-family: 'Nunito', sans-serif;
/* Fuente Nunito */
            font-size: 3.5rem;
/* Tamaño muy grande (3.5 veces el tamaño base) */
            font-weight: 800;
/* Texto muy grueso (bold) */
            margin-bottom: 1.5rem;
/* Espacio de 1.5rem abajo */
            line-height: 1.1;
/* Altura de línea ajustada */
            background: linear-gradient(135deg, #7390A0 0%, #5a7080 100%);
/* Degradado azul para el texto */
            -webkit-background-clip: text;
/* Recortamos el fondo al texto (Safari/Chrome) */
            -webkit-text-fill-color: transparent;
/* Hacemos el texto transparente para ver el degradado */
            background-clip: text;
/* Recortamos el fondo al texto (estándar) */
            text-shadow: none;
/* Sin sombra de texto para que el degradado se vea mejor */
        }
/* Cerramos la clase hero-title */
        
/* Línea vacía */
        .hero-subtitle {
/* Clase para el subtítulo del hero */
            font-size: 1.5rem;
/* Tamaño mediano-grande */
            font-weight: 600;
/* Texto semi-bold aumentado */
            margin-bottom: 2rem;
/* Espacio de 2rem abajo */
            color: #7390A0;
/* Color azul principal */
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
/* Sombra de texto sutil para mejor legibilidad */
        }
/* Cerramos la clase hero-subtitle */
        
/* Línea vacía */
        .hero-description {
/* Clase para la descripción del hero */
            font-size: 1.1rem;
/* Tamaño un poco más grande que el normal */
            max-width: 700px;
/* Ancho máximo de 700px para que no se vea muy estirado */
            margin: 0 auto 3rem;
/* Centramos horizontalmente y agregamos 3rem de margen abajo */
            line-height: 1.8;
/* Espaciado entre líneas */
            color: #333;
/* Color gris oscuro para mejor contraste */
            font-weight: 400;
/* Peso de fuente normal */
            text-shadow: 0 1px 2px rgba(255,255,255,0.8);
/* Sombra de texto blanca para mejor legibilidad sobre la imagen */
        }
/* Cerramos la clase hero-description */
        
/* Línea vacía */
        .btn-hero {
/* Clase para el botón principal del hero */
            background: linear-gradient(135deg, #7390A0 0%, #5a7080 100%);
/* Fondo con degradado azul */
            color: white;
/* Texto blanco */
            padding: 18px 45px;
/* Espaciado interno: 18px arriba/abajo, 45px izquierda/derecha */
            font-size: 1.2rem;
/* Tamaño de fuente grande */
            font-weight: 600;
/* Texto semi-bold */
            border-radius: 50px;
/* Esquinas muy redondeadas (forma de píldora) */
            text-decoration: none;
/* Sin subrayado */
            display: inline-block;
/* Se comporta como bloque pero en línea */
            transition: all 0.3s ease;
/* Animación suave de 0.3 segundos para todos los cambios */
            box-shadow: 0 8px 25px rgba(115, 144, 160, 0.4);
/* Sombra azulada más pronunciada para que se vea elevado */
            border: none;
/* Sin borde */
            position: relative;
/* Posición relativa para efectos */
            overflow: hidden;
/* Esconder desbordes para efectos */
        }
/* Cerramos la clase btn-hero */
        
/* Línea vacía */
        .btn-hero::before {
/* Pseudo-elemento para efecto de brillo */
            content: '';
/* Contenido vacío */
            position: absolute;
/* Posición absoluta */
            top: 0;
/* Arriba */
            left: -100%;
/* Fuera a la izquierda */
            width: 100%;
/* Ancho completo */
            height: 100%;
/* Alto completo */
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
/* Gradiente de brillo */
            transition: left 0.5s ease;
/* Transición del efecto */
        }
/* Cerramos pseudo-elemento */
        
/* Línea vacía */
        .btn-hero:hover {
/* Estilos cuando pasas el mouse sobre el botón */
            transform: translateY(-3px) scale(1.05);
/* Movemos el botón 3px hacia arriba y lo agrandamos 5% */
            box-shadow: 0 12px 35px rgba(115, 144, 160, 0.5);
/* Sombra más grande y más azulada */
            color: white;
/* Mantenemos el texto blanco */
        }
/* Cerramos el hover del botón */
        
/* Línea vacía */
        .btn-hero:hover::before {
/* Efecto de brillo al hacer hover */
            left: 100%;
/* Se mueve hacia la derecha */
        }
/* Cerramos hover del pseudo-elemento */
        
/* Línea vacía */
        .features-section {
/* Clase para la sección de características */
            padding: 80px 0;
/* Espaciado interno: 80px arriba/abajo, 0 izquierda/derecha */
            background: #f8f9fa;
/* Fondo gris muy claro */
        }
/* Cerramos la clase features-section */
        
/* Línea vacía */
        .feature-card {
/* Clase para cada tarjeta de característica */
            background: white;
/* Fondo blanco */
            padding: 40px 30px;
/* Espaciado interno: 40px arriba/abajo, 30px izquierda/derecha */
            border-radius: 15px;
/* Esquinas redondeadas */
            text-align: center;
/* Texto centrado */
            transition: all 0.3s ease;
/* Animación suave */
            height: 100%;
/* Altura del 100% del contenedor */
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
/* Sombra muy suave */
        }
/* Cerramos la clase feature-card */
        
/* Línea vacía */
        .feature-card:hover {
/* Estilos cuando pasas el mouse sobre la tarjeta */
            transform: translateY(-10px);
/* Movemos la tarjeta 10px hacia arriba */
            box-shadow: 0 10px 30px rgba(115, 144, 160, 0.2);
/* Sombra más grande con el color principal */
        }
/* Cerramos el hover de la tarjeta */
        
/* Línea vacía */
        .feature-icon {
/* Clase para el icono de la característica */
            font-size: 3rem;
/* Tamaño muy grande */
            color: #7390A0;
/* Color azul principal */
            margin-bottom: 1.5rem;
/* Espacio de 1.5rem abajo */
        }
/* Cerramos la clase feature-icon */
        
/* Línea vacía */
        .feature-title {
/* Clase para el título de la característica */
            font-size: 1.5rem;
/* Tamaño mediano-grande */
            font-weight: 600;
/* Texto semi-bold */
            margin-bottom: 1rem;
/* Espacio de 1rem abajo */
            color: #333;
/* Color gris oscuro */
        }
/* Cerramos la clase feature-title */
        
/* Línea vacía */
        .feature-description {
/* Clase para la descripción de la característica */
            color: #666;
/* Color gris medio */
            line-height: 1.6;
/* Espaciado entre líneas */
        }
/* Cerramos la clase feature-description */
        
/* Línea vacía */
        .stats-section {
/* Clase para la sección de estadísticas */
            background: linear-gradient(135deg, #7390A0 0%, #5a7080 100%);
/* Fondo con degradado azul */
            color: white;
/* Texto blanco */
            padding: 60px 0;
/* Espaciado interno: 60px arriba/abajo */
        }
/* Cerramos la clase stats-section */
        
/* Línea vacía */
        .stat-item {
/* Clase para cada item de estadística */
            text-align: center;
/* Texto centrado */
            padding: 20px;
/* Espaciado interno de 20px */
        }
/* Cerramos la clase stat-item */
        
/* Línea vacía */
        .stat-number {
/* Clase para el número de la estadística */
            font-size: 3rem;
/* Tamaño muy grande */
            font-weight: 700;
/* Texto muy grueso */
            margin-bottom: 0.5rem;
/* Espacio de 0.5rem abajo */
        }
/* Cerramos la clase stat-number */
        
/* Línea vacía */
        .stat-label {
/* Clase para la etiqueta de la estadística */
            font-size: 1.1rem;
/* Tamaño un poco más grande que el normal */
            opacity: 0.9;
/* 90% opaco */
        }
/* Cerramos la clase stat-label */
        
/* Línea vacía */
        @media (max-width: 768px) {
/* Media query para pantallas de máximo 768px (celulares y tablets) */
            .hero-title {
/* Estilos para el título en pantallas pequeñas */
                font-size: 2.5rem;
/* Hacemos el título más pequeño */
            }
/* Cerramos los estilos del título */
            
/* Línea vacía */
            .hero-subtitle {
/* Estilos para el subtítulo en pantallas pequeñas */
                font-size: 1.2rem;
/* Hacemos el subtítulo más pequeño */
            }
/* Cerramos los estilos del subtítulo */
            
/* Línea vacía */
            .hero-description {
/* Estilos para la descripción en pantallas pequeñas */
                font-size: 1rem;
/* Tamaño normal */
            }
/* Cerramos los estilos de la descripción */
        }
/* Cerramos el media query */
    </style>
<!-- Cerramos la etiqueta style -->
</head>
<!-- Cerramos la etiqueta head -->
<body>
<!-- Abrimos la etiqueta body (el cuerpo visible de la página) -->
    <!-- Header -->
<!-- Comentario HTML para el header -->
    <header id="header" class="header position-relative">
<!-- Abrimos el header con id y clases de Bootstrap -->
        <div class="container-fluid container-xl position-relative">
<!-- Contenedor con clases de Bootstrap para el ancho -->
            <div class="top-row d-flex align-items-center justify-content-between">
<!-- Fila superior con flexbox de Bootstrap -->
                <a href="pagina-principal.php" class="logo d-flex align-items-end">
<!-- Link al inicio con clases de flexbox -->
                    <img src="assets/img/logo/nuevologo.ico" alt="logo-lab">
<!-- Imagen del logo -->
                    <h1 class="sitename">Lab-Explorer</h1><span></span>
<!-- Nombre del sitio y un span vacío -->
                </a>
<!-- Cerramos el link del logo -->

<!-- Línea vacía -->
                <div class="d-flex align-items-center">
<!-- Contenedor con flexbox para alinear elementos -->
                    <div class="social-links">
<!-- Contenedor para los links sociales y opciones de usuario -->
                        <a href="#" title="Facebook"><i class="bi bi-facebook"></i></a>
<!-- Link a Facebook con icono -->
                        <a href="#" title="Twitter"><i class="bi bi-twitter"></i></a>
<!-- Link a Twitter con icono -->
                        <a href="#" title="Instagram"><i class="bi bi-instagram"></i></a>
<!-- Link a Instagram con icono -->
                        
<!-- Línea vacía -->
                        <?php if (isset($_SESSION['usuario_id'])): ?>
<!-- Si hay un usuario logueado (si existe la variable de sesión) -->
                            <span class="saludo">Hola, <?= htmlspecialchars($_SESSION['usuario_nombre']) ?></span>
<!-- Mostramos un saludo con el nombre del usuario (htmlspecialchars previene ataques XSS) -->
                            <a href="./forms/perfil.php">Perfil</a>
<!-- Link al perfil del usuario -->
                            <a href="forms/logout.php" class="btn-publicador">
<!-- Link para cerrar sesión -->
                                <i class="bi bi-box-arrow-right"></i>
<!-- Icono de salida -->
                                Cerrar Sesión
<!-- Texto del botón -->
                            </a>
<!-- Cerramos el link de cerrar sesión -->
                        <?php else: ?>
<!-- Si NO hay usuario logueado -->
                            <a href="forms/inicio-sesion.php" class="btn-publicador">
<!-- Link para iniciar sesión -->
                                <i class="bi bi-box-arrow-in-right"></i>
<!-- Icono de entrada -->
                                Inicia sesión
<!-- Texto del botón -->
                            </a>
<!-- Cerramos el link de iniciar sesión -->
                            <a href="forms/register.php" class="btn-publicador">
<!-- Link para crear cuenta -->
                                <i class="bi bi-person-plus"></i>
<!-- Icono de persona con plus -->
                                Crear Cuenta
<!-- Texto del botón -->
                            </a>
<!-- Cerramos el link de crear cuenta -->
                        <?php endif; ?>
<!-- Cerramos el if/else de usuario logueado -->
                        
<!-- Línea vacía -->
                        <span style="color: var(--border); margin: 0 5px;">|</span>
<!-- Separador visual (una línea vertical) -->
                        
<!-- Línea vacía -->
                        <a href="forms/publicadores/inicio-sesion-publicadores.php" class="btn-publicador">
<!-- Link para publicadores -->
                            <i class="bi bi-pencil-square"></i>
<!-- Icono de lápiz -->
                            ¿Eres publicador?
<!-- Texto del link -->
                        </a>
<!-- Cerramos el link de publicadores -->
                    </div>
<!-- Cerramos el contenedor de social-links -->
                </div>
<!-- Cerramos el contenedor de flexbox -->
            </div>
<!-- Cerramos la fila superior -->
        </div>
<!-- Cerramos el contenedor del header -->
    </header>
<!-- Cerramos el header -->

<!-- Línea vacía -->
    <!-- Hero Section -->
<!-- Comentario HTML para la sección principal de bienvenida -->
    <section class="hero-section">
<!-- Abrimos la sección hero -->
        <div class="container">
<!-- Contenedor de Bootstrap para el ancho -->
            <div class="hero-content" data-aos="fade-up">
<!-- Contenido del hero con animación de aparecer hacia arriba -->
                <h1 class="hero-title">🧪 Bienvenido a Lab-Explorer</h1>
<!-- Título principal con emoji de laboratorio -->
                <p class="hero-subtitle">Tu Plataforma de Conocimiento Científico en Laboratorio Clínico</p>
<!-- Subtítulo descriptivo -->
                <p class="hero-description">
<!-- Descripción detallada -->
                    Descubre, aprende y comparte conocimiento científico de calidad. 
<!-- Primera línea de la descripción -->
                    Lab-Explorer es una plataforma dedicada a profesionales y estudiantes del área de laboratorio clínico, 
<!-- Segunda línea explicando a quién va dirigido -->
                    donde encontrarás artículos, casos clínicos y recursos educativos verificados por expertos.
<!-- Tercera línea explicando qué encontrarás -->
                </p>
<!-- Cerramos el párrafo de descripción -->
                <a href="index.php" class="btn-hero">
<!-- Botón principal que lleva a las publicaciones -->
                    <i class="bi bi-book me-2"></i>
<!-- Icono de libro con margen a la derecha -->
                    Explorar Publicaciones
<!-- Texto del botón -->
                </a>
<!-- Cerramos el botón -->
            </div>
<!-- Cerramos el contenido del hero -->
        </div>
<!-- Cerramos el contenedor -->
    </section>
<!-- Cerramos la sección hero -->

<!-- Línea vacía -->
    <!-- Features Section -->
<!-- Comentario HTML para la sección de características -->
    <section class="features-section">
<!-- Abrimos la sección de características -->
        <div class="container">
<!-- Contenedor de Bootstrap -->
            <div class="section-title text-center mb-5" data-aos="fade-up">
<!-- Título de la sección centrado con margen abajo y animación -->
                <h2>¿Qué Encontrarás en Lab-Explorer?</h2>
<!-- Título de la sección -->
                <p class="text-muted">Una plataforma completa para el aprendizaje y desarrollo profesional</p>
<!-- Subtítulo en gris claro -->
            </div>
<!-- Cerramos el título de la sección -->

<!-- Línea vacía -->
            <div class="row g-4">
<!-- Fila con gap de 4 (espacio entre columnas) -->
                <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="100">
<!-- Columna: 4 de 12 en pantallas grandes, 6 de 12 en medianas, con animación retrasada 100ms -->
                    <div class="feature-card">
<!-- Tarjeta de característica -->
                        <div class="feature-icon">
<!-- Contenedor del icono -->
                            <i class="bi bi-journal-medical"></i>
<!-- Icono de diario médico -->
                        </div>
<!-- Cerramos el contenedor del icono -->
                        <h3 class="feature-title">Artículos Científicos</h3>
<!-- Título de la característica -->
                        <p class="feature-description">
<!-- Descripción de la característica -->
                            Accede a artículos de investigación y revisión en diversas áreas del laboratorio clínico, 
<!-- Primera línea -->
                            escritos por profesionales experimentados y validados por expertos.
<!-- Segunda línea -->
                        </p>
<!-- Cerramos la descripción -->
                    </div>
<!-- Cerramos la tarjeta -->
                </div>
<!-- Cerramos la columna -->

<!-- Línea vacía -->
                <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="200">
<!-- Segunda columna con retraso de 200ms -->
                    <div class="feature-card">
<!-- Tarjeta de característica -->
                        <div class="feature-icon">
<!-- Contenedor del icono -->
                            <i class="bi bi-clipboard2-pulse"></i>
<!-- Icono de portapapeles con pulso -->
                        </div>
<!-- Cerramos el contenedor del icono -->
                        <h3 class="feature-title">Casos Clínicos</h3>
<!-- Título de la característica -->
                        <p class="feature-description">
<!-- Descripción -->
                            Estudia casos clínicos reales que te ayudarán a desarrollar tu capacidad de análisis 
<!-- Primera línea -->
                            y toma de decisiones en situaciones prácticas del laboratorio.
<!-- Segunda línea -->
                        </p>
<!-- Cerramos la descripción -->
                    </div>
<!-- Cerramos la tarjeta -->
                </div>
<!-- Cerramos la columna -->

<!-- Línea vacía -->
                <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="300">
<!-- Tercera columna con retraso de 300ms -->
                    <div class="feature-card">
<!-- Tarjeta de característica -->
                        <div class="feature-icon">
<!-- Contenedor del icono -->
                            <i class="bi bi-people"></i>
<!-- Icono de personas -->
                        </div>
<!-- Cerramos el contenedor del icono -->
                        <h3 class="feature-title">Comunidad de Expertos</h3>
<!-- Título de la característica -->
                        <p class="feature-description">
<!-- Descripción -->
                            Conéctate con profesionales del área, comparte conocimientos y mantente actualizado 
<!-- Primera línea -->
                            con las últimas tendencias en laboratorio clínico.
<!-- Segunda línea -->
                        </p>
<!-- Cerramos la descripción -->
                    </div>
<!-- Cerramos la tarjeta -->
                </div>
<!-- Cerramos la columna -->

<!-- Línea vacía -->
                <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="400">
<!-- Cuarta columna con retraso de 400ms -->
                    <div class="feature-card">
<!-- Tarjeta de característica -->
                        <div class="feature-icon">
<!-- Contenedor del icono -->
                            <i class="bi bi-folder2-open"></i>
<!-- Icono de carpeta abierta -->
                        </div>
<!-- Cerramos el contenedor del icono -->
                        <h3 class="feature-title">Categorías Especializadas</h3>
<!-- Título de la característica -->
                        <p class="feature-description">
<!-- Descripción -->
                            Contenido organizado por áreas: Hematología, Bacteriología, Parasitología, 
<!-- Primera línea -->
                            Serie Roja, Toma de Muestras y más.
<!-- Segunda línea -->
                        </p>
<!-- Cerramos la descripción -->
                    </div>
<!-- Cerramos la tarjeta -->
                </div>
<!-- Cerramos la columna -->

<!-- Línea vacía -->
                <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="500">
<!-- Quinta columna con retraso de 500ms -->
                    <div class="feature-card">
<!-- Tarjeta de característica -->
                        <div class="feature-icon">
<!-- Contenedor del icono -->
                            <i class="bi bi-shield-check"></i>
<!-- Icono de escudo con check -->
                        </div>
<!-- Cerramos el contenedor del icono -->
                        <h3 class="feature-title">Contenido Verificado</h3>
<!-- Título de la característica -->
                        <p class="feature-description">
<!-- Descripción -->
                            Todas las publicaciones pasan por un proceso de revisión por parte de administradores 
<!-- Primera línea -->
                            para garantizar la calidad y veracidad de la información.
<!-- Segunda línea -->
                        </p>
<!-- Cerramos la descripción -->
                    </div>
<!-- Cerramos la tarjeta -->
                </div>
<!-- Cerramos la columna -->

<!-- Línea vacía -->
                <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="600">
<!-- Sexta columna con retraso de 600ms -->
                    <div class="feature-card">
<!-- Tarjeta de característica -->
                        <div class="feature-icon">
<!-- Contenedor del icono -->
                            <i class="bi bi-pencil-square"></i>
<!-- Icono de lápiz cuadrado -->
                        </div>
<!-- Cerramos el contenedor del icono -->
                        <h3 class="feature-title">Publica tu Conocimiento</h3>
<!-- Título de la característica -->
                        <p class="feature-description">
<!-- Descripción -->
                            ¿Eres profesional del área? Regístrate como publicador y comparte tu experiencia 
<!-- Primera línea -->
                            y conocimientos con la comunidad científica.
<!-- Segunda línea -->
                        </p>
<!-- Cerramos la descripción -->
                    </div>
<!-- Cerramos la tarjeta -->
                </div>
<!-- Cerramos la columna -->
            </div>
<!-- Cerramos la fila -->
        </div>
<!-- Cerramos el contenedor -->
    </section>
<!-- Cerramos la sección de características -->

<!-- Línea vacía -->
    <!-- Stats Section -->
<!-- Comentario HTML para la sección de estadísticas -->
    <section class="stats-section">
<!-- Abrimos la sección de estadísticas -->
        <div class="container">
<!-- Contenedor de Bootstrap -->
            <div class="row">
<!-- Fila de Bootstrap -->
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="100">
<!-- Columna: 4 de 12 en pantallas medianas, con animación retrasada 100ms -->
                    <div class="stat-item">
<!-- Item de estadística -->
                        <div class="stat-number">
<!-- Número de la estadística -->
                            <i class="bi bi-file-earmark-text"></i>
<!-- Icono de archivo de texto -->
                        </div>
<!-- Cerramos el número -->
                        <div class="stat-label">Publicaciones Científicas</div>
<!-- Etiqueta de la estadística -->
                    </div>
<!-- Cerramos el item -->
                </div>
<!-- Cerramos la columna -->
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="200">
<!-- Segunda columna con retraso de 200ms -->
                    <div class="stat-item">
<!-- Item de estadística -->
                        <div class="stat-number">
<!-- Número de la estadística -->
                            <i class="bi bi-people"></i>
<!-- Icono de personas -->
                        </div>
<!-- Cerramos el número -->
                        <div class="stat-label">Comunidad de Profesionales</div>
<!-- Etiqueta de la estadística -->
                    </div>
<!-- Cerramos el item -->
                </div>
<!-- Cerramos la columna -->
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="300">
<!-- Tercera columna con retraso de 300ms -->
                    <div class="stat-item">
<!-- Item de estadística -->
                        <div class="stat-number">
<!-- Número de la estadística -->
                            <i class="bi bi-bookmark-check"></i>
<!-- Icono de marcador con check -->
                        </div>
<!-- Cerramos el número -->
                        <div class="stat-label">Contenido Verificado</div>
<!-- Etiqueta de la estadística -->
                    </div>
<!-- Cerramos el item -->
                </div>
<!-- Cerramos la columna -->
            </div>
<!-- Cerramos la fila -->
        </div>
<!-- Cerramos el contenedor -->
    </section>
<!-- Cerramos la sección de estadísticas -->

<!-- Línea vacía -->
    <!-- CTA Section -->
<!-- Comentario HTML para la sección de llamada a la acción -->
    <section class="py-5 bg-white">
<!-- Sección con padding vertical de 5 y fondo blanco -->
        <div class="container text-center" data-aos="fade-up">
<!-- Contenedor centrado con animación -->
            <h2 class="mb-4">¿Listo para Comenzar?</h2>
<!-- Título con margen abajo de 4 -->
            <p class="lead mb-4 text-muted">
<!-- Párrafo grande con margen abajo y color gris -->
                Únete a nuestra comunidad y accede a contenido científico de calidad
<!-- Texto de la llamada a la acción -->
            </p>
<!-- Cerramos el párrafo -->
            <div class="d-flex gap-3 justify-content-center flex-wrap">
<!-- Contenedor flexbox con gap de 3, centrado y que se envuelve en pantallas pequeñas -->
                <a href="index.php" class="btn btn-primary btn-lg">
<!-- Botón primario grande que lleva a las publicaciones -->
                    <i class="bi bi-book me-2"></i>
<!-- Icono de libro con margen a la derecha -->
                    Ver Publicaciones
<!-- Texto del botón -->
                </a>
<!-- Cerramos el botón -->
                <a href="forms/register.php" class="btn btn-outline-primary btn-lg">
<!-- Botón outline (solo borde) primario grande -->
                    <i class="bi bi-person-plus me-2"></i>
<!-- Icono de persona con plus -->
                    Registrarse
<!-- Texto del botón -->
                </a>
<!-- Cerramos el botón -->
            </div>
<!-- Cerramos el contenedor de botones -->
        </div>
<!-- Cerramos el contenedor -->
    </section>
<!-- Cerramos la sección CTA -->
</body>
<!-- Cerramos el body -->
</html>
<!-- Cerramos el HTML -->
