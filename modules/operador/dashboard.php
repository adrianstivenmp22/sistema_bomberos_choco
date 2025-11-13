<?php
session_start();
require_once '../../includes/auth.php';
require_once '../../includes/database.php';

if (!isOperador()) {
    header('Location: /sistema_bomberos_choco/index.php');
    exit();
}

$db = connectDatabase();

// Obtener estadísticas en tiempo real
$estadisticas = obtenerEstadisticasTiempoReal($db);
$emergencias_activas = obtenerEmergenciasActivas($db);
$bomberos_disponibles = obtenerBomberosDisponibles($db);

function obtenerEstadisticasTiempoReal($db) {
    $stats = [];
    
    // Emergencias por estado
    $sql = "SELECT 
                COUNT(*) as total,
                SUM(estado = 'reportada') as pendientes,
                SUM(estado = 'en_progreso') as en_progreso,
                SUM(estado = 'resuelta') as resueltas,
                SUM(estado = 'cancelada') as canceladas
            FROM emergencias 
            WHERE DATE(fecha_reporte) = CURDATE()";
    $result = $db->query($sql);
    $stats['emergencias'] = $result->fetch_assoc();
    
    // Tiempo promedio de respuesta
    $sql_tiempo = "SELECT AVG(TIMESTAMPDIFF(MINUTE, fecha_reporte, fecha_asignacion)) as tiempo_asignacion,
                          AVG(TIMESTAMPDIFF(MINUTE, fecha_asignacion, fecha_cierre)) as tiempo_resolucion
                   FROM emergencias 
                   WHERE estado = 'resuelta' 
                   AND fecha_reporte >= CURDATE()";
    $result_tiempo = $db->query($sql_tiempo);
    $stats['tiempos'] = $result_tiempo->fetch_assoc();
    
    // Bomberos disponibles
    $sql_bomberos = "SELECT COUNT(*) as disponibles FROM bomberos WHERE disponible = 1 AND activo = 1";
    $result_bomberos = $db->query($sql_bomberos);
    $stats['bomberos'] = $result_bomberos->fetch_assoc();
    
    return $stats;
}

function obtenerEmergenciasActivas($db) {
    $sql = "SELECT e.*, u.nombre as ciudadano_nombre, u.telefono as ciudadano_telefono,
                   COUNT(a.id) as total_asignaciones,
                   TIMESTAMPDIFF(MINUTE, e.fecha_reporte, NOW()) as minutos_espera
            FROM emergencias e
            JOIN usuarios u ON e.ciudadano_id = u.id
            LEFT JOIN asignaciones a ON e.id = a.emergencia_id AND a.estado != 'completada'
            WHERE e.estado IN ('reportada', 'en_progreso')
            GROUP BY e.id
            ORDER BY 
                CASE e.gravedad 
                    WHEN 'critica' THEN 1
                    WHEN 'alta' THEN 2 
                    WHEN 'media' THEN 3
                    ELSE 4
                END,
                e.fecha_reporte ASC
            LIMIT 20";
    return $db->query($sql)->fetch_all(MYSQLI_ASSOC);
}

