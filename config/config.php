<?php
// ===== CONFIGURACIÓN DEL SISTEMA BOMBEROS DEL CHOCÓ =====

// Configuración de entorno
define('ENVIRONMENT', 'development'); // 'development' o 'production'

// Configuración de base de datos
define('DB_HOST', 'localhost');
define('DB_NAME', 'sistema_bomberos_choco');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Configuración de la aplicación
define('APP_NAME', 'Sistema Integral de Emergencias - Bomberos del Chocó');
define('APP_VERSION', '1.0.0');
define('APP_URL', 'http://localhost/sistema_bomberos_choco');
define('APP_TIMEZONE', 'America/Bogota');

// Configuración de seguridad
define('SESSION_TIMEOUT', 3600); // 1 hora en segundos
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_TIME', 900); // 15 minutos en segundos

// Configuración de archivos
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB
define('ALLOWED_FILE_TYPES', [
    'image/jpeg',
    'image/png', 
    'image/gif',
    'video/mp4',
    'video/avi',
    'application/pdf',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
]);

// Configuración de Google Maps
define('GMAPS_API_KEY', 'TU_GOOGLE_MAPS_API_KEY');
define('GMAPS_DEFAULT_LAT', 5.6946); // Quibdó
define('GMAPS_DEFAULT_LNG', -76.6610); // Quibdó
define('GMAPS_DEFAULT_ZOOM', 12);

// Configuración de notificaciones
define('NOTIFICATIONS_ENABLED', true);
define('PUSH_NOTIFICATIONS', false);
define('EMAIL_NOTIFICATIONS', false);

// Configuración de operaciones
define('MAX_RESPONSE_TIME', 15); // minutos
define('AUTO_ASSIGN_RADIUS', 10); // kilómetros
define('EMERGENCY_TIMEOUT', 240); // 4 horas en minutos

// Configuración de reportes
define('REPORTS_PER_PAGE', 20);
define('AUTO_REFRESH_INTERVAL', 30000); // 30 segundos en milisegundos

// Configuración de backup
define('BACKUP_ENABLED', true);
define('BACKUP_PATH', '../assets/backups/');
define('BACKUP_RETENTION_DAYS', 30);

// Configuración de logs
define('LOG_ENABLED', true);
define('LOG_LEVEL', 'DEBUG'); // DEBUG, INFO, WARNING, ERROR
define('LOG_PATH', '../logs/');

// ===== FUNCIONES DE CONFIGURACIÓN =====

/**
 * Configurar zona horaria
 */
function setupTimezone() {
    date_default_timezone_set(APP_TIMEZONE);
}

/**
 * Configurar manejo de errores según el entorno
 */
function setupErrorHandling() {
    if (ENVIRONMENT === 'development') {
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
        ini_set('log_errors', 1);
    } else {
        error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
        ini_set('display_errors', 0);
        ini_set('log_errors', 1);
    }
    
    // Configurar archivo de log de errores
    if (LOG_ENABLED) {
        $log_file = LOG_PATH . 'error_log_' . date('Y-m-d') . '.log';
        ini_set('error_log', $log_file);
    }
}

/**
 * Iniciar sesión segura
 */
function setupSession() {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', 1);
    ini_set('session.use_strict_mode', 1);
    ini_set('session.cookie_samesite', 'Strict');
    
    session_start();
    
    // Verificar timeout de sesión
    if (isset($_SESSION['LAST_ACTIVITY']) && 
        (time() - $_SESSION['LAST_ACTIVITY'] > SESSION_TIMEOUT)) {
        session_unset();
        session_destroy();
        session_start();
    }
    $_SESSION['LAST_ACTIVITY'] = time();
}

/**
 * Validar tipo de archivo
 */
function isValidFileType($file_type) {
    return in_array($file_type, ALLOWED_FILE_TYPES);
}

/**
 * Validar tamaño de archivo
 */
function isValidFileSize($file_size) {
    return $file_size <= MAX_FILE_SIZE;
}

/**
 * Obtener configuración del sistema desde la base de datos
 */
function getSystemConfig($db, $key = null) {
    $config = [];
    
    if ($key) {
        $sql = "SELECT valor FROM configuraciones WHERE clave = ?";
        $stmt = $db->prepare($sql);
        $stmt->bind_param("s", $key);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return $row['valor'];
        }
        return null;
    } else {
        $sql = "SELECT clave, valor FROM configuraciones";
        $result = $db->query($sql);
        
        while ($row = $result->fetch_assoc()) {
            $config[$row['clave']] = $row['valor'];
        }
        return $config;
    }
}

/**
 * Actualizar configuración del sistema
 */
function updateSystemConfig($db, $key, $value) {
    $sql = "UPDATE configuraciones SET valor = ?, fecha_actualizacion = NOW() WHERE clave = ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("ss", $value, $key);
    return $stmt->execute();
}

/**
 * Registrar evento en el log del sistema
 */
