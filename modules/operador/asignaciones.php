<?php
session_start();
require_once '../../includes/auth.php';
require_once '../../includes/database.php';

if (!isOperador()) {
    header('Location: /sistema_bomberos_choco/index.php');
    exit();
}

$db = connectDatabase();

// Obtener ID de emergencia si se proporciona
$emergencia_id = $_GET['emergencia_id'] ?? null;
$bombero_id = $_GET['bombero_id'] ?? null;

// Procesar asignación
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'asignar_bombero') {
        $emergencia_id = $_POST['emergencia_id'];
        $bombero_id = $_POST['bombero_id'];
        
        asignarBombero($db, $emergencia_id, $bombero_id, $_SESSION['user_id']);
    } elseif ($action === 'asignacion_multiple') {
        $emergencia_id = $_POST['emergencia_id'];
        $bomberos_ids = $_POST['bomberos'] ?? [];
        
        foreach ($bomberos_ids as $bombero_id) {
            asignarBombero($db, $emergencia_id, $bombero_id, $_SESSION['user_id']);
        }
    } elseif ($action === 'reasignar_emergencia') {
        $emergencia_id = $_POST['emergencia_id'];
        $nuevo_bombero_id = $_POST['nuevo_bombero_id'];
        
        reasignarEmergencia($db, $emergencia_id, $nuevo_bombero_id, $_SESSION['user_id']);
    }
}

// Obtener datos
$emergencia = $emergencia_id ? obtenerEmergencia($db, $emergencia_id) : null;
$bomberos_disponibles = obtenerBomberosDisponibles($db);
$asignaciones_activas = $emergencia_id ? obtenerAsignacionesEmergencia($db, $emergencia_id) : [];
$emergencias_pendientes = obtenerEmergenciasPendientes($db);