function obtenerBomberosDisponibles($db) {
    $sql = "SELECT b.*, u.nombre, u.telefono, un.nombre as unidad_nombre,
                   (SELECT COUNT(*) FROM asignaciones a 
                    WHERE a.bombero_id = b.id AND a.estado != 'completada') as asignaciones_activas
            FROM bomberos b
            JOIN usuarios u ON b.usuario_id = u.id
            LEFT JOIN unidades un ON b.unidad_id = un.id
            WHERE b.disponible = 1 AND b.activo = 1
            ORDER BY b.especialidad, asignaciones_activas ASC";
    return $db->query($sql)->fetch_all(MYSQLI_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Operador</title>
    <link rel="stylesheet" href="/sistema_bomberos_choco/css/styles.css">
    <style>
        .alert-badge {
            position: relative;
            animation: blink 2s infinite;
        }
        @keyframes blink {
            0%, 50% { opacity: 1; }
            51%, 100% { opacity: 0.3; }
        }
        .emergency-card {
            border-left: 5px solid;
            transition: all 0.3s ease;
        }
        .emergency-card.critica { border-left-color: #dc3545; background: #f8d7da; }
        .emergency-card.alta { border-left-color: #fd7e14; background: #fff3cd; }
        .emergency-card.media { border-left-color: #ffc107; background: #fefefe; }
        .emergency-card.baja { border-left-color: #20c997; background: #f8f9fa; }
        .emergency-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .bombero-card {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            margin: 10px 0;
            background: #f8f9fa;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .stat-number {
            font-size: 2.5em;
            font-weight: bold;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <?php include '../../includes/header.php'; ?>
    
    <div class="container">
        <h1>📊 Dashboard de Control</h1>
        <p class="text-muted">Sistema de Monitoreo en Tiempo Real - Bomberos del Chocó</p>
        
        <!-- Estadísticas Principales -->
        <div class="stats-grid">
            <div class="stat-card">
                <h3>🚨 Emergencias Hoy</h3>
                <div class="stat-number"><?= $estadisticas['emergencias']['total'] ?></div>
                <div class="stat-details">
                    <span class="badge badge-danger">Pend: <?= $estadisticas['emergencias']['pendientes'] ?></span>
                    <span class="badge badge-warning">Prog: <?= $estadisticas['emergencias']['en_progreso'] ?></span>
                </div>
            </div>
            
            <div class="stat-card">
                <h3>👨‍🚒 Bomberos Disp.</h3>
                <div class="stat-number text-success"><?= $estadisticas['bomberos']['disponibles'] ?></div>
                <small>Total activos: <?= count($bomberos_disponibles) ?></small>
            </div>
            
            <div class="stat-card">
                <h3>⏱️ Tiempo Asignación</h3>
                <div class="stat-number">
                    <?= $estadisticas['tiempos']['tiempo_asignacion'] ? round($estadisticas['tiempos']['tiempo_asignacion']) : '0' ?> min
                </div>
                <small>Promedio hoy</small>
            </div>
            
            <div class="stat-card">
                <h3>✅ Resueltas Hoy</h3>
                <div class="stat-number text-info"><?= $estadisticas['emergencias']['resueltas'] ?></div>
                <small>Eficiencia operativa</small>
            </div>
        </div>

        <div class="dashboard-grid">
            <!-- Emergencias Activas -->
            <div class="card">
                <div class="card-header">
                    <h2>🚨 Emergencias Activas</h2>
                    <span class="badge badge-danger alert-badge">
                        <?= count($emergencias_activas) ?> ACTIVAS
                    </span>
                </div>
                
                <div class="emergencies-list">
                    <?php if (empty($emergencias_activas)): ?>
                        <div class="alert alert-success">
                            ✅ No hay emergencias activas en este momento
                        </div>
                    <?php else: ?>
                        <?php foreach ($emergencias_activas as $emergencia): ?>
                        <div class="emergency-card <?= $emergencia['gravedad'] ?>">
                            <div class="emergency-header">
                                <div class="emergency-title">
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
                                <div class="emergency-status">
                                    <span class="badge badge-<?= $emergencia['estado'] ?>">
                                        <?= strtoupper(str_replace('_', ' ', $emergencia['estado'])) ?>
                                    </span>
                                </div>
                            </div>
                            
                            <div class="emergency-body">
                                <p><strong>📝:</strong> <?= htmlspecialchars(substr($emergencia['descripcion'], 0, 100)) ?>...</p>
                                <p><strong>📍:</strong> <?= htmlspecialchars($emergencia['direccion']) ?></p>
                                <p><strong>👤:</strong> <?= htmlspecialchars($emergencia['ciudadano_nombre']) ?> • 
                                   📞 <?= $emergencia['ciudadano_telefono'] ?></p>
                            </div>
                            
                            <div class="emergency-actions">
                                <a href="asignaciones.php?emergencia_id=<?= $emergencia['id'] ?>" 
                                   class="btn btn-sm btn-primary">
                                    👨‍🚒 Asignar Recursos
                                </a>
                                <a href="mapa.php?emergencia_id=<?= $emergencia['id'] ?>" 
                                   class="btn btn-sm btn-info">
                                    🗺️ Ver en Mapa
                                </a>
                                <button class="btn btn-sm btn-outline" 
                                        onclick="verDetalles(<?= $emergencia['id'] ?>)">
                                    👁️ Detalles
                                </button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Bomberos Disponibles -->
            <div class="card">
                <div class="card-header">
                    <h2>👨‍🚒 Recursos Disponibles</h2>
                    <span class="badge badge-success"><?= count($bomberos_disponibles) ?> DISPONIBLES</span>
                </div>
                
                <div class="resources-list">
                    <?php if (empty($bomberos_disponibles)): ?>
                        <div class="alert alert-warning">
                            ⚠️ No hay bomberos disponibles en este momento
                        </div>
                    <?php else: ?>
                        <?php foreach ($bomberos_disponibles as $bombero): ?>
                        <div class="bombero-card">
                            <div class="bombero-info">
                                <h4>
                                    <?= htmlspecialchars($bombero['nombre']) ?>
                                    <span class="badge badge-<?= $bombero['especialidad'] ?? 'general' ?>">
                                        <?= ucfirst($bombero['especialidad'] ?? 'General') ?>
                                    </span>
                                </h4>
                                <div class="bombero-details">
                                    <small>🆔 Placa: <?= $bombero['numero_placa'] ?></small> •
                                    <small>🏢 <?= $bombero['unidad_nombre'] ?? 'Sin unidad' ?></small> •
                                    <small>📞 <?= $bombero['telefono'] ?></small>
                                </div>
                                <div class="bombero-stats">
                                    <span class="badge badge-<?= $bombero['asignaciones_activas'] > 0 ? 'warning' : 'success' ?>">
                                        📋 <?= $bombero['asignaciones_activas'] ?> asignaciones activas
                                    </span>
                                </div>
                            </div>
                            <div class="bombero-actions">
                                <button class="btn btn-sm btn-success" 
                                        onclick="asignarEmergenciaRapida(<?= $bombero['id'] ?>)">
                                    ⚡ Asignar Rápido
                                </button>
                                <button class="btn btn-sm btn-outline" 
                                        onclick="verUbicacion(<?= $bombero['id'] ?>)">
                                    📍 Ubicación
                                </button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Alertas del Sistema -->
        <div class="card">
            <h2>⚠️ Alertas del Sistema</h2>
            <div class="alerts-container">
                <?php if ($estadisticas['emergencias']['pendientes'] > 5): ?>
                <div class="alert alert-warning">
                    ⚠️ <strong>ALTA CARGA DE TRABAJO:</strong> Hay <?= $estadisticas['emergencias']['pendientes'] ?> emergencias pendientes de asignación
                </div>
                <?php endif; ?>
                
                <?php if (count($bomberos_disponibles) < 3): ?>
                <div class="alert alert-danger">
                    🚨 <strong>RECURSOS LIMITADOS:</strong> Solo <?= count($bomberos_disponibles) ?> bomberos disponibles
                </div>
                <?php endif; ?>
                
                <?php if ($estadisticas['tiempos']['tiempo_asignacion'] > 10): ?>
                <div class="alert alert-info">
                    ℹ️ <strong>TIEMPO DE RESPUESTA:</strong> Tiempo promedio de asignación: <?= round($estadisticas['tiempos']['tiempo_asignacion']) ?> minutos
                </div>
                <?php endif; ?>
                
                <?php if ($estadisticas['emergencias']['pendientes'] == 0 && count($bomberos_disponibles) > 0): ?>
                <div class="alert alert-success">
                    ✅ <strong>SITUACIÓN ESTABLE:</strong> Todas las emergencias están siendo atendidas
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Auto-refrescar cada 30 segundos
        setInterval(() => {
            window.location.reload();
        }, 30000);

        function verDetalles(emergenciaId) {
            window.open(`detalles_emergencia.php?id=${emergenciaId}`, '_blank');
        }

        function asignarEmergenciaRapida(bomberoId) {
            const emergenciasPendientes = <?= json_encode(array_filter($emergencias_activas, function($e) { return $e['estado'] === 'reportada'; })) ?>;
            
            if (emergenciasPendientes.length === 0) {
                alert('No hay emergencias pendientes para asignar');
                return;
            }
            
            // Tomar la primera emergencia pendiente (más crítica)
            const emergencia = emergenciasPendientes[0];
            
            if (confirm(`¿Asignar bombero a emergencia #${emergencia.id} (${emergencia.tipo})?`)) {
                window.location.href = `asignaciones.php?emergencia_id=${emergencia.id}&bombero_id=${bomberoId}`;
            }
        }

        function verUbicacion(bomberoId) {
            alert(`Funcionalidad de ubicación en tiempo real para bombero ${bomberoId}`);
            // Aquí se integraría con el mapa en tiempo real
        }

        // Notificación sonora para nuevas emergencias (opcional)
        let ultimaEmergencia = <?= count($emergencias_activas) ?>;
        
        function verificarNuevasEmergencias() {
            fetch('../../includes/check_new_emergencies.php')
                .then(response => response.json())
                .then(data => {
                    if (data.total > ultimaEmergencia) {
                        // Reproducir sonido de alerta
                        playAlertSound();
                        // Mostrar notificación
                        showNotification('Nueva emergencia reportada');
                        ultimaEmergencia = data.total;
                    }
                });
        }

        function playAlertSound() {
            const audio = new Audio('../../assets/sounds/alert.mp3');
            audio.play().catch(e => console.log('Audio no disponible'));
        }

        function showNotification(message) {
            if ('Notification' in window && Notification.permission === 'granted') {
                new Notification('Sistema Bomberos Chocó', {
                    body: message,
                    icon: '../../assets/icons/icon.png'
                });
            }
        }

        // Solicitar permisos para notificaciones
        if ('Notification' in window) {
            Notification.requestPermission();
        }

        // Verificar nuevas emergencias cada 15 segundos
        setInterval(verificarNuevasEmergencias, 15000);
    </script>
</body>
</html>