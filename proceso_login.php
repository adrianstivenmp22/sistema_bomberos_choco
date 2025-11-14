<?php
/**
 * Procesamiento de Login - Sistema de Bomberos del Chocó
 */

session_start();

// Recibir datos del formulario
$username = isset($_POST['username']) ? trim($_POST['username']) : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';
$rol = isset($_POST['rol']) ? trim($_POST['rol']) : '';

// Base de datos de usuarios (en producción esto sería una BD real)
$usuarios = [
    'comandante' => [
        'password' => 'ComandanteSeguro2025!',
        'nombre' => 'Comandante García',
        'rol' => 'comandante',
        'email' => 'comandante@bomberos.com'
    ],
    'oficial' => [
        'password' => 'OficialSeguro2025!',
        'nombre' => 'Oficial Martínez',
        'rol' => 'oficial',
        'email' => 'oficial@bomberos.com'
    ],
    'bombero' => [
        'password' => 'BomberoSeguro2025!',
        'nombre' => 'Bombero Pérez',
        'rol' => 'bombero',
        'email' => 'bombero@bomberos.com'
    ],
    'ciudadano' => [
        'password' => 'CiudadanoSeguro2025!',
        'nombre' => 'Ciudadano',
        'rol' => 'ciudadano',
        'email' => 'ciudadano@email.com'
    ],
    'administrativo' => [
        'password' => 'AdminSeguro2025!',
        'nombre' => 'Personal Administrativo',
        'rol' => 'administrativo',
        'email' => 'admin@bomberos.com'
    ]
];

// Validar campos
if (empty($username) || empty($password) || empty($rol)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Faltan campos requeridos'
    ]);
    exit();
}

// Buscar usuario
if (!isset($usuarios[$username])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Usuario no encontrado'
    ]);
    exit();
}

$usuario_data = $usuarios[$username];

// Verificar contraseña
if ($usuario_data['password'] !== $password) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Contraseña incorrecta'
    ]);
    exit();
}

// Verificar rol
if ($usuario_data['rol'] !== $rol) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Rol no coincide'
    ]);
    exit();
}

// Crear sesión
$_SESSION['usuario'] = [
    'nombre' => $usuario_data['nombre'],
    'rol' => $usuario_data['rol'],
    'email' => $usuario_data['email'],
    'usuario' => $username,
    'login_time' => time(),
    'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'localhost'
];

// Determinar página de redirección
$redirect_pages = [
    'comandante' => 'comandante.php',
    'oficial' => 'oficial.php',
    'bombero' => 'bombero.php',
    'ciudadano' => 'ciudadano.php',
    'administrativo' => 'administrativo.php'
];

$redirect = isset($redirect_pages[$rol]) ? $redirect_pages[$rol] : 'index.php';

// Retornar respuesta exitosa
http_response_code(200);
echo json_encode([
    'success' => true,
    'message' => 'Login exitoso',
    'redirect' => $redirect,
    'usuario' => $_SESSION['usuario']
]);
exit();
?>
