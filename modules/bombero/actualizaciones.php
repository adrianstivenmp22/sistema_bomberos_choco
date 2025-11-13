<?php
session_start();
require_once '../../includes/auth.php';
require_once '../../includes/database.php';

if (!isBombero()) {
    header('Location: /sistema_bomberos_choco/index.php');
    exit();
}

$db = connectDatabase();
$bombero_id = obtenerBomberoId($db, $_SESSION['user_id']);

// Obtener estadísticas personales
$estadisticas = obtenerEstadisticasBombero($db, $bombero_id);
$ultimas_intervenciones = obtenerUltimasIntervenciones($db, $bombero_id);

function obtenerEstadisticasBombero($db, $bombero_id) {
    $sql = "SELECT 
                COUNT(*) as total_intervenciones,
                SUM(estado = 'completada') as intervenciones_completadas,
                AVG(TIMESTAMPDIFF(MINUTE, fecha_asignacion, fecha_cierre)) as tiempo_promedio,
                COUNT(DISTINCT emergencia_id) as emergencias_atendidas,
                MIN(fecha_asignacion) as fecha_primer_intervencion
            FROM asignaciones 
            WHERE bombero_id = ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("i", $bombero_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

function obtenerUltimasIntervenciones($db, $bombero_id) {
    $sql = "SELECT a.*, e.tipo, e.descripcion, e.gravedad,
                   TIMESTAMPDIFF(MINUTE, a.fecha_asignacion, a.fecha_cierre) as duracion
            FROM asignaciones a
            JOIN emergencias e ON a.emergencia_id = e.id
            WHERE a.bombero_id = ? AND a.estado = 'completada'
            ORDER BY a.fecha_cierre DESC
            LIMIT 10";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("i", $bombero_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Desempeño - Bombero</title>
    <link rel="stylesheet" href="/sistema_bomberos_choco/css/styles.css">
</head>
<body>
    <?php include '../../includes/header.php'; ?>
    
    <div class="container">
        <h1>Mi Desempeño y Estadísticas</h1>
        
        <!-- Estadísticas Personales -->
        <div class="card">
            <h2>Estadísticas Personales</h2>
            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Total Intervenciones</h3>
                    <div class="stat-number"><?= $estadisticas['total_intervenciones'] ?></div>
                </div>
                
                <div class="stat-card">
                    <h3>Completadas</h3>
                    <div class="stat-number text-success"><?= $estadisticas['intervenciones_completadas'] ?></div>
                </div>
                
                <div class="stat-card">
                    <h3>Tiempo Promedio</h3>
                    <div class="stat-number">
                        <?= $estadisticas['tiempo_promedio'] ? round($estadisticas['tiempo_promedio']) : '0' ?> min
                    </div>
                </div>
                
                <div class="stat-card">
                    <h3>Emergencias Atendidas</h3>
                    <div class="stat-number"><?= $estadisticas['emergencias_atendidas'] ?></div>
                </div>
            </div>
            
            <?php if ($estadisticas['fecha_primer_intervencion']): ?>
            <div class="info-text">
                <strong>Servicio desde:</strong> 
                <?= date('d/m/Y', strtotime($estadisticas['fecha_primer_intervencion'])) ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Últimas Intervenciones -->
        <div class="card">
            <h2>Últimas Intervenciones</h2>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Emergencia</th>
                            <th>Tipo</th>
                            <th>Gravedad</th>
                            <th>Fecha</th>
                            <th>Duración</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ultimas_intervenciones as $intervencion): ?>
                        <tr>
                            <td>#<?= $intervencion['emergencia_id'] ?></td>
                            <td>
                                <span class="badge badge-<?= $intervencion['tipo'] ?>">
                                    <?= ucfirst($intervencion['tipo']) ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge badge-<?= $intervencion['gravedad'] ?>">
                                    <?= ucfirst($intervencion['gravedad']) ?>
                                </span>
                            </td>
                            <td><?= date('d/m/Y H:i', strtotime($intervencion['fecha_asignacion'])) ?></td>
                            <td><?= $intervencion['duracion'] ?> min</td>
                            <td>
                                <span class="badge badge-success">COMPLETADA</span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Herramientas Rápidas -->
        <div class="card">
            <h2>Acciones Rápidas</h2>
            <div class="actions-grid">
                <a href="tareas.php" class="action-card">
                    <h3>📋 Mis Tareas</h3>
                    <p>Ver asignaciones activas</p>
                </a>
                
                <a href="navegacion.php" class="action-card">
                    <h3>🧭 Navegación</h3>
                    <p>Ir a emergencia asignada</p>
                </a>
                
                <div class="action-card" onclick="actualizarDisponibilidad()">
                    <h3>🔄 Estado</h3>
                    <p>Cambiar disponibilidad</p>
                </div>
                
                <a href="/sistema_bomberos_choco/index.php?logout=1" class="action-card">
                    <h3>🚪 Cerrar Sesión</h3>
                    <p>Salir del sistema</p>
                </a>
            </div>
        </div>
    </div>

    <script>
        function actualizarDisponibilidad() {
            if (confirm('¿Deseas cambiar tu estado de disponibilidad?')) {
                fetch('../../includes/update_availability.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Estado de disponibilidad actualizado: ' + (data.disponible ? 'Disponible' : 'No disponible'));
                        location.reload();
                    } else {
                        alert('Error al actualizar disponibilidad');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error de conexión');
                });
            }
        }
    </script>
</body>
</html>