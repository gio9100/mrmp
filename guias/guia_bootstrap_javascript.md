# 🚀 GUÍA COMPLETA DE BOOTSTRAP JAVASCRIPT

## 📚 ÍNDICE
1. [Introducción](#intro)
2. [Cómo se Carga Bootstrap JS](#carga)
3. [Dropdown - Menú Desplegable](#dropdown)
4. [Collapse - Menú Hamburguesa](#collapse)
5. [Atributos data-bs-*](#data-attributes)
6. [API de JavaScript](#api)
7. [Eventos](#eventos)
8. [Popper.js](#popper)
9. [Ejemplos Prácticos](#ejemplos)

---

## <a name="intro"></a>🌟 INTRODUCCIÓN

Bootstrap JavaScript proporciona **interactividad** a los componentes HTML.

### ¿Qué hace Bootstrap JS?

✅ **Detecta atributos `data-bs-*`** en el HTML  
✅ **Agrega/quita clases CSS** para mostrar/ocultar elementos  
✅ **Maneja eventos** (click, hover, focus)  
✅ **Posiciona elementos** (dropdowns, tooltips)  
✅ **Proporciona una API** para controlar componentes desde JavaScript  

---

## <a name="carga"></a>📦 CÓMO SE CARGA BOOTSTRAP JS

### En carrito.php:

```html
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
```

### ¿Qué incluye `bootstrap.bundle.min.js`?

1. **Popper.js** - Biblioteca para posicionar elementos (dropdowns, tooltips)
2. **Bootstrap JavaScript** - Todos los componentes interactivos

### Componentes incluidos:

```javascript
// Componentes disponibles en Bootstrap 5.3.0
bootstrap.Alert
bootstrap.Button
bootstrap.Carousel
bootstrap.Collapse      // ← Usado en carrito.php (menú hamburguesa)
bootstrap.Dropdown      // ← Usado en carrito.php (menú usuario)
bootstrap.Modal
bootstrap.Offcanvas
bootstrap.Popover
bootstrap.ScrollSpy
bootstrap.Tab
bootstrap.Toast
bootstrap.Tooltip
```

---

## <a name="dropdown"></a>🎛️ DROPDOWN - MENÚ DESPLEGABLE

### 📝 HTML en carrito.php

```html
<div class="nav-item dropdown">
  <!-- Botón que abre el dropdown -->
  <a class="nav-link dropdown-toggle" 
     href="#" 
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

### ⚙️ CÓMO FUNCIONA INTERNAMENTE

#### 1. **Inicialización Automática**

Cuando se carga la página, Bootstrap ejecuta:

```javascript
// Bootstrap detecta TODOS los elementos con data-bs-toggle="dropdown"
document.addEventListener('DOMContentLoaded', function() {
  const dropdownTriggers = document.querySelectorAll('[data-bs-toggle="dropdown"]');
  
  dropdownTriggers.forEach(trigger => {
    // Crea una instancia de Dropdown para cada botón
    new bootstrap.Dropdown(trigger);
  });
});
```

#### 2. **Código Real del Constructor Dropdown**

```javascript
class Dropdown {
  constructor(element, config = {}) {
    this._element = element;  // El botón <a>
    this._menu = this._getMenuElement();  // El <ul class="dropdown-menu">
    this._popper = null;  // Instancia de Popper.js
    this._config = config;
    this._isShown = false;
    
    // Agregar event listener al botón
    this._element.addEventListener('click', (e) => {
      e.preventDefault();
      this.toggle();  // Alternar mostrar/ocultar
    });
    
    // Cerrar al hacer clic fuera
    document.addEventListener('click', (e) => {
      if (!this._element.contains(e.target) && !this._menu.contains(e.target)) {
        this.hide();
      }
    });
  }
  
  // Obtener el menú dropdown
  _getMenuElement() {
    return this._element.nextElementSibling;
  }
  
  // Alternar mostrar/ocultar
  toggle() {
    if (this._isShown) {
      this.hide();
    } else {
      this.show();
    }
  }
  
  // Mostrar el dropdown
  show() {
    if (this._isShown) return;
    
    // Disparar evento 'show.bs.dropdown' (antes de mostrar)
    const showEvent = new Event('show.bs.dropdown', { cancelable: true });
    this._element.dispatchEvent(showEvent);
    
    if (showEvent.defaultPrevented) return;  // Si se canceló, no mostrar
    
    // Agregar clase .show al menú
    this._menu.classList.add('show');
    this._isShown = true;
    
    // Posicionar el menú con Popper.js
    this._createPopper();
    
    // Disparar evento 'shown.bs.dropdown' (después de mostrar)
    const shownEvent = new Event('shown.bs.dropdown');
    this._element.dispatchEvent(shownEvent);
  }
  
  // Ocultar el dropdown
  hide() {
    if (!this._isShown) return;
    
    // Disparar evento 'hide.bs.dropdown'
    const hideEvent = new Event('hide.bs.dropdown', { cancelable: true });
    this._element.dispatchEvent(hideEvent);
    
    if (hideEvent.defaultPrevented) return;
    
    // Quitar clase .show
    this._menu.classList.remove('show');
    this._isShown = false;
    
    // Destruir Popper
    if (this._popper) {
      this._popper.destroy();
      this._popper = null;
    }
    
    // Disparar evento 'hidden.bs.dropdown'
    const hiddenEvent = new Event('hidden.bs.dropdown');
    this._element.dispatchEvent(hiddenEvent);
  }
  
  // Crear instancia de Popper.js para posicionar
  _createPopper() {
    this._popper = Popper.createPopper(this._element, this._menu, {
      placement: 'bottom-start',  // Debajo del botón, alineado a la izquierda
      modifiers: [
        {
          name: 'offset',
          options: { offset: [0, 2] }  // 2px de separación
        }
      ]
    });
  }
}
```

### 📊 FLUJO DE EJECUCIÓN PASO A PASO

```
USUARIO HACE CLIC EN EL BOTÓN
         ↓
1. Event listener detecta el click
         ↓
2. e.preventDefault() previene navegación
         ↓
3. Se llama a toggle()
         ↓
4. toggle() verifica si está mostrado
         ↓
5. Como está oculto, llama a show()
         ↓
6. show() dispara evento 'show.bs.dropdown'
         ↓
7. Si no se cancela, agrega clase .show al menú
         ↓
8. CSS cambia display: none → display: block
         ↓
9. Popper.js calcula la posición óptima
         ↓
10. Popper.js posiciona el menú debajo del botón
         ↓
11. show() dispara evento 'shown.bs.dropdown'
         ↓
12. El menú es visible
         ↓
USUARIO HACE CLIC FUERA
         ↓
13. Document click listener detecta click fuera
         ↓
14. Se llama a hide()
         ↓
15. hide() dispara evento 'hide.bs.dropdown'
         ↓
16. Quita clase .show del menú
         ↓
17. CSS cambia display: block → display: none
         ↓
18. Popper.js se destruye
         ↓
19. hide() dispara evento 'hidden.bs.dropdown'
         ↓
20. El menú se oculta
```

### 🎨 CAMBIOS CSS DURANTE EL PROCESO

```css
/* Estado inicial (oculto) */
.dropdown-menu {
  display: none;
}

/* Cuando Bootstrap agrega .show */
.dropdown-menu.show {
  display: block;
}
```

---

## <a name="collapse"></a>🍔 COLLAPSE - MENÚ HAMBURGUESA

### 📝 HTML en carrito.php

```html
<!-- Botón hamburguesa -->
<button class="navbar-toggler" 
        type="button" 
        data-bs-toggle="collapse" 
        data-bs-target="#navbarNav">
  <span class="navbar-toggler-icon"></span>
</button>

<!-- Contenido colapsable -->
<div class="collapse navbar-collapse" id="navbarNav">
  <ul class="navbar-nav me-auto">
    <li class="nav-item">
      <a class="nav-link" href="dashboard-piezas.php">Piezas</a>
    </li>
  </ul>
</div>
```

### ⚙️ CÓDIGO REAL DEL COLLAPSE

```javascript
class Collapse {
  constructor(element, config = {}) {
    this._element = element;  // El <div class="collapse">
    this._config = config;
    this._isTransitioning = false;
    this._triggerArray = [];  // Botones que controlan este collapse
    
    // Buscar todos los botones que apuntan a este elemento
    const triggers = document.querySelectorAll(
      `[data-bs-toggle="collapse"][data-bs-target="#${element.id}"]`
    );
    
    triggers.forEach(trigger => {
      this._triggerArray.push(trigger);
      trigger.addEventListener('click', (e) => {
        e.preventDefault();
        this.toggle();
      });
    });
  }
  
  toggle() {
    if (this._element.classList.contains('show')) {
      this.hide();
    } else {
      this.show();
    }
  }
  
  show() {
    if (this._isTransitioning || this._element.classList.contains('show')) {
      return;
    }
    
    this._isTransitioning = true;
    
    // Disparar evento 'show.bs.collapse'
    const showEvent = new Event('show.bs.collapse', { cancelable: true });
    this._element.dispatchEvent(showEvent);
    
    if (showEvent.defaultPrevented) return;
    
    // Agregar clase .collapsing para animación
    this._element.classList.add('collapsing');
    this._element.classList.remove('collapse');
    
    // Establecer altura inicial a 0
    this._element.style.height = '0';
    
    // Forzar reflow para que la animación funcione
    this._element.offsetHeight;
    
    // Establecer altura final (altura del contenido)
    this._element.style.height = `${this._element.scrollHeight}px`;
    
    // Cuando termina la transición CSS
    const complete = () => {
      this._element.classList.remove('collapsing');
      this._element.classList.add('collapse', 'show');
      this._element.style.height = '';  // Quitar altura fija
      this._isTransitioning = false;
      
      // Disparar evento 'shown.bs.collapse'
      const shownEvent = new Event('shown.bs.collapse');
      this._element.dispatchEvent(shownEvent);
    };
    
    // Esperar a que termine la transición CSS (350ms)
    this._element.addEventListener('transitionend', complete, { once: true });
  }
  
  hide() {
    if (this._isTransitioning || !this._element.classList.contains('show')) {
      return;
    }
    
    this._isTransitioning = true;
    
    // Disparar evento 'hide.bs.collapse'
    const hideEvent = new Event('hide.bs.collapse', { cancelable: true });
    this._element.dispatchEvent(hideEvent);
    
    if (hideEvent.defaultPrevented) return;
    
    // Establecer altura actual
    this._element.style.height = `${this._element.scrollHeight}px`;
    
    // Forzar reflow
    this._element.offsetHeight;
    
    // Agregar clase .collapsing
    this._element.classList.add('collapsing');
    this._element.classList.remove('collapse', 'show');
    
    // Animar a altura 0
    this._element.style.height = '0';
    
    const complete = () => {
      this._element.classList.remove('collapsing');
      this._element.classList.add('collapse');
      this._element.style.height = '';
      this._isTransitioning = false;
      
      // Disparar evento 'hidden.bs.collapse'
      const hiddenEvent = new Event('hidden.bs.collapse');
      this._element.dispatchEvent(hiddenEvent);
    };
    
    this._element.addEventListener('transitionend', complete, { once: true });
  }
}
```

### 📊 FLUJO DE ANIMACIÓN DEL COLLAPSE

```
USUARIO HACE CLIC EN BOTÓN HAMBURGUESA
         ↓
1. Click listener detecta el click
         ↓
2. Se llama a toggle()
         ↓
3. Como está oculto, llama a show()
         ↓
4. Dispara evento 'show.bs.collapse'
         ↓
5. Agrega clase .collapsing (para animación)
         ↓
6. Quita clase .collapse
         ↓
7. Establece height: 0
         ↓
8. Fuerza reflow (para que el navegador procese)
         ↓
9. Establece height: [altura del contenido]px
         ↓
10. CSS transition anima de 0 a altura completa
         ↓
11. Animación dura 350ms (definido en CSS)
         ↓
12. Al terminar, dispara evento 'transitionend'
         ↓
13. Quita clase .collapsing
         ↓
14. Agrega clases .collapse .show
         ↓
15. Quita height inline (deja que CSS lo maneje)
         ↓
16. Dispara evento 'shown.bs.collapse'
         ↓
17. El menú está completamente visible
```

### 🎨 CSS DURANTE LA ANIMACIÓN

```css
/* Estado inicial (oculto) */
.collapse:not(.show) {
  display: none;
}

/* Durante la animación de apertura */
.collapsing {
  height: 0;
  overflow: hidden;
  transition: height 0.35s ease;  /* 350ms */
}

/* Estado final (visible) */
.collapse.show {
  display: block;
}
```

---

## <a name="data-attributes"></a>🏷️ ATRIBUTOS data-bs-*

### ¿Qué son los atributos data-*?

Son atributos HTML personalizados que Bootstrap JavaScript detecta automáticamente.

### Atributos usados en carrito.php:

#### 1. **data-bs-toggle**

```html
<a data-bs-toggle="dropdown">Usuario</a>
<button data-bs-toggle="collapse">☰</button>
```

**Función:** Indica qué tipo de componente activar

**Valores posibles:**
- `dropdown` - Menú desplegable
- `collapse` - Colapsar/expandir
- `modal` - Ventana modal
- `tab` - Pestañas
- `tooltip` - Tooltip
- `popover` - Popover

**Cómo lo detecta Bootstrap:**

```javascript
// Bootstrap busca TODOS los elementos con data-bs-toggle
document.querySelectorAll('[data-bs-toggle]').forEach(element => {
  const toggleType = element.getAttribute('data-bs-toggle');
  
  switch(toggleType) {
    case 'dropdown':
      new bootstrap.Dropdown(element);
      break;
    case 'collapse':
      // Busca el target
      const targetId = element.getAttribute('data-bs-target');
      const target = document.querySelector(targetId);
      new bootstrap.Collapse(target);
      break;
    // ... otros casos
  }
});
```

#### 2. **data-bs-target**

```html
<button data-bs-toggle="collapse" data-bs-target="#navbarNav">☰</button>
```

**Función:** Indica qué elemento controlar (usando selector CSS)

**Cómo funciona:**

```javascript
const targetSelector = button.getAttribute('data-bs-target');  // "#navbarNav"
const targetElement = document.querySelector(targetSelector);  // <div id="navbarNav">
```

#### 3. **data-bs-auto-close** (opcional)

```html
<a data-bs-toggle="dropdown" data-bs-auto-close="true">Usuario</a>
```

**Valores:**
- `true` (default) - Cierra al hacer clic fuera o en un item
- `false` - No cierra automáticamente
- `inside` - Solo cierra al hacer clic en un item
- `outside` - Solo cierra al hacer clic fuera

---

## <a name="api"></a>🔧 API DE JAVASCRIPT

### Crear instancias manualmente

```javascript
// Obtener el elemento
const dropdownButton = document.querySelector('#myDropdown');

// Crear instancia
const dropdown = new bootstrap.Dropdown(dropdownButton);

// Métodos disponibles
dropdown.show();    // Mostrar
dropdown.hide();    // Ocultar
dropdown.toggle();  // Alternar
dropdown.update();  // Actualizar posición (Popper.js)
dropdown.dispose(); // Destruir instancia
```

### Obtener instancia existente

```javascript
// Bootstrap guarda las instancias en el elemento
const dropdownButton = document.querySelector('#myDropdown');
const dropdown = bootstrap.Dropdown.getInstance(dropdownButton);

if (dropdown) {
  dropdown.show();
}
```

### Crear o obtener instancia

```javascript
const dropdown = bootstrap.Dropdown.getOrCreateInstance(dropdownButton);
dropdown.show();
```

---

## <a name="eventos"></a>📡 EVENTOS

### Eventos del Dropdown

```javascript
const dropdownButton = document.querySelector('[data-bs-toggle="dropdown"]');

// Antes de mostrar (cancelable)
dropdownButton.addEventListener('show.bs.dropdown', function(event) {
  console.log('El dropdown se va a mostrar');
  
  // Puedes cancelar la acción
  // event.preventDefault();
});

// Después de mostrar (no cancelable)
dropdownButton.addEventListener('shown.bs.dropdown', function(event) {
  console.log('El dropdown ya se mostró');
});

// Antes de ocultar (cancelable)
dropdownButton.addEventListener('hide.bs.dropdown', function(event) {
  console.log('El dropdown se va a ocultar');
});

// Después de ocultar (no cancelable)
dropdownButton.addEventListener('hidden.bs.dropdown', function(event) {
  console.log('El dropdown ya se ocultó');
});
```

### Eventos del Collapse

```javascript
const collapseElement = document.querySelector('#navbarNav');

collapseElement.addEventListener('show.bs.collapse', function() {
  console.log('El menú se va a expandir');
});

collapseElement.addEventListener('shown.bs.collapse', function() {
  console.log('El menú ya se expandió');
});

collapseElement.addEventListener('hide.bs.collapse', function() {
  console.log('El menú se va a colapsar');
});

collapseElement.addEventListener('hidden.bs.collapse', function() {
  console.log('El menú ya se colapsó');
});
```

---

## <a name="popper"></a>🎯 POPPER.JS

### ¿Qué es Popper.js?

Es una biblioteca que **posiciona elementos flotantes** (dropdowns, tooltips, popovers) de forma inteligente.

### ¿Cómo funciona?

```javascript
// Bootstrap crea una instancia de Popper cuando muestra el dropdown
this._popper = Popper.createPopper(
  this._element,  // Elemento de referencia (botón)
  this._menu,     // Elemento a posicionar (menú)
  {
    placement: 'bottom-start',  // Posición preferida
    modifiers: [
      {
        name: 'offset',
        options: {
          offset: [0, 2]  // Separación de 2px
        }
      },
      {
        name: 'preventOverflow',
        options: {
          boundary: 'viewport'  // No salir de la pantalla
        }
      }
    ]
  }
);
```

### Posiciones disponibles:

```
top-start    top       top-end
   ↑          ↑          ↑
   └──────────┴──────────┘
        [BOTÓN]
   ┌──────────┬──────────┐
   ↓          ↓          ↓
bottom-start bottom  bottom-end

left-start ← [BOTÓN] → right-start
left       ← [BOTÓN] → right
left-end   ← [BOTÓN] → right-end
```

### Detección inteligente:

Si el dropdown no cabe abajo, Popper lo coloca arriba automáticamente.

```javascript
// Popper calcula:
1. Espacio disponible abajo del botón
2. Espacio disponible arriba del botón
3. Altura del menú dropdown
4. Si no cabe abajo, lo coloca arriba
5. Si no cabe a la izquierda, lo alinea a la derecha
```

---

## <a name="ejemplos"></a>💡 EJEMPLOS PRÁCTICOS

### Ejemplo 1: Cerrar dropdown programáticamente

```javascript
// Cerrar todos los dropdowns abiertos
document.querySelectorAll('.dropdown-menu.show').forEach(menu => {
  const dropdown = bootstrap.Dropdown.getInstance(menu.previousElementSibling);
  if (dropdown) {
    dropdown.hide();
  }
});
```

### Ejemplo 2: Abrir dropdown al pasar el mouse

```javascript
const dropdownButton = document.querySelector('[data-bs-toggle="dropdown"]');
const dropdown = new bootstrap.Dropdown(dropdownButton);

dropdownButton.addEventListener('mouseenter', () => {
  dropdown.show();
});

dropdownButton.parentElement.addEventListener('mouseleave', () => {
  dropdown.hide();
});
```

### Ejemplo 3: Prevenir que se cierre el dropdown

```javascript
dropdownButton.addEventListener('hide.bs.dropdown', function(event) {
  // Prevenir que se cierre si hay un formulario sin guardar
  if (formHasUnsavedChanges) {
    event.preventDefault();
    alert('Guarda los cambios primero');
  }
});
```

### Ejemplo 4: Ejecutar código después de expandir el menú

```javascript
const navbarCollapse = document.querySelector('#navbarNav');

navbarCollapse.addEventListener('shown.bs.collapse', function() {
  // Hacer focus en el primer enlace
  const firstLink = this.querySelector('.nav-link');
  if (firstLink) {
    firstLink.focus();
  }
});
```

---

## 📚 RESUMEN FINAL

### ✅ Bootstrap JavaScript hace:

1. **Detecta atributos `data-bs-*`** automáticamente
2. **Crea instancias** de componentes (Dropdown, Collapse)
3. **Agrega event listeners** a botones y elementos
4. **Agrega/quita clases CSS** (.show, .collapsing)
5. **Usa Popper.js** para posicionar dropdowns
6. **Dispara eventos** personalizados (show, shown, hide, hidden)
7. **Proporciona API** para control manual

### ✅ Flujo general:

```
HTML con data-bs-* 
    ↓
Bootstrap JS detecta atributos
    ↓
Crea instancias de componentes
    ↓
Agrega event listeners
    ↓
Usuario interactúa (click, hover)
    ↓
JavaScript agrega/quita clases
    ↓
CSS muestra/oculta elementos
    ↓
Popper.js posiciona elementos
    ↓
Se disparan eventos personalizados
```

### ✅ Sin Bootstrap JS:

Tendrías que escribir **cientos de líneas de JavaScript** para:
- Detectar clicks
- Agregar/quitar clases
- Posicionar elementos
- Manejar eventos
- Animar transiciones

### ✅ Con Bootstrap JS:

Solo necesitas agregar `data-bs-toggle="dropdown"` y Bootstrap hace todo automáticamente.

---

**Creado por:** Antigravity AI  
**Proyecto:** MRMP - Mexican Racing Motor Parts  
**Fecha:** 2025-11-26
