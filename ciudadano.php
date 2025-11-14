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
    <title>Ciudadano - Sistema de Bomberos</title>
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --ciudadano: #ea580c;
            --ciudadano-dark: #c2410c;
        }
        
        .hero-section {
            background: linear-gradient(135deg, #ea580c 0%, #c2410c 100%);
            color: white;
        }
        
        .card-ciudadano {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }
        
        .card-ciudadano:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 25px -5px rgba(234, 88, 12, 0.2);
        }
        
        .btn-ciudadano {
            background: linear-gradient(135deg, var(--ciudadano), var(--ciudadano-dark));
            color: white;
            border: none;
            border-radius: 8px;
            padding: 12px 24px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-ciudadano:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 15px rgba(234, 88, 12, 0.3);
            color: white;
        }
        
        .btn-gps {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 10px 15px;
            font-weight: 600;
            transition: all 0.3s ease;
            width: 100%;
        }
        
        .btn-gps:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 15px rgba(16, 185, 129, 0.3);
            color: white;
        }
        
        .btn-gps.loading {
            opacity: 0.7;
            pointer-events: none;
        }
        
        .emergency-card {
            border-left: 4px solid #dc2626;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.02); }
            100% { transform: scale(1); }
        }
        
        .feature-icon {
            width: 80px;
            height: 80px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 2rem;
            background: linear-gradient(135deg, var(--ciudadano), var(--ciudadano-dark));
            color: white;
        }
        
        .nav-pills .nav-link.active {
            background: var(--ciudadano);
            color: white;
        }
        
        .location-info {
            background: #ecfdf5;
            border: 2px solid #10b981;
            border-radius: 8px;
            padding: 12px;
            margin-top: 10px;
            display: none;
        }
        
        .location-info.active {
            display: block;
        }
        
        .location-info i {
            color: #10b981;
        }
        
        .map-container {
            height: 300px;
            background: linear-gradient(135deg, #ecfdf5, #d1fae5);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-top: 10px;
            position: relative;
            overflow: hidden;
            border: 2px solid #10b981;
        }
        
        .map-marker {
            position: absolute;
            animation: dropPin 0.6s ease-out;
        }
        
        @keyframes dropPin {
            0% {
                transform: translateY(-100%) scale(0);
                opacity: 0;
            }
            50% {
                opacity: 1;
            }
            100% {
                transform: translateY(0) scale(1);
                opacity: 1;
            }
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Emergency Header -->
    <div class="emergency-card bg-red-600 text-white py-3">
        <div class="container text-center">
            <i class="fas fa-exclamation-triangle fa-2x me-2"></i>
            <h4 class="d-inline-block mb-0">
                <strong>EMERGENCIA:</strong> Si necesita ayuda inmediata, llame al 
                <span class="fs-3">911</span>
            </h4>
        </div>
    </div>

    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold text-orange-600" href="#">
                <i class="fas fa-fire-extinguisher me-2"></i>
                Bomberos - Portal Ciudadano
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="#inicio">Inicio</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#reportar">Reportar Emergencia</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#servicios">Servicios</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#prevencion">Prevención</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#contacto">Contacto</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section id="inicio" class="hero-section py-5">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h1 class="display-5 fw-bold mb-4">
                        Portal de Servicios para Ciudadanos
                    </h1>
                    <p class="lead mb-4">
                        Acceso público al sistema de bomberos. Reporte emergencias, solicite servicios 
                        y acceda a información de prevención.
                    </p>
                    <div class="d-flex gap-3 flex-wrap">
                        <a href="#reportar" class="btn btn-light btn-lg">
                            <i class="fas fa-exclamation-triangle me-2"></i>Reportar Emergencia
                        </a>
                        <a href="#servicios" class="btn btn-outline-light btn-lg">
                            <i class="fas fa-info-circle me-2"></i>Conocer Servicios
                        </a>
                    </div>
                </div>
                <div class="col-lg-6 text-center">
                    <img src="https://cdn-icons-png.flaticon.com/512/2991/2991312.png" 
                         alt="Bomberos" class="img-fluid" style="max-height: 300px;">
                </div>
            </div>
        </div>
    </section>

    <!-- Quick Actions -->
    <section class="py-5 bg-white">
        <div class="container">
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card card-ciudadano text-center h-100">
                        <div class="card-body p-4">
                            <div class="feature-icon">
                                <i class="fas fa-exclamation-triangle"></i>
                            </div>
                            <h4 class="card-title">Reportar Emergencia</h4>
                            <p class="card-text text-muted">
                                Notifique situaciones de riesgo, incendios o accidentes de forma inmediata
                            </p>
                            <button class="btn btn-ciudadano" data-bs-toggle="modal" data-bs-target="#modalEmergencia">
                                Reportar Ahora
                            </button>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card card-ciudadano text-center h-100">
                        <div class="card-body p-4">
                            <div class="feature-icon">
                                <i class="fas fa-calendar-check"></i>
                            </div>
                            <h4 class="card-title">Solicitar Servicios</h4>
                            <p class="card-text text-muted">
                                Solicite inspecciones, charlas preventivas o servicios no urgentes
                            </p>
                            <button class="btn btn-ciudadano" data-bs-toggle="modal" data-bs-target="#modalServicios">
                                Solicitar Servicio
                            </button>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card card-ciudadano text-center h-100">
                        <div class="card-body p-4">
                            <div class="feature-icon">
                                <i class="fas fa-graduation-cap"></i>
                            </div>
                            <h4 class="card-title">Educación Preventiva</h4>
                            <p class="card-text text-muted">
                                Acceda a recursos educativos y programas de prevención de incendios
                            </p>
                            <a href="#prevencion" class="btn btn-ciudadano">
                                Aprender Más
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Emergency Report Section -->
    <section id="reportar" class="py-5 bg-light">
        <div class="container">
            <div class="row">
                <div class="col-lg-8 mx-auto">
                    <div class="text-center mb-5">
                        <h2 class="text-3xl font-bold text-gray-800 mb-3">
                            <i class="fas fa-exclamation-circle me-2 text-red-600"></i>
                            Reporte de Emergencia
                        </h2>
                        <p class="text-gray-600">
                            Complete el siguiente formulario para reportar una situación de emergencia
                        </p>
                    </div>

                    <div class="card card-ciudadano">
                        <div class="card-body p-4">
                            <form id="formEmergencia">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Tipo de Emergencia *</label>
                                        <select class="form-select" id="tipo-emergencia" required>
                                            <option value="">Seleccionar tipo...</option>
                                            <option value="incendio">Incendio</option>
                                            <option value="accidente">Accidente de Tráfico</option>
                                            <option value="rescate">Persona en Peligro</option>
                                            <option value="fuga">Fuga de Gas</option>
                                            <option value="derrame">Derrame Químico</option>
                                            <option value="otro">Otro</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Gravedad *</label>
                                        <select class="form-select" id="gravedad" required>
                                            <option value="leve">Leve (Sin riesgo inminente)</option>
                                            <option value="moderada">Moderada (Riesgo controlado)</option>
                                            <option value="grave">Grave (Riesgo inminente)</option>
                                            <option value="critica">Crítica (Peligro de vida)</option>
                                        </select>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label fw-semibold">
                                            <i class="fas fa-map-marker-alt text-danger me-1"></i>
                                            Ubicación Exacta *
                                        </label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="ubicacion-input" 
                                                   placeholder="Dirección, referencia, coordenadas..." required>
                                            <button class="btn btn-gps" type="button" id="btn-gps" onclick="obtenerGPS()">
                                                <i class="fas fa-location-crosshairs me-2"></i>
                                                <span id="btn-gps-text">Usar GPS</span>
                                            </button>
                                        </div>
                                        
                                        <!-- Información de ubicación -->
                                        <div class="location-info" id="location-info">
                                            <div class="d-flex align-items-center">
                                                <i class="fas fa-check-circle fa-lg me-2"></i>
                                                <div>
                                                    <strong>Ubicación Capturada</strong><br>
                                                    <small class="text-muted">
                                                        Lat: <span id="lat-value">--</span>, 
                                                        Lon: <span id="lon-value">--</span>
                                                    </small>
                                                </div>
                                            </div>
                                            <div class="mt-2">
                                                <a href="#" id="mapa-link" target="_blank" class="btn btn-sm btn-outline-success">
                                                    <i class="fas fa-map me-1"></i>Ver en Google Maps
                                                </a>
                                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="limpiarGPS()">
                                                    <i class="fas fa-times me-1"></i>Limpiar
                                                </button>
                                            </div>
                                        </div>
                                        
                                        <!-- Mapa visual -->
                                        <div class="map-container" id="map-preview">
                                            <div class="text-center text-green-600">
                                                <i class="fas fa-map fa-3x mb-2"></i>
                                                <p class="small">Tu ubicación aparecerá aquí</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Su Nombre</label>
                                        <input type="text" class="form-control" id="nombre" placeholder="Opcional">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Teléfono de Contacto</label>
                                        <input type="tel" class="form-control" id="telefono" placeholder="Opcional">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label fw-semibold">Descripción Detallada *</label>
                                        <textarea class="form-control" id="descripcion" rows="4" 
                                                  placeholder="Describa la situación con el mayor detalle posible..." required></textarea>
                                    </div>
                                    <div class="col-12">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="confirmacion" required>
                                            <label class="form-check-label" for="confirmacion">
                                                Confirmo que esta es una situación real que requiere atención de bomberos
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <button type="submit" class="btn btn-ciudadano w-100 py-3">
                                            <i class="fas fa-paper-plane me-2"></i>
                                            ENVIAR REPORTE DE EMERGENCIA
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Prevention Section -->
    <section id="prevencion" class="py-5 bg-white">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="text-3xl font-bold text-gray-800 mb-3">Educación y Prevención</h2>
                <p class="text-gray-600">Recursos para la prevención de incendios y emergencias</p>
            </div>

            <div class="row g-4">
                <div class="col-md-6">
                    <div class="card card-ciudadano h-100">
                        <div class="card-body">
                            <h5 class="card-title">
                                <i class="fas fa-house-fire me-2 text-red-600"></i>
                                Prevención en el Hogar
                            </h5>
                            <ul class="list-unstyled">
                                <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Instale detectores de humo</li>
                                <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Mantenga extintores accesibles</li>
                                <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Revise instalaciones eléctricas</li>
                                <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Plan de evacuación familiar</li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card card-ciudadano h-100">
                        <div class="card-body">
                            <h5 class="card-title">
                                <i class="fas fa-car-burst me-2 text-orange-600"></i>
                                Seguridad Vial
                            </h5>
                            <ul class="list-unstyled">
                                <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Mantenimiento vehicular</li>
                                <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Kit de emergencia en auto</li>
                                <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Conducción preventiva</li>
                                <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Números de emergencia</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section id="contacto" class="py-5 bg-dark text-white">
        <div class="container">
            <div class="row">
                <div class="col-lg-8 mx-auto text-center">
                    <h3 class="fw-bold mb-4">Contacto de Emergencia</h3>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <div class="p-3">
                                <i class="fas fa-phone fa-2x text-red-500 mb-2"></i>
                                <h5>Emergencias</h5>
                                <p class="fs-4 fw-bold">911</p>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="p-3">
                                <i class="fas fa-phone-alt fa-2x text-blue-500 mb-2"></i>
                                <h5>Administración</h5>
                                <p>(01) 234-5678</p>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="p-3">
                                <i class="fas fa-envelope fa-2x text-green-500 mb-2"></i>
                                <h5>Email</h5>
                                <p>contacto@bomberos.gob</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-900 text-white py-4">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <p class="mb-0">
                        <i class="fas fa-fire-extinguisher me-2 text-orange-500"></i>
                        Cuerpo de Bomberos - Portal Ciudadano
                    </p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="mb-0">
                        &copy; 2024 Todos los derechos reservados
                    </p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Modals -->
    <div class="modal fade" id="modalEmergencia" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-red-600 text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Reporte de Emergencia
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>¿Está seguro de que desea reportar una emergencia?</p>
                    <p class="text-muted small">
                        <strong>Importante:</strong> Este sistema es para situaciones reales que requieren 
                        atención inmediata de bomberos. El mal uso puede tener consecuencias legales.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <a href="#reportar" class="btn btn-danger" data-bs-dismiss="modal">Continuar</a>
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
        
        // Variables globales para GPS
        let gpsData = null;

        // Función para obtener la ubicación GPS
        function obtenerGPS() {
            const btnGps = document.getElementById('btn-gps');
            const btnGpsText = document.getElementById('btn-gps-text');
            
            // Cambiar estado del botón
            btnGps.classList.add('loading');
            btnGpsText.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Localizando...';
            
            // Verificar si el navegador soporta geolocalización
            if (!navigator.geolocation) {
                alert('⚠️ Tu navegador no soporta geolocalización. Por favor, ingresa la dirección manualmente.');
                btnGps.classList.remove('loading');
                btnGpsText.textContent = 'Usar GPS';
                return;
            }
            
            // Obtener la ubicación
            navigator.geolocation.getCurrentPosition(
                function(position) {
                    const lat = position.coords.latitude;
                    const lon = position.coords.longitude;
                    const accuracy = position.coords.accuracy;
                    
                    // Guardar datos
                    gpsData = { lat, lon, accuracy };
                    
                    // Actualizar UI
                    document.getElementById('lat-value').textContent = lat.toFixed(6);
                    document.getElementById('lon-value').textContent = lon.toFixed(6);
                    document.getElementById('ubicacion-input').value = `${lat.toFixed(6)}, ${lon.toFixed(6)}`;
                    
                    // Mostrar info de ubicación
                    document.getElementById('location-info').classList.add('active');
                    
                    // Actualizar link de Google Maps
                    const mapsLink = `https://www.google.com/maps?q=${lat},${lon}`;
                    document.getElementById('mapa-link').href = mapsLink;
                    
                    // Mostrar mapa visual
                    mostrarMapaVisual(lat, lon);
                    
                    // Restaurar botón
                    btnGps.classList.remove('loading');
                    btnGpsText.innerHTML = '<i class="fas fa-check-circle me-2"></i>Ubicación Capturada';
                    
                    console.log(`Ubicación: ${lat}, ${lon} (Precisión: ${accuracy.toFixed(0)}m)`);
                },
                function(error) {
                    let mensaje = '';
                    switch(error.code) {
                        case error.PERMISSION_DENIED:
                            mensaje = 'Permiso denegado. Por favor, habilita el acceso a tu ubicación en la configuración del navegador.';
                            break;
                        case error.POSITION_UNAVAILABLE:
                            mensaje = 'Ubicación no disponible. Intenta en una ubicación más abierta.';
                            break;
                        case error.TIMEOUT:
                            mensaje = 'Tiempo de espera agotado. Intenta nuevamente.';
                            break;
                        default:
                            mensaje = 'Error al obtener la ubicación.';
                    }
                    alert('❌ ' + mensaje);
                    
                    // Restaurar botón
                    btnGps.classList.remove('loading');
                    btnGpsText.textContent = 'Usar GPS';
                },
                {
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 0
                }
            );
        }

        // Función para mostrar mapa visual
        function mostrarMapaVisual(lat, lon) {
            const mapPreview = document.getElementById('map-preview');
            mapPreview.innerHTML = `
                <div style="position: relative; width: 100%; height: 100%; display: flex; align-items: center; justify-content: center;">
                    <div class="map-marker">
                        <i class="fas fa-map-pin fa-4x" style="color: #dc2626; filter: drop-shadow(0 2px 4px rgba(0,0,0,0.3));"></i>
                    </div>
                    <div style="position: absolute; bottom: 10px; left: 10px; background: white; padding: 8px 12px; border-radius: 6px; font-size: 0.85rem; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                        <strong>${lat.toFixed(4)}, ${lon.toFixed(4)}</strong><br>
                        <small style="color: #6b7280;">Tu ubicación actual</small>
                    </div>
                </div>
            `;
        }

        // Función para limpiar GPS
        function limpiarGPS() {
            gpsData = null;
            document.getElementById('ubicacion-input').value = '';
            document.getElementById('location-info').classList.remove('active');
            document.getElementById('map-preview').innerHTML = `
                <div class="text-center text-green-600">
                    <i class="fas fa-map fa-3x mb-2"></i>
                    <p class="small">Tu ubicación aparecerá aquí</p>
                </div>
            `;
            document.getElementById('btn-gps-text').textContent = 'Usar GPS';
            document.getElementById('btn-gps').classList.remove('loading');
        }

        // Manejar envío del formulario
        document.getElementById('formEmergencia').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const tipo = document.getElementById('tipo-emergencia').value;
            const gravedad = document.getElementById('gravedad').value;
            const ubicacion = document.getElementById('ubicacion-input').value;
            const nombre = document.getElementById('nombre').value || 'Anónimo';
            const telefono = document.getElementById('telefono').value || 'No disponible';
            const descripcion = document.getElementById('descripcion').value;
            
            let mensaje = '✅ ¡REPORTE DE EMERGENCIA ENVIADO!\n\n';
            mensaje += `📋 Tipo: ${tipo}\n`;
            mensaje += `⚠️ Gravedad: ${gravedad}\n`;
            mensaje += `📍 Ubicación: ${ubicacion}\n`;
            
            if (gpsData) {
                mensaje += `🛰️ Coordenadas GPS: ${gpsData.lat.toFixed(6)}, ${gpsData.lon.toFixed(6)}\n`;
                mensaje += `📡 Precisión: ±${gpsData.accuracy.toFixed(0)}m\n`;
            }
            
            mensaje += `👤 Nombre: ${nombre}\n`;
            mensaje += `☎️ Teléfono: ${telefono}\n\n`;
            mensaje += '✨ Los bomberos han sido alertados y tu ubicación será compartida con ellos.\n';
            mensaje += '⏱️ Las unidades de emergencia están siendo despachadas.\n';
            mensaje += '🚨 Permanece en línea para comunicaciones posteriores.';
            
            alert(mensaje);
            
            // Simular envío a servidor
            console.log({
                tipo,
                gravedad,
                ubicacion,
                gpsData,
                nombre,
                telefono,
                descripcion,
                timestamp: new Date().toISOString()
            });
            
            // Limpiar formulario
            this.reset();
            limpiarGPS();
            
            // Scroll suave al inicio
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });

        // Navegación suave
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({ behavior: 'smooth' });
                }
            });
        });

        // Mensaje de bienvenida en consola
        console.log('%c🚨 SISTEMA DE EMERGENCIAS - BOMBEROS 🚨', 'color: #ea580c; font-size: 16px; font-weight: bold;');
        console.log('%cSi necesitas ayuda: ☎️ 911', 'color: #dc2626; font-size: 14px;');
    </script>
</body>
</html>