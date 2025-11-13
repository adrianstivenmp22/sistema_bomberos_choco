<?php
// includes/database.php

/**
 * Sistema Integral de Emergencias - Bomberos del Chocó
 * Conexión y funciones de base de datos
 */

// Cargar configuración
require_once '../config/config.php';

/**
 * Establecer conexión con la base de datos
 */
function connectDatabase() {
    try {
        $db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        // Verificar conexión
        if ($db->connect_error) {
            throw new Exception("Error de conexión a la base de datos: " . $db->connect_error);
        }
        
        // Establecer charset
        if (!$db->set_charset(DB_CHARSET)) {
            throw new Exception("Error estableciendo charset: " . $db->error);
        }
        
        systemLog("Conexión a base de datos establecida correctamente", "INFO");
        return $db;
        
    } catch (Exception $e) {
        systemLog("Error crítico de base de datos: " . $e->getMessage(), "ERROR");
        
        // Mostrar error amigable en desarrollo
        if (ENVIRONMENT === 'development') {
            die("Error de base de datos: " . $e->getMessage());
        } else {
            die("Error del sistema. Por favor contacte al administrador.");
        }
    }
}

/**
 * Ejecutar consulta con parámetros seguros
 */
function executeQuery($db, $sql, $params = [], $types = '') {
    try {
        $stmt = $db->prepare($sql);
        
        if (!$stmt) {
            throw new Exception("Error preparando consulta: " . $db->error);
        }
        
        // Bind parameters si existen
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        // Ejecutar consulta
        if (!$stmt->execute()) {
            throw new Exception("Error ejecutando consulta: " . $stmt->error);
        }
        
        // Obtener resultado si es SELECT
        if (stripos($sql, 'SELECT') === 0) {
            $result = $stmt->get_result();
            return $result;
        } else {
            // Para INSERT, UPDATE, DELETE retornar affected rows
            return $stmt->affected_rows;
        }
        
    } catch (Exception $e) {
        systemLog("Error en consulta SQL: " . $e->getMessage() . " - SQL: " . $sql, "ERROR");
        throw $e;
    }
}

/**
 * Obtener un solo registro
 */
function getSingleRecord($db, $sql, $params = [], $types = '') {
    $result = executeQuery($db, $sql, $params, $types);
    
    if ($result && $result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    
    return null;
}

/**
 * Obtener múltiples registros
 */
function getMultipleRecords($db, $sql, $params = [], $types = '') {
    $result = executeQuery($db, $sql, $params, $types);
    
    $records = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $records[] = $row;
        }
    }
    
    return $records;
}

/**
 * Insertar registro y retornar ID
 */
function insertRecord($db, $table, $data) {
    $columns = implode(', ', array_keys($data));
    $placeholders = implode(', ', array_fill(0, count($data), '?'));
    $values = array_values($data);
    $types = str_repeat('s', count($data));
    
    $sql = "INSERT INTO $table ($columns) VALUES ($placeholders)";
    
    executeQuery($db, $sql, $values, $types);
    
    return $db->insert_id;
}

/**
 * Actualizar registro
 */
function updateRecord($db, $table, $data, $where, $where_params = [], $where_types = '') {
    $set_clause = implode(' = ?, ', array_keys($data)) . ' = ?';
    $values = array_merge(array_values($data), $where_params);
    $types = str_repeat('s', count($data)) . $where_types;
    
    $sql = "UPDATE $table SET $set_clause WHERE $where";
    
    return executeQuery($db, $sql, $values, $types);
}

/**
 * Eliminar registro
 */
function deleteRecord($db, $table, $where, $params = [], $types = '') {
    $sql = "DELETE FROM $table WHERE $where";
    return executeQuery($db, $sql, $params, $types);
}

/**
 * Verificar si un registro existe
 */
function recordExists($db, $table, $where, $params = [], $types = '') {
    $sql = "SELECT 1 FROM $table WHERE $where LIMIT 1";
    $result = executeQuery($db, $sql, $params, $types);
    
    return $result && $result->num_rows > 0;
}

/**
 * Contar registros
 */
function countRecords($db, $table, $where = '1', $params = [], $types = '') {
    $sql = "SELECT COUNT(*) as total FROM $table WHERE $where";
    $result = getSingleRecord($db, $sql, $params, $types);
    
    return $result ? $result['total'] : 0;
}

/**
 * Obtener estadísticas del sistema
 */