function systemLog($message, $level = 'INFO') {
    if (!LOG_ENABLED) return;
    
    $log_levels = ['DEBUG' => 1, 'INFO' => 2, 'WARNING' => 3, 'ERROR' => 4];
    $current_level = $log_levels[LOG_LEVEL] ?? 2;
    $message_level = $log_levels[$level] ?? 2;
    
    if ($message_level < $current_level) return;
    
    $log_file = LOG_PATH . 'system_log_' . date('Y-m-d') . '.log';
    $timestamp = date('Y-m-d H:i:s');
    $log_message = "[$timestamp] [$level] $message" . PHP_EOL;
    
    // Crear directorio de logs si no existe
    if (!is_dir(LOG_PATH)) {
        mkdir(LOG_PATH, 0755, true);
    }
    
    file_put_contents($log_file, $log_message, FILE_APPEND | LOCK_EX);
}

/**
 * Generar backup de la base de datos
 */
function generateBackup($db) {
    if (!BACKUP_ENABLED) return false;
    
    try {
        // Crear directorio de backups si no existe
        if (!is_dir(BACKUP_PATH)) {
            mkdir(BACKUP_PATH, 0755, true);
        }
        
        $backup_file = BACKUP_PATH . 'backup_' . date('Y-m-d_H-i-s') . '.sql';
        $tables = ['usuarios', 'emergencias', 'asignaciones', 'bomberos', 'unidades', 'configuraciones', 'logs_sistema'];
        $backup_content = "";
        
        foreach ($tables as $table) {
            // Obtener estructura de la tabla
            $result = $db->query("SHOW CREATE TABLE $table");
            $row = $result->fetch_assoc();
            $backup_content .= "-- Estructura de tabla para $table\n";
            $backup_content .= $row['Create Table'] . ";\n\n";
            
            // Obtener datos de la tabla
            $result = $db->query("SELECT * FROM $table");
            $backup_content .= "-- Datos de la tabla $table\n";
            
            while ($row = $result->fetch_assoc()) {
                $columns = implode("`, `", array_keys($row));
                $values = implode("', '", array_map([$db, 'real_escape_string'], array_values($row)));
                $backup_content .= "INSERT INTO `$table` (`$columns`) VALUES ('$values');\n";
            }
            $backup_content .= "\n";
        }
        
        if (file_put_contents($backup_file, $backup_content)) {
            systemLog("Backup generado exitosamente: $backup_file", 'INFO');
            
            // Limpiar backups antiguos
            cleanupOldBackups();
            
            return $backup_file;
        }
        
        return false;
        
    } catch (Exception $e) {
        systemLog("Error generando backup: " . $e->getMessage(), 'ERROR');
        return false;
    }
}

/**
 * Limpiar backups antiguos
 */
function cleanupOldBackups() {
    $files = glob(BACKUP_PATH . 'backup_*.sql');
    $now = time();
    $retention_seconds = BACKUP_RETENTION_DAYS * 24 * 60 * 60;
    
    foreach ($files as $file) {
        if (is_file($file)) {
            if ($now - filemtime($file) >= $retention_seconds) {
                unlink($file);
                systemLog("Backup eliminado por antigüedad: $file", 'INFO');
            }
        }
    }
}

/**
 * Calcular distancia entre dos puntos geográficos (Haversine)
 */
function calculateDistance($lat1, $lon1, $lat2, $lon2) {
    $earth_radius = 6371; // Radio de la Tierra en kilómetros
    
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    
    $a = sin($dLat/2) * sin($dLat/2) + 
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * 
         sin($dLon/2) * sin($dLon/2);
    
    $c = 2 * atan2(sqrt($a), sqrt(1-$a));
    
    return $earth_radius * $c;
}

/**
 * Obtener bomberos más cercanos a una emergencia
 */
function getNearestBombers($db, $emergency_lat, $emergency_lon, $limit = 5) {
    $sql = "SELECT b.*, u.nombre, 
                   (6371 * acos(cos(radians(?)) * cos(radians(b.latitud_actual)) * 
                   cos(radians(b.longitud_actual) - radians(?)) + 
                   sin(radians(?)) * sin(radians(b.latitud_actual)))) as distance
            FROM bomberos b
            JOIN usuarios u ON b.usuario_id = u.id
            WHERE b.activo = 1 AND b.disponible = 1 
            AND b.latitud_actual IS NOT NULL AND b.longitud_actual IS NOT NULL
            HAVING distance <= ?
            ORDER BY distance ASC
            LIMIT ?";
    
    $stmt = $db->prepare($sql);
    $radius = AUTO_ASSIGN_RADIUS;
    $stmt->bind_param("ddddi", $emergency_lat, $emergency_lon, $emergency_lat, $radius, $limit);
    $stmt->execute();
    
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// ===== INICIALIZACIÓN =====
setupTimezone();
setupErrorHandling();
setupSession();

// Mensaje de configuración cargada
systemLog("Configuración del sistema cargada - Entorno: " . ENVIRONMENT, 'INFO');

?>