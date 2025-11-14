<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistema de Bomberos</title>
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --primary-red: #dc2626;
            --primary-dark: #991b1b;
        }
        
        .login-container {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .login-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            overflow: hidden;
            max-width: 400px;
            width: 100%;
        }
        
        .login-header {
            background: linear-gradient(135deg, var(--primary-red), var(--primary-dark));
            color: white;
            padding: 2rem;
            text-align: center;
        }
        
        .login-body {
            padding: 2rem;
        }
        
        .form-control {
            border-radius: 10px;
            padding: 12px 15px;
            border: 2px solid #e5e7eb;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: var(--primary-red);
            box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.1);
        }
        
        .btn-login {
            background: linear-gradient(135deg, var(--primary-red), var(--primary-dark));
            color: white;
            border: none;
            border-radius: 10px;
            padding: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 15px rgba(220, 38, 38, 0.3);
        }
        
        .role-selector {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
            margin-bottom: 1.5rem;
        }
        
        .role-option {
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            padding: 10px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            background: white;
        }
        
        .role-option:hover {
            border-color: var(--primary-red);
            background: #fef2f2;
        }
        
        .role-option.active {
            border-color: var(--primary-red);
            background: #fef2f2;
            color: var(--primary-red);
        }
        
        .role-icon {
            font-size: 1.5rem;
            margin-bottom: 5px;
        }
        
        .demo-credentials {
            background: #f8fafc;
            border-radius: 10px;
            padding: 1rem;
            margin-top: 1.5rem;
            border-left: 4px solid var(--primary-red);
        }
        
        .floating-shapes {
            position: fixed;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            pointer-events: none;
            z-index: -1;
        }
        
        .shape {
            position: absolute;
            opacity: 0.1;
            animation: float 6s ease-in-out infinite;
        }
        
        .shape-1 {
            width: 100px;
            height: 100px;
            background: var(--primary-red);
            border-radius: 30% 70% 70% 30% / 30% 30% 70% 70%;
            top: 10%;
            left: 10%;
            animation-delay: 0s;
        }
        
        .shape-2 {
            width: 150px;
            height: 150px;
            background: #2563eb;
            border-radius: 50%;
            top: 60%;
            right: 10%;
            animation-delay: 2s;
        }
        
        .shape-3 {
            width: 80px;
            height: 80px;
            background: #16a34a;
            border-radius: 40% 60% 60% 40% / 40% 40% 60% 60%;
            bottom: 20%;
            left: 20%;
            animation-delay: 4s;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(180deg); }
        }
        
        .alert-login {
            animation: slideIn 0.5s ease;
        }
        
        @keyframes slideIn {
            from { 
                opacity: 0;
                transform: translateY(-10px);
            }
            to { 
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body>
    <div class="floating-shapes">
        <div class="shape shape-1"></div>
        <div class="shape shape-2"></div>
        <div class="shape shape-3"></div>
    </div>

    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <i class="fas fa-fire-extinguisher fa-3x mb-3"></i>
                <h2 class="mb-2">Sistema de Bomberos</h2>
                <p class="opacity-90 mb-0">Acceso al Sistema Integral</p>
            </div>
            
            <div class="login-body">
                <!-- Alertas -->
                <div id="alert-container"></div>

                <!-- Selector de Rol -->
                <div class="mb-4">
                    <label class="form-label fw-semibold mb-3">Seleccione su rol:</label>
                    <div class="role-selector">
                        <div class="role-option active" data-role="comandante">
                            <div class="role-icon text-red-600">
                                <i class="fas fa-crown"></i>
                            </div>
                            <div class="role-name small">Comandante</div>
                        </div>
                        <div class="role-option" data-role="oficial">
                            <div class="role-icon text-blue-600">
                                <i class="fas fa-user-tie"></i>
                            </div>
                            <div class="role-name small">Oficial</div>
                        </div>
                        <div class="role-option" data-role="bombero">
                            <div class="role-icon text-green-600">
                                <i class="fas fa-firefighter"></i>
                            </div>
                            <div class="role-name small">Bombero</div>
                        </div>
                        <div class="role-option" data-role="ciudadano">
                            <div class="role-icon text-orange-600">
                                <i class="fas fa-user"></i>
                            </div>
                            <div class="role-name small">Ciudadano</div>
                        </div>
                    </div>
                </div>

                <!-- Formulario de Login -->
                <form id="loginForm">
                    <div class="mb-3">
                        <label for="username" class="form-label">Usuario</label>
                        <div class="input-group">
                            <span class="input-group-text bg-gray-100 border-end-0">
                                <i class="fas fa-user text-gray-500"></i>
                            </span>
                            <input type="text" class="form-control border-start-0" id="username" 
                                   placeholder="Ingrese su usuario" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">Contraseña</label>
                        <div class="input-group">
                            <span class="input-group-text bg-gray-100 border-end-0">
                                <i class="fas fa-lock text-gray-500"></i>
                            </span>
                            <input type="password" class="form-control border-start-0" id="password" 
                                   placeholder="Ingrese su contraseña" required>
                        </div>
                    </div>
                    
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="rememberMe">
                        <label class="form-check-label" for="rememberMe">Recordar sesión</label>
                    </div>
                    
                    <button type="submit" class="btn btn-login w-100 mb-3">
                        <i class="fas fa-sign-in-alt me-2"></i>
                        Iniciar Sesión
                    </button>
                    
                    <div class="text-center">
                        <a href="#" class="text-sm text-gray-600">¿Olvidó su contraseña?</a>
                    </div>
                </form>

                <!-- Credenciales de Demo -->
                <div class="demo-credentials">
                    <h6 class="fw-semibold mb-2">
                        <i class="fas fa-key me-1 text-red-600"></i>
                        Credenciales de Demo
                    </h6>
                    <div class="row small text-muted">
                        <div class="col-6">
                            <strong>Comandante:</strong><br>
                            usuario: <span class="text-primary">comandante</span><br>
                            clave: <span class="text-primary">1234</span>
                        </div>
                        <div class="col-6">
                            <strong>Oficial:</strong><br>
                            usuario: <span class="text-primary">oficial</span><br>
                            clave: <span class="text-primary">1234</span>
                        </div>
                        <div class="col-6 mt-2">
                            <strong>Bombero:</strong><br>
                            usuario: <span class="text-primary">bombero</span><br>
                            clave: <span class="text-primary">1234</span>
                        </div>
                        <div class="col-6 mt-2">
                            <strong>Ciudadano:</strong><br>
                            usuario: <span class="text-primary">ciudadano</span><br>
                            clave: <span class="text-primary">1234</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Base de datos de usuarios (en un sistema real esto estaría en el backend)
        const usuarios = {
            'comandante': {
                password: '1234',
                nombre: 'Comandante García',
                rol: 'comandante',
                redirect: 'comandante.html'
            },
            'oficial': {
                password: '1234',
                nombre: 'Oficial Martínez',
                rol: 'oficial',
                redirect: 'oficial.html'
            },
            'bombero': {
                password: '1234',
                nombre: 'Bombero Pérez',
                rol: 'bombero',
                redirect: 'bombero.html'
            },
            'ciudadano': {
                password: '1234',
                nombre: 'Ciudadano',
                rol: 'ciudadano',
                redirect: 'ciudadano.html'
            }
        };

        // Variables globales
        let rolSeleccionado = 'comandante';

        // Selección de rol
        document.querySelectorAll('.role-option').forEach(option => {
            option.addEventListener('click', function() {
                // Remover clase active de todos
                document.querySelectorAll('.role-option').forEach(opt => {
                    opt.classList.remove('active');
                });
                
                // Agregar clase active al seleccionado
                this.classList.add('active');
                
                // Actualizar rol seleccionado
                rolSeleccionado = this.getAttribute('data-role');
                
                // Actualizar placeholder sugerido
                const usernameInput = document.getElementById('username');
                usernameInput.placeholder = `Usuario ${this.querySelector('.role-name').textContent.toLowerCase()}`;
                
                // Mostrar mensaje informativo
                mostrarAlerta(`Rol cambiado a: ${this.querySelector('.role-name').textContent}`, 'info');
            });
        });

        // Manejo del formulario de login
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value;
            
            // Validar campos vacíos
            if (!username || !password) {
                mostrarAlerta('Por favor, complete todos los campos', 'error');
                return;
            }
            
            // Verificar credenciales
            const usuario = usuarios[username.toLowerCase()];
            
            if (!usuario) {
                mostrarAlerta('Usuario no encontrado', 'error');
                return;
            }
            
            if (usuario.password !== password) {
                mostrarAlerta('Contraseña incorrecta', 'error');
                return;
            }
            
            if (usuario.rol !== rolSeleccionado) {
                mostrarAlerta(`Este usuario no tiene permisos de ${rolSeleccionado}`, 'error');
                return;
            }
            
            // Login exitoso
            loginExitoso(usuario);
        });

        // Función para mostrar alertas
        function mostrarAlerta(mensaje, tipo) {
            const alertContainer = document.getElementById('alert-container');
            const alertClass = tipo === 'error' ? 'alert-danger' : 'alert-info';
            const icon = tipo === 'error' ? 'exclamation-triangle' : 'info-circle';
            
            const alertHTML = `
                <div class="alert ${alertClass} alert-login d-flex align-items-center" role="alert">
                    <i class="fas fa-${icon} me-2"></i>
                    <div>${mensaje}</div>
                </div>
            `;
            
            alertContainer.innerHTML = alertHTML;
            
            // Auto-remover después de 5 segundos
            setTimeout(() => {
                alertContainer.innerHTML = '';
            }, 5000);
        }

        // Función para login exitoso
        function loginExitoso(usuario) {
            // Mostrar loading
            const submitBtn = document.querySelector('.btn-login');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Iniciando sesión...';
            submitBtn.disabled = true;
            
            // Simular proceso de autenticación
            setTimeout(() => {
                // Guardar datos de sesión (en un sistema real sería más seguro)
                sessionStorage.setItem('usuarioLogueado', JSON.stringify({
                    username: usuario.nombre,
                    rol: usuario.rol,
                    timestamp: new Date().toISOString()
                }));
                
                // Mostrar mensaje de éxito
                mostrarAlerta(`¡Bienvenido, ${usuario.nombre}!`, 'info');
                
                // Redirigir después de 1.5 segundos
                setTimeout(() => {
                    window.location.href = usuario.redirect;
                }, 1500);
                
            }, 1000);
        }

        // Auto-completar credenciales para testing
        document.addEventListener('keydown', function(e) {
            // Atajo Ctrl + 1 para Comandante
            if (e.ctrlKey && e.key === '1') {
                autoCompletarCredenciales('comandante');
            }
            // Atajo Ctrl + 2 para Oficial
            if (e.ctrlKey && e.key === '2') {
                autoCompletarCredenciales('oficial');
            }
            // Atajo Ctrl + 3 para Bombero
            if (e.ctrlKey && e.key === '3') {
                autoCompletarCredenciales('bombero');
            }
            // Atajo Ctrl + 4 para Ciudadano
            if (e.ctrlKey && e.key === '4') {
                autoCompletarCredenciales('ciudadano');
            }
        });

        function autoCompletarCredenciales(rol) {
            // Seleccionar rol
            document.querySelectorAll('.role-option').forEach(opt => {
                opt.classList.remove('active');
                if (opt.getAttribute('data-role') === rol) {
                    opt.classList.add('active');
                    rolSeleccionado = rol;
                }
            });
            
            // Completar credenciales
            document.getElementById('username').value = rol;
            document.getElementById('password').value = '1234';
            
            mostrarAlerta(`Credenciales de ${rol} cargadas. Presione "Iniciar Sesión"`, 'info');
        }

        // Verificar si ya hay una sesión activa
        window.addEventListener('load', function() {
            const usuarioLogueado = sessionStorage.getItem('usuarioLogueado');
            if (usuarioLogueado) {
                const usuario = JSON.parse(usuarioLogueado);
                mostrarAlerta(`Sesión activa detectada para ${usuario.username}. <a href="#" onclick="cerrarSesion()">Cerrar sesión</a>`, 'info');
            }
        });

        // Función para cerrar sesión
        function cerrarSesion() {
            sessionStorage.removeItem('usuarioLogueado');
            mostrarAlerta('Sesión cerrada correctamente', 'info');
            setTimeout(() => {
                location.reload();
            }, 1000);
        }

        // Efectos de entrada para los campos
        document.addEventListener('DOMContentLoaded', function() {
            const inputs = document.querySelectorAll('.form-control');
            inputs.forEach((input, index) => {
                input.style.opacity = '0';
                input.style.transform = 'translateY(20px)';
                
                setTimeout(() => {
                    input.style.transition = 'all 0.5s ease';
                    input.style.opacity = '1';
                    input.style.transform = 'translateY(0)';
                }, index * 100);
            });
        });
    </script>
</body>
</html>