function getSystemStats($db) {
    $stats = [];
    
    // Total de usuarios por tipo
    $stats['usuarios'] = getMultipleRecords($db, 
        "SELECT tipo, COUNT(*) as total FROM usuarios WHERE activo = 1 GROUP BY tipo"
    );
    
    // Emergencias del día
    $stats['emergencias_hoy'] = getSingleRecord($db,
        "SELECT COUNT(*) as total,
                SUM(estado = 'reportada') as pendientes,
                SUM(estado = 'en_progreso') as en_progreso,
                SUM(estado = 'resuelta') as resueltas
         FROM emergencias 
         WHERE DATE(fecha_reporte) = CURDATE()"
    );
    
    // Bomberos disponibles
    $stats['bomberos'] = getSingleRecord($db,
        "SELECT COUNT(*) as total,
                SUM(disponible = 1) as disponibles,
                SUM(disponible = 0) as ocupados
         FROM bomberos 
         WHERE activo = 1"
    );
    
    // Tiempo promedio de respuesta
    $stats['tiempos'] = getSingleRecord($db,
        "SELECT AVG(TIMESTAMPDIFF(MINUTE, fecha_reporte, fecha_asignacion)) as asignacion,
                AVG(TIMESTAMPDIFF(MINUTE, fecha_asignacion, fecha_cierre)) as resolucion
         FROM emergencias 
         WHERE estado = 'resuelta' 
         AND fecha_reporte >= DATE_SUB(NOW(), INTERVAL 7 DAY)"
    );
    
    return $stats;
}

/**
 * Backup de datos críticos
 */
function backupCriticalData($db) {
    $backup_data = [];
    
    // Datos de emergencias activas
    $backup_data['emergencias_activas'] = getMultipleRecords($db,
        "SELECT e.*, u.nombre as ciudadano_nombre, u.telefono
         FROM emergencias e
         JOIN usuarios u ON e.ciudadano_id = u.id
         WHERE e.estado IN ('reportada', 'en_progreso')
         ORDER BY e.fecha_reporte DESC"
    );
    
    // Asignaciones activas
    $backup_data['asignaciones_activas'] = getMultipleRecords($db,
        "SELECT a.*, b.numero_placa, u.nombre as bombero_nombre
         FROM asignaciones a
         JOIN bomberos b ON a.bombero_id = b.id
         JOIN usuarios u ON b.usuario_id = u.id
         WHERE a.estado != 'completada'
         ORDER BY a.fecha_asignacion DESC"
    );
    
    // Bomberos activos
    $backup_data['bomberos_activos'] = getMultipleRecords($db,
        "SELECT b.*, u.nombre, u.telefono, un.nombre as unidad_nombre
         FROM bomberos b
         JOIN usuarios u ON b.usuario_id = u.id
         LEFT JOIN unidades un ON b.unidad_id = un.id
         WHERE b.activo = 1
         ORDER BY b.disponible DESC, u.nombre ASC"
    );
    
    return $backup_data;
}

/**
 * Limpiar datos antiguos (mantenimiento)
 */
function cleanupOldData($db) {
    $cleaned = 0;
    
    // Emergencias resueltas con más de 30 días
    $result = executeQuery($db,
        "DELETE FROM emergencias 
         WHERE estado = 'resuelta' 
         AND fecha_cierre < DATE_SUB(NOW(), INTERVAL 30 DAY)"
    );
    $cleaned += $result;
    
    // Logs del sistema con más de 90 días
    $result = executeQuery($db,
        "DELETE FROM logs_sistema 
         WHERE fecha_log < DATE_SUB(NOW(), INTERVAL 90 DAY)"
    );
    $cleaned += $result;
    
    // Backups antiguos (se maneja en la función de backup)
    
    systemLog("Limpieza de datos antiguos completada: $cleaned registros eliminados", "INFO");
    return $cleaned;
}

/**
 * Verificar integridad de la base de datos
 */
function checkDatabaseIntegrity($db) {
    $integrity_checks = [];
    
    // Verificar usuarios sin tipo
    $integrity_checks['usuarios_sin_tipo'] = countRecords($db, 'usuarios', 'tipo IS NULL OR tipo = ""');
    
    // Verificar emergencias sin ciudadano
    $integrity_checks['emergencias_sin_ciudadano'] = countRecords($db, 'emergencias', 'ciudadano_id NOT IN (SELECT id FROM usuarios)');
    
    // Verificar asignaciones sin bombero
    $integrity_checks['asignaciones_sin_bombero'] = countRecords($db, 'asignaciones', 'bombero_id NOT IN (SELECT id FROM bomberos)');
    
    // Verificar bomberos sin usuario
    $integrity_checks['bomberos_sin_usuario'] = countRecords($db, 'bomberos', 'usuario_id NOT IN (SELECT id FROM usuarios)');
    
    // Verificar coordenadas inválidas
    $integrity_checks['coordenadas_invalidas'] = countRecords($db, 'emergencias', 
        'latitud IS NULL OR longitud IS NULL OR latitud = 0 OR longitud = 0'
    );
    
    return $integrity_checks;
}

// Conexión global (opcional, usar con cuidado)
try {
    $GLOBALS['db'] = connectDatabase();
} catch (Exception $e) {
    // La conexión se establecerá cuando sea necesaria
    systemLog("Conexión global no establecida: " . $e->getMessage(), "WARNING");
}

?>