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
    <title>Comandante - Sistema de Bomberos</title>
    
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
            --comandante: #dc2626;
            --comandante-dark: #991b1b;
        }
        
        body {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
        }
        
        .sidebar-comandante {
            background: linear-gradient(180deg, #1e293b 0%, #0f172a 100%);
            min-height: 100vh;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
        }
        
        .nav-link-comandante {
            color: #cbd5e1;
            border-radius: 8px;
            margin: 4px 0;
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
            padding-left: 12px;
        }
        
        .nav-link-comandante:hover {
            background: rgba(220, 38, 38, 0.1);
            color: #fca5a5;
            border-left-color: var(--comandante);
        }
        
        .nav-link-comandante.active {
            background: var(--comandante);
            color: white;
            border-left-color: var(--comandante);
        }
        
        .card-comandante {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            border-top: 4px solid var(--comandante);
            background: white;
        }
        
        .card-comandante:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 35px rgba(220, 38, 38, 0.15);
        }
        
        .card-header {
            border-bottom: 2px solid #e2e8f0 !important;
            background-color: #f9fafb !important;
        }
        
        .btn-comandante {
            background: linear-gradient(135deg, var(--comandante), var(--comandante-dark));
            color: white;
            border: none;
            border-radius: 8px;
            padding: 10px 20px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-comandante:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(220, 38, 38, 0.3);
            color: white;
        }
        
        .badge-comandante {
            background: var(--comandante);
            color: white;
        }
        
        .emergency-alert {
            animation: pulse 2s infinite;
            border-left: 4px solid var(--comandante);
            background: linear-gradient(90deg, #dc2626, #991b1b) !important;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.02); }
            100% { transform: scale(1); }
        }
        
        .divider-line {
            border-top: 2px solid #e2e8f0;
            margin: 1.5rem 0;
        }
        
        .section-divider {
            background: linear-gradient(90deg, transparent, #dc2626, transparent);
            height: 3px;
            margin: 2rem 0;
            border-radius: 2px;
        }
        
        .stat-badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.85rem;
        }
        
        .stat-badge.critical {
            background: #fecaca;
            color: #991b1b;
        }
        
        .stat-badge.warning {
            background: #fef3c7;
            color: #92400e;
        }
        
        .stat-badge.success {
            background: #dcfce7;
            color: #15803d;
        }
        
        .chart-container {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            margin-bottom: 1.5rem;
        }
        
        .info-card {
            border-left: 4px solid var(--comandante);
            padding: 1rem;
            background: #fff5f5;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
        
        .table-dark thead {
            background: linear-gradient(135deg, #1e293b, #0f172a) !important;
        }
    </style>
</head>
<body>
    <!-- Emergency Alert -->
    <div class="emergency-alert bg-red-600 text-white py-2">
        <div class="container text-center">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <strong>MÁXIMA ALERTA:</strong> Sistema de Comandante Activo - Supervisión General
        </div>
    </div>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 sidebar-comandante p-0">
                <div class="d-flex flex-column p-3">
                    <!-- Logo -->
                    <div class="text-center mb-4">
                        <i class="fas fa-crown fa-2x text-red-500 mb-2"></i>
                        <h5 class="text-white">COMANDANTE</h5>
                        <small class="text-gray-400">Supervisión General</small>
                    </div>
                    
                    <!-- Navigation -->
                    <ul class="nav nav-pills flex-column mb-auto">
                        <li class="nav-item">
                            <a href="#" class="nav-link-comandante active">
                                <i class="fas fa-tachometer-alt me-2"></i>
                                Dashboard General
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="#" class="nav-link-comandante">
                                <i class="fas fa-chart-line me-2"></i>
                                Reportes Ejecutivos
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="#" class="nav-link-comandante">
                                <i class="fas fa-users me-2"></i>
                                Gestión de Personal
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="#" class="nav-link-comandante">
                                <i class="fas fa-truck me-2"></i>
                                Control de Recursos
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="#" class="nav-link-comandante">
                                <i class="fas fa-cog me-2"></i>
                                Configuración Sistema
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 bg-gray-50 p-0">
                <!-- Top Bar -->
                <nav class="navbar navbar-light bg-white border-bottom px-4 shadow-sm">
                    <div class="container-fluid">
                        <span class="navbar-brand fw-bold text-red-600">
                            <i class="fas fa-fire-extinguisher me-2"></i>
                            Sistema de Bomberos - Comandancia
                        </span>
                        <div class="d-flex align-items-center gap-3">
                            <span class="badge badge-comandante">
                                <i class="fas fa-shield-alt me-1"></i>Nivel Máximo
                            </span>
                            <span class="text-muted" id="current-time"></span>
                            <a href="index.php" class="btn btn-outline-secondary btn-sm">
                                <i class="fas fa-home me-1"></i>Inicio
                            </a>
                            <button class="btn btn-outline-danger btn-sm">
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
                                <i class="fas fa-crown me-2 text-red-600"></i>
                                Panel de Comandancia
                            </h2>
                            <p class="text-gray-600">Supervisión general y control estratégico del sistema</p>
                        </div>
                    </div>

                    <!-- Stats Overview -->
                    <div class="row g-4 mb-4">
                        <div class="col-xl-3 col-md-6">
                            <div class="card card-comandante">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h6 class="text-muted mb-2">Emergencias Activas</h6>
                                            <h3 class="text-red-600 fw-bold">3</h3>
                                            <small class="text-muted">2 críticas</small>
                                        </div>
                                        <i class="fas fa-fire fa-2x text-red-300 align-self-center"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6">
                            <div class="card card-comandante">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h6 class="text-muted mb-2">Unidades Desplegadas</h6>
                                            <h3 class="text-blue-600 fw-bold">18/24</h3>
                                            <small class="text-muted">75% operativo</small>
                                        </div>
                                        <i class="fas fa-truck fa-2x text-blue-300 align-self-center"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6">
                            <div class="card card-comandante">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h6 class="text-muted mb-2">Personal Activo</h6>
                                            <h3 class="text-green-600 fw-bold">142/156</h3>
                                            <small class="text-muted">91% disponible</small>
                                        </div>
                                        <i class="fas fa-users fa-2x text-green-300 align-self-center"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6">
                            <div class="card card-comandante">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h6 class="text-muted mb-2">Presupuesto Mensual</h6>
                                            <h3 class="text-purple-600 fw-bold">85%</h3>
                                            <small class="text-muted">$245,000/$288,000</small>
                                        </div>
                                        <i class="fas fa-chart-pie fa-2x text-purple-300 align-self-center"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Emergency Overview -->
                    <div class="row g-4 mb-4">
                        <div class="col-lg-8">
                            <div class="card card-comandante">
                                <div class="card-header bg-white">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-fire me-2 text-red-600"></i>
                                        Emergencias Críticas - Control Directo
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead class="table-dark">
                                                <tr>
                                                    <th>Incidente</th>
                                                    <th>Ubicación</th>
                                                    <th>Unidades</th>
                                                    <th>Jefe Operativo</th>
                                                    <th>Estado</th>
                                                    <th>Acciones</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td>
                                                        <strong>Incendio Industrial</strong>
                                                        <br><small class="text-muted">Zona Norte</small>
                                                    </td>
                                                    <td>Av. Industrial 2345</td>
                                                    <td>
                                                        <span class="badge bg-danger">4 unidades</span>
                                                    </td>
                                                    <td>Oficial García</td>
                                                    <td><span class="badge bg-warning">En curso</span></td>
                                                    <td>
                                                        <button class="btn btn-comandante btn-sm">
                                                            <i class="fas fa-broadcast-tower me-1"></i>Comandar
                                                        </button>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <strong>Rescate en Altura</strong>
                                                        <br><small class="text-muted">Centro</small>
                                                    </td>
                                                    <td>Torre Ejecutiva</td>
                                                    <td>
                                                        <span class="badge bg-primary">2 unidades</span>
                                                    </td>
                                                    <td>Oficial Martínez</td>
                                                    <td><span class="badge bg-success">Controlado</span></td>
                                                    <td>
                                                        <button class="btn btn-outline-secondary btn-sm">
                                                            <i class="fas fa-eye me-1"></i>Supervisar
                                                        </button>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-lg-4">
                            <div class="card card-comandante">
                                <div class="card-header bg-white">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-chart-bar me-2"></i>
                                        Métricas Ejecutivas
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <canvas id="metricsChart" height="250"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Section Divider -->
                    <div class="section-divider"></div>

                    <!-- Quick Actions -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h4 class="text-gray-800 fw-bold mb-3">
                                <i class="fas fa-bolt me-2 text-yellow-500"></i>
                                Acciones de Comando Rápido
                            </h4>
                            <div class="card card-comandante">
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-md-3 col-sm-6">
                                            <button class="btn btn-comandante w-100 h-100 py-4" onclick="alertaGeneral()">
                                                <i class="fas fa-bell fa-2x mb-2 d-block"></i>
                                                Alerta General
                                            </button>
                                        </div>
                                        <div class="col-md-3 col-sm-6">
                                            <button class="btn btn-outline-danger w-100 h-100 py-4">
                                                <i class="fas fa-file-pdf fa-2x mb-2 d-block"></i>
                                                Reporte Ejecutivo
                                            </button>
                                        </div>
                                        <div class="col-md-3 col-sm-6">
                                            <button class="btn btn-outline-primary w-100 h-100 py-4">
                                                <i class="fas fa-users-cog fa-2x mb-2 d-block"></i>
                                                Gestión Personal
                                            </button>
                                        </div>
                                        <div class="col-md-3 col-sm-6">
                                            <button class="btn btn-outline-success w-100 h-100 py-4">
                                                <i class="fas fa-cog fa-2x mb-2 d-block"></i>
                                                Configuración
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Section Divider -->
                    <div class="section-divider"></div>

                    <!-- Resources Management -->
                    <div class="row g-4 mb-4">
                        <div class="col-lg-6">
                            <h4 class="text-gray-800 fw-bold mb-3">
                                <i class="fas fa-truck me-2 text-blue-600"></i>
                                Estado de Unidades
                            </h4>
                            <div class="card card-comandante">
                                <div class="card-header bg-white">
                                    <h6 class="card-title mb-0">Disponibilidad por Tipo</h6>
                                </div>
                                <div class="card-body">
                                    <div class="info-card">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <strong>Bombas de Agua</strong>
                                            <span class="stat-badge success">12/12</span>
                                        </div>
                                        <div class="progress" style="height: 8px;">
                                            <div class="progress-bar bg-success" style="width: 100%"></div>
                                        </div>
                                    </div>
                                    <div class="info-card">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <strong>Escaleras Mecánicas</strong>
                                            <span class="stat-badge warning">7/10</span>
                                        </div>
                                        <div class="progress" style="height: 8px;">
                                            <div class="progress-bar bg-warning" style="width: 70%"></div>
                                        </div>
                                    </div>
                                    <div class="info-card">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <strong>Ambulancias</strong>
                                            <span class="stat-badge success">8/8</span>
                                        </div>
                                        <div class="progress" style="height: 8px;">
                                            <div class="progress-bar bg-success" style="width: 100%"></div>
                                        </div>
                                    </div>
                                    <div class="info-card">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <strong>Grúas de Rescate</strong>
                                            <span class="stat-badge critical">2/4</span>
                                        </div>
                                        <div class="progress" style="height: 8px;">
                                            <div class="progress-bar bg-danger" style="width: 50%"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-6">
                            <h4 class="text-gray-800 fw-bold mb-3">
                                <i class="fas fa-users me-2 text-green-600"></i>
                                Personal por Turno
                            </h4>
                            <div class="card card-comandante">
                                <div class="card-header bg-white">
                                    <h6 class="card-title mb-0">Distribución Horaria</h6>
                                </div>
                                <div class="card-body">
                                    <canvas id="turnoChart" height="200"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Section Divider -->
                    <div class="section-divider"></div>

                    <!-- Operational Reports -->
                    <div class="row g-4">
                        <div class="col-lg-6">
                            <h4 class="text-gray-800 fw-bold mb-3">
                                <i class="fas fa-history me-2 text-purple-600"></i>
                                Última Actividad
                            </h4>
                            <div class="card card-comandante">
                                <div class="card-body">
                                    <div class="info-card">
                                        <strong>14:30 - Incendio controlado</strong><br>
                                        <small class="text-muted">Zona Industrial - 4 unidades</small>
                                    </div>
                                    <div class="info-card">
                                        <strong>12:15 - Rescate completado</strong><br>
                                        <small class="text-muted">Accidente vial - 1 herido leve</small>
                                    </div>
                                    <div class="info-card">
                                        <strong>09:45 - Mantenimiento programado</strong><br>
                                        <small class="text-muted">Estación Sur - 2 horas estimadas</small>
                                    </div>
                                    <div class="info-card">
                                        <strong>08:00 - Cambio de turno</strong><br>
                                        <small class="text-muted">142 bomberos operativos</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-6">
                            <h4 class="text-gray-800 fw-bold mb-3">
                                <i class="fas fa-chart-line me-2 text-orange-600"></i>
                                Indicadores de Desempeño
                            </h4>
                            <div class="card card-comandante">
                                <div class="card-body">
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between mb-2">
                                            <strong>Tiempo Promedio Respuesta</strong>
                                            <span class="text-success fw-bold">6.2 min</span>
                                        </div>
                                        <div class="progress" style="height: 10px;">
                                            <div class="progress-bar bg-success" style="width: 85%"></div>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between mb-2">
                                            <strong>Tasa de Resolución</strong>
                                            <span class="text-success fw-bold">94%</span>
                                        </div>
                                        <div class="progress" style="height: 10px;">
                                            <div class="progress-bar bg-success" style="width: 94%"></div>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between mb-2">
                                            <strong>Disponibilidad de Recursos</strong>
                                            <span class="text-warning fw-bold">79%</span>
                                        </div>
                                        <div class="progress" style="height: 10px;">
                                            <div class="progress-bar bg-warning" style="width: 79%"></div>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between mb-2">
                                            <strong>Capacitación del Personal</strong>
                                            <span class="text-success fw-bold">97%</span>
                                        </div>
                                        <div class="progress" style="height: 10px;">
                                            <div class="progress-bar bg-success" style="width: 97%"></div>
                                        </div>
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

        // Función de alerta general
        function alertaGeneral() {
            alert('⚠️ ALERTA GENERAL EMITIDA\n\nTodas las unidades han sido notificadas. Centro de Comando activo.');
        }

        // Gráficos
        document.addEventListener('DOMContentLoaded', function() {
            // Gráfico de métricas
            const metricsCtx = document.getElementById('metricsChart').getContext('2d');
            new Chart(metricsCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Operativo', 'Mantenimiento', 'Reserva', 'Fuera Servicio'],
                    datasets: [{
                        data: [75, 12, 8, 5],
                        backgroundColor: ['#16a34a', '#f59e0b', '#2563eb', '#dc2626'],
                        borderColor: ['#15803d', '#d97706', '#1d4ed8', '#991b1b'],
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { 
                            position: 'bottom',
                            labels: { padding: 15, font: { size: 12, weight: 'bold' } }
                        }
                    }
                }
            });

            // Gráfico de turno
            const turnoCtx = document.getElementById('turnoChart').getContext('2d');
            new Chart(turnoCtx, {
                type: 'bar',
                data: {
                    labels: ['00:00-08:00', '08:00-16:00', '16:00-24:00'],
                    datasets: [{
                        label: 'Personal Activo',
                        data: [48, 142, 52],
                        backgroundColor: ['#3b82f6', '#10b981', '#f59e0b'],
                        borderRadius: 8,
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    indexAxis: 'y',
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        x: {
                            beginAtZero: true,
                            max: 150
                        }
                    }
                }
            });
        });

        // Animar números
        function animateNumbers() {
            const counters = document.querySelectorAll('.fw-bold');
            counters.forEach(counter => {
                const target = parseInt(counter.textContent);
                if (!isNaN(target)) {
                    let current = 0;
                    const increment = target / 30;
                    const timer = setInterval(() => {
                        current += increment;
                        if (current >= target) {
                            counter.textContent = target;
                            clearInterval(timer);
                        } else {
                            counter.textContent = Math.floor(current);
                        }
                    }, 50);
                }
            });
        }
        window.addEventListener('load', animateNumbers);
    </script>
</body>
</html>