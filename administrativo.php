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
    <title>Panel de Administrador - Sistema de Bomberos</title>
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        :root {
            --primary-red: #dc2626;
            --primary-dark: #991b1b;
            --accent-orange: #ea580c;
            --accent-yellow: #f59e0b;
            --success-green: #16a34a;
            --info-blue: #2563eb;
            --purple: #7c3aed;
        }
        
        .sidebar {
            background: linear-gradient(180deg, #1e293b 0%, #0f172a 100%);
            min-height: 100vh;
            transition: all 0.3s ease;
        }
        
        .sidebar.collapsed {
            width: 80px;
        }
        
        .sidebar.collapsed .sidebar-text {
            display: none;
        }
        
        .nav-link {
            color: #cbd5e1;
            border-radius: 8px;
            margin: 4px 0;
            transition: all 0.3s ease;
        }
        
        .nav-link:hover, .nav-link.active {
            background: var(--primary-red);
            color: white;
        }
        
        .main-content {
            background: #f8fafc;
            min-height: 100vh;
        }
        
        .dashboard-card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }
        
        .dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        }
        
        .stat-card {
            border-left: 4px solid;
        }
        
        .stat-card.emergencias { border-left-color: var(--primary-red); }
        .stat-card.unidades { border-left-color: var(--info-blue); }
        .stat-card.personal { border-left-color: var(--success-green); }
        .stat-card.formularios { border-left-color: var(--purple); }
        
        .btn-admin {
            background: linear-gradient(135deg, var(--primary-red), var(--primary-dark));
            color: white;
            border: none;
            border-radius: 8px;
            padding: 10px 20px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-admin:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 15px rgba(220, 38, 38, 0.3);
            color: white;
        }
        
        .table-hover tbody tr:hover {
            background-color: #f1f5f9;
        }
        
        .badge-estado {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .modal-header {
            background: linear-gradient(135deg, var(--primary-red), var(--primary-dark));
            color: white;
        }
        
        .form-section {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: var(--accent-orange);
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-size: 0.7rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .chart-container {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 sidebar p-0">
                <div class="d-flex flex-column p-3">
                    <!-- Logo -->
                    <div class="text-center mb-4">
                        <i class="fas fa-fire-extinguisher fa-2x text-red-500 mb-2"></i>
                        <h5 class="text-white sidebar-text">Sistema Bomberos</h5>
                        <small class="text-gray-400 sidebar-text">Panel de Administración</small>
                    </div>
                    
                    <!-- Navigation -->
                    <ul class="nav nav-pills flex-column mb-auto">
                        <li class="nav-item">
                            <a href="#dashboard" class="nav-link active" onclick="mostrarSeccion('dashboard')">
                                <i class="fas fa-tachometer-alt me-2"></i>
                                <span class="sidebar-text">Dashboard</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="#formularios" class="nav-link" onclick="mostrarSeccion('formularios')">
                                <i class="fas fa-clipboard-list me-2"></i>
                                <span class="sidebar-text">Gestión de Formularios</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="#personal" class="nav-link" onclick="mostrarSeccion('personal')">
                                <i class="fas fa-users me-2"></i>
                                <span class="sidebar-text">Gestión de Personal</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="#inventario" class="nav-link" onclick="mostrarSeccion('inventario')">
                                <i class="fas fa-boxes me-2"></i>
                                <span class="sidebar-text">Control de Inventario</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="#reportes" class="nav-link" onclick="mostrarSeccion('reportes')">
                                <i class="fas fa-chart-bar me-2"></i>
                                <span class="sidebar-text">Reportes Administrativos</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="#documentos" class="nav-link" onclick="mostrarSeccion('documentos')">
                                <i class="fas fa-file-contract me-2"></i>
                                <span class="sidebar-text">Gestión Documental</span>
                                <span class="notification-badge">3</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="#configuracion" class="nav-link" onclick="mostrarSeccion('configuracion')">
                                <i class="fas fa-cog me-2"></i>
                                <span class="sidebar-text">Configuración</span>
                            </a>
                        </li>
                    </ul>
                    
                    <!-- User Info -->
                    <div class="mt-auto border-top pt-3">
                        <div class="d-flex align-items-center">
                            <img src="https://ui-avatars.com/api/?name=Admin+Bomberos&background=dc2626&color=fff" 
                                 alt="Admin" class="rounded-circle me-2" width="40" height="40">
                            <div class="sidebar-text">
                                <small class="text-white">Administrador</small>
                                <br>
                                <small class="text-gray-400">admin@bomberos.gob</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content p-0">
                <!-- Top Bar -->
                <nav class="navbar navbar-light bg-white border-bottom px-4">
                    <div class="container-fluid">
                        <button class="btn btn-outline-secondary" onclick="toggleSidebar()">
                            <i class="fas fa-bars"></i>
                        </button>
                        
                        <div class="d-flex align-items-center">
                            <div class="me-3">
                                <i class="fas fa-bell text-gray-600 position-relative">
                                    <span class="notification-badge">5</span>
                                </i>
                            </div>
                            <div class="dropdown">
                                <button class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                                    <i class="fas fa-user me-1"></i>
                                    Administrador
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="#"><i class="fas fa-user me-2"></i>Perfil</a></li>
                                    <li><a class="dropdown-item" href="#"><i class="fas fa-cog me-2"></i>Configuración</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item text-danger" href="#"><i class="fas fa-sign-out-alt me-2"></i>Cerrar Sesión</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </nav>

                <!-- Content Sections -->
                <div class="container-fluid p-4">
                    <!-- Dashboard Section -->
                    <section id="dashboard" class="content-section">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h2 class="text-2xl font-bold text-gray-800">
                                <i class="fas fa-tachometer-alt me-2 text-red-600"></i>
                                Dashboard de Administración
                            </h2>
                            <div class="text-muted">
                                <i class="fas fa-clock me-1"></i>
                                <span id="current-time"><?php echo date('d/m/Y H:i:s'); ?></span>
                            </div>
                        </div>

                        <!-- Stats Cards -->
                        <div class="row g-4 mb-4">
                            <div class="col-xl-3 col-md-6">
                                <div class="card dashboard-card stat-card emergencias">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between">
                                            <div>
                                                <h6 class="card-title text-muted mb-2">Emergencias Activas</h6>
                                                <h3 class="text-red-600 fw-bold" id="emergencias-count">3</h3>
                                            </div>
                                            <div class="align-self-center">
                                                <i class="fas fa-fire fa-2x text-red-300"></i>
                                            </div>
                                        </div>
                                        <p class="card-text text-muted small">+1 desde ayer</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-3 col-md-6">
                                <div class="card dashboard-card stat-card unidades">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between">
                                            <div>
                                                <h6 class="card-title text-muted mb-2">Unidades Operativas</h6>
                                                <h3 class="text-blue-600 fw-bold" id="unidades-count">24</h3>
                                            </div>
                                            <div class="align-self-center">
                                                <i class="fas fa-truck fa-2x text-blue-300"></i>
                                            </div>
                                        </div>
                                        <p class="card-text text-muted small">Todas operativas</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-3 col-md-6">
                                <div class="card dashboard-card stat-card personal">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between">
                                            <div>
                                                <h6 class="card-title text-muted mb-2">Personal Activo</h6>
                                                <h3 class="text-green-600 fw-bold" id="personal-count">156</h3>
                                            </div>
                                            <div class="align-self-center">
                                                <i class="fas fa-users fa-2x text-green-300"></i>
                                            </div>
                                        </div>
                                        <p class="card-text text-muted small">12 en licencia</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-3 col-md-6">
                                <div class="card dashboard-card stat-card formularios">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between">
                                            <div>
                                                <h6 class="card-title text-muted mb-2">Formularios Pendientes</h6>
                                                <h3 class="text-purple-600 fw-bold" id="formularios-count">18</h3>
                                            </div>
                                            <div class="align-self-center">
                                                <i class="fas fa-clipboard-list fa-2x text-purple-300"></i>
                                            </div>
                                        </div>
                                        <p class="card-text text-muted small">Por revisar</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Charts and Tables -->
                        <div class="row g-4">
                            <div class="col-lg-8">
                                <div class="chart-container">
                                    <h5 class="fw-bold mb-3">Estadísticas de Emergencias</h5>
                                    <canvas id="emergenciasChart" height="250"></canvas>
                                </div>
                            </div>
                            <div class="col-lg-4">
                                <div class="chart-container">
                                    <h5 class="fw-bold mb-3">Distribución de Personal</h5>
                                    <canvas id="personalChart" height="250"></canvas>
                                </div>
                            </div>
                        </div>

                        <!-- Recent Activity -->
                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="card dashboard-card">
                                    <div class="card-header bg-white">
                                        <h5 class="card-title mb-0">
                                            <i class="fas fa-history me-2"></i>
                                            Actividad Reciente
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>Usuario</th>
                                                        <th>Acción</th>
                                                        <th>Fecha/Hora</th>
                                                        <th>Estado</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr>
                                                        <td>Juan Pérez</td>
                                                        <td>Registro de formulario de incidente</td>
                                                        <td>15/01/2024 14:30</td>
                                                        <td><span class="badge-estado bg-success">Completado</span></td>
                                                    </tr>
                                                    <tr>
                                                        <td>María García</td>
                                                        <td>Actualización de inventario</td>
                                                        <td>15/01/2024 13:15</td>
                                                        <td><span class="badge-estado bg-warning text-dark">Pendiente</span></td>
                                                    </tr>
                                                    <tr>
                                                        <td>Carlos López</td>
                                                        <td>Reporte de mantenimiento</td>
                                                        <td>15/01/2024 11:45</td>
                                                        <td><span class="badge-estado bg-success">Completado</span></td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- Formularios Section -->
                    <section id="formularios" class="content-section" style="display: none;">
                        <h2 class="text-2xl font-bold text-gray-800 mb-4">
                            <i class="fas fa-clipboard-list me-2 text-purple-600"></i>
                            Gestión de Formularios
                        </h2>
                        
                        <div class="row">
                            <div class="col-md-8">
                                <div class="card dashboard-card">
                                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                                        <h5 class="card-title mb-0">Formularios Pendientes</h5>
                                        <button class="btn btn-admin btn-sm" onclick="mostrarModalFormulario()">
                                            <i class="fas fa-plus me-1"></i> Nuevo Formulario
                                        </button>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>ID</th>
                                                        <th>Tipo</th>
                                                        <th>Solicitante</th>
                                                        <th>Fecha</th>
                                                        <th>Prioridad</th>
                                                        <th>Acciones</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr>
                                                        <td>#F-001</td>
                                                        <td>Incidente</td>
                                                        <td>Estación Central</td>
                                                        <td>15/01/2024</td>
                                                        <td><span class="badge-estado bg-danger">Alta</span></td>
                                                        <td>
                                                            <button class="btn btn-sm btn-outline-primary me-1">
                                                                <i class="fas fa-eye"></i>
                                                            </button>
                                                            <button class="btn btn-sm btn-outline-success me-1">
                                                                <i class="fas fa-check"></i>
                                                            </button>
                                                            <button class="btn btn-sm btn-outline-danger">
                                                                <i class="fas fa-times"></i>
                                                            </button>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td>#F-002</td>
                                                        <td>Mantenimiento</td>
                                                        <td>Unidad 101</td>
                                                        <td>14/01/2024</td>
                                                        <td><span class="badge-estado bg-warning text-dark">Media</span></td>
                                                        <td>
                                                            <button class="btn btn-sm btn-outline-primary me-1">
                                                                <i class="fas fa-eye"></i>
                                                            </button>
                                                            <button class="btn btn-sm btn-outline-success me-1">
                                                                <i class="fas fa-check"></i>
                                                            </button>
                                                            <button class="btn btn-sm btn-outline-danger">
                                                                <i class="fas fa-times"></i>
                                                            </button>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card dashboard-card">
                                    <div class="card-header bg-white">
                                        <h5 class="card-title mb-0">Estadísticas de Formularios</h5>
                                    </div>
                                    <div class="card-body">
                                        <canvas id="formulariosChart" height="200"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- Personal Section -->
                    <section id="personal" class="content-section" style="display: none;">
                        <h2 class="text-2xl font-bold text-gray-800 mb-4">
                            <i class="fas fa-users me-2 text-blue-600"></i>
                            Gestión de Personal
                        </h2>
                        
                        <div class="row">
                            <div class="col-md-12">
                                <div class="card dashboard-card">
                                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                                        <h5 class="card-title mb-0">Lista de Personal</h5>
                                        <button class="btn btn-admin btn-sm">
                                            <i class="fas fa-user-plus me-1"></i> Agregar Personal
                                        </button>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>Nombre</th>
                                                        <th>Rango</th>
                                                        <th>Estación</th>
                                                        <th>Turno</th>
                                                        <th>Estado</th>
                                                        <th>Contacto</th>
                                                        <th>Acciones</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr>
                                                        <td>Juan Pérez</td>
                                                        <td>Bombero</td>
                                                        <td>Central</td>
                                                        <td>Mañana</td>
                                                        <td><span class="badge-estado bg-success">Activo</span></td>
                                                        <td>juan@bomberos.gob</td>
                                                        <td>
                                                            <button class="btn btn-sm btn-outline-primary me-1">
                                                                <i class="fas fa-edit"></i>
                                                            </button>
                                                            <button class="btn btn-sm btn-outline-info me-1">
                                                                <i class="fas fa-eye"></i>
                                                            </button>
                                                            <button class="btn btn-sm btn-outline-danger">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td>María García</td>
                                                        <td>Oficial</td>
                                                        <td>Norte</td>
                                                        <td>Tarde</td>
                                                        <td><span class="badge-estado bg-success">Activo</span></td>
                                                        <td>maria@bomberos.gob</td>
                                                        <td>
                                                            <button class="btn btn-sm btn-outline-primary me-1">
                                                                <i class="fas fa-edit"></i>
                                                            </button>
                                                            <button class="btn btn-sm btn-outline-info me-1">
                                                                <i class="fas fa-eye"></i>
                                                            </button>
                                                            <button class="btn btn-sm btn-outline-danger">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- Inventario Section -->
                    <section id="inventario" class="content-section" style="display: none;">
                        <h2 class="text-2xl font-bold text-gray-800 mb-4">
                            <i class="fas fa-boxes me-2 text-blue-600"></i>
                            Control de Inventario
                        </h2>
                        
                        <div class="row g-4 mb-4">
                            <div class="col-md-3">
                                <div class="card dashboard-card">
                                    <div class="card-body text-center">
                                        <h6 class="text-muted mb-2">Equipos Disponibles</h6>
                                        <h3 class="text-blue-600 fw-bold">127</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card dashboard-card">
                                    <div class="card-body text-center">
                                        <h6 class="text-muted mb-2">En Mantenimiento</h6>
                                        <h3 class="text-orange-500 fw-bold">8</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card dashboard-card">
                                    <div class="card-body text-center">
                                        <h6 class="text-muted mb-2">Nivel Bajo</h6>
                                        <h3 class="text-red-600 fw-bold">12</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card dashboard-card">
                                    <div class="card-body text-center">
                                        <h6 class="text-muted mb-2">Valor Total</h6>
                                        <h3 class="text-green-600 fw-bold">$45.8K</h3>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card dashboard-card">
                            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0">Inventario de Equipos</h5>
                                <button class="btn btn-admin btn-sm">
                                    <i class="fas fa-plus me-1"></i> Agregar Equipo
                                </button>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Código</th>
                                                <th>Descripción</th>
                                                <th>Cantidad</th>
                                                <th>Ubicación</th>
                                                <th>Estado</th>
                                                <th>Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td><strong>EQ-001</strong></td>
                                                <td>Manguera de Incendio 50m</td>
                                                <td><span class="badge bg-success">24</span></td>
                                                <td>Almacén Central</td>
                                                <td><span class="badge-estado bg-success">Operativo</span></td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-primary"><i class="fas fa-edit"></i></button>
                                                    <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td><strong>EQ-002</strong></td>
                                                <td>Escalera Extensible</td>
                                                <td><span class="badge bg-warning text-dark">8</span></td>
                                                <td>Estación Sur</td>
                                                <td><span class="badge-estado bg-warning text-dark">En Mantenimiento</span></td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-primary"><i class="fas fa-edit"></i></button>
                                                    <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td><strong>EQ-003</strong></td>
                                                <td>Traje de Protección</td>
                                                <td><span class="badge bg-danger">4</span></td>
                                                <td>Almacén Central</td>
                                                <td><span class="badge-estado bg-danger">Nivel Bajo</span></td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-primary"><i class="fas fa-edit"></i></button>
                                                    <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td><strong>EQ-004</strong></td>
                                                <td>Casco de Seguridad</td>
                                                <td><span class="badge bg-success">45</span></td>
                                                <td>Estación Norte</td>
                                                <td><span class="badge-estado bg-success">Operativo</span></td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-primary"><i class="fas fa-edit"></i></button>
                                                    <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- Reportes Section -->
                    <section id="reportes" class="content-section" style="display: none;">
                        <h2 class="text-2xl font-bold text-gray-800 mb-4">
                            <i class="fas fa-chart-bar me-2 text-green-600"></i>
                            Reportes Administrativos
                        </h2>

                        <div class="row g-4 mb-4">
                            <div class="col-md-6">
                                <div class="card dashboard-card">
                                    <div class="card-header bg-white">
                                        <h5 class="card-title mb-0">Reporte de Incidentes por Mes</h5>
                                    </div>
                                    <div class="card-body">
                                        <canvas id="reporteIncidentesChart" height="200"></canvas>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card dashboard-card">
                                    <div class="card-header bg-white">
                                        <h5 class="card-title mb-0">Desempeño de Unidades</h5>
                                    </div>
                                    <div class="card-body">
                                        <canvas id="desempenoUnidadesChart" height="200"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card dashboard-card">
                            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0">Reportes Generados</h5>
                                <button class="btn btn-admin btn-sm">
                                    <i class="fas fa-download me-1"></i> Descargar PDF
                                </button>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Tipo de Reporte</th>
                                                <th>Fecha Generación</th>
                                                <th>Período</th>
                                                <th>Estado</th>
                                                <th>Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td><strong>Reporte Mensual</strong></td>
                                                <td>14/11/2024</td>
                                                <td>Noviembre 2024</td>
                                                <td><span class="badge-estado bg-success">Completado</span></td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-info"><i class="fas fa-eye"></i></button>
                                                    <button class="btn btn-sm btn-outline-primary"><i class="fas fa-download"></i></button>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td><strong>Análisis de Personal</strong></td>
                                                <td>13/11/2024</td>
                                                <td>Octubre 2024</td>
                                                <td><span class="badge-estado bg-success">Completado</span></td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-info"><i class="fas fa-eye"></i></button>
                                                    <button class="btn btn-sm btn-outline-primary"><i class="fas fa-download"></i></button>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td><strong>Inventario Trimestral</strong></td>
                                                <td>10/11/2024</td>
                                                <td>Q3 2024</td>
                                                <td><span class="badge-estado bg-warning text-dark">En Proceso</span></td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-info"><i class="fas fa-eye"></i></button>
                                                    <button class="btn btn-sm btn-outline-primary"><i class="fas fa-download"></i></button>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- Documentos Section -->
                    <section id="documentos" class="content-section" style="display: none;">
                        <h2 class="text-2xl font-bold text-gray-800 mb-4">
                            <i class="fas fa-file-contract me-2 text-purple-600"></i>
                            Gestión Documental
                        </h2>

                        <div class="row g-4 mb-4">
                            <div class="col-md-3">
                                <div class="card dashboard-card">
                                    <div class="card-body text-center">
                                        <i class="fas fa-file-pdf fa-2x text-red-500 mb-2"></i>
                                        <h6 class="text-muted mb-2">Documentos PDF</h6>
                                        <h3 class="text-red-600 fw-bold">24</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card dashboard-card">
                                    <div class="card-body text-center">
                                        <i class="fas fa-file-word fa-2x text-blue-500 mb-2"></i>
                                        <h6 class="text-muted mb-2">Documentos Word</h6>
                                        <h3 class="text-blue-600 fw-bold">18</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card dashboard-card">
                                    <div class="card-body text-center">
                                        <i class="fas fa-file-excel fa-2x text-green-500 mb-2"></i>
                                        <h6 class="text-muted mb-2">Hojas de Cálculo</h6>
                                        <h3 class="text-green-600 fw-bold">12</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card dashboard-card">
                                    <div class="card-body text-center">
                                        <i class="fas fa-file-archive fa-2x text-orange-500 mb-2"></i>
                                        <h6 class="text-muted mb-2">Otros Archivos</h6>
                                        <h3 class="text-orange-600 fw-bold">8</h3>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card dashboard-card">
                            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0">Documentos en Sistema</h5>
                                <button class="btn btn-admin btn-sm">
                                    <i class="fas fa-upload me-1"></i> Subir Documento
                                </button>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Nombre</th>
                                                <th>Tipo</th>
                                                <th>Tamaño</th>
                                                <th>Fecha Carga</th>
                                                <th>Cargado por</th>
                                                <th>Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td><i class="fas fa-file-pdf text-red-600 me-2"></i><strong>Protocolo_Emergencias_2024.pdf</strong></td>
                                                <td>PDF</td>
                                                <td>2.4 MB</td>
                                                <td>12/11/2024</td>
                                                <td>Admin</td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-info"><i class="fas fa-download"></i></button>
                                                    <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td><i class="fas fa-file-word text-blue-600 me-2"></i><strong>Reglamento_Interno.docx</strong></td>
                                                <td>DOCX</td>
                                                <td>1.8 MB</td>
                                                <td>10/11/2024</td>
                                                <td>Admin</td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-info"><i class="fas fa-download"></i></button>
                                                    <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td><i class="fas fa-file-excel text-green-600 me-2"></i><strong>Inventario_Q4_2024.xlsx</strong></td>
                                                <td>XLSX</td>
                                                <td>856 KB</td>
                                                <td>08/11/2024</td>
                                                <td>Maria García</td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-info"><i class="fas fa-download"></i></button>
                                                    <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- Configuración Section -->
                    <section id="configuracion" class="content-section" style="display: none;">
                        <h2 class="text-2xl font-bold text-gray-800 mb-4">
                            <i class="fas fa-cog me-2 text-gray-600"></i>
                            Configuración del Sistema
                        </h2>

                        <div class="row">
                            <div class="col-lg-8">
                                <div class="form-section mb-4">
                                    <h5 class="fw-bold mb-4">Configuración General</h5>
                                    <form>
                                        <div class="mb-3">
                                            <label class="form-label">Nombre de la Institución</label>
                                            <input type="text" class="form-control" value="Cuerpo de Bomberos del Chocó" disabled>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Correo de Contacto</label>
                                            <input type="email" class="form-control" value="admin@bomberos.gov.co">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Teléfono de Emergencia</label>
                                            <input type="tel" class="form-control" value="911">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Dirección Principal</label>
                                            <input type="text" class="form-control" value="Calle Principal 123, Quibdó">
                                        </div>
                                    </form>
                                </div>

                                <div class="form-section mb-4">
                                    <h5 class="fw-bold mb-4">Configuración de Seguridad</h5>
                                    <form>
                                        <div class="mb-3">
                                            <label class="form-label">Política de Contraseña</label>
                                            <select class="form-select">
                                                <option selected>Contraseña fuerte (Recomendado)</option>
                                                <option>Contraseña media</option>
                                                <option>Contraseña simple</option>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="verificacion2fa" checked>
                                                <label class="form-check-label" for="verificacion2fa">
                                                    Habilitar autenticación de dos factores
                                                </label>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="notificacionesEmail" checked>
                                                <label class="form-check-label" for="notificacionesEmail">
                                                    Notificaciones por correo
                                                </label>
                                            </div>
                                        </div>
                                    </form>
                                </div>

                                <div class="form-section">
                                    <h5 class="fw-bold mb-4">Acciones del Sistema</h5>
                                    <button class="btn btn-admin btn-sm me-2">
                                        <i class="fas fa-download me-1"></i> Hacer Copia de Seguridad
                                    </button>
                                    <button class="btn btn-outline-warning btn-sm me-2">
                                        <i class="fas fa-refresh me-1"></i> Restaurar Base de Datos
                                    </button>
                                    <button class="btn btn-outline-danger btn-sm">
                                        <i class="fas fa-eraser me-1"></i> Limpiar Caché
                                    </button>
                                </div>
                            </div>

                            <div class="col-lg-4">
                                <div class="form-section">
                                    <h5 class="fw-bold mb-4">Información del Sistema</h5>
                                    <div class="mb-3">
                                        <p class="text-muted mb-1">Versión</p>
                                        <p class="fw-bold">2.1.0</p>
                                    </div>
                                    <div class="mb-3">
                                        <p class="text-muted mb-1">Última Actualización</p>
                                        <p class="fw-bold">13/11/2024</p>
                                    </div>
                                    <div class="mb-3">
                                        <p class="text-muted mb-1">Base de Datos</p>
                                        <p class="fw-bold">MySQL 8.0</p>
                                    </div>
                                    <div class="mb-3">
                                        <p class="text-muted mb-1">Espacio en Disco</p>
                                        <div class="progress">
                                            <div class="progress-bar bg-success" style="width: 65%"></div>
                                        </div>
                                        <small class="text-muted">65% Utilizado (3.2 GB / 5 GB)</small>
                                    </div>
                                    <div class="mb-3">
                                        <p class="text-muted mb-1">Usuarios Activos</p>
                                        <p class="fw-bold">
                                            <span class="badge bg-success">45</span>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>
                    
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para Nuevo Formulario -->
    <div class="modal fade" id="modalFormulario" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-white">
                        <i class="fas fa-plus-circle me-2"></i>
                        Nuevo Formulario
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="formNuevoFormulario">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Tipo de Formulario</label>
                                <select class="form-select" required>
                                    <option value="">Seleccionar...</option>
                                    <option value="incidente">Reporte de Incidente</option>
                                    <option value="mantenimiento">Solicitud de Mantenimiento</option>
                                    <option value="inventario">Control de Inventario</option>
                                    <option value="personal">Registro de Personal</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Prioridad</label>
                                <select class="form-select" required>
                                    <option value="baja">Baja</option>
                                    <option value="media">Media</option>
                                    <option value="alta">Alta</option>
                                    <option value="urgente">Urgente</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Descripción</label>
                                <textarea class="form-control" rows="4" placeholder="Descripción detallada..." required></textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Adjuntar Archivos</label>
                                <input type="file" class="form-control" multiple>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-admin" onclick="guardarFormulario()">
                        <i class="fas fa-save me-1"></i> Guardar Formulario
                    </button>
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
        
        // Inicializar tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        });

        // Toggle sidebar
        function toggleSidebar() {
            const sidebar = document.querySelector('.sidebar');
            sidebar.classList.toggle('collapsed');
        }

        // Mostrar sección específica
        function mostrarSeccion(seccionId) {
            // Ocultar todas las secciones
            document.querySelectorAll('.content-section').forEach(section => {
                section.style.display = 'none';
            });
            
            // Mostrar la sección seleccionada
            document.getElementById(seccionId).style.display = 'block';
            
            // Actualizar navegación activa
            document.querySelectorAll('.nav-link').forEach(link => {
                link.classList.remove('active');
            });
            event.target.classList.add('active');
        }

        // Mostrar modal de formulario
        function mostrarModalFormulario() {
            const modal = new bootstrap.Modal(document.getElementById('modalFormulario'));
            modal.show();
        }

        // Guardar formulario
        function guardarFormulario() {
            alert('Formulario guardado exitosamente');
            const modal = bootstrap.Modal.getInstance(document.getElementById('modalFormulario'));
            modal.hide();
        }

        // Actualizar hora en tiempo real
        function actualizarHora() {
            const now = new Date();
            document.getElementById('current-time').textContent = 
                now.toLocaleDateString('es-ES') + ' ' + now.toLocaleTimeString('es-ES');
        }
        setInterval(actualizarHora, 1000);

        // Gráficos con Chart.js
        document.addEventListener('DOMContentLoaded', function() {
            // Gráfico de emergencias
            const emergenciasCtx = document.getElementById('emergenciasChart').getContext('2d');
            new Chart(emergenciasCtx, {
                type: 'line',
                data: {
                    labels: ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun'],
                    datasets: [{
                        label: 'Emergencias',
                        data: [12, 19, 15, 25, 22, 30],
                        borderColor: '#dc2626',
                        backgroundColor: 'rgba(220, 38, 38, 0.1)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });

            // Gráfico de personal
            const personalCtx = document.getElementById('personalChart').getContext('2d');
            new Chart(personalCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Activo', 'Licencia', 'Vacaciones', 'Capacitación'],
                    datasets: [{
                        data: [120, 12, 8, 16],
                        backgroundColor: [
                            '#16a34a',
                            '#f59e0b',
                            '#dc2626',
                            '#2563eb'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });

            // Gráfico de formularios
            const formulariosCtx = document.getElementById('formulariosChart').getContext('2d');
            new Chart(formulariosCtx, {
                type: 'bar',
                data: {
                    labels: ['Pendientes', 'En Proceso', 'Completados', 'Rechazados'],
                    datasets: [{
                        data: [18, 12, 45, 3],
                        backgroundColor: [
                            '#f59e0b',
                            '#2563eb',
                            '#16a34a',
                            '#dc2626'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });

            // Gráfico de reportes - Incidentes
            const reporteIncidentesCtx = document.getElementById('reporteIncidentesChart').getContext('2d');
            new Chart(reporteIncidentesCtx, {
                type: 'bar',
                data: {
                    labels: ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio'],
                    datasets: [{
                        label: 'Incidentes',
                        data: [45, 52, 48, 61, 55, 67],
                        backgroundColor: '#dc2626'
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });

            // Gráfico de desempeño
            const desempenoUnidadesCtx = document.getElementById('desempenoUnidadesChart').getContext('2d');
            new Chart(desempenoUnidadesCtx, {
                type: 'radar',
                data: {
                    labels: ['Tiempo Respuesta', 'Disponibilidad', 'Eficacia', 'Mantenimiento', 'Seguridad'],
                    datasets: [{
                        label: 'Desempeño',
                        data: [85, 92, 88, 80, 95],
                        borderColor: '#2563eb',
                        backgroundColor: 'rgba(37, 99, 235, 0.2)'
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        });

        // Simular datos en tiempo real
        function simularDatosTiempoReal() {
            document.getElementById('emergencias-count').textContent = 
                Math.floor(Math.random() * 5) + 1;
            document.getElementById('formularios-count').textContent = 
                Math.floor(Math.random() * 10) + 15;
        }
        setInterval(simularDatosTiempoReal, 10000);
    </script>
</body>
</html>