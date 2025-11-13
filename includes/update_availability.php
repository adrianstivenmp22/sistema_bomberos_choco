<?php
// /opt/lampp/htdocs/sistema_bomberos_choco/includes/update_availability.php

session_start();
require_once 'auth.php';
require_once 'database.php';

header('Content-Type: application/json');

// Verificar que el usuario sea bombero
if (!isBombero()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'No autorizado: Solo bomberos pueden cambiar disponibilidad']);
    exit();
}

try {
    $db = connectDatabase();
    $bombero_id = obtenerBomberoId($db, $_SESSION['user_id']);
    
    if (!$bombero_id) {
        throw new Exception('Bombero no encontrado');
    }
    
    // Primero obtener el estado actual
    $sql_estado_actual = "SELECT disponible, numero_placa FROM bomberos WHERE id = ?";
    $stmt_estado = $db->prepare($sql_estado_actual);
    
    if (!$stmt_estado) {
        throw new Exception('Error preparando consulta de estado: ' . $db->error);
    }
    
    $stmt_estado->bind_param("i", $bombero_id);
    $stmt_estado->execute();
    $result_estado = $stmt_estado->get_result();
    
    if ($result_estado->num_rows === 0) {
        throw new Exception('Bombero no encontrado en la base de datos');
    }
    
    $bombero_data = $result_estado->fetch_assoc();
    $disponible_actual = $bombero_data['disponible'];
    $nuevo_estado = $disponible_actual ? 0 : 1;
    
    $stmt_estado->close();
    
    // Actualizar disponibilidad
    $sql = "UPDATE bomberos SET disponible = ? WHERE id = ?";
    $stmt = $db->prepare($sql);
    
    if (!$stmt) {
        throw new Exception('Error preparando consulta de actualización: ' . $db->error);
    }
    
    $stmt->bind_param("ii", $nuevo_estado, $bombero_id);
    
    if (!$stmt->execute()) {
        throw new Exception('Error ejecutando actualización: ' . $stmt->error);
    }
    
    // Verificar que se actualizó correctamente
    if ($stmt->affected_rows === 0) {
        throw new Exception('No se pudo actualizar la disponibilidad');
    }
    
    // Registrar log del sistema
    $estado_texto = $nuevo_estado ? 'Disponible' : 'No disponible';
    registrarLog('cambio_disponibilidad', 'bombero', 
        "Bombero {$bombero_data['numero_placa']} cambió estado a: $estado_texto");
    
    // Éxito - devolver nuevo estado
    echo json_encode([
        'success' => true, 
        'disponible' => (bool)$nuevo_estado,
        'estado_texto' => $estado_texto,
        'mensaje' => "Estado actualizado a: $estado_texto",
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
    $stmt->close();
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'error' => 'Error del servidor: ' . $e->getMessage()
    ]);
    
    // Registrar error en logs
    error_log("Error en update_availability.php: " . $e->getMessage());
}
?>