<?php
// ... código existente ...

function isAdmin() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin';
}

// Función para registrar logs del sistema
function registrarLog($accion, $modulo, $descripcion = '') {
    $db = connectDatabase();
    $usuario_id = $_SESSION['user_id'] ?? null;
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    
    $sql = "INSERT INTO logs_sistema (usuario_id, accion, modulo, descripcion, ip_address, user_agent) 
            VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("isssss", $usuario_id, $accion, $modulo, $descripcion, $ip_address, $user_agent);
    $stmt->execute();
}
// Función para obtener ID de bombero
function obtenerBomberoId($db, $usuario_id) {
    $sql = "SELECT id FROM bomberos WHERE usuario_id = ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    return $result['id'];
}

// Función para determinar gravedad automáticamente
function determinarGravedad($tipo) {
    $gravedades = [
        'incendio' => 'alta',
        'accidente' => 'alta', 
        'medica' => 'alta',
        'rescate' => 'critica',
        'inundacion' => 'media',
        'otro' => 'media'
    ];
    return $gravedades[$tipo] ?? 'media';
}
?>