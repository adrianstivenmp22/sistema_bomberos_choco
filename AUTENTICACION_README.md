# Sistema de Autenticación - Sistema de Bomberos Chocó

## 📋 Descripción General

Se ha implementado un sistema de autenticación y autorización de dos niveles en todos los archivos de redirección del sistema:

1. **Validación Backend (PHP)** - Servidor
2. **Validación Frontend (JavaScript)** - Cliente

---

## 🔐 Estructura de Autenticación Backend (PHP)

### Validación en el Servidor

Cada archivo PHP contiene las siguientes validaciones:

```php
<?php
session_start();

// 1. Obtener rol esperado de la página
$pagina_actual = basename($_SERVER['PHP_SELF'], '.php');
$rol_requerido = $pagina_actual; // ciudadano, bombero, oficial, comandante, administrativo

// 2. Verificar si el usuario está autenticado
if (!isset($_SESSION['usuario'])) {
    header('Location: index.php');
    exit();
}

// 3. Verificar que tenga el rol correcto
if ($usuario['rol'] !== $rol_requerido) {
    session_unset();
    session_destroy();
    header('Location: index.php');
    exit();
}

// 4. Verificar timeout de sesión (1 hora)
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > 3600)) {
    session_unset();
    session_destroy();
    header('Location: index.php');
    exit();
}
$_SESSION['LAST_ACTIVITY'] = time();
?>
```

### Archivos Protegidos

- `ciudadano.php` → Requiere rol: `ciudadano`
- `bombero.php` → Requiere rol: `bombero`
- `oficial.php` → Requiere rol: `oficial`
- `comandante.php` → Requiere rol: `comandante`
- `administrativo.php` → Requiere rol: `administrativo`

---

## 🎯 Validación Frontend (JavaScript)

### Script de Autenticación en Cliente

Cada página HTML contiene el siguiente script al cargar:

```javascript
document.addEventListener('DOMContentLoaded', function() {
    const usuarioLogueado = sessionStorage.getItem('usuarioLogueado');
    
    if (!usuarioLogueado) {
        // Redirigir al login si no hay sesión
        window.location.href = 'index.php';
        return;
    }
    
    const usuario = JSON.parse(usuarioLogueado);
    
    // Verificar que el usuario tenga acceso a esta página
    const paginaActual = window.location.pathname.split('/').pop().replace('.php', '');
    const rolPermitido = paginaActual;
    
    if (usuario.rol !== rolPermitido) {
        alert(`No tiene permisos para acceder a esta página. Su rol es: ${usuario.rol}`);
        window.location.href = 'index.php';
        return;
    }
    
    // Mostrar información del usuario
    const elementosUsuario = document.querySelectorAll('[data-usuario]');
    elementosUsuario.forEach(elemento => {
        elemento.textContent = usuario.username;
    });
    
    // Configurar logout
    const btnLogout = document.querySelector('[data-logout]');
    if (btnLogout) {
        btnLogout.addEventListener('click', function(e) {
            e.preventDefault();
            sessionStorage.removeItem('usuarioLogueado');
            window.location.href = 'index.php';
        });
    }
});
```

---

## 🔄 Flujo de Autenticación

### 1. Inicio de Sesión

```
Usuario → index.php (Login)
         ↓
    Validar Credenciales
         ↓
    Crear sesión PHP: $_SESSION['usuario'] = [
        'id' => usuario_id,
        'username' => username,
        'rol' => rol_usuario
    ]
    ↓
    Guardar en sessionStorage (Frontend):
    sessionStorage.setItem('usuarioLogueado', JSON.stringify(usuario))
    ↓
    Redirigir al dashboard según rol (ciudadano.php, bombero.php, etc.)
```

### 2. Acceso a Página Protegida

```
Usuario solicita acceso → bombero.php
         ↓
    [BACKEND] Verificar $_SESSION['usuario']
         ↓
    ¿Existe sesión?
         ├─ NO → Redirigir a index.php
         └─ SÍ → Continuar
         ↓
    ¿Rol correcto?
         ├─ NO → Limpiar sesión, redirigir a index.php
         └─ SÍ → Continuar
         ↓
    ¿Sesión expirada (>1 hora)?
         ├─ SÍ → Limpiar sesión, redirigir a index.php
         └─ NO → Actualizar LAST_ACTIVITY, mostrar página
         ↓
    [FRONTEND] Verificar sessionStorage['usuarioLogueado']
         ↓
    ¿Existe sesión en cliente?
         ├─ NO → Redirigir a index.php
         └─ SÍ → Continuar
         ↓
    ¿Rol coincide con la página?
         ├─ NO → Mostrar alerta, redirigir a index.php
         └─ SÍ → Permitir acceso
```

### 3. Cierre de Sesión

```
Usuario hace click en "Salir"
         ↓
    [FRONTEND] Limpiar sessionStorage
    sessionStorage.removeItem('usuarioLogueado')
         ↓
    [BACKEND] Limpiar sesión PHP
    session_unset()
    session_destroy()
         ↓
    Redirigir a index.php
```

---

## 📊 Estructura de Datos de Usuario

### En la Sesión PHP
```php
$_SESSION['usuario'] = [
    'id' => 1,
    'username' => 'juan_perez',
    'rol' => 'bombero',
    'nombre_completo' => 'Juan Pérez García',
    'email' => 'juan@bomberos.gov.co'
];
```

### En el sessionStorage (JavaScript)
```javascript
{
    id: 1,
    username: "juan_perez",
    rol: "bombero",
    nombre_completo: "Juan Pérez García",
    email: "juan@bomberos.gov.co"
}
```

---

## 🛡️ Medidas de Seguridad Implementadas

### 1. **Validación en Múltiples Niveles**
- ✅ Backend PHP (más seguro)
- ✅ Frontend JavaScript (UX mejorada)

### 2. **Timeout de Sesión**
- Duración: 1 hora (3600 segundos)
- Se actualiza con cada petición
- Si se excede, se limpia y redirige

### 3. **Verificación de Rol**
- Cada página requiere un rol específico
- El nombre de la página debe coincidir con el rol
- Si no coincide, se limpia la sesión y redirige

### 4. **Protección de Sesión**
```php
ini_set('session.cookie_httponly', 1);  // No accesible por JavaScript
ini_set('session.cookie_secure', 1);    // Solo HTTPS en producción
ini_set('session.use_strict_mode', 1);  // Sesiones estrictas
ini_set('session.cookie_samesite', 'Strict'); // CSRF protection
```

### 5. **Limpieza de Sesión**
- Al acceso no autorizado
- Al timeout de sesión
- Al logout del usuario

---

## 🚀 Roles Disponibles

| Rol | Archivo | Permisos |
|-----|---------|----------|
| **ciudadano** | `ciudadano.php` | Reportar emergencias, acceder a servicios |
| **bombero** | `bombero.php` | Dashboard operativo, intervenciones |
| **oficial** | `oficial.php` | Control de operaciones, coordinación |
| **comandante** | `comandante.php` | Supervisión general, reportes ejecutivos |
| **administrativo** | `administrativo.php` | Gestión general del sistema |

---

## 💡 Cómo Implementar el Login

### En `index.php` o donde esté el formulario de login:

```php
<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar credenciales contra la base de datos
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    // Aquí va la consulta a BD para validar
    // $resultado = validar_usuario($username, $password);
    
    if ($usuario_valido) {
        // Crear sesión
        $_SESSION['usuario'] = [
            'id' => $usuario['id'],
            'username' => $usuario['username'],
            'rol' => $usuario['rol'],
            'nombre_completo' => $usuario['nombre_completo'],
            'email' => $usuario['email']
        ];
        $_SESSION['LAST_ACTIVITY'] = time();
        
        // Redirigir según rol
        header('Location: ' . strtolower($usuario['rol']) . '.php');
        exit();
    } else {
        $error = "Credenciales inválidas";
    }
}
?>
```

### En el formulario HTML/JavaScript:

```javascript
// Después de validar en servidor, guardar en sessionStorage
const usuario = {
    id: 1,
    username: 'juan_perez',
    rol: 'bombero',
    nombre_completo: 'Juan Pérez García',
    email: 'juan@bomberos.gov.co'
};

sessionStorage.setItem('usuarioLogueado', JSON.stringify(usuario));
window.location.href = usuario.rol + '.php';
```

---

## 🔍 Debugging

### Verificar Sesión Backend
```php
<?php
session_start();
echo '<pre>';
print_r($_SESSION);
echo '</pre>';
?>
```

### Verificar Sesión Frontend
```javascript
console.log('Sesión actual:', sessionStorage.getItem('usuarioLogueado'));
console.log('Todas las sesiones:', sessionStorage);
```

### Limpiar Sesión Manual
```javascript
// En la consola del navegador
sessionStorage.clear();
// O específicamente
sessionStorage.removeItem('usuarioLogueado');
```

---

## ⚠️ Errores Comunes

| Error | Causa | Solución |
|-------|-------|----------|
| Redirección a index.php | No hay sesión PHP | Iniciar sesión en login |
| "No tiene permisos" | Rol no coincide | Verificar rol en BD |
| Timeout de sesión | > 1 hora sin actividad | Reloguear |
| sessionStorage vacío | Sesión de navegador cerrada | Reabrir navegador |

---

## 📱 Responsividad

Los scripts de autenticación funcionan en:
- ✅ Navegadores de escritorio
- ✅ Tablets
- ✅ Móviles
- ✅ Aplicaciones embebidas

---

## 🔗 Recursos Relacionados

- `config/config.php` - Configuración general del sistema
- `includes/auth.php` - Funciones de autenticación adicionales
- `includes/database.php` - Conexión a BD

---

## 📝 Versión

**Sistema de Autenticación v1.0**
- Fecha: 13 de Noviembre de 2024
- Estado: ✅ Implementado

---

## ✅ Checklist de Seguridad

- [x] Validación backend implementada
- [x] Validación frontend implementada
- [x] Timeout de sesión configurado
- [x] Verificación de roles implementada
- [x] Logout seguro implementado
- [x] Protección CSRF incluida
- [x] Cookies seguras configuradas
- [x] Manejo de errores implementado

---

**Desarrollado por: Sistema Bomberos Chocó**  
**Última actualización: 13/11/2024**
