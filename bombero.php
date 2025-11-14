<?php
// Verificar autenticación del usuario
session_start();

// Obtener el rol esperado de la página actual
$pagina_actual = basename($_SERVER['PHP_SELF'], '.php');
$rol_requerido = $pagina_actual; // ciudadano, bombero, oficial, comandante, administrativo

// Verificar si el usuario está autenticado
if (!isset($_SESSION['usuario'])) {
    // No hay sesión - redirigir a login
    header('Location: index.php');
    exit();
}

$usuario = $_SESSION['usuario'];

// Verificar que el usuario tenga el rol correcto
if ($usuario['rol'] !== $rol_requerido) {
    // Rol incorrecto - redirigir a página no autorizada o login
    session_unset();
    session_destroy();
    session_start();
    $_SESSION['error_mensaje'] = "No tiene permisos para acceder a esta página. Su rol es: " . $usuario['rol'];
    header('Location: index.php');
    exit();
}

// Configuración de seguridad de sesión
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > 3600)) {
    // Sesión expirada (1 hora)
    session_unset();
    session_destroy();
    session_start();
    $_SESSION['error_mensaje'] = "Su sesión ha expirado. Por favor, inicie sesión nuevamente.";
    header('Location: index.php');
    exit();
}
$_SESSION['LAST_ACTIVITY'] = time();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bombero - Sistema de Bomberos</title>
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --bombero: #16a34a;
            --bombero-dark: #15803d;
        }
        
        .sidebar-bombero {
            background: linear-gradient(180deg, #1e293b 0%, #0f172a 100%);
            min-height: 100vh;
        }
        
        .nav-link-bombero {
            color: #cbd5e1;
            border-radius: 8px;
            margin: 4px 0;
            transition: all 0.3s ease;
        }
        
        .nav-link-bombero:hover, .nav-link-bombero.active {
            background: var(--bombero);
            color: white;
        }
        
        .card-bombero {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            border-left: 4px solid var(--bombero);
        }
        
        .btn-bombero {
            background: linear-gradient(135deg, var(--bombero), var(--bombero-dark));
            color: white;
            border: none;
            border-radius: 8px;
            padding: 10px 20px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-bombero:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 15px rgba(22, 163, 74, 0.3);
            color: white;
        }
        
        .equipment-card {
            transition: all 0.3s ease;
            border: 1px solid #e5e7eb;
        }
        
        .equipment-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }
        
        .status-indicator {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 8px;
        }
        
        .status-active { background: #16a34a; }
        .status-warning { background: #f59e0b; }
        .status-danger { background: #dc2626; }
    </style>
</head>
<body>
    <!-- Emergency Alert -->
    <div class="bg-green-600 text-white py-2">
        <div class="container text-center">
            <i class="fas fa-firefighter me-2"></i>
            <strong>BOMBERO ACTIVO:</strong> Sistema Operativo - Listo para Intervención
        </div>
    </div>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 sidebar-bombero p-0">
                <div class="d-flex flex-column p-3">
                    <!-- Logo -->
                    <div class="text-center mb-4">
                        <i class="fas fa-firefighter fa-2x text-green-500 mb-2"></i>
                        <h5 class="text-white">BOMBERO</h5>
                        <small class="text-gray-400">Operaciones en Campo</small>
                    </div>
                    
                    <!-- Navigation -->
                    <ul class="nav nav-pills flex-column mb-auto">
                        <li class="nav-item">
                            <a href="#" class="nav-link-bombero active">
                                <i class="fas fa-home me-2"></i>
                                Mi Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="#" class="nav-link-bombero">
                                <i class="fas fa-fire-extinguisher me-2"></i>
                                Mis Intervenciones
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="#" class="nav-link-bombero">
                                <i class="fas fa-tools me-2"></i>
                                Estado de Equipos
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="#" class="nav-link-bombero">
                                <i class="fas fa-walkie-talkie me-2"></i>
                                Comunicaciones
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="#" class="nav-link-bombero">
                                <i class="fas fa-file-alt me-2"></i>
                                Reportes Diarios
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 bg-gray-50 p-0">
                <!-- Top Bar -->
                <nav class="navbar navbar-light bg-white border-bottom px-4">
                    <div class="container-fluid">
                        <span class="navbar-brand fw-bold text-green-600">
                            <i class="fas fa-fire-extinguisher me-2"></i>
                            Panel de Bombero - Estación Central
                        </span>
                        <div class="d-flex align-items-center">
                            <span class="badge bg-green-600 me-3">
                                <i class="fas fa-user me-1"></i>Juan Pérez
                            </span>
                            <span class="text-muted me-3" id="current-time"></span>
                            <button class="btn btn-outline-green btn-sm">
                                <i class="fas fa-sign-out-alt me-1"></i>Salir
                            </button>
                        </div>
                    </div>
                </nav>

                <!-- Content -->
                <div class="container-fluid p-4">
                    <!-- Header -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h2 class="text-2xl font-bold text-gray-800">
                                <i class="fas fa-firefighter me-2 text-green-600"></i>
                                Dashboard Operativo
                            </h2>
                            <p class="text-gray-600">Sistema de intervención y reportes para bomberos en campo</p>
                        </div>
                    </div>

                    <!-- Current Assignment -->
                    <div class="row g-4 mb-4">
                        <div class="col-12">
                            <div class="card card-bombero border-warning">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col-md-8">
                                            <h5 class="card-title text-warning">
                                                <i class="fas fa-exclamation-circle me-2"></i>
                                                ASIGNACIÓN ACTIVA
                                            </h5>
                                            <p class="card-text mb-1">
                                                <strong>Incendio Residencial</strong> - Zona Norte
                                            </p>
                                            <p class="card-text text-muted small mb-2">
                                                <i class="fas fa-map-marker-alt me-1"></i>
                                                Av. Principal 123, Sector Residencial
                                            </p>
                                            <div class="d-flex gap-3">
                                                <span class="text-muted small">
                                                    <i class="fas fa-clock me-1"></i>Inicio: 14:30
                                                </span>
                                                <span class="text-muted small">
                                                    <i class="fas fa-truck me-1"></i>Unidad: U-101
                                                </span>
                                                <span class="text-muted small">
                                                    <i class="fas fa-users me-1"></i>Equipo: 6 bomberos
                                                </span>
                                            </div>
                                        </div>
                                        <div class="col-md-4 text-end">
                                            <button class="btn btn-warning me-2">
                                                <i class="fas fa-broadcast-tower me-1"></i> Reportar
                                            </button>
                                            <button class="btn btn-outline-danger">
                                                <i class="fas fa-first-aid me-1"></i> Emergencia
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="row g-4 mb-4">
                        <div class="col-md-3">
                            <div class="card card-bombero text-center">
                                <div class="card-body">
                                    <i class="fas fa-fire-extinguisher fa-3x text-red-500 mb-3"></i>
                                    <h5>Nueva Intervención</h5>
                                    <p class="text-muted small">Registrar acción en campo</p>
                                    <button class="btn btn-bombero btn-sm w-100">
                                        Iniciar Reporte
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card card-bombero text-center">
                                <div class="card-body">
                                    <i class="fas fa-tools fa-3x text-blue-500 mb-3"></i>
                                    <h5>Estado Equipos</h5>
                                    <p class="text-muted small">Verificar equipamiento</p>
                                    <button class="btn btn-bombero btn-sm w-100">
                                        Verificar
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card card-bombero text-center">
                                <div class="card-body">
                                    <i class="fas fa-walkie-talkie fa-3x text-green-500 mb-3"></i>
                                    <h5>Comunicaciones</h5>
                                    <p class="text-muted small">Contactar comando</p>
                                    <button class="btn btn-bombero btn-sm w-100">
                                        Conectar
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card card-bombero text-center">
                                <div class="card-body">
                                    <i class="fas fa-file-medical fa-3x text-orange-500 mb-3"></i>
                                    <h5>Reporte Médico</h5>
                                    <p class="text-muted small">Incidente médico</p>
                                    <button class="btn btn-bombero btn-sm w-100">
                                        Generar
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Equipment Status -->
                    <div class="row g-4">
                        <div class="col-lg-8">
                            <div class="card card-bombero">
                                <div class="card-header bg-white">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-tools me-2"></i>
                                        Estado de Mi Equipamiento
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <div class="equipment-card card">
                                                <div class="card-body">
                                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                                        <h6 class="card-title">
                                                            <span class="status-indicator status-active"></span>
                                                            Equipo de Respiración
                                                        </h6>
                                                        <span class="badge bg-success">Óptimo</span>
                                                    </div>
                                                    <p class="card-text small text-muted">
                                                        SCBA Mark-5<br>
                                                        <strong>Presión:</strong> 280 bar<br>
                                                        <strong>Próxima revisión:</strong> 30/01/2024
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="equipment-card card">
                                                <div class="card-body">
                                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                                        <h6 class="card-title">
                                                            <span class="status-indicator status-warning"></span>
                                                            Traje de Protección
                                                        </h6>
                                                        <span class="badge bg-warning">Revisar</span>
                                                    </div>
                                                    <p class="card-text small text-muted">
                                                        Traje Nomex Pro<br>
                                                        <strong>Última limpieza:</strong> 10/01/2024<br>
                                                        <strong>Estado:</strong> Ligero desgaste
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="equipment-card card">
                                                <div class="card-body">
                                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                                        <h6 class="card-title">
                                                            <span class="status-indicator status-active"></span>
                                                            Herramientas de Rescate
                                                        </h6>
                                                        <span class="badge bg-success">Completo</span>
                                                    </div>
                                                    <p class="card-text small text-muted">
                                                        Kit de herramientas hidráulicas<br>
                                                        <strong>Verificado:</strong> Hoy<br>
                                                        <strong>Responsable:</strong> Juan Pérez
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="equipment-card card">
                                                <div class="card-body">
                                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                                        <h6 class="card-title">
                                                            <span class="status-indicator status-danger"></span>
                                                            Comunicaciones
                                                        </h6>
                                                        <span class="badge bg-danger">Fallando</span>
                                                    </div>
                                                    <p class="card-text small text-muted">
                                                        Radio portátil T80<br>
                                                        <strong>Batería:</strong> 15%<br>
                                                        <strong>Reportado:</strong> 15/01/2024
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-lg-4">
                            <div class="card card-bombero">
                                <div class="card-header bg-white">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-chart-line me-2"></i>
                                        Mis Estadísticas
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="text-center mb-4">
                                        <div class="display-4 text-green-600 fw-bold">24</div>
                                        <small class="text-muted">Intervenciones este mes</small>
                                    </div>
                                    
                                    <div class="list-group list-group-flush">
                                        <div class="list-group-item d-flex justify-content-between px-0">
                                            <span>Incendios</span>
                                            <span class="fw-bold">12</span>
                                        </div>
                                        <div class="list-group-item d-flex justify-content-between px-0">
                                            <span>Rescates</span>
                                            <span class="fw-bold">6</span>
                                        </div>
                                        <div class="list-group-item d-flex justify-content-between px-0">
                                            <span>Asistencias</span>
                                            <span class="fw-bold">4</span>
                                        </div>
                                        <div class="list-group-item d-flex justify-content-between px-0">
                                            <span>Prevención</span>
                                            <span class="fw-bold">2</span>
                                        </div>
                                    </div>
                                    
                                    <div class="mt-3 p-2 bg-light rounded">
                                        <small class="text-muted">
                                            <i class="fas fa-award me-1 text-warning"></i>
                                            <strong>Rendimiento:</strong> Excelente
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Verificar autenticación al cargar la página
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
            
            // Mostrar información del usuario en la interfaz
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
        
        // Actualizar hora
        function updateTime() {
            const now = new Date();
            document.getElementById('current-time').textContent = 
                now.toLocaleDateString('es-ES') + ' ' + now.toLocaleTimeString('es-ES');
        }
        setInterval(updateTime, 1000);
        updateTime();
    </script>
</body>
</html>