function obtenerEmergencia($db, $emergencia_id) {
    $sql = "SELECT e.*, u.nombre as ciudadano_nombre, u.telefono as ciudadano_telefono,
                   TIMESTAMPDIFF(MINUTE, e.fecha_reporte, NOW()) as minutos_espera
            FROM emergencias e
            JOIN usuarios u ON e.ciudadano_id = u.id
            WHERE e.id = ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("i", $emergencia_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

function obtenerBomberosDisponibles($db) {
    $sql = "SELECT b.*, u.nombre, u.telefono, un.nombre as unidad_nombre,
                   (SELECT COUNT(*) FROM asignaciones a 
                    WHERE a.bombero_id = b.id AND a.estado != 'completada') as asignaciones_activas,
                   b.latitud_actual, b.longitud_actual
            FROM bomberos b
            JOIN usuarios u ON b.usuario_id = u.id
            LEFT JOIN unidades un ON b.unidad_id = un.id
            WHERE b.activo = 1
            ORDER BY b.disponible DESC, asignaciones_activas ASC, b.especialidad";
    return $db->query($sql)->fetch_all(MYSQLI_ASSOC);
}

function obtenerAsignacionesEmergencia($db, $emergencia_id) {
    $sql = "SELECT a.*, b.numero_placa, u.nombre as bombero_nombre, 
                   un.nombre as unidad_nombre, b.especialidad
            FROM asignaciones a
            JOIN bomberos b ON a.bombero_id = b.id
            JOIN usuarios u ON b.usuario_id = u.id
            LEFT JOIN unidades un ON b.unidad_id = un.id
            WHERE a.emergencia_id = ? AND a.estado != 'completada'
            ORDER BY a.fecha_asignacion DESC";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("i", $emergencia_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function obtenerEmergenciasPendientes($db) {
    $sql = "SELECT e.*, u.nombre as ciudadano_nombre,
                   TIMESTAMPDIFF(MINUTE, e.fecha_reporte, NOW()) as minutos_espera
            FROM emergencias e
            JOIN usuarios u ON e.ciudadano_id = u.id
            WHERE e.estado = 'reportada'
            ORDER BY 
                CASE e.gravedad 
                    WHEN 'critica' THEN 1
                    WHEN 'alta' THEN 2
                    ELSE 3
                END,
                e.fecha_reporte ASC
            LIMIT 10";
    return $db->query($sql)->fetch_all(MYSQLI_ASSOC);
}

function asignarBombero($db, $emergencia_id, $bombero_id, $operador_id) {
    // Verificar si ya existe asignación activa
    $sql_check = "SELECT id FROM asignaciones 
                  WHERE emergencia_id = ? AND bombero_id = ? AND estado != 'completada'";
    $stmt_check = $db->prepare($sql_check);
    $stmt_check->bind_param("ii", $emergencia_id, $bombero_id);
    $stmt_check->execute();
    
    if ($stmt_check->get_result()->num_rows > 0) {
        $_SESSION['error'] = "Este bombero ya está asignado a esta emergencia";
        return;
    }
    
    // Crear asignación
    $sql = "INSERT INTO asignaciones (emergencia_id, bombero_id, operador_id, estado) 
            VALUES (?, ?, ?, 'asignada')";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("iii", $emergencia_id, $bombero_id, $operador_id);
    
    if ($stmt->execute()) {
        // Actualizar estado de la emergencia
        $sql_update = "UPDATE emergencias SET estado = 'en_progreso', fecha_asignacion = NOW() 
                       WHERE id = ? AND estado = 'reportada'";
        $stmt_update = $db->prepare($sql_update);
        $stmt_update->bind_param("i", $emergencia_id);
        $stmt_update->execute();
        
        // Actualizar disponibilidad del bombero
        $sql_bombero = "UPDATE bomberos SET disponible = 0 WHERE id = ?";
        $stmt_bombero = $db->prepare($sql_bombero);
        $stmt_bombero->bind_param("i", $bombero_id);
        $stmt_bombero->execute();
        
        // Registrar log
        registrarLog('asignacion_creada', 'operador', 
            "Bombero $bombero_id asignado a emergencia $emergencia_id");
        
        $_SESSION['success'] = "Bombero asignado exitosamente a la emergencia";
    } else {
        $_SESSION['error'] = "Error al asignar bombero: " . $db->error;
    }
}

function reasignarEmergencia($db, $emergencia_id, $nuevo_bombero_id, $operador_id) {
    // Aquí se implementaría la reasignación
    // (lógica similar a asignarBombero pero con manejo de la asignación anterior)
    $_SESSION['success'] = "Funcionalidad de reasignación en desarrollo";
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Asignaciones - Operador</title>
    <link rel="stylesheet" href="/sistema_bomberos_choco/css/styles.css">
</head>
<body>
    <?php include '../../includes/header.php'; ?>
    
    <div class="container">
        <h1>👨‍🚒 Gestión de Asignaciones</h1>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <?= $_SESSION['success'] ?>
                <?php unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger">
                <?= $_SESSION['error'] ?>
                <?php unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <div class="dashboard-grid">
            <!-- Lista de Emergencias Pendientes -->
            <div class="card">
                <h2>🚨 Emergencias Pendientes de Asignación</h2>
                
                <?php if (empty($emergencias_pendientes)): ?>
                    <div class="alert alert-success">
                        ✅ No hay emergencias pendientes de asignación
                    </div>
                <?php else: ?>
                    <div class="emergencies-list">
                        <?php foreach ($emergencias_pendientes as $emergencia): ?>
                        <div class="emergency-item <?= $emergencia['gravedad'] ?>">
                            <div class="emergency-header">
                                <h4>
                                    <span class="badge badge-<?= $emergencia['tipo'] ?>">
                                        <?= ucfirst($emergencia['tipo']) ?>
                                    </span>
                                    <span class="badge badge-<?= $emergencia['gravedad'] ?>">
                                        <?= strtoupper($emergencia['gravedad']) ?>
                                    </span>
                                </h4>
                                <div class="emergency-meta">
                                    <strong>Caso #<?= $emergencia['id'] ?></strong> • 
                                    <?= date('H:i', strtotime($emergencia['fecha_reporte'])) ?> •
                                    <span class="text-danger"><?= $emergencia['minutos_espera'] ?> min</span>
                                </div>
                            </div>
                            
                            <div class="emergency-body">
                                <p><?= htmlspecialchars(substr($emergencia['descripcion'], 0, 100)) ?>...</p>
                                <p><small>📍 <?= htmlspecialchars($emergencia['direccion']) ?></small></p>
                                <p><small>👤 <?= htmlspecialchars($emergencia['ciudadano_nombre']) ?></small></p>
                            </div>
                            
                            <div class="emergency-actions">
                                <a href="asignaciones.php?emergencia_id=<?= $emergencia['id'] ?>" 
                                   class="btn btn-sm btn-primary">
                                    ⚡ Asignar Recursos
                                </a>
                                <a href="mapa.php?emergencia_id=<?= $emergencia['id'] ?>" 
                                   class="btn btn-sm btn-outline">
                                    🗺️ Ver Mapa
                                </a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Gestión de Asignación Específica -->
            <?php if ($emergencia): ?>
            <div class="card">
                <h2>⚡ Asignar Recursos - Caso #<?= $emergencia['id'] ?></h2>
                
                <!-- Información de la Emergencia -->
                <div class="emergency-detail">
                    <div class="detail-header">
                        <h3>
                            <span class="badge badge-<?= $emergencia['tipo'] ?>">
                                <?= ucfirst($emergencia['tipo']) ?>
                            </span>
                            <span class="badge badge-<?= $emergencia['gravedad'] ?>">
                                GRAVEDAD: <?= strtoupper($emergencia['gravedad']) ?>
                            </span>
                        </h3>
                        <div class="detail-meta">
                            <strong>Reportado:</strong> <?= date('d/m/Y H:i', strtotime($emergencia['fecha_reporte'])) ?> •
                            <strong>Espera:</strong> <span class="text-danger"><?= $emergencia['minutos_espera'] ?> minutos</span>
                        </div>
                    </div>
                    
                    <div class="detail-body">
                        <p><strong>📝 Descripción:</strong> <?= htmlspecialchars($emergencia['descripcion']) ?></p>
                        <p><strong>📍 Ubicación:</strong> <?= htmlspecialchars($emergencia['direccion']) ?></p>
                        <p><strong>👤 Reportante:</strong> <?= htmlspecialchars($emergencia['ciudadano_nombre']) ?></p>
                        <p><strong>📞 Teléfono:</strong> <?= $emergencia['ciudadano_telefono'] ?></p>
                    </div>
                </div>

                <!-- Asignaciones Existentes -->
                <?php if (!empty($asignaciones_activas)): ?>
                <div class="current-assignments">
                    <h3>👨‍🚒 Recursos Asignados</h3>
                    <?php foreach ($asignaciones_activas as $asignacion): ?>
                    <div class="assignment-item">
                        <div class="assignment-info">
                            <h4><?= htmlspecialchars($asignacion['bombero_nombre']) ?></h4>
                            <p>
                                <span class="badge badge-<?= $asignacion['especialidad'] ?? 'general' ?>">
                                    <?= ucfirst($asignacion['especialidad'] ?? 'General') ?>
                                </span>
                                • 🆔 <?= $asignacion['numero_placa'] ?>
                                • 🏢 <?= $asignacion['unidad_nombre'] ?? 'Sin unidad' ?>
                            </p>
                            <p>
                                <strong>Estado:</strong> 
                                <span class="badge badge-<?= $asignacion['estado'] ?>">
                                    <?= strtoupper(str_replace('_', ' ', $asignacion['estado'])) ?>
                                </span>
                                • <strong>Asignado:</strong> <?= date('H:i', strtotime($asignacion['fecha_asignacion'])) ?>
                            </p>
                        </div>
                        <div class="assignment-actions">
                            <button class="btn btn-sm btn-warning" 
                                    onclick="reasignarBombero(<?= $asignacion['bombero_id'] ?>, <?= $emergencia['id'] ?>)">
                                🔄 Reasignar
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <!-- Lista de Bomberos Disponibles -->
                <div class="available-resources">
                    <h3>💪 Bomberos Disponibles</h3>
                    
                    <?php if (empty($bomberos_disponibles)): ?>
                        <div class="alert alert-warning">
                            ⚠️ No hay bomberos disponibles en este momento
                        </div>
                    <?php else: ?>
                        <form method="POST" id="asignacionForm">
                            <input type="hidden" name="action" value="asignar_bombero">
                            <input type="hidden" name="emergencia_id" value="<?= $emergencia['id'] ?>">
                            
                            <div class="bomberos-grid">
                                <?php foreach ($bomberos_disponibles as $bombero): ?>
                                <div class="bombero-card <?= $bombero['disponible'] ? 'disponible' : 'ocupado' ?>">
                                    <div class="bombero-info">
                                        <h4>
                                            <?= htmlspecialchars($bombero['nombre']) ?>
                                            <?php if (!$bombero['disponible']): ?>
                                                <span class="badge badge-warning">OCUPADO</span>
                                            <?php endif; ?>
                                        </h4>
                                        <div class="bombero-details">
                                            <p>
                                                <span class="badge badge-<?= $bombero['especialidad'] ?? 'general' ?>">
                                                    <?= ucfirst($bombero['especialidad'] ?? 'General') ?>
                                                </span>
                                                • 🆔 <?= $bombero['numero_placa'] ?>
                                            </p>
                                            <p>🏢 <?= $bombero['unidad_nombre'] ?? 'Sin unidad' ?></p>
                                            <p>📞 <?= $bombero['telefono'] ?></p>
                                            <p>
                                                <span class="badge badge-<?= $bombero['asignaciones_activas'] > 0 ? 'warning' : 'success' ?>">
                                                    📋 <?= $bombero['asignaciones_activas'] ?> asignaciones activas
                                                </span>
                                            </p>
                                            <?php if ($bombero['latitud_actual'] && $bombero['longitud_actual']): ?>
                                                <p><small>📍 Con ubicación GPS</small></p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="bombero-actions">
                                        <?php if ($bombero['disponible']): ?>
                                            <button type="submit" name="bombero_id" value="<?= $bombero['id'] ?>" 
                                                    class="btn btn-sm btn-success">
                                                ✅ Asignar
                                            </button>
                                        <?php else: ?>
                                            <button type="button" class="btn btn-sm btn-outline" disabled>
                                                ⏳ No Disponible
                                            </button>
                                        <?php endif; ?>
                                        <button type="button" class="btn btn-sm btn-info"
                                                onclick="verUbicacionBombero(<?= $bombero['id'] ?>)">
                                            📍 Ubicación
                                        </button>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>

                <!-- Asignación Múltiple -->
                <div class="multiple-assignment">
                    <h3>👥 Asignación Múltiple</h3>
                    <form method="POST">
                        <input type="hidden" name="action" value="asignacion_multiple">
                        <input type="hidden" name="emergencia_id" value="<?= $emergencia['id'] ?>">
                        
                        <div class="form-group">
                            <label>Seleccionar múltiples bomberos:</label>
                            <div class="bomberos-checkbox">
                                <?php foreach ($bomberos_disponibles as $bombero): ?>
                                    <?php if ($bombero['disponible']): ?>
                                    <label class="checkbox-item">
                                        <input type="checkbox" name="bomberos[]" value="<?= $bombero['id'] ?>">
                                        <?= htmlspecialchars($bombero['nombre']) ?> 
                                        (<?= $bombero['numero_placa'] ?> - <?= $bombero['especialidad'] ?>)
                                    </label>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            👥 Asignar Bomberos Seleccionados
                        </button>
                    </form>
                </div>
            </div>
            <?php else: ?>
            <div class="card">
                <h2>ℹ️ Información</h2>
                <p>Selecciona una emergencia de la lista para gestionar sus asignaciones.</p>
                <p>Puedes usar el mapa interactivo para ver la ubicación de las emergencias y bomberos disponibles.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function reasignarBombero(bomberoId, emergenciaId) {
            if (confirm('¿Estás seguro de que deseas reasignar este bombero?')) {
                // Aquí se implementaría la reasignación
                alert('Funcionalidad de reasignación en desarrollo');
            }
        }

        function verUbicacionBombero(bomberoId) {
            window.open(`mapa.php?bombero_id=${bomberoId}`, '_blank');
        }

        // Auto-redirigir si no hay emergencia seleccionada pero hay pendientes
        <?php if (!$emergencia && !empty($emergencias_pendientes)): ?>
        setTimeout(() => {
            window.location.href = `asignaciones.php?emergencia_id=<?= $emergencias_pendientes[0]['id'] ?>`;
        }, 5000);
        <?php endif; ?>
    </script>
</body>
</html>