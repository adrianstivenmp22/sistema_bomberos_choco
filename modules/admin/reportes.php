<?php
session_start();
require_once '../../includes/auth.php';
require_once '../../includes/database.php';

if (!isAdmin()) {
    header('Location: /sistema_bomberos_choco/index.php');
    exit();
}

$db = connectDatabase();

// Obtener parámetros de filtro
$fecha_inicio = $_GET['fecha_inicio'] ?? date('Y-m-01');
$fecha_fin = $_GET['fecha_fin'] ?? date('Y-m-d');
$tipo_emergencia = $_GET['tipo_emergencia'] ?? '';

// Generar reportes
$estadisticas = obtenerEstadisticas($db, $fecha_inicio, $fecha_fin, $tipo_emergencia);
$emergencias = obtenerEmergenciasReporte($db, $fecha_inicio, $fecha_fin, $tipo_emergencia);

function obtenerEstadisticas($db, $fecha_inicio, $fecha_fin, $tipo_emergencia) {
    $where = "WHERE fecha_reporte BETWEEN ? AND ?";
    $params = [$fecha_inicio, $fecha_fin];
    $types = "ss";
    
    if ($tipo_emergencia) {
        $where .= " AND tipo = ?";
        $params[] = $tipo_emergencia;
        $types .= "s";
    }
    
    // Total emergencias
    $sql = "SELECT COUNT(*) as total, 
                   SUM(estado = 'resuelta') as resueltas,
                   SUM(estado = 'en_progreso') as en_progreso,
                   SUM(estado = 'reportada') as pendientes
            FROM emergencias $where";
    $stmt = $db->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    // Tiempo promedio de respuesta
    $sql_tiempo = "SELECT AVG(TIMESTAMPDIFF(MINUTE, fecha_reporte, fecha_cierre)) as tiempo_promedio
                   FROM emergencias 
                   WHERE estado = 'resuelta' AND fecha_reporte BETWEEN ? AND ?";
    if ($tipo_emergencia) {
        $sql_tiempo .= " AND tipo = ?";
    }
    $stmt_tiempo = $db->prepare($sql_tiempo);
    $stmt_tiempo->bind_param($types, ...$params);
    $stmt_tiempo->execute();
    $tiempo = $stmt_tiempo->get_result()->fetch_assoc();
    
    // Emergencias por tipo
    $sql_tipos = "SELECT tipo, COUNT(*) as cantidad 
                  FROM emergencias 
                  WHERE fecha_reporte BETWEEN ? AND ? 
                  GROUP BY tipo";
    $stmt_tipos = $db->prepare($sql_tipos);
    $stmt_tipos->bind_param("ss", $fecha_inicio, $fecha_fin);
    $stmt_tipos->execute();
    $por_tipo = $stmt_tipos->get_result()->fetch_all(MYSQLI_ASSOC);
    
    return array_merge($result, $tiempo, ['por_tipo' => $por_tipo]);
}

