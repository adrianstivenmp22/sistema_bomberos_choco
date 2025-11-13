<?php
// /opt/lampp/htdocs/sistema_bomberos_choco/includes/update_location.php

session_start();
require_once 'auth.php';
require_once 'database.php';

header('Content-Type: application/json');

// Verificar que el usuario sea bombero
if (!isBombero()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'No autorizado: Solo bomberos pueden actualizar ubicación']);
    exit();
}

// Obtener datos JSON del cuerpo de la petición
$input = json_decode(file_get_contents('php://input'), true);
$lat = $input['lat'] ?? null;
$lng = $input['lng'] ?? null;

// Validar coordenadas
if ($lat === null || $lng === null) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Coordenadas requeridas']);
    exit();
}

if (!is_numeric($lat) || !is_numeric($lng)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Coordenadas inválidas']);
    exit();
}

// Convertir a float
$lat = floatval($lat);
$lng = floatval($lng);

// Validar rangos de coordenadas (Chocó, Colombia aproximadamente)
if ($lat < 4.0 || $lat > 8.0 || $lng < -78.0 || $lng > -76.0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Coordenadas fuera del rango válido para Chocó']);
    exit();
}

try {
    $db = connectDatabase();
    $bombero_id = obtenerBomberoId($db, $_SESSION['user_id']);
    
    if (!$bombero_id) {
        throw new Exception('Bombero no encontrado');
    }
    
    // Actualizar ubicación en la base de datos
    $sql = "UPDATE bomberos SET latitud_actual = ?, longitud_actual = ?, ultima_actualizacion_ubicacion = NOW() WHERE id = ?";
    $stmt = $db->prepare($sql);
    
    if (!$stmt) {
        throw new Exception('Error preparando consulta: ' . $db->error);
    }
    
    $stmt->bind_param("ddi", $lat, $lng, $bombero_id);
    
    if (!$stmt->execute()) {
        throw new Exception('Error ejecutando consulta: ' . $stmt->error);
    }
    
    // Registrar log del sistema
    registrarLog('ubicacion_actualizada', 'bombero', 
        "Bombero $bombero_id actualizó ubicación a: $lat, $lng");
    
    // Éxito
    echo json_encode([
        'success' => true, 
        'message' => 'Ubicación actualizada correctamente',
        'coordenadas' => [
            'lat' => $lat,
            'lng' => $lng,
            'timestamp' => date('Y-m-d H:i:s')
        ]
    ]);
    
    $stmt->close();
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'error' => 'Error del servidor: ' . $e->getMessage()
    ]);
    
    // Registrar error en logs
    error_log("Error en update_location.php: " . $e->getMessage());
}
?>