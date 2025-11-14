<?php
/**
 * Sistema Integral de Emergencias - Bomberos del Chocó
 * Funciones de Autenticación
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/database.php';

/**
 * Autenticar usuario con base de datos
 */
function authenticateUser($username, $password, $rol = null) {
    try {
        $db = connectDatabase();
        
        // Buscar usuario por nombre de usuario
        $sql = "SELECT u.id, u.nombre, u.email, u.tipo, u.activo, u.password 
                FROM usuarios u 
                WHERE u.nombre = ? AND u.activo = 1";
        
        $stmt = $db->prepare($sql);
        if (!$stmt) {
            throw new Exception("Error en preparación de consulta: " . $db->error);
        }
        
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            registrarLog('LOGIN_FALLIDO', 'AUTH', "Usuario no encontrado: $username");
            return false;
        }
        
        $usuario = $result->fetch_assoc();
        
        // Verificar contraseña (usando password_verify si está hasheada)
        if (!password_verify($password, $usuario['password']) && $usuario['password'] !== $password) {
            registrarLog('LOGIN_FALLIDO', 'AUTH', "Contraseña incorrecta para: $username");
            return false;
        }
        
        // Verificar rol si se especificó
        if ($rol && $usuario['tipo'] !== $rol) {
            registrarLog('LOGIN_FALLIDO', 'AUTH', "Rol incorrecto para usuario: $username");
            return false;
        }
        
        // Login exitoso - crear sesión
        $_SESSION['user_id'] = $usuario['id'];
        $_SESSION['username'] = $usuario['nombre'];
        $_SESSION['user_email'] = $usuario['email'];
        $_SESSION['user_type'] = $usuario['tipo'];
        $_SESSION['login_time'] = time();
        $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'];
        
        registrarLog('LOGIN_EXITOSO', 'AUTH', "Usuario logueado: $username (Rol: {$usuario['tipo']})");
        
        return $usuario;
        
    } catch (Exception $e) {
        systemLog("Error en autenticación: " . $e->getMessage(), "ERROR");
        return false;
    }
}

/**
 * Verificar si el usuario está autenticado
 */
function isAuthenticated() {
    return isset($_SESSION['user_id']) && isset($_SESSION['user_type']);
}

/**
 * Verificar tipo de usuario
 */
function isUserType($type) {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === $type;
}

/**
 * Función específica para cada tipo de usuario
 */
function isComandante() {
    return isUserType('comandante');
}

function isOficial() {
    return isUserType('oficial');
}

function isBombero() {
    return isUserType('bombero');
}

function isAdmin() {
    return isUserType('admin') || isUserType('administrativo');
}

function isCiudadano() {
    return isUserType('ciudadano');
}

function isOperador() {
    return isUserType('operador') || isUserType('oficial');
}

/**
 * Cerrar sesión del usuario
 */
function logoutUser() {
    $username = $_SESSION['username'] ?? 'desconocido';
    registrarLog('LOGOUT', 'AUTH', "Usuario deslogueado: $username");
    
    session_unset();
    session_destroy();
}

/**
 * Redirigir si no está autenticado
 */
function requireAuth($types = null) {
    if (!isAuthenticated()) {
        header("Location: /sistema_bomberos_choco/login.php");
        exit();
    }
    
    if ($types && !in_array($_SESSION['user_type'], (array)$types)) {
        header("Location: /sistema_bomberos_choco/index.php");
        exit();
    }
}

/**
 * Obtener datos del usuario actual
 */
function getCurrentUser($db = null) {
    if (!isAuthenticated()) {
        return null;
    }
    
    if ($db === null) {
        $db = connectDatabase();
    }
    
    $user_id = $_SESSION['user_id'];
    $sql = "SELECT * FROM usuarios WHERE id = ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->fetch_assoc();
}

/**
 * Registrar acción en el log del sistema
 */
function registrarLog($accion, $modulo, $descripcion = '') {
    try {
        $db = connectDatabase();
        $usuario_id = $_SESSION['user_id'] ?? null;
        $ip_address = $_SERVER['REMOTE_ADDR'];
        $user_agent = substr($_SERVER['HTTP_USER_AGENT'], 0, 255);
        
        $sql = "INSERT INTO logs_sistema (usuario_id, accion, modulo, descripcion, ip_address, user_agent, fecha_log) 
                VALUES (?, ?, ?, ?, ?, ?, NOW())";
        
        $stmt = $db->prepare($sql);
        $stmt->bind_param("isssss", $usuario_id, $accion, $modulo, $descripcion, $ip_address, $user_agent);
        $stmt->execute();
        
    } catch (Exception $e) {
        systemLog("Error registrando log: " . $e->getMessage(), "ERROR");
    }
}

/**
 * Obtener ID de bombero desde usuario_id
 */
function obtenerBomberoId($db, $usuario_id) {
    $sql = "SELECT id FROM bomberos WHERE usuario_id = ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    return $result ? $result['id'] : null;
}

/**
 * Determinar gravedad de emergencia por tipo
 */
function determinarGravedad($tipo) {
    $gravedades = [
        'incendio' => 'critica',
        'accidente' => 'alta', 
        'medica' => 'alta',
        'rescate' => 'critica',
        'inundacion' => 'media',
        'otro' => 'media'
    ];
    return $gravedades[strtolower($tipo)] ?? 'media';
}

/**
 * Verificar nuevas emergencias sin asignar
 */
function checkNewEmergencies($db) {
    $sql = "SELECT COUNT(*) as total FROM emergencias 
            WHERE estado = 'reportada' AND DATE(fecha_reporte) = CURDATE()";
    $result = $db->query($sql);
    $row = $result->fetch_assoc();
    return $row['total'] ?? 0;
}

/**
 * Obtener perfil completo del usuario
 */
function getUserProfile($db, $user_id) {
    $user = getSingleRecord($db, "SELECT * FROM usuarios WHERE id = ?", [$user_id], "i");
    
    if (!$user) {
        return null;
    }
    
    // Agregar datos específicos según el tipo de usuario
    if ($user['tipo'] === 'bombero') {
        $user['bombero_data'] = getSingleRecord($db, 
            "SELECT * FROM bomberos WHERE usuario_id = ?", 
            [$user_id], 
            "i"
        );
    }
    
    return $user;
}

/**
 * Actualizar última actividad del usuario
 */
function updateLastActivity() {
    if (isAuthenticated()) {
        $_SESSION['LAST_ACTIVITY'] = time();
    }
}

?>