function obtenerEmergenciasReporte($db, $fecha_inicio, $fecha_fin, $tipo_emergencia) {
    $where = "WHERE e.fecha_reporte BETWEEN ? AND ?";
    $params = [$fecha_inicio, $fecha_fin];
    $types = "ss";
    
    if ($tipo_emergencia) {
        $where .= " AND e.tipo = ?";
        $params[] = $tipo_emergencia;
        $types .= "s";
    }
    
    $sql = "SELECT e.*, u.nombre as ciudadano_nombre, u.telefono,
                   COUNT(a.id) as asignaciones,
                   TIMESTAMPDIFF(MINUTE, e.fecha_reporte, e.fecha_cierre) as tiempo_resolucion
            FROM emergencias e
            LEFT JOIN usuarios u ON e.ciudadano_id = u.id
            LEFT JOIN asignaciones a ON e.id = a.emergencia_id
            $where
            GROUP BY e.id
            ORDER BY e.fecha_reporte DESC";
    
    $stmt = $db->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes - Sistema Bomberos Chocó</title>
    <link rel="stylesheet" href="/sistema_bomberos_choco/css/styles.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <?php include '../../includes/header.php'; ?>
    
    <div class="container">
        <h1>Reportes y Estadísticas</h1>
        
        <!-- Filtros -->
        <div class="card">
            <h2>Filtros</h2>
            <form method="GET" class="form-grid">
                <div class="form-group">
                    <label>Fecha Inicio:</label>
                    <input type="date" name="fecha_inicio" value="<?= $fecha_inicio ?>">
                </div>
                
                <div class="form-group">
                    <label>Fecha Fin:</label>
                    <input type="date" name="fecha_fin" value="<?= $fecha_fin ?>">
                </div>
                
                <div class="form-group">
                    <label>Tipo de Emergencia:</label>
                    <select name="tipo_emergencia">
                        <option value="">Todos los tipos</option>
                        <option value="incendio" <?= $tipo_emergencia == 'incendio' ? 'selected' : '' ?>>Incendio</option>
                        <option value="accidente" <?= $tipo_emergencia == 'accidente' ? 'selected' : '' ?>>Accidente</option>
                        <option value="rescate" <?= $tipo_emergencia == 'rescate' ? 'selected' : '' ?>>Rescate</option>
                        <option value="inundacion" <?= $tipo_emergencia == 'inundacion' ? 'selected' : '' ?>>Inundación</option>
                        <option value="medica" <?= $tipo_emergencia == 'medica' ? 'selected' : '' ?>>Médica</option>
                        <option value="otro" <?= $tipo_emergencia == 'otro' ? 'selected' : '' ?>>Otro</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">Generar Reporte</button>
                    <button type="button" class="btn btn-success" onclick="exportarPDF()">Exportar PDF</button>
                </div>
            </form>
        </div>

        <!-- Estadísticas -->
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Total Emergencias</h3>
                <div class="stat-number"><?= $estadisticas['total'] ?></div>
            </div>
            
            <div class="stat-card">
                <h3>Resueltas</h3>
                <div class="stat-number text-success"><?= $estadisticas['resueltas'] ?></div>
            </div>
            
            <div class="stat-card">
                <h3>En Progreso</h3>
                <div class="stat-number text-warning"><?= $estadisticas['en_progreso'] ?></div>
            </div>
            
            <div class="stat-card">
                <h3>Tiempo Promedio</h3>
                <div class="stat-number"><?= $estadisticas['tiempo_promedio'] ? round($estadisticas['tiempo_promedio']) : '0' ?> min</div>
            </div>
        </div>

        <!-- Gráficos -->
        <div class="card">
            <h2>Distribución por Tipo de Emergencia</h2>
            <canvas id="chartTipos" width="400" height="200"></canvas>
        </div>

        <!-- Tabla de emergencias -->
        <div class="card">
            <h2>Detalle de Emergencias</h2>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Tipo</th>
                            <th>Descripción</th>
                            <th>Ciudadano</th>
                            <th>Fecha</th>
                            <th>Estado</th>
                            <th>Tiempo Respuesta</th>
                            <th>Asignaciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($emergencias as $emergencia): ?>
                        <tr>
                            <td><?= $emergencia['id'] ?></td>
                            <td>
                                <span class="badge badge-<?= $emergencia['tipo'] ?>">
                                    <?= ucfirst($emergencia['tipo']) ?>
                                </span>
                            </td>
                            <td><?= substr($emergencia['descripcion'], 0, 50) ?>...</td>
                            <td><?= htmlspecialchars($emergencia['ciudadano_nombre']) ?></td>
                            <td><?= date('d/m/Y H:i', strtotime($emergencia['fecha_reporte'])) ?></td>
                            <td>
                                <span class="badge badge-<?= $emergencia['estado'] ?>">
                                    <?= ucfirst(str_replace('_', ' ', $emergencia['estado'])) ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($emergencia['tiempo_resolucion']): ?>
                                    <?= $emergencia['tiempo_resolucion'] ?> min
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td><?= $emergencia['asignaciones'] ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        // Gráfico de tipos de emergencia
        const tiposData = {
            labels: [<?php foreach ($estadisticas['por_tipo'] as $tipo) echo "'" . ucfirst($tipo['tipo']) . "',"; ?>],
            datasets: [{
                data: [<?php foreach ($estadisticas['por_tipo'] as $tipo) echo $tipo['cantidad'] . ','; ?>],
                backgroundColor: ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF', '#FF9F40']
            }]
        };

        const ctx = document.getElementById('chartTipos').getContext('2d');
        new Chart(ctx, {
            type: 'pie',
            data: tiposData,
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        function exportarPDF() {
            alert('Funcionalidad de exportación PDF - En desarrollo');
            // Aquí se implementaría la generación de PDF
        }
    </script>
</body>
</html>