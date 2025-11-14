<?php
/**
 * Login Simple - Sistema de Bomberos del Chocó
 * Este es un archivo de prueba para verificar que el login funciona
 */

// Verificar si se envió el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recibir datos
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $rol = isset($_POST['rol']) ? trim($_POST['rol']) : '';
    
    // Base de datos de usuarios
    $usuarios = [
        'comandante' => ['password' => 'ComandanteSeguro2025!', 'nombre' => 'Comandante García', 'rol' => 'comandante'],
        'oficial' => ['password' => 'OficialSeguro2025!', 'nombre' => 'Oficial Martínez', 'rol' => 'oficial'],
        'bombero' => ['password' => 'BomberoSeguro2025!', 'nombre' => 'Bombero Pérez', 'rol' => 'bombero'],
        'ciudadano' => ['password' => 'CiudadanoSeguro2025!', 'nombre' => 'Ciudadano', 'rol' => 'ciudadano'],
        'administrativo' => ['password' => 'AdminSeguro2025!', 'nombre' => 'Personal Administrativo', 'rol' => 'administrativo']
    ];
    
    // Validar entrada
    if (empty($username) || empty($password) || empty($rol)) {
        $error = 'Por favor complete todos los campos';
    } elseif (!isset($usuarios[$username])) {
        $error = 'Usuario no encontrado';
    } elseif ($usuarios[$username]['password'] !== $password) {
        $error = 'Contraseña incorrecta';
    } elseif ($usuarios[$username]['rol'] !== $rol) {
        $error = 'El rol no coincide con el usuario';
    } else {
        // Login exitoso
        session_start();
        $_SESSION['usuario'] = [
            'nombre' => $usuarios[$username]['nombre'],
            'rol' => $usuarios[$username]['rol'],
            'usuario' => $username
        ];
        
        // Redireccionar
        $pages = [
            'comandante' => 'comandante.php',
            'oficial' => 'oficial.php',
            'bombero' => 'bombero.php',
            'ciudadano' => 'ciudadano.php',
            'administrativo' => 'administrativo.php'
        ];
        
        header('Location: ' . $pages[$rol]);
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistema de Bomberos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .login-container {
            width: 100%;
            max-width: 450px;
            padding: 20px;
        }
        .login-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        .login-header {
            background: linear-gradient(135deg, #dc2626, #991b1b);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        .login-header h2 {
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
            font-weight: bold;
        }
        .login-header p {
            margin: 0;
            opacity: 0.9;
        }
        .login-body {
            padding: 2.5rem;
        }
        .form-label {
            font-weight: 600;
            color: #374151;
            margin-bottom: 0.5rem;
        }
        .form-control {
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            padding: 0.75rem 1rem;
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }
        .form-control:focus {
            border-color: #dc2626;
            box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.1);
            outline: none;
        }
        .role-selector {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 0.75rem;
            margin-bottom: 1.5rem;
        }
        .role-option {
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            padding: 0.75rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s ease;
            background: white;
            font-size: 0.85rem;
        }
        .role-option:hover {
            border-color: #dc2626;
            background: #fef2f2;
        }
        .role-option.active {
            border-color: #dc2626;
            background: #fef2f2;
            color: #dc2626;
            font-weight: 600;
        }
        .role-option i {
            display: block;
            font-size: 1.5rem;
            margin-bottom: 0.25rem;
        }
        .btn-login {
            background: linear-gradient(135deg, #dc2626, #991b1b);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            width: 100%;
            margin-top: 1rem;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 15px rgba(220, 38, 38, 0.3);
        }
        .btn-login:active {
            transform: translateY(0);
        }
        .alert {
            border-radius: 8px;
            margin-bottom: 1.5rem;
            border: none;
            padding: 0.75rem 1rem;
        }
        .credentials-box {
            background: #f3f4f6;
            border-left: 4px solid #dc2626;
            border-radius: 6px;
            padding: 1rem;
            margin-top: 1.5rem;
            font-size: 0.85rem;
        }
        .credentials-box h5 {
            color: #dc2626;
            margin-bottom: 0.75rem;
            font-weight: 600;
        }
        .credential-item {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 0.5rem;
        }
        .credential-item strong {
            color: #374151;
        }
        .credential-value {
            color: #2563eb;
            font-family: monospace;
            word-break: break-all;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <i class="fas fa-fire-extinguisher fa-2x mb-2"></i>
                <h2>Sistema de Bomberos</h2>
                <p>Ingrese sus credenciales</p>
            </div>
            
            <div class="login-body">
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <strong>Error:</strong> <?php echo htmlspecialchars($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <!-- Selector de Rol -->
                    <label class="form-label d-block mb-3">Seleccione su rol:</label>
                    <div class="role-selector">
                        <div class="role-option active" data-role="comandante">
                            <i class="fas fa-crown"></i>
                            <small>Comandante</small>
                        </div>
                        <div class="role-option" data-role="oficial">
                            <i class="fas fa-user-tie"></i>
                            <small>Oficial</small>
                        </div>
                        <div class="role-option" data-role="bombero">
                            <i class="fas fa-firefighter"></i>
                            <small>Bombero</small>
                        </div>
                        <div class="role-option" data-role="ciudadano">
                            <i class="fas fa-user"></i>
                            <small>Ciudadano</small>
                        </div>
                    </div>
                    
                    <!-- Campo de rol oculto -->
                    <input type="hidden" id="rol" name="rol" value="comandante">
                    
                    <!-- Usuario -->
                    <div class="mb-3">
                        <label for="username" class="form-label">Usuario</label>
                        <input type="text" class="form-control" id="username" name="username" 
                               placeholder="Ej: comandante" required autofocus>
                    </div>
                    
                    <!-- Contraseña -->
                    <div class="mb-3">
                        <label for="password" class="form-label">Contraseña</label>
                        <input type="password" class="form-control" id="password" name="password" 
                               placeholder="Ingrese su contraseña" required>
                    </div>
                    
                    <!-- Botón de login -->
                    <button type="submit" class="btn btn-login">
                        <i class="fas fa-sign-in-alt me-2"></i>
                        Iniciar Sesión
                    </button>
                </form>
                
                <!-- Credenciales de Demo -->
                <div class="credentials-box">
                    <h5><i class="fas fa-key me-1"></i>Credenciales de Demo</h5>
                    <div class="credential-item">
                        <div><strong>Comandante:</strong></div>
                        <div></div>
                    </div>
                    <div style="font-size: 0.8rem; margin-left: 0.5rem;">
                        usuario: <span class="credential-value">comandante</span><br>
                        clave: <span class="credential-value">ComandanteSeguro2025!</span>
                    </div>
                    
                    <div class="credential-item" style="margin-top: 0.75rem;">
                        <div><strong>Oficial:</strong></div>
                        <div></div>
                    </div>
                    <div style="font-size: 0.8rem; margin-left: 0.5rem;">
                        usuario: <span class="credential-value">oficial</span><br>
                        clave: <span class="credential-value">OficialSeguro2025!</span>
                    </div>
                    
                    <div class="credential-item" style="margin-top: 0.75rem;">
                        <div><strong>Bombero:</strong></div>
                        <div></div>
                    </div>
                    <div style="font-size: 0.8rem; margin-left: 0.5rem;">
                        usuario: <span class="credential-value">bombero</span><br>
                        clave: <span class="credential-value">BomberoSeguro2025!</span>
                    </div>
                    
                    <div class="credential-item" style="margin-top: 0.75rem;">
                        <div><strong>Ciudadano:</strong></div>
                        <div></div>
                    </div>
                    <div style="font-size: 0.8rem; margin-left: 0.5rem;">
                        usuario: <span class="credential-value">ciudadano</span><br>
                        clave: <span class="credential-value">CiudadanoSeguro2025!</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Variables globales
        let rolSeleccionado = 'comandante';
        
        // Datos de credenciales
        const credenciales = {
            'comandante': { usuario: 'comandante', password: 'ComandanteSeguro2025!' },
            'oficial': { usuario: 'oficial', password: 'OficialSeguro2025!' },
            'bombero': { usuario: 'bombero', password: 'BomberoSeguro2025!' },
            'ciudadano': { usuario: 'ciudadano', password: 'CiudadanoSeguro2025!' }
        };
        
        // Manejador de selección de rol
        document.querySelectorAll('.role-option').forEach(option => {
            option.addEventListener('click', function() {
                // Remover activo de todos
                document.querySelectorAll('.role-option').forEach(opt => {
                    opt.classList.remove('active');
                });
                
                // Agregar activo al seleccionado
                this.classList.add('active');
                
                // Actualizar rol
                rolSeleccionado = this.getAttribute('data-role');
                document.getElementById('rol').value = rolSeleccionado;
                
                // Opcional: auto-llenar usuario
                const cred = credenciales[rolSeleccionado];
                document.getElementById('username').value = cred.usuario;
                document.getElementById('password').value = cred.password;
                document.getElementById('password').focus();
            });
        });
        
        // Atajo de teclado: Ctrl + Enter para enviar
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey && e.key === 'Enter') {
                document.querySelector('form').submit();
            }
        });
    </script>
</body>
</html>
