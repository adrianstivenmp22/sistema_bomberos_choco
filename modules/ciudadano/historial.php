<?php
session_start();
require_once '../../includes/auth.php';
require_once '../../includes/database.php';

if (!isCiudadano()) {
    header('Location: /sistema_bomberos_choco/index.php');
    exit();
}

$db = connectDatabase();
$usuario_id = $_SESSION['user_id'];

// Obtener reportes del ciudadano
$reportes = obtenerReportesCiudadano($db, $usuario_id);

function obtenerReportesCiudadano($db, $usuario_id) {
    $sql = "SELECT e.*, 
                   COUNT(a.id) as total_asignaciones,
                   COUNT(DISTINCT em.id) as total_multimedia,
                   TIMESTAMPDIFF(MINUTE, e.fecha_reporte, COALESCE(e.fecha_cierre, NOW())) as minutos_transcurridos
            FROM emergencias e
            LEFT JOIN asignaciones a ON e.id = a.emergencia_id
            LEFT JOIN emergencia_multimedia em ON e.id = em.emergencia_id
            WHERE e.ciudadano_id = ?
            GROUP BY e.id
            ORDER BY e.fecha_reporte DESC";
    
    $stmt = $db->prepare($sql);
    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Historial - Sistema Bomberos Chocó</title>
    <link rel="stylesheet" href="/sistema_bomberos_choco/css/styles.css">
</head>
<body>
    <?php include '../../includes/header.php'; ?>
    
    <div class="container">
        <h1>Mi Historial de Reportes</h1>
        
        <?php if (empty($reportes)): ?>
            <div class="card text-center">
                <h2>📝 Aún no has reportado emergencias</h2>
                <p>Cuando reportes una emergencia, aparecerá en este historial.</p>
                <a href="reporte.php" class="btn btn-primary">Reportar Primera Emergencia</a>
            </div>
        <?php else: ?>
            <!-- Estadísticas rápidas -->
            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Total Reportes</h3>
                    <div class="stat-number"><?= count($reportes) ?></div>
                </div>
                
                <div class="stat-card">
                    <h3>Resueltos</h3>
                    <div class="stat-number text-success">
                        <?= count(array_filter($reportes, function($r) { return $r['estado'] === 'resuelta'; })) ?>
                    </div>
                </div>
                
                <div class="stat-card">
                    <h3>En Progreso</h3>
                    <div class="stat-number text-warning">
                        <?= count(array_filter($reportes, function($r) { return $r['estado'] === 'en_progreso'; })) ?>
                    </div>
                </div>
                
                <div class="stat-card">
                    <h3>Pendientes</h3>
                    <div class="stat-number text-danger">
                        <?= count(array_filter($reportes, function($r) { return $r['estado'] === 'reportada'; })) ?>
                    </div>
                </div>
            </div>

            <!-- Lista de reportes -->
            <div class="card">
                <h2>Todos mis Reportes</h2>
                <div class="reportes-list">
                    <?php foreach ($reportes as $reporte): ?>
                    <div class="reporte-item <?= $reporte['estado'] ?>">
                        <div class="reporte-header">
                            <div class="reporte-info">
                                <h3>
                                    <span class="badge badge-<?= $reporte['tipo'] ?>">
                                        <?= ucfirst($reporte['tipo']) ?>
                                    </span>
                                    <span class="badge badge-<?= $reporte['estado'] ?>">
                                        <?= ucfirst(str_replace('_', ' ', $reporte['estado'])) ?>
                                    </span>
                                </h3>
                                <div class="reporte-meta">
                                    <strong>Caso #<?= $reporte['id'] ?></strong> • 
                                    <?= date('d/m/Y H:i', strtotime($reporte['fecha_reporte'])) ?>
                                </div>
                            </div>
                            <div class="reporte-stats">
                                <small>📎 <?= $reporte['total_multimedia'] ?> archivos</small>
                                <small>👨‍🚒 <?= $reporte['total_asignaciones'] ?> asignaciones</small>
                            </div>
                        </div>
                        
                        <div class="reporte-body">
                            <p><strong>Descripción:</strong> <?= htmlspecialchars($reporte['descripcion']) ?></p>
                            <p><strong>Ubicación:</strong> <?= htmlspecialchars($reporte['direccion']) ?></p>
                            
                            <?php if ($reporte['estado'] === 'resuelta' && $reporte['fecha_cierre']): ?>
                                <div class="reporte-cierre">
                                    <strong>✅ Resuelto:</strong> 
                                    <?= date('d/m/Y H:i', strtotime($reporte['fecha_cierre'])) ?>
                                    (<?= $reporte['minutos_transcurridos'] ?> minutos)
                                </div>
                                <?php if ($reporte['notas_cierre']): ?>
                                    <div class="notas-cierre">
                                        <strong>Notas de cierre:</strong> <?= htmlspecialchars($reporte['notas_cierre']) ?>
                                    </div>
                                <?php endif; ?>
                            <?php elseif ($reporte['estado'] === 'en_progreso'): ?>
                                <div class="reporte-progreso">
                                    <strong>🟡 En progreso:</strong> 
                                    Equipos de bomberos atendiendo la emergencia
                                    (<?= $reporte['minutos_transcurridos'] ?> minutos)
                                </div>
                            <?php else: ?>
                                <div class="reporte-pendiente">
                                    <strong>🟣 Pendiente:</strong> 
                                    Esperando asignación de equipos
                                    (<?= $reporte['minutos_transcurridos'] ?> minutos)
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="reporte-actions">
                            <button class="btn btn-sm btn-outline" 
                                    onclick="verDetalles(<?= $reporte['id'] ?>)">
                                👁️ Ver Detalles
                            </button>
                            <?php if ($reporte['estado'] !== 'resuelta'): ?>
                                <button class="btn btn-sm btn-warning" 
                                        onclick="actualizarReporte(<?= $reporte['id'] ?>)">
                                    ✏️ Actualizar
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function verDetalles(reporteId) {
            window.location.href = `detalles.php?id=${reporteId}`;
        }

        function actualizarReporte(reporteId) {
            if (confirm('¿Deseas agregar información adicional a este reporte?')) {
                // Aquí se implementaría la funcionalidad de actualización
                alert('Funcionalidad de actualización en desarrollo');
            }
        }

        // Auto-refrescar cada 2 minutos para actualizar estados
        setInterval(() => {
            const itemsPendientes = document.querySelectorAll('.reporte-item:not(.resuelta)');
            if (itemsPendientes.length > 0) {
                window.location.reload();
            }
        }, 120000); // 2 minutos
    </script>
</body>
</html>