<?php
// includes/header.php

/**
 * Header del sistema - Bomberos del Chocó
 */

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Determinar el módulo actual
$current_module = 'inicio';
$request_uri = $_SERVER['REQUEST_URI'] ?? '';

if (strpos($request_uri, '/modules/ciudadano/') !== false) {
    $current_module = 'ciudadano';
} elseif (strpos($request_uri, '/modules/operador/') !== false) {
    $current_module = 'operador';
} elseif (strpos($request_uri, '/modules/bombero/') !== false) {
    $current_module = 'bombero';
} elseif (strpos($request_uri, '/modules/admin/') !== false) {
    $current_module = 'admin';
}

// Obtener información del usuario actual
$user_name = $_SESSION['user_name'] ?? 'Invitado';
$user_type = $_SESSION['user_type'] ?? 'guest';
$user_id = $_SESSION['user_id'] ?? null;

// Mapear tipos de usuario a nombres legibles
$user_type_names = [
    'ciudadano' => 'Ciudadano',
    'operador' => 'Operador',
    'bombero' => 'Bombero',
    'admin' => 'Administrador',
    'guest' => 'Invitado'
];

$current_user_type = $user_type_names[$user_type] ?? 'Usuario';

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME . ' - ' . $current_user_type; ?></title>
    
    <!-- Estilos -->
    <link rel="stylesheet" href="/sistema_bomberos_choco/css/styles.css">
    <link rel="stylesheet" href="/sistema_bomberos_choco/css/responsive.css">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="/sistema_bomberos_choco/assets/icons/favicon.ico">
    
    <!-- Meta tags -->
    <meta name="description" content="Sistema Integral de Emergencias - Bomberos del Chocó">
    <meta name="keywords" content="bomberos, emergencias, chocó, rescate, incendios">
    <meta name="author" content="Bomberos del Chocó">
    
    <!-- Preload crítico -->
    <link rel="preload" href="/sistema_bomberos_choco/css/styles.css" as="style">
    
    <style>
        /* Estilos específicos del header */
        .emergency-alert {
            background: linear-gradient(45deg, #dc3545, #ff6b7a);
            color: white;
            padding: 10px;
            text-align: center;
            font-weight: bold;
            animation: blinkAlert 2s infinite;
        }
        
        @keyframes blinkAlert {
            0%, 50% { opacity: 1; }
            51%, 100% { opacity: 0.7; }
        }
        
        .user-menu {
            position: relative;
            display: inline-block;
        }
        
        .user-dropdown {
            display: none;
            position: absolute;
            right: 0;
            top: 100%;
            background: white;
            min-width: 200px;
            box-shadow: var(--shadow-lg);
            border-radius: var(--border-radius);
            z-index: 1000;
        }
        
        .user-menu:hover .user-dropdown {
            display: block;
        }
        
        .user-dropdown a {
            display: block;
            padding: 10px 15px;
            color: var(--dark-color);
            text-decoration: none;
            border-bottom: 1px solid #eee;
        }
        
        .user-dropdown a:hover {
            background: var(--light-color);
        }
        
        .module-badge {
            background: var(--primary-color);
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.7em;
            margin-left: 5px;
        }
    </style>
</head>
<body>
    <!-- Alertas del sistema -->
    <?php if (isset($_SESSION['emergency_alert'])): ?>
    <div class="emergency-alert">
        🚨 <?php echo htmlspecialchars($_SESSION['emergency_alert']); ?>
    </div>
    <?php unset($_SESSION['emergency_alert']); ?>
    <?php endif; ?>

    <!-- Header principal -->
    <header class="header">
        <div class="header-content">
            <!-- Logo y título -->
            <div class="logo">
                <div style="font-size: 2em;">🚒</div>
                <div>
                    <h1><?php echo APP_NAME; ?></h1>
                    <small style="opacity: 0.8;">Versión <?php echo APP_VERSION; ?></small>
                </div>
            </div>

            <!-- Información del usuario -->
            <div class="user-info">
                <?php if ($user_type !== 'guest'): ?>
                <div class="user-menu">
                    <span>👤 <?php echo htmlspecialchars($user_name); ?></span>
                    <span class="module-badge"><?php echo $current_user_type; ?></span>
                    
                    <div class="user-dropdown">
                        <div style="padding: 10px 15px; background: var(--light-color);">
                            <strong><?php echo htmlspecialchars($user_name); ?></strong><br>
                            <small><?php echo $current_user_type; ?></small>
                        </div>
                        
                        <?php if ($user_type === 'ciudadano'): ?>
                            <a href="/sistema_bomberos_choco/modules/ciudadano/historial.php">📝 Mi Historial</a>
                            <a href="/sistema_bomberos_choco/modules/ciudadano/reporte.php">🚨 Reportar Emergencia</a>
                        <?php elseif ($user_type === 'operador'): ?>
                            <a href="/sistema_bomberos_choco/modules/operador/dashboard.php">📊 Dashboard</a>
                            <a href="/sistema_bomberos_choco/modules/operador/mapa.php">🗺️ Mapa</a>
                            <a href="/sistema_bomberos_choco/modules/operador/asignaciones.php">👨‍🚒 Asignaciones</a>
                        <?php elseif ($user_type === 'bombero'): ?>
                            <a href="/sistema_bomberos_choco/modules/bombero/tareas.php">📋 Mis Tareas</a>
                            <a href="/sistema_bomberos_choco/modules/bombero/actualizaciones.php">📊 Mi Desempeño</a>
                        <?php elseif ($user_type === 'admin'): ?>
                            <a href="/sistema_bomberos_choco/modules/admin/usuarios.php">👥 Usuarios</a>
                            <a href="/sistema_bomberos_choco/modules/admin/reportes.php">📈 Reportes</a>
                            <a href="/sistema_bomberos_choco/modules/admin/sistema.php">⚙️ Sistema</a>
                        <?php endif; ?>
                        
                        <div style="border-top: 1px solid #eee;"></div>
                        <a href="/sistema_bomberos_choco/includes/logout.php">🚪 Cerrar Sesión</a>
                    </div>
                </div>
                <?php else: ?>
                <div>
                    <a href="/sistema_bomberos_choco/index.php" class="btn btn-outline" style="color: white; border-color: white;">
                        🔐 Iniciar Sesión
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- Menú de navegación -->
    <?php if ($user_type !== 'guest'): ?>
    <nav class="nav-menu">
        <ul>
            <?php if ($user_type === 'ciudadano'): ?>
                <li><a href="/sistema_bomberos_choco/modules/ciudadano/reporte.php" 
                       class="<?php echo $current_module === 'ciudadano' ? 'active' : ''; ?>">
                    🚨 Reportar Emergencia
                </a></li>
                <li><a href="/sistema_bomberos_choco/modules/ciudadano/sos.php" 
                       class="<?php echo basename($_SERVER['PHP_SELF']) === 'sos.php' ? 'active' : ''; ?>">
                    🆘 Botón SOS
                </a></li>
                <li><a href="/sistema_bomberos_choco/modules/ciudadano/historial.php" 
                       class="<?php echo basename($_SERVER['PHP_SELF']) === 'historial.php' ? 'active' : ''; ?>">
                    📝 Mi Historial
                </a></li>
                
            <?php elseif ($user_type === 'operador'): ?>
                <li><a href="/sistema_bomberos_choco/modules/operador/dashboard.php" 
                       class="<?php echo basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : ''; ?>">
                    📊 Dashboard
                </a></li>
                <li><a href="/sistema_bomberos_choco/modules/operador/mapa.php" 
                       class="<?php echo basename($_SERVER['PHP_SELF']) === 'mapa.php' ? 'active' : ''; ?>">
                    🗺️ Mapa Interactivo
                </a></li>
                <li><a href="/sistema_bomberos_choco/modules/operador/asignaciones.php" 
                       class="<?php echo basename($_SERVER['PHP_SELF']) === 'asignaciones.php' ? 'active' : ''; ?>">
                    👨‍🚒 Gestión de Asignaciones
                </a></li>
                
            <?php elseif ($user_type === 'bombero'): ?>
                <li><a href="/sistema_bomberos_choco/modules/bombero/tareas.php" 
                       class="<?php echo basename($_SERVER['PHP_SELF']) === 'tareas.php' ? 'active' : ''; ?>">
                    📋 Mis Tareas
                </a></li>
                <li><a href="/sistema_bomberos_choco/modules/bombero/navegacion.php" 
                       class="<?php echo basename($_SERVER['PHP_SELF']) === 'navegacion.php' ? 'active' : ''; ?>">
                    🧭 Navegación
                </a></li>
                <li><a href="/sistema_bomberos_choco/modules/bombero/actualizaciones.php" 
                       class="<?php echo basename($_SERVER['PHP_SELF']) === 'actualizaciones.php' ? 'active' : ''; ?>">
                    📊 Mi Desempeño
                </a></li>
                
            <?php elseif ($user_type === 'admin'): ?>
                <li><a href="/sistema_bomberos_choco/modules/admin/usuarios.php" 
                       class="<?php echo basename($_SERVER['PHP_SELF']) === 'usuarios.php' ? 'active' : ''; ?>">
                    👥 Gestión de Usuarios
                </a></li>
                <li><a href="/sistema_bomberos_choco/modules/admin/reportes.php" 
                       class="<?php echo basename($_SERVER['PHP_SELF']) === 'reportes.php' ? 'active' : ''; ?>">
                    📈 Reportes y Estadísticas
                </a></li>
                <li><a href="/sistema_bomberos_choco/modules/admin/sistema.php" 
                       class="<?php echo basename($_SERVER['PHP_SELF']) === 'sistema.php' ? 'active' : ''; ?>">
                    ⚙️ Configuración del Sistema
                </a></li>
            <?php endif; ?>
            
            <!-- Enlace común a todos los usuarios -->
            <li><a href="/sistema_bomberos_choco/index.php">
                🏠 Inicio
            </a></li>
        </ul>
    </nav>
    <?php endif; ?>

    <!-- Contenedor principal -->
    <main class="container">
    
    <!-- Mostrar mensajes flash -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            ✅ <?php echo htmlspecialchars($_SESSION['success']); ?>
            <?php unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger">
            ❌ <?php echo htmlspecialchars($_SESSION['error']); ?>
            <?php unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['warning'])): ?>
        <div class="alert alert-warning">
            ⚠️ <?php echo htmlspecialchars($_SESSION['warning']); ?>
            <?php unset($_SESSION['warning']); ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['info'])): ?>
        <div class="alert alert-info">
            ℹ️ <?php echo htmlspecialchars($_SESSION['info']); ?>
            <?php unset($_SESSION['info']); ?>
        </div>
    <?php endif; ?>