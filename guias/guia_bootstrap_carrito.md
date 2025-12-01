# 📚 GUÍA COMPLETA DE BOOTSTRAP CSS + JS EN CARRITO.PHP

## 🎯 ÍNDICE
1. [Introducción a Bootstrap](#intro)
2. [Navbar - Barra de Navegación](#navbar)
3. [Dropdown - Menú Desplegable](#dropdown)
4. [Collapse - Menú Hamburguesa](#collapse)
5. [Grid System - Sistema de Rejilla](#grid)
6. [Cards - Tarjetas](#cards)
7. [Tables - Tablas](#tables)
8. [Buttons - Botones](#buttons)
9. [Forms - Formularios](#forms)
10. [Bootstrap JavaScript](#js)

---

## <a name="intro"></a>🌟 INTRODUCCIÓN A BOOTSTRAP

Bootstrap es un **framework CSS + JavaScript** que proporciona:
- ✅ Componentes prediseñados (navbar, cards, buttons, etc.)
- ✅ Sistema de grid responsive (12 columnas)
- ✅ Utilidades CSS (espaciado, colores, texto)
- ✅ JavaScript para interactividad (dropdown, collapse, modal)

**Versión usada:** Bootstrap 5.3.0

---

## <a name="navbar"></a>🔝 NAVBAR - BARRA DE NAVEGACIÓN

### 📝 HTML Completo

```html
<header class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
  <div class="container">
    <!-- Logo -->
    <a class="navbar-brand" href="pagina-principal.php">
      <img src="img/mrmp logo.png" height="70" class="d-inline-block align-text-top">
      <span class="brand-text">Mexican Racing Motor Parts</span>
    </a>
    
    <!-- Botón hamburguesa (móviles) -->
    <button class="navbar-toggler" type="button" 
            data-bs-toggle="collapse" 
            data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    
    <!-- Menú colapsable -->
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav me-auto">
        <li class="nav-item">
          <a class="nav-link" href="dashboard-piezas.php">
            <i class="fas fa-cogs me-1"></i>Piezas
          </a>
        </li>
      </ul>
    </div>
  </div>
</header>
```

### 🎨 CSS Generado por Bootstrap

#### `.navbar`
```css
.navbar {
  display: flex;              /* Contenedor flexible */
  flex-wrap: wrap;            /* Permite envolver en móviles */
  align-items: center;        /* Alinea verticalmente */
  justify-content: space-between;  /* Espacio entre elementos */
  padding: 0.5rem 1rem;       /* 8px 16px */
  position: relative;
}
```

#### `.navbar-expand-lg`
```css
/* En móviles (<992px): El menú se colapsa */
.navbar-expand-lg .navbar-collapse {
  display: none;  /* Oculto por defecto */
}

/* En pantallas grandes (≥992px): El menú se expande */
@media (min-width: 992px) {
  .navbar-expand-lg {
    flex-wrap: nowrap;  /* No envuelve */
  }
  .navbar-expand-lg .navbar-collapse {
    display: flex !important;  /* Siempre visible */
  }
  .navbar-expand-lg .navbar-toggler {
    display: none;  /* Oculta el botón hamburguesa */
  }
}
```

#### `.navbar-dark`
```css
.navbar-dark .navbar-nav .nav-link {
  color: rgba(255,255,255,0.55);  /* Blanco semi-transparente */
}
.navbar-dark .navbar-nav .nav-link:hover {
  color: rgba(255,255,255,0.75);  /* Más opaco al hover */
}
```

**Sobrescrito en main.css:**
```css
.navbar-dark {
  background-color: #000000;  /* Fondo negro */
  border-bottom: 2px solid #dc2626;  /* Borde rojo */
}
.navbar-dark .navbar-nav .nav-link:hover {
  color: #dc2626;  /* Hover rojo */
}
```

#### `.fixed-top`
```css
.fixed-top {
  position: fixed;  /* Fijo al hacer scroll */
  top: 0;
  right: 0;
  left: 0;
  z-index: 1030;    /* Sobre otros elementos */
}
```

### 📦 `.container`
```css
.container {
  width: 100%;
  padding-right: 15px;
  padding-left: 15px;
  margin-right: auto;  /* Centra horizontalmente */
  margin-left: auto;
}

/* Anchos máximos según breakpoint */
@media (min-width: 576px)  { .container { max-width: 540px; } }
@media (min-width: 768px)  { .container { max-width: 720px; } }
@media (min-width: 992px)  { .container { max-width: 960px; } }
@media (min-width: 1200px) { .container { max-width: 1140px; } }
@media (min-width: 1400px) { .container { max-width: 1320px; } }
```

---

## <a name="dropdown"></a>🎛️ DROPDOWN - MENÚ DESPLEGABLE

### 📝 HTML Completo

```html
<div class="nav-item dropdown">
  <!-- Botón que abre el dropdown -->
  <a class="nav-link dropdown-toggle" href="#" 
     role="button" 
     data-bs-toggle="dropdown">
    <i class="fas fa-user me-1"></i>Hola, Usuario
  </a>
  
  <!-- Menú desplegable -->
  <ul class="dropdown-menu">
    <li><a class="dropdown-item" href="perfil.php">Perfil</a></li>
    <li><a class="dropdown-item active" href="carrito.php">Carrito</a></li>
    <li><hr class="dropdown-divider"></li>
    <li><a class="dropdown-item" href="?logout=1">Cerrar Sesión</a></li>
  </ul>
</div>
```

### 🎨 CSS Generado

#### `.dropdown`
```css
.dropdown {
  position: relative;  /* Para posicionar el menú */
}
```

#### `.dropdown-toggle::after`
```css
.dropdown-toggle::after {
  display: inline-block;
  margin-left: 0.255em;
  vertical-align: 0.255em;
  content: "";
  border-top: 0.3em solid;      /* ▼ */
  border-right: 0.3em solid transparent;
  border-bottom: 0;
  border-left: 0.3em solid transparent;
}
```

#### `.dropdown-menu`
```css
.dropdown-menu {
  position: absolute;  /* Se posiciona sobre el contenido */
  top: 100%;           /* Justo debajo del botón */
  left: 0;
  z-index: 1000;
  display: none;       /* Oculto por defecto */
  min-width: 10rem;    /* 160px */
  padding: 0.5rem 0;
  margin: 0;
  font-size: 1rem;
  color: #212529;
  text-align: left;
  list-style: none;
  background-color: #fff;
  background-clip: padding-box;
  border: 1px solid rgba(0,0,0,0.15);
  border-radius: 0.25rem;
  box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.15);
}

/* Cuando está abierto */
.dropdown-menu.show {
  display: block;  /* Visible */
}
```

**Sobrescrito en main.css:**
```css
.dropdown-menu {
  background-color: #1a1a1a;  /* Fondo oscuro */
  border: 1px solid #404040;
}
```

#### `.dropdown-item`
```css
.dropdown-item {
  display: block;
  width: 100%;
  padding: 0.25rem 1rem;  /* 4px 16px */
  clear: both;
  font-weight: 400;
  color: #212529;
  text-align: inherit;
  text-decoration: none;
  white-space: nowrap;
  background-color: transparent;
  border: 0;
  transition: color 0.15s, background-color 0.15s;
}

.dropdown-item:hover {
  color: #1e2125;
  background-color: #e9ecef;
}

.dropdown-item.active {
  color: #fff;
  background-color: #0d6efd;  /* Azul */
}
```

**Sobrescrito en main.css:**
```css
.dropdown-item {
  color: #ffffff;  /* Texto blanco */
}
.dropdown-item:hover {
  background-color: #dc2626;  /* Fondo rojo */
  color: #ffffff;
}
.dropdown-item.active {
  background-color: #dc2626;  /* Rojo MRMP */
}
```

### ⚙️ JavaScript del Dropdown

**Atributos importantes:**
- `data-bs-toggle="dropdown"` → Bootstrap JS detecta este atributo
- `role="button"` → Accesibilidad

**Flujo de funcionamiento:**

```
1. Usuario hace clic en el botón
   ↓
2. Bootstrap JS detecta data-bs-toggle="dropdown"
   ↓
3. Bootstrap JS agrega la clase .show al .dropdown-menu
   ↓
4. CSS cambia display: none → display: block
   ↓
5. El menú se hace visible
   ↓
6. Usuario hace clic fuera o en un item
   ↓
7. Bootstrap JS quita la clase .show
   ↓
8. El menú se oculta
```

**Código JavaScript interno de Bootstrap:**
```javascript
// Simplificado - Bootstrap hace esto automáticamente
document.querySelectorAll('[data-bs-toggle="dropdown"]').forEach(button => {
  button.addEventListener('click', function(e) {
    e.preventDefault();
    const menu = this.nextElementSibling;
    menu.classList.toggle('show');  // Agrega/quita .show
  });
});

// Cerrar al hacer clic fuera
document.addEventListener('click', function(e) {
  if (!e.target.closest('.dropdown')) {
    document.querySelectorAll('.dropdown-menu.show').forEach(menu => {
      menu.classList.remove('show');
    });
  }
});
```

---

## <a name="collapse"></a>🍔 COLLAPSE - MENÚ HAMBURGUESA

### 📝 HTML Completo

```html
<!-- Botón hamburguesa -->
<button class="navbar-toggler" type="button" 
        data-bs-toggle="collapse" 
        data-bs-target="#navbarNav">
  <span class="navbar-toggler-icon"></span>
</button>

<!-- Contenido colapsable -->
<div class="collapse navbar-collapse" id="navbarNav">
  <!-- Menú aquí -->
</div>
```

### 🎨 CSS Generado

#### `.collapse`
```css
.collapse:not(.show) {
  display: none;  /* Oculto por defecto */
}

.collapse.show {
  display: block;  /* Visible cuando está abierto */
}
```

#### `.navbar-toggler`
```css
.navbar-toggler {
  padding: 0.25rem 0.75rem;
  font-size: 1.25rem;
  line-height: 1;
  background-color: transparent;
  border: 1px solid transparent;
  border-radius: 0.25rem;
  transition: box-shadow 0.15s ease-in-out;
}
```

#### `.navbar-toggler-icon`
```css
.navbar-toggler-icon {
  display: inline-block;
  width: 1.5em;
  height: 1.5em;
  vertical-align: middle;
  background-image: url("data:image/svg+xml,<svg>...</svg>");  /* 3 líneas */
  background-repeat: no-repeat;
  background-position: center;
  background-size: 100%;
}
```

### ⚙️ JavaScript del Collapse

**Atributos:**
- `data-bs-toggle="collapse"` → Activa el collapse
- `data-bs-target="#navbarNav"` → ID del elemento a colapsar

**Flujo:**
```
1. Usuario hace clic en el botón hamburguesa
   ↓
2. Bootstrap JS detecta data-bs-toggle="collapse"
   ↓
3. Bootstrap JS busca el elemento con id="navbarNav"
   ↓
4. Bootstrap JS agrega la clase .show
   ↓
5. CSS cambia display: none → display: block
   ↓
6. El menú se despliega con animación
   ↓
7. Usuario hace clic de nuevo
   ↓
8. Bootstrap JS quita la clase .show
   ↓
9. El menú se colapsa
```

**Código JavaScript interno:**
```javascript
// Bootstrap hace esto automáticamente
document.querySelectorAll('[data-bs-toggle="collapse"]').forEach(button => {
  button.addEventListener('click', function() {
    const targetId = this.getAttribute('data-bs-target');
    const target = document.querySelector(targetId);
    
    if (target.classList.contains('show')) {
      // Cerrar
      target.classList.remove('show');
      target.style.height = '0';
    } else {
      // Abrir
      target.classList.add('show');
      target.style.height = target.scrollHeight + 'px';
    }
  });
});
```

---

## <a name="grid"></a>🏗️ GRID SYSTEM - SISTEMA DE REJILLA

### 📝 HTML Completo

```html
<div class="container">
  <div class="row">
    <div class="col-md-6">Columna 1 (50%)</div>
    <div class="col-md-6">Columna 2 (50%)</div>
  </div>
</div>
```

### 🎨 CSS Generado

#### `.row`
```css
.row {
  display: flex;
  flex-wrap: wrap;
  margin-right: -0.75rem;  /* Compensa el padding de las columnas */
  margin-left: -0.75rem;
}
```

#### `.col-md-6`
```css
/* En móviles (<768px) */
.col-md-6 {
  flex: 0 0 auto;
  width: 100%;  /* Ocupa todo el ancho */
}

/* En pantallas Medium (≥768px) */
@media (min-width: 768px) {
  .col-md-6 {
    flex: 0 0 auto;
    width: 50%;  /* Ocupa 6/12 = 50% */
  }
}
```

### 📊 Sistema de 12 Columnas

```css
.col-md-1  { width: 8.333%; }   /* 1/12 */
.col-md-2  { width: 16.666%; }  /* 2/12 */
.col-md-3  { width: 25%; }      /* 3/12 = 1/4 */
.col-md-4  { width: 33.333%; }  /* 4/12 = 1/3 */
.col-md-6  { width: 50%; }      /* 6/12 = 1/2 */
.col-md-8  { width: 66.666%; }  /* 8/12 = 2/3 */
.col-md-9  { width: 75%; }      /* 9/12 = 3/4 */
.col-md-12 { width: 100%; }     /* 12/12 */
```

### 📱 Breakpoints

```css
/* Extra Small (móviles) */
.col-*     /* <576px */

/* Small (móviles grandes) */
.col-sm-*  /* ≥576px */

/* Medium (tablets) */
.col-md-*  /* ≥768px */

/* Large (laptops) */
.col-lg-*  /* ≥992px */

/* Extra Large (desktops) */
.col-xl-*  /* ≥1200px */

/* Extra Extra Large (pantallas grandes) */
.col-xxl-* /* ≥1400px */
```

---

## <a name="cards"></a>🎴 CARDS - TARJETAS

### 📝 HTML Completo

```html
<div class="card">
  <div class="card-body">
    <h2 class="card-title">Título</h2>
    <p class="card-text">Contenido de la tarjeta</p>
    <a href="#" class="btn btn-primary">Botón</a>
  </div>
</div>
```

### 🎨 CSS Generado

#### `.card`
```css
.card {
  position: relative;
  display: flex;
  flex-direction: column;
  min-width: 0;
  word-wrap: break-word;
  background-color: #fff;
  background-clip: border-box;
  border: 1px solid rgba(0,0,0,0.125);
  border-radius: 0.25rem;
}
```

**Sobrescrito en main.css:**
```css
.card {
  background: #1a1a1a;  /* Gris oscuro */
  box-shadow: 0 4px 6px rgba(0,0,0,0.3);
  border: none;
  transition: transform 0.3s, box-shadow 0.3s;
}

.card:hover {
  transform: translateY(-5px);  /* Se eleva */
  box-shadow: 0 8px 15px rgba(0,0,0,0.4);
}
```

#### `.card-body`
```css
.card-body {
  flex: 1 1 auto;  /* Crece y se encoge */
  padding: 1rem 1rem;  /* 16px */
}
```

---

## <a name="tables"></a>📊 TABLES - TABLAS

### 📝 HTML Completo

```html
<div class="table-responsive">
  <table class="table table-hover">
    <thead class="bg-primary text-white">
      <tr>
        <th>Columna 1</th>
        <th>Columna 2</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td>Dato 1</td>
        <td>Dato 2</td>
      </tr>
    </tbody>
  </table>
</div>
```

### 🎨 CSS Generado

#### `.table`
```css
.table {
  width: 100%;
  margin-bottom: 1rem;
  color: #212529;
  vertical-align: top;
  border-color: #dee2e6;
}

.table > :not(caption) > * > * {
  padding: 0.5rem 0.5rem;
  background-color: var(--bs-table-bg);
  border-bottom-width: 1px;
}
```

#### `.table-hover`
```css
.table-hover > tbody > tr:hover > * {
  background-color: rgba(0,0,0,0.075);
}
```

#### `.table-responsive`
```css
.table-responsive {
  overflow-x: auto;  /* Scroll horizontal en móviles */
  -webkit-overflow-scrolling: touch;
}
```

---

## <a name="buttons"></a>🔘 BUTTONS - BOTONES

### 📝 HTML

```html
<button class="btn btn-primary">Botón Primario</button>
<button class="btn btn-secondary">Botón Secundario</button>
<button class="btn btn-success">Éxito</button>
<button class="btn btn-danger">Peligro</button>
```

### 🎨 CSS Generado

#### `.btn`
```css
.btn {
  display: inline-block;
  font-weight: 400;
  line-height: 1.5;
  color: #212529;
  text-align: center;
  text-decoration: none;
  vertical-align: middle;
  cursor: pointer;
  user-select: none;
  background-color: transparent;
  border: 1px solid transparent;
  padding: 0.375rem 0.75rem;  /* 6px 12px */
  font-size: 1rem;
  border-radius: 0.25rem;
  transition: color 0.15s, background-color 0.15s, 
              border-color 0.15s, box-shadow 0.15s;
}
```

#### `.btn-primary`
```css
.btn-primary {
  color: #fff;
  background-color: #0d6efd;  /* Azul Bootstrap */
  border-color: #0d6efd;
}

.btn-primary:hover {
  background-color: #0b5ed7;
  border-color: #0a58ca;
}
```

**Sobrescrito en main.css:**
```css
.btn-primary {
  background-color: #dc2626;  /* Rojo MRMP */
  border-color: #dc2626;
}

.btn-primary:hover {
  background-color: #b91c1c;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(220,38,38,0.3);
}
```

### 📏 Tamaños

```css
.btn-sm  /* Pequeño: padding: 0.25rem 0.5rem; font-size: 0.875rem; */
.btn     /* Normal: padding: 0.375rem 0.75rem; font-size: 1rem; */
.btn-lg  /* Grande: padding: 0.5rem 1rem; font-size: 1.25rem; */
```

---

## <a name="forms"></a>📝 FORMS - FORMULARIOS

### 📝 HTML

```html
<input type="text" class="form-control" placeholder="Nombre">
<input type="number" class="form-control form-control-sm" placeholder="Cantidad">
```

### 🎨 CSS Generado

#### `.form-control`
```css
.form-control {
  display: block;
  width: 100%;
  padding: 0.375rem 0.75rem;
  font-size: 1rem;
  font-weight: 400;
  line-height: 1.5;
  color: #212529;
  background-color: #fff;
  background-clip: padding-box;
  border: 1px solid #ced4da;
  appearance: none;
  border-radius: 0.25rem;
  transition: border-color 0.15s, box-shadow 0.15s;
}

.form-control:focus {
  color: #212529;
  background-color: #fff;
  border-color: #86b7fe;
  outline: 0;
  box-shadow: 0 0 0 0.25rem rgba(13,110,253,0.25);
}
```

**Sobrescrito en main.css:**
```css
.form-control {
  background: #1a1a1a;
  color: #ffffff;
  border: 2px solid #404040;
}

.form-control:focus {
  border-color: #dc2626;
  box-shadow: 0 0 0 0.2rem rgba(220,38,38,0.25);
}
```

---

## <a name="js"></a>⚙️ BOOTSTRAP JAVASCRIPT

### 📦 Cómo se Carga

```html
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
```

**Este archivo incluye:**
- ✅ Popper.js (para posicionar dropdowns)
- ✅ Bootstrap JavaScript (todos los componentes)

### 🎯 Componentes JavaScript

#### 1. **Dropdown**
```javascript
// Atributos data-*
data-bs-toggle="dropdown"  // Activa el dropdown
data-bs-auto-close="true"  // Cierra al hacer clic fuera

// API JavaScript
const dropdown = new bootstrap.Dropdown(element);
dropdown.show();   // Mostrar
dropdown.hide();   // Ocultar
dropdown.toggle(); // Alternar
```

#### 2. **Collapse**
```javascript
// Atributos data-*
data-bs-toggle="collapse"   // Activa el collapse
data-bs-target="#navbarNav" // Elemento a colapsar

// API JavaScript
const collapse = new bootstrap.Collapse(element);
collapse.show();   // Expandir
collapse.hide();   // Colapsar
collapse.toggle(); // Alternar
```

#### 3. **Modal** (si se usara)
```javascript
data-bs-toggle="modal"
data-bs-target="#myModal"

const modal = new bootstrap.Modal(element);
modal.show();
modal.hide();
```

### 🔄 Eventos JavaScript

```javascript
// Dropdown
element.addEventListener('show.bs.dropdown', function() {
  console.log('Dropdown se va a mostrar');
});

element.addEventListener('shown.bs.dropdown', function() {
  console.log('Dropdown ya se mostró');
});

element.addEventListener('hide.bs.dropdown', function() {
  console.log('Dropdown se va a ocultar');
});

element.addEventListener('hidden.bs.dropdown', function() {
  console.log('Dropdown ya se ocultó');
});
```

---

## 📚 RESUMEN FINAL

### ✅ Bootstrap CSS proporciona:
- Componentes prediseñados (navbar, cards, buttons, tables)
- Sistema de grid responsive (12 columnas)
- Utilidades (espaciado, colores, texto, display)

### ✅ Bootstrap JavaScript proporciona:
- Interactividad para dropdown (menú desplegable)
- Collapse para menú hamburguesa
- Eventos para controlar componentes

### ✅ main.css personaliza:
- Colores (negro/rojo en lugar de azul/blanco)
- Tema oscuro para todos los componentes
- Efectos hover mejorados

**Sin Bootstrap:** Tendríamos que escribir miles de líneas de CSS y JavaScript.

**Con Bootstrap:** Solo usamos clases y atributos data-*.

---

**Creado por:** Antigravity AI  
**Proyecto:** MRMP - Mexican Racing Motor Parts  
**Fecha:** 2025-11-26
