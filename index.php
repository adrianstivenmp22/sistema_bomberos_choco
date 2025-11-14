<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Bomberos - Acceso por Actores</title>
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --comandante: #dc2626;
            --oficial: #2563eb;
            --bombero: #16a34a;
            --administrativo: #7c3aed;
            --ciudadano: #ea580c;
        }
        
        .hero-section {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            position: relative;
            overflow: hidden;
        }
        
        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 1000"><polygon fill="%23dc2626" fill-opacity="0.05" points="0,1000 1000,0 1000,1000"/></svg>');
            background-size: cover;
        }
        
        .actor-card {
            transition: all 0.3s ease;
            border: none;
            position: relative;
            overflow: hidden;
        }
        
        .actor-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
        }
        
        .actor-card.comandante::before { background: var(--comandante); }
        .actor-card.oficial::before { background: var(--oficial); }
        .actor-card.bombero::before { background: var(--bombero); }
        
        .actor-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
        }
        
        .feature-icon {
            width: 80px;
            height: 80px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            font-size: 2rem;
        }
        
        .comandante .feature-icon { 
            background: linear-gradient(135deg, var(--comandante), #b91c1c);
            color: white;
        }
        
        .oficial .feature-icon { 
            background: linear-gradient(135deg, var(--oficial), #1d4ed8);
            color: white;
        }
        
        .bombero .feature-icon { 
            background: linear-gradient(135deg, var(--bombero), #15803d);
            color: white;
        }
        
        .btn-access {
            padding: 12px 30px;
            font-weight: 600;
            border: none;
            border-radius: 10px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .btn-access::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }
        
        .btn-access:hover::before {
            left: 100%;
        }
        
        .btn-comandante {
            background: linear-gradient(135deg, var(--comandante), #b91c1c);
            color: white;
        }
        
        .btn-oficial {
            background: linear-gradient(135deg, var(--oficial), #1d4ed8);
            color: white;
        }
        
        .btn-bombero {
            background: linear-gradient(135deg, var(--bombero), #15803d);
            color: white;
        }
        
        .feature-list li {
            padding: 8px 0;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            align-items: center;
        }
        
        .feature-list li:last-child {
            border-bottom: none;
        }
        
        .feature-list i {
            width: 20px;
            margin-right: 10px;
        }
        
        .comandante .feature-list i { color: var(--comandante); }
        .oficial .feature-list i { color: var(--oficial); }
        .bombero .feature-list i { color: var(--bombero); }
        
        .floating-shapes {
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            pointer-events: none;
        }
        
        .shape {
            position: absolute;
            opacity: 0.1;
            animation: float 6s ease-in-out infinite;
        }
        
        .shape-1 {
            width: 100px;
            height: 100px;
            background: var(--comandante);
            border-radius: 30% 70% 70% 30% / 30% 30% 70% 70%;
            top: 10%;
            left: 10%;
            animation-delay: 0s;
        }
        
        .shape-2 {
            width: 150px;
            height: 150px;
            background: var(--oficial);
            border-radius: 50%;
            top: 60%;
            right: 10%;
            animation-delay: 2s;
        }
        
        .shape-3 {
            width: 80px;
            height: 80px;
            background: var(--bombero);
            border-radius: 40% 60% 60% 40% / 40% 40% 60% 60%;
            bottom: 20%;
            left: 20%;
            animation-delay: 4s;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(180deg); }
        }
        
        .emergency-alert {
            animation: pulse 2s infinite;
            border-left: 4px solid #dc2626;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.02); }
            100% { transform: scale(1); }
        }
        
        .stats-card {
            background: linear-gradient(135deg, #1e293b, #0f172a);
            border: 1px solid #334155;
            transition: all 0.3s ease;
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
            border-color: #dc2626;
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Emergency Alert -->
    <div class="emergency-alert bg-red-600 text-white py-3">
        <div class="container text-center">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <strong>EMERGENCIA:</strong> En caso de incendio o accidente, llame inmediatamente al 
            <strong class="text-lg">911</strong>
            <span class="ms-3">| Servicio 24/7 | Respuesta inmediata</span>
        </div>
    </div>

    <!-- Hero Section -->
    <section class="hero-section text-white py-5">
        <div class="floating-shapes">
            <div class="shape shape-1"></div>
            <div class="shape shape-2"></div>
            <div class="shape shape-3"></div>
        </div>
        
        <div class="container position-relative">
            <div class="row align-items-center min-vh-50 py-5">
                <div class="col-lg-8 mx-auto text-center">
                    <div class="mb-4">
                        <i class="fas fa-fire-extinguisher fa-4x text-red-500 mb-3"></i>
                        <h1 class="display-4 fw-bold mb-3">Sistema de Gestión de Bomberos</h1>
                        <p class="lead fs-4 opacity-75">
                            Plataforma integral para la gestión y coordinación de emergencias
                        </p>
                    </div>
                    
                    <!-- Quick Stats -->
                    <div class="row mt-5">
                        <div class="col-md-3 col-6 mb-4">
                            <div class="stats-card rounded-lg p-4 text-center">
                                <div class="text-red-400 text-2xl font-bold">24/7</div>
                                <div class="text-gray-400 text-sm">Servicio Continuo</div>
                            </div>
                        </div>
                        <div class="col-md-3 col-6 mb-4">
                            <div class="stats-card rounded-lg p-4 text-center">
                                <div class="text-blue-400 text-2xl font-bold">156</div>
                                <div class="text-gray-400 text-sm">Bomberos Activos</div>
                            </div>
                        </div>
                        <div class="col-md-3 col-6 mb-4">
                            <div class="stats-card rounded-lg p-4 text-center">
                                <div class="text-green-400 text-2xl font-bold">1,247</div>
                                <div class="text-gray-400 text-sm">Intervenciones</div>
                            </div>
                        </div>
                        <div class="col-md-3 col-6 mb-4">
                            <div class="stats-card rounded-lg p-4 text-center">
                                <div class="text-yellow-400 text-2xl font-bold">89</div>
                                <div class="text-gray-400 text-sm">Rescates</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Actors Section -->
    <section class="py-5">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="text-3xl font-bold text-gray-800 mb-3">Acceso al Sistema por Perfil</h2>
                <p class="text-lg text-gray-600 max-w-2xl mx-auto">
                    Seleccione su rol para acceder a las herramientas específicas de gestión y coordinación
                </p>
                <div class="mt-4">
                    <a href="login_nuevo.php" class="btn btn-primary btn-lg">
                        <i class="fas fa-sign-in-alt me-2"></i>
                        Ir al Login
                    </a>
                </div>
            </div>

            <div class="row g-4">
                <!-- Comandante -->
                <div class="col-lg-4 col-md-6">
                    <div class="card actor-card comandante shadow-lg h-100">
                        <div class="card-body p-4">
                            <div class="text-center mb-4">
                                <div class="feature-icon">
                                    <i class="fas fa-crown"></i>
                                </div>
                                <h3 class="h2 fw-bold text-gray-800">Comandante</h3>
                                <p class="text-muted mb-4">
                                    Acceso completo al sistema de gestión y supervisión operativa
                                </p>
                            </div>
                            
                            <div class="mb-4">
                                <h5 class="fw-semibold text-gray-700 mb-3">
                                    <i class="fas fa-list-check me-2 text-red-600"></i>
                                    Funcionalidades Principales
                                </h5>
                                <ul class="feature-list list-unstyled">
                                    <li>
                                        <i class="fas fa-check-circle"></i>
                                        Supervisión general
                                    </li>
                                    <li>
                                        <i class="fas fa-check-circle"></i>
                                        Reportes ejecutivos
                                    </li>
                                    <li>
                                        <i class="fas fa-check-circle"></i>
                                        Gestión de personal
                                    </li>
                                    <li>
                                        <i class="fas fa-check-circle"></i>
                                        Control de recursos
                                    </li>
                                </ul>
                            </div>
                            
                            <div class="text-center mt-auto">
                                <button class="btn btn-access btn-comandante w-100 py-3" onclick="accederComoComandante()">
                                    <i class="fas fa-sign-in-alt me-2"></i>
                                    Acceder como Comandante
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Oficial -->
                <div class="col-lg-4 col-md-6">
                    <div class="card actor-card oficial shadow-lg h-100">
                        <div class="card-body p-4">
                            <div class="text-center mb-4">
                                <div class="feature-icon">
                                    <i class="fas fa-user-tie"></i>
                                </div>
                                <h3 class="h2 fw-bold text-gray-800">Oficial</h3>
                                <p class="text-muted mb-4">
                                    Gestión operativa y coordinación de emergencias en tiempo real
                                </p>
                            </div>
                            
                            <div class="mb-4">
                                <h5 class="fw-semibold text-gray-700 mb-3">
                                    <i class="fas fa-list-check me-2 text-blue-600"></i>
                                    Funcionalidades Principales
                                </h5>
                                <ul class="feature-list list-unstyled">
                                    <li>
                                        <i class="fas fa-check-circle"></i>
                                        Coordinación operativa
                                    </li>
                                    <li>
                                        <i class="fas fa-check-circle"></i>
                                        Gestión de incidentes
                                    </li>
                                    <li>
                                        <i class="fas fa-check-circle"></i>
                                        Control de unidades
                                    </li>
                                    <li>
                                        <i class="fas fa-check-circle"></i>
                                        Reportes operativos
                                    </li>
                                </ul>
                            </div>
                            
                            <div class="text-center mt-auto">
                                <button class="btn btn-access btn-oficial w-100 py-3" onclick="accederComoOficial()">
                                    <i class="fas fa-sign-in-alt me-2"></i>
                                    Acceder como Oficial
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Bombero -->
                <div class="col-lg-4 col-md-6">
                    <div class="card actor-card bombero shadow-lg h-100">
                        <div class="card-body p-4">
                            <div class="text-center mb-4">
                                <div class="feature-icon">
                                    <i class="fas fa-firefighter"></i>
                                </div>
                                <h3 class="h2 fw-bold text-gray-800">Bombero</h3>
                                <p class="text-muted mb-4">
                                    Acceso operativo para intervención en emergencias y reportes de campo
                                </p>
                            </div>
                            
                            <div class="mb-4">
                                <h5 class="fw-semibold text-gray-700 mb-3">
                                    <i class="fas fa-list-check me-2 text-green-600"></i>
                                    Funcionalidades Principales
                                </h5>
                                <ul class="feature-list list-unstyled">
                                    <li>
                                        <i class="fas fa-check-circle"></i>
                                        Registro de intervenciones
                                    </li>
                                    <li>
                                        <i class="fas fa-check-circle"></i>
                                        Estado de equipos
                                    </li>
                                    <li>
                                        <i class="fas fa-check-circle"></i>
                                        Comunicaciones
                                    </li>
                                    <li>
                                        <i class="fas fa-check-circle"></i>
                                        Reportes de servicio
                                    </li>
                                </ul>
                            </div>
                            
                            <div class="text-center mt-auto">
                                <button class="btn btn-access btn-bombero w-100 py-3" onclick="accederComoBombero()">
                                    <i class="fas fa-sign-in-alt me-2"></i>
                                    Acceder como Bombero
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Additional Actors Row -->
            <div class="row g-4 mt-2">
                <!-- Administrativo -->
                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body p-4">
                            <div class="row align-items-center">
                                <div class="col-md-3 text-center">
                                    <div class="feature-icon" style="background: linear-gradient(135deg, var(--administrativo), #6d28d9); color: white;">
                                        <i class="fas fa-clipboard-list"></i>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <h4 class="fw-bold text-gray-800">Personal Administrativo</h4>
                                    <p class="text-muted mb-3">
                                        Gestión documental, registro de formularios y apoyo administrativo
                                    </p>
                                    <ul class="list-unstyled">
                                        <li><i class="fas fa-check text-purple-600 me-2"></i>Gestión documental</li>
                                        <li><i class="fas fa-check text-purple-600 me-2"></i>Registro de formularios</li>
                                    </ul>
                                </div>
                                <div class="col-md-3 text-center">
                                    <button class="btn btn-outline-purple w-100" onclick="accederComoAdministrativo()">
                                        <i class="fas fa-sign-in-alt me-1"></i>Acceder
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Ciudadano -->
                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body p-4">
                            <div class="row align-items-center">
                                <div class="col-md-3 text-center">
                                    <div class="feature-icon" style="background: linear-gradient(135deg, var(--ciudadano), #c2410c); color: white;">
                                        <i class="fas fa-user"></i>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <h4 class="fw-bold text-gray-800">Ciudadano</h4>
                                    <p class="text-muted mb-3">
                                        Acceso público para reportar emergencias y consultar información
                                    </p>
                                    <ul class="list-unstyled">
                                        <li><i class="fas fa-check text-orange-600 me-2"></i>Reportar emergencias</li>
                                        <li><i class="fas fa-check text-orange-600 me-2"></i>Consultar información</li>
                                    </ul>
                                </div>
                                <div class="col-md-3 text-center">
                                    <button class="btn btn-outline-orange w-100" onclick="accederComoCiudadano()">
                                        <i class="fas fa-sign-in-alt me-1"></i>Acceder
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- System Features -->
    <section class="py-5 bg-dark text-white">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="text-3xl font-bold mb-3">Características del Sistema</h2>
                <p class="text-gray-300 max-w-2xl mx-auto">
                    Tecnología avanzada al servicio de la seguridad ciudadana
                </p>
            </div>
            
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="text-center">
                        <div class="text-red-400 text-4xl mb-3">
                            <i class="fas fa-bolt"></i>
                        </div>
                        <h4 class="fw-bold">Respuesta Inmediata</h4>
                        <p class="text-gray-300">Sistema optimizado para tiempos de respuesta mínimos</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="text-center">
                        <div class="text-blue-400 text-4xl mb-3">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <h4 class="fw-bold">Seguridad Garantizada</h4>
                        <p class="text-gray-300">Protección de datos y comunicaciones seguras</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="text-center">
                        <div class="text-green-400 text-4xl mb-3">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <h4 class="fw-bold">Monitoreo en Tiempo Real</h4>
                        <p class="text-gray-300">Seguimiento continuo de operaciones y recursos</p>
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
                        <i class="fas fa-fire-extinguisher me-2 text-red-500"></i>
                        Sistema de Gestión - Cuerpo de Bomberos
                    </p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="mb-0">
                        <i class="fas fa-phone me-1"></i> Emergencias: 911 | 
                        <i class="fas fa-clock me-1 ms-2"></i> Servicio 24/7
                    </p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Funciones de acceso al sistema
        // Nota: estas funciones redirigen al login con el rol seleccionado
        function accederComoComandante() { irAlLogin('comandante'); }
        function accederComoOficial()    { irAlLogin('oficial'); }
        function accederComoBombero()    { irAlLogin('bombero'); }
        function accederComoAdministrativo() { irAlLogin('administrativo'); }
        function accederComoCiudadano()  { irAlLogin('ciudadano'); }

        function irAlLogin(rol) {
            // Guardar el rol seleccionado en sessionStorage
            sessionStorage.setItem('rolSeleccionado', rol);
            
            // Mostrar modal de transición
            const modal = document.createElement('div');
            modal.className = 'modal fade show d-block';
            modal.id = 'transicionModal';
            modal.style.backgroundColor = 'rgba(0,0,0,0.5)';
            
            const colorMap = {
                'comandante': 'red',
                'oficial': 'blue',
                'bombero': 'green',
                'administrativo': 'purple',
                'ciudadano': 'orange'
            };
            
            const roleMap = {
                'comandante': 'Comandante',
                'oficial': 'Oficial',
                'bombero': 'Bombero',
                'administrativo': 'Personal Administrativo',
                'ciudadano': 'Ciudadano'
            };
            
            const colorClass = colorMap[rol] || 'secondary';
            const roleName = roleMap[rol] || 'Usuario';
            
            modal.innerHTML = `
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header border-0">
                            <h5 class="modal-title">
                                <i class="fas fa-user-shield me-2 text-${colorClass}-600"></i>
                                Acceso como ${roleName}
                            </h5>
                            <button type="button" class="btn-close" onclick="cerrarTransicion()"></button>
                        </div>
                        <div class="modal-body text-center py-4">
                            <div class="mb-4">
                                <i class="fas fa-fire-extinguisher fa-4x text-${colorClass}-500 mb-3"></i>
                                <h4>Redireccionando</h4>
                                <p class="text-muted">Accediendo a login como ${roleName}...</p>
                            </div>
                            <div class="spinner-border text-${colorClass}-500" role="status">
                                <span class="visually-hidden">Cargando...</span>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            // Añadir modal al DOM
            document.body.appendChild(modal);

            // Redirigir al login después de un breve retardo
            setTimeout(() => {
                cerrarTransicion();
                window.location.href = 'login_nuevo.php';
            }, 800);
        }

        // Eliminar modal si existe
        function cerrarTransicion() {
            const modal = document.getElementById('transicionModal') || document.querySelector('.modal');
            if (modal) modal.remove();
        }

        // Cerrar modal con ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') cerrarTransicion();
        });
    </script>
</body>
</html>