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
    <title>Oficial - Sistema de Bomberos</title>
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --oficial: #2563eb;
            --oficial-dark: #1d4ed8;
        }
        
        body {
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
        }
        
        .sidebar-oficial {
            background: linear-gradient(180deg, #1e293b 0%, #0f172a 100%);
            min-height: 100vh;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
        }
        
        .nav-link-oficial {
            color: #cbd5e1;
            border-radius: 8px;
            margin: 4px 0;
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
            padding-left: 12px;
        }
        
        .nav-link-oficial:hover {
            background: rgba(37, 99, 235, 0.1);
            color: #93c5fd;
            border-left-color: var(--oficial);
        }
        
        .nav-link-oficial.active {
            background: var(--oficial);
            color: white;
            border-left-color: var(--oficial);
        }
        
        .card-oficial {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            border-top: 4px solid var(--oficial);
            background: white;
        }
        
        .card-oficial:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 35px rgba(37, 99, 235, 0.15);
        }
        
        .card-header {
            border-bottom: 2px solid #e0f2fe !important;
            background-color: #f9fafb !important;
        }
        
        .btn-oficial {
            background: linear-gradient(135deg, var(--oficial), var(--oficial-dark));
            color: white;
            border: none;
            border-radius: 8px;
            padding: 10px 20px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-oficial:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(37, 99, 235, 0.3);
            color: white;
        }
        
        .emergency-badge {
            animation: pulse 1.5s infinite;
        }
        
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.5; }
            100% { opacity: 1; }
        }
        
        .map-container {
            height: 400px;
            background: linear-gradient(135deg, #e0f2fe, #bae6fd);
            border-radius: 10px;
            position: relative;
            overflow: hidden;
        }
        
        .section-divider {
            background: linear-gradient(90deg, transparent, #2563eb, transparent);
            height: 3px;
            margin: 2rem 0;
            border-radius: 2px;
        }
        
        .emergency-card {
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .emergency-card:hover {
            transform: translateX(5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        
        .unit-status {
            padding: 10px;
            border-radius: 8px;
            margin: 5px 0;
            transition: all 0.3s ease;
        }
        
        .unit-status:hover {
            background: #f0f9ff;
        }
        
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.8rem;
        }
    </style>
</head>
<body>
    <!-- Emergency Alert -->
    <div class="bg-blue-600 text-white py-2">
        <div class="container text-center">
            <i class="fas fa-user-tie me-2"></i>
            <strong>OFICIAL DE GUARDIA:</strong> Coordinación Operativa Activa - Control de Emergencias
        </div>
    </div>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 sidebar-oficial p-0">
                <div class="d-flex flex-column p-3">
                    <!-- Logo -->
                    <div class="text-center mb-4">
                        <i class="fas fa-user-tie fa-2x text-blue-500 mb-2"></i>
                        <h5 class="text-white">OFICIAL</h5>
                        <small class="text-gray-400">Coordinación Operativa</small>
                    </div>
                    
                    <!-- Navigation -->
                    <ul class="nav nav-pills flex-column mb-auto">
                        <li class="nav-item">
                            <a href="#" class="nav-link-oficial active">
                                <i class="fas fa-tachometer-alt me-2"></i>
                                Control Operativo
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="#" class="nav-link-oficial">
                                <i class="fas fa-fire me-2"></i>
                                Gestión Incidentes
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="#" class="nav-link-oficial">
                                <i class="fas fa-truck me-2"></i>
                                Control Unidades
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="#" class="nav-link-oficial">
                                <i class="fas fa-walkie-talkie me-2"></i>
                                Comunicaciones
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="#" class="nav-link-oficial">
                                <i class="fas fa-file-alt me-2"></i>
                                Reportes Operativos
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
                        <span class="navbar-brand fw-bold text-blue-600">
                            <i class="fas fa-fire-extinguisher me-2"></i>
                            Centro de Operaciones - Oficial
                        </span>
                        <div class="d-flex align-items-center gap-3">
                            <span class="badge bg-blue-600">
                                <i class="fas fa-broadcast-tower me-1"></i>En Línea
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
                                <i class="fas fa-user-tie me-2 text-blue-600"></i>
                                Panel de Control Operativo
                            </h2>
                            <p class="text-gray-600">Coordinación y gestión de emergencias en tiempo real</p>
                        </div>
                    </div>

                    <!-- Quick Stats -->
                    <div class="row g-4 mb-4">
                        <div class="col-xl-3 col-md-6">
                            <div class="card card-oficial">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h6 class="text-muted mb-2">Emergencias Activas</h6>
                                            <h3 class="text-danger fw-bold" id="emergencias-count">2</h3>
                                        </div>
                                        <i class="fas fa-fire fa-2x text-red-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6">
                            <div class="card card-oficial">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h6 class="text-muted mb-2">Unidades en Operación</h6>
                                            <h3 class="text-blue-600 fw-bold" id="unidades-count">6</h3>
                                        </div>
                                        <i class="fas fa-truck fa-2x text-blue-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6">
                            <div class="card card-oficial">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h6 class="text-muted mb-2">Personal Desplegado</h6>
                                            <h3 class="text-green-600 fw-bold" id="personal-count">24</h3>
                                        </div>
                                        <i class="fas fa-users fa-2x text-green-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6">
                            <div class="card card-oficial">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h6 class="text-muted mb-2">Tiempo Promedio Respuesta</h6>
                                            <h3 class="text-purple-600 fw-bold" id="tiempo-respuesta">6.2 min</h3>
                                        </div>
                                        <i class="fas fa-clock fa-2x text-purple-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Section Divider -->
                    <div class="section-divider"></div>

                    <!-- Emergency Overview -->
                    <div class="row g-4 mb-4">
                        <div class="col-lg-8">
                            <div class="card card-oficial">
                                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-fire me-2 text-red-600"></i>
                                        Emergencias Activas - Control Directo
                                    </h5>
                                    <button class="btn btn-oficial btn-sm" onclick="abrirModalEmergencia()">
                                        <i class="fas fa-plus me-1"></i> Nueva Emergencia
                                    </button>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3" id="emergencias-container">
                                        <!-- Las emergencias se cargan aquí dinámicamente -->
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-lg-4">
                            <div class="card card-oficial">
                                <div class="card-header bg-white">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-truck me-2"></i>
                                        Estado de Unidades
                                    </h5>
                                </div>
                                <div class="card-body p-0">
                                    <div class="list-group list-group-flush" id="unidades-lista">
                                        <!-- Las unidades se cargan aquí dinámicamente -->
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Section Divider -->
                    <div class="section-divider"></div>

                    <!-- Map and Communications -->
                    <div class="row g-4">
                        <div class="col-lg-8">
                            <div class="card card-oficial">
                                <div class="card-header bg-white">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-map-marked-alt me-2"></i>
                                        Mapa Operativo - Unidades y Emergencias en Tiempo Real
                                    </h5>
                                </div>
                                <div class="card-body p-0">
                                    <div class="map-container d-flex align-items-center justify-content-center position-relative" id="mapa-interactivo">
                                        <div class="text-center text-blue-600" style="width: 100%; height: 100%; display: flex; flex-direction: column; align-items: center; justify-content: center;">
                                            <i class="fas fa-map fa-4x mb-3"></i>
                                            <h5>Mapa de Operaciones</h5>
                                            <p class="text-muted">Mostrando ubicaciones en tiempo real</p>
                                            <div class="mt-3">
                                                <span class="badge bg-red-500 me-2">
                                                    <i class="fas fa-map-pin me-1"></i>Emergencias
                                                </span>
                                                <span class="badge bg-blue-500 me-2">
                                                    <i class="fas fa-truck me-1"></i>Unidades
                                                </span>
                                                <span class="badge bg-green-500">
                                                    <i class="fas fa-signal me-1"></i>En Línea
                                                </span>
                                            </div>
                                        </div>
                                        
                                        <!-- Marcadores en el mapa -->
                                        <div id="mapa-marcadores" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%;"></div>
                                    </div>
                                    
                                    <!-- Información de emergencias reportadas -->
                                    <div class="p-3 border-top" style="background: #f9fafb;">
                                        <h6 class="fw-bold mb-2">
                                            <i class="fas fa-exclamation-circle text-danger me-2"></i>
                                            Emergencias Reportadas con GPS
                                        </h6>
                                        <div id="emergencias-gps" style="max-height: 200px; overflow-y: auto;">
                                            <!-- Se carga dinámicamente -->
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-lg-4">
                            <div class="card card-oficial">
                                <div class="card-header bg-white">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-walkie-talkie me-2"></i>
                                        Comunicaciones
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Canal de Comunicación</label>
                                        <select class="form-select" id="canal-select" onchange="cambiarCanal()">
                                            <option value="principal">Canal Principal (Todos)</option>
                                            <option value="incendios">Canal Incendios</option>
                                            <option value="rescates">Canal Rescates</option>
                                            <option value="comando">Canal Comando</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Mensaje Urgente</label>
                                        <textarea class="form-control" id="mensaje-urgente" rows="3" placeholder="Escriba mensaje de emergencia..."></textarea>
                                    </div>
                                    <button class="btn btn-oficial w-100" onclick="transmitirMensaje()">
                                        <i class="fas fa-broadcast-tower me-1"></i> Transmitir Mensaje
                                    </button>
                                    
                                    <div class="mt-3 p-2 bg-light rounded">
                                        <small class="text-muted">
                                            <i class="fas fa-info-circle me-1"></i>
                                            Última transmisión: <span id="ultima-transmision">Hace 2 min</span>
                                        </small>
                                    </div>

                                    <div class="mt-3 border-top pt-3">
                                        <label class="form-label fw-bold">Mensajes Rápidos</label>
                                        <div class="d-grid gap-2">
                                            <button class="btn btn-outline-danger btn-sm" onclick="mensajeRapido('¡ALERTA! Incidente crítico reportado')">
                                                <i class="fas fa-exclamation-triangle me-1"></i>Alerta Crítica
                                            </button>
                                            <button class="btn btn-outline-warning btn-sm" onclick="mensajeRapido('Solicito refuerzos inmediatamente')">
                                                <i class="fas fa-people-carry me-1"></i>Solicitar Refuerzos
                                            </button>
                                            <button class="btn btn-outline-success btn-sm" onclick="mensajeRapido('Incidente controlado')">
                                                <i class="fas fa-check-circle me-1"></i>Incidente Controlado
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Section Divider -->
                    <div class="section-divider"></div>

                    <!-- Operations History -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card card-oficial">
                                <div class="card-header bg-white">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-history me-2 text-purple-600"></i>
                                        Historial de Operaciones
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-hover" id="historial-table">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Hora</th>
                                                    <th>Tipo Incidente</th>
                                                    <th>Ubicación</th>
                                                    <th>Unidades</th>
                                                    <th>Estado</th>
                                                    <th>Acciones</th>
                                                </tr>
                                            </thead>
                                            <tbody id="historial-body">
                                                <!-- Se carga dinámicamente -->
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para Nueva Emergencia -->
    <div class="modal fade" id="modalEmergencia" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header" style="background: linear-gradient(135deg, #2563eb, #1d4ed8); color: white;">
                    <h5 class="modal-title">
                        <i class="fas fa-plus-circle me-2"></i>
                        Registrar Nueva Emergencia
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="formEmergencia">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Tipo de Emergencia</label>
                                <select class="form-select" id="tipo-emergencia" required>
                                    <option value="">Seleccionar...</option>
                                    <option value="incendio">Incendio</option>
                                    <option value="accidente">Accidente Vial</option>
                                    <option value="rescate">Rescate</option>
                                    <option value="medico">Asistencia Médica</option>
                                    <option value="otro">Otro</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Prioridad</label>
                                <select class="form-select" id="prioridad" required>
                                    <option value="baja">Baja</option>
                                    <option value="media">Media</option>
                                    <option value="alta">Alta</option>
                                    <option value="critica">Crítica</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-bold">Ubicación</label>
                                <input type="text" class="form-control" id="ubicacion" placeholder="Dirección exacta" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-bold">Descripción del Incidente</label>
                                <textarea class="form-control" id="descripcion" rows="3" placeholder="Detalles del incidente..." required></textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Unidades Solicitadas</label>
                                <input type="number" class="form-control" id="unidades-solicitadas" min="1" value="1" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Persona que Reporta</label>
                                <input type="text" class="form-control" id="reporta" placeholder="Nombre/Teléfono" required>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-oficial" onclick="registrarEmergencia()">
                        <i class="fas fa-check-circle me-1"></i> Registrar Emergencia
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
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
            
            // Cargar datos de la página
            cargarEmergencias();
            cargarUnidades();
            cargarHistorial();
            renderizarMapaGPS();
            cargarEmergenciasGPS();
        });
        
        // Actualizar hora
        let emergencias = [
            {
                id: 1,
                tipo: 'INCENDIO INDUSTRIAL',
                ubicacion: 'Av. Industrial 2345, Zona Norte',
                prioridad: 'CRÍTICO',
                unidades: 4,
                bomberos: 16,
                duracion: '45 min',
                estado: 'En curso',
                color: 'warning',
                gps: { lat: 3.5236, lon: -76.5261 } // Quibdó
            },
            {
                id: 2,
                tipo: 'ACCIDENTE VIAL',
                ubicacion: 'Carretera Sur KM 45',
                prioridad: 'ALTO',
                unidades: 2,
                bomberos: 8,
                duracion: '25 min',
                estado: 'Controlado',
                color: 'primary',
                gps: { lat: 3.5100, lon: -76.5400 }
            }
        ];

        let unidades = [
            { id: 'U-101', tipo: 'Camión Incendios', estado: 'Disponible', color: 'success', icon: 'fa-truck text-red-500' },
            { id: 'U-205', tipo: 'Ambulancia Rescate', estado: 'En camino', color: 'warning', icon: 'fa-ambulance text-orange-500' },
            { id: 'U-308', tipo: 'Unidad Comando', estado: 'En emergencia', color: 'danger', icon: 'fa-truck text-blue-500' },
            { id: 'U-412', tipo: 'Escalera', estado: 'Disponible', color: 'success', icon: 'fa-truck text-green-500' }
        ];

        let historial = [
            { hora: '14:30', tipo: 'Incendio', ubicacion: 'Zona Norte', unidades: 4, estado: 'Controlado' },
            { hora: '12:15', tipo: 'Accidente Vial', ubicacion: 'Carretera Sur', unidades: 2, estado: 'Resuelto' },
            { hora: '09:45', tipo: 'Rescate', ubicacion: 'Centro', unidades: 3, estado: 'Completado' }
        ];

        // Actualizar hora
        function updateTime() {
            const now = new Date();
            document.getElementById('current-time').textContent = 
                now.toLocaleDateString('es-ES') + ' ' + now.toLocaleTimeString('es-ES');
        }
        setInterval(updateTime, 1000);
        updateTime();

        // Cargar emergencias
        function cargarEmergencias() {
            const container = document.getElementById('emergencias-container');
            container.innerHTML = '';
            
            emergencias.forEach(e => {
                const html = `
                    <div class="col-md-6">
                        <div class="card emergency-card border-${e.color}">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h6 class="card-title text-${e.color}">
                                        <i class="fas fa-exclamation-triangle me-1"></i>
                                        ${e.tipo}
                                    </h6>
                                    <span class="badge bg-${e.color === 'warning' ? 'danger' : e.color} emergency-badge">${e.prioridad}</span>
                                </div>
                                <p class="card-text small">
                                    <i class="fas fa-map-marker-alt text-muted me-1"></i>
                                    ${e.ubicacion}
                                </p>
                                <div class="row text-center small mb-2">
                                    <div class="col-4">
                                        <strong>${e.unidades}</strong><br>
                                        <span class="text-muted">Unidades</span>
                                    </div>
                                    <div class="col-4">
                                        <strong>${e.bomberos}</strong><br>
                                        <span class="text-muted">Bomberos</span>
                                    </div>
                                    <div class="col-4">
                                        <strong>${e.duracion}</strong><br>
                                        <span class="text-muted">Duración</span>
                                    </div>
                                </div>
                                <div class="d-grid gap-2">
                                    <button class="btn btn-${e.color} btn-sm" onclick="coordinarEmergencia(${e.id})">
                                        <i class="fas fa-broadcast-tower me-1"></i> Coordinar
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                container.innerHTML += html;
            });
        }

        // Cargar unidades
        function cargarUnidades() {
            const container = document.getElementById('unidades-lista');
            container.innerHTML = '';
            
            unidades.forEach(u => {
                const html = `
                    <div class="unit-status list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <i class="fas ${u.icon} me-2"></i>
                            <strong>${u.id}</strong>
                            <small class="text-muted d-block">${u.tipo}</small>
                        </div>
                        <span class="status-badge bg-${u.color}">${u.estado}</span>
                    </div>
                `;
                container.innerHTML += html;
            });
        }

        // Cargar historial
        function cargarHistorial() {
            const tbody = document.getElementById('historial-body');
            tbody.innerHTML = '';
            
            historial.forEach(h => {
                const html = `
                    <tr>
                        <td><strong>${h.hora}</strong></td>
                        <td>${h.tipo}</td>
                        <td>${h.ubicacion}</td>
                        <td><span class="badge bg-info">${h.unidades}</span></td>
                        <td><span class="badge bg-success">${h.estado}</span></td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary" onclick="verDetalles()">
                                <i class="fas fa-eye"></i>
                            </button>
                        </td>
                    </tr>
                `;
                tbody.innerHTML += html;
            });
        }

        // Funciones interactivas
        function abrirModalEmergencia() {
            const modal = new bootstrap.Modal(document.getElementById('modalEmergencia'));
            modal.show();
        }

        function registrarEmergencia() {
            const tipo = document.getElementById('tipo-emergencia').value;
            const ubicacion = document.getElementById('ubicacion').value;
            const prioridad = document.getElementById('prioridad').value;
            
            if (tipo && ubicacion && prioridad) {
                alert('✅ Emergencia registrada correctamente\n\nTipo: ' + tipo + '\nUbicación: ' + ubicacion + '\nPrioridad: ' + prioridad);
                bootstrap.Modal.getInstance(document.getElementById('modalEmergencia')).hide();
                document.getElementById('formEmergencia').reset();
            }
        }

        function coordinarEmergencia(id) {
            alert(`🎯 Coordinando emergencia #${id}\n\nSe han notificado todas las unidades disponibles.`);
        }

        function transmitirMensaje() {
            const canal = document.getElementById('canal-select').value;
            const mensaje = document.getElementById('mensaje-urgente').value;
            
            if (mensaje.trim()) {
                alert(`📢 Mensaje transmitido por Canal: ${canal}\n\nMensaje: "${mensaje}"`);
                document.getElementById('mensaje-urgente').value = '';
                document.getElementById('ultima-transmision').textContent = 'Hace unos segundos';
            } else {
                alert('⚠️ Por favor escriba un mensaje antes de transmitir');
            }
        }

        function cambiarCanal() {
            const canal = document.getElementById('canal-select').value;
            console.log('Canal cambiado a:', canal);
        }

        function mensajeRapido(mensaje) {
            document.getElementById('mensaje-urgente').value = mensaje;
            alert(`📢 Mensaje preparado:\n"${mensaje}"\n\nHaga clic en "Transmitir Mensaje" para enviar.`);
        }

        function verDetalles() {
            alert('📋 Mostrando detalles completos del incidente...');
        }

        // Inicializar
        document.addEventListener('DOMContentLoaded', function() {
            cargarEmergencias();
            cargarUnidades();
            cargarHistorial();
            renderizarMapaGPS();
            cargarEmergenciasGPS();
        });

        // Función para renderizar mapa con GPS
        function renderizarMapaGPS() {
            const marcadoresContainer = document.getElementById('mapa-marcadores');
            marcadoresContainer.innerHTML = '';
            
            emergencias.forEach((e, idx) => {
                if (e.gps) {
                    // Calcular posición en el mapa (entre 10% y 90% del contenedor)
                    const x = 20 + (idx * 30) + Math.random() * 20; // Dispersar marcadores
                    const y = 20 + Math.random() * 60;
                    
                    const marker = document.createElement('div');
                    marker.style.position = 'absolute';
                    marker.style.left = x + '%';
                    marker.style.top = y + '%';
                    marker.style.transform = 'translate(-50%, -50%)';
                    marker.style.zIndex = 10;
                    marker.innerHTML = `
                        <div title="${e.tipo} - ${e.ubicacion}" style="cursor: pointer; animation: bounce 2s infinite;">
                            <i class="fas fa-map-pin fa-3x" style="color: #dc2626; filter: drop-shadow(0 2px 4px rgba(0,0,0,0.3));"></i>
                            <div style="position: absolute; top: 100%; left: 50%; transform: translateX(-50%); background: white; padding: 6px 10px; border-radius: 4px; white-space: nowrap; font-size: 0.8rem; box-shadow: 0 2px 8px rgba(0,0,0,0.15); margin-top: 5px;">
                                <strong>${e.tipo}</strong><br>
                                <small>${e.gps.lat.toFixed(4)}, ${e.gps.lon.toFixed(4)}</small>
                            </div>
                        </div>
                        <style>
                            @keyframes bounce {
                                0%, 100% { transform: translate(-50%, -50%) scale(1); }
                                50% { transform: translate(-50%, -60%) scale(1.1); }
                            }
                        </style>
                    `;
                    marker.onclick = function() {
                        alert(`📍 ${e.tipo}\n${e.ubicacion}\n\nCoordenadas: ${e.gps.lat.toFixed(6)}, ${e.gps.lon.toFixed(6)}\n\nhttps://www.google.com/maps?q=${e.gps.lat},${e.gps.lon}`);
                    };
                    marcadoresContainer.appendChild(marker);
                }
            });
        }

        // Función para cargar emergencias con GPS
        function cargarEmergenciasGPS() {
            const container = document.getElementById('emergencias-gps');
            container.innerHTML = '';
            
            emergencias.forEach(e => {
                if (e.gps) {
                    const html = `
                        <div style="padding: 8px; border-bottom: 1px solid #e5e7eb; font-size: 0.9rem;" onclick="alert('📍 ${e.tipo}\\n${e.ubicacion}\\n\\nCoordenadas:\\nLat: ${e.gps.lat.toFixed(6)}\\nLon: ${e.gps.lon.toFixed(6)}');" style="cursor: pointer;">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <strong class="text-danger">
                                        <i class="fas fa-exclamation-circle me-1"></i>
                                        ${e.tipo}
                                    </strong><br>
                                    <small class="text-muted">${e.ubicacion}</small>
                                </div>
                                <span class="badge bg-${e.color}">${e.prioridad}</span>
                            </div>
                            <small style="display: block; margin-top: 4px; color: #059669;">
                                🛰️ ${e.gps.lat.toFixed(6)}, ${e.gps.lon.toFixed(6)}
                            </small>
                            <a href="https://www.google.com/maps?q=${e.gps.lat},${e.gps.lon}" target="_blank" style="font-size: 0.75rem; color: #2563eb; text-decoration: none;">
                                Ver en Google Maps →
                            </a>
                        </div>
                    `;
                    container.innerHTML += html;
                }
            });
        }
    </script>
</body>
</html>