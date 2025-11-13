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

// Procesar acciones
if ($_POST['action'] ?? '') {
    $action = $_POST['action'];
    
    switch ($action) {
        case 'aceptar_asignacion':
            aceptarAsignacion($db, $_POST['asignacion_id'], $bombero_id);
            break;
        case 'actualizar_estado':
            actualizarEstadoAsignacion($db, $_POST['asignacion_id'], $_POST['estado'], $_POST['notas'] ?? '');
            break;
        case 'marcar_en_camino':
            marcarEnCamino($db, $_POST['asignacion_id'], $bombero_id);
            break;
        case 'marcar_en_sitio':
            marcarEnSitio($db, $_POST['asignacion_id'], $bombero_id);
            break;
    }
}

// Obtener asignaciones
$asignaciones_activas = obtenerAsignacionesActivas($db, $bombero_id);
$historial = obtenerHistorialAsignaciones($db, $bombero_id);

function obtenerBomberoId($db, $usuario_id) {
    $sql = "SELECT id FROM bomberos WHERE usuario_id = ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    return $result['id'];
}

function obtenerAsignacionesActivas($db, $bombero_id) {
    $sql = "SELECT a.*, e.tipo, e.descripcion, e.direccion, e.latitud, e.longitud, 
                   e.gravedad, e.fecha_reporte, u.nombre as ciudadano_nombre, 
                   u.telefono as ciudadano_telefono,
                   TIMESTAMPDIFF(MINUTE, a.fecha_asignacion, NOW()) as minutos_desde_asignacion
            FROM asignaciones a
            JOIN emergencias e ON a.emergencia_id = e.id
            JOIN usuarios u ON e.ciudadano_id = u.id
            WHERE a.bombero_id = ? AND a.estado != 'completada'
            ORDER BY 
                CASE e.gravedad 
                    WHEN 'critica' THEN 1
                    WHEN 'alta' THEN 2
                    WHEN 'media' THEN 3
                    ELSE 4
                END,
                a.fecha_asignacion DESC";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("i", $bombero_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function obtenerHistorialAsignaciones($db, $bombero_id) {
    $sql = "SELECT a.*, e.tipo, e.descripcion, e.direccion, e.gravedad,
                   e.fecha_reporte, e.fecha_cierre,
                   TIMESTAMPDIFF(MINUTE, a.fecha_asignacion, a.fecha_cierre) as duracion_minutos
            FROM asignaciones a
            JOIN emergencias e ON a.emergencia_id = e.id
            WHERE a.bombero_id = ? AND a.estado = 'completada'
            ORDER BY a.fecha_cierre DESC
            LIMIT 20";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("i", $bombero_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function aceptarAsignacion($db, $asignacion_id, $bombero_id) {
    $sql = "UPDATE asignaciones SET estado = 'aceptada', fecha_aceptacion = NOW() 
            WHERE id = ? AND bombero_id = ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("ii", $asignacion_id, $bombero_id);
    $stmt->execute();
    
    // Registrar log
    registrarLog('asignacion_aceptada', 'bombero', "Asignación $asignacion_id aceptada");
    
    $_SESSION['success'] = "Asignación aceptada exitosamente";
}

function marcarEnCamino($db, $asignacion_id, $bombero_id) {
    $sql = "UPDATE asignaciones SET estado = 'en_camino' 
            WHERE id = ? AND bombero_id = ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("ii", $asignacion_id, $bombero_id);
    $stmt->execute();
    
    $_SESSION['success'] = "Estado actualizado: En camino al incidente";
}

function marcarEnSitio($db, $asignacion_id, $bombero_id) {
    $sql = "UPDATE asignaciones SET estado = 'en_sitio', fecha_llegada = NOW() 
            WHERE id = ? AND bombero_id = ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("ii", $asignacion_id, $bombero_id);
    $stmt->execute();
    
    $_SESSION['success'] = "Estado actualizado: En sitio del incidente";
}

function actualizarEstadoAsignacion($db, $asignacion_id, $estado, $notas) {
    $sql = "UPDATE asignaciones SET estado = ?, notas_bombero = CONCAT(IFNULL(notas_bombero, ''), ?, NOW()) 
            WHERE id = ?";
    $stmt = $db->prepare($sql);
    $notas_con_fecha = "\n[" . date('Y-m-d H:i:s') . "] " . $notas;
    $stmt->bind_param("ssi", $estado, $notas_con_fecha, $asignacion_id);
    $stmt->execute();
    
    $_SESSION['success'] = "Estado actualizado exitosamente";
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Tareas - Bombero</title>
    <link rel="stylesheet" href="/sistema_bomberos_choco/css/styles.css">
</head>
<body>
    <?php include '../../includes/header.php'; ?>
    
    <div class="container">
        <h1>Mis Asignaciones Activas</h1>
        
        <!-- Asignaciones Pendientes -->
        <div class="card">
            <h2>Emergencias Asignadas</h2>
            
            <?php if (empty($asignaciones_activas)): ?>
                <div class="alert alert-info">
                    No tienes asignaciones activas en este momento.
                </div>
            <?php else: ?>
                <div class="asignaciones-grid">
                    <?php foreach ($asignaciones_activas as $asignacion): ?>
                    <div class="asignacion-card <?= $asignacion['gravedad'] ?>">
                        <div class="asignacion-header">
                            <h3>
                                <span class="badge badge-<?= $asignacion['tipo'] ?>">
                                    <?= ucfirst($asignacion['tipo']) ?>
                                </span>
                                <span class="badge badge-<?= $asignacion['gravedad'] ?> gravedad">
                                    <?= ucfirst($asignacion['gravedad']) ?>
                                </span>
                            </h3>
                            <div class="estado-actual">
                                Estado: <strong><?= strtoupper(str_replace('_', ' ', $asignacion['estado'])) ?></strong>
                            </div>
                        </div>
                        
                        <div class="asignacion-info">
                            <p><strong>Descripción:</strong> <?= htmlspecialchars($asignacion['descripcion']) ?></p>
                            <p><strong>Dirección:</strong> <?= htmlspecialchars($asignacion['direccion']) ?></p>
                            <p><strong>Reportante:</strong> <?= htmlspecialchars($asignacion['ciudadano_nombre']) ?></p>
                            <p><strong>Teléfono:</strong> <?= $asignacion['ciudadano_telefono'] ?></p>
                            <p><strong>Asignada hace:</strong> <?= $asignacion['minutos_desde_asignacion'] ?> minutos</p>
                        </div>
                        
                        <div class="asignacion-actions">
                            <?php if ($asignacion['estado'] === 'asignada'): ?>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="aceptar_asignacion">
                                    <input type="hidden" name="asignacion_id" value="<?= $asignacion['id'] ?>">
                                    <button type="submit" class="btn btn-success">Aceptar Asignación</button>
                                </form>
                            <?php elseif ($asignacion['estado'] === 'aceptada'): ?>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="marcar_en_camino">
                                    <input type="hidden" name="asignacion_id" value="<?= $asignacion['id'] ?>">
                                    <button type="submit" class="btn btn-warning">Marcar en Camino</button>
                                </form>
                            <?php elseif ($asignacion['estado'] === 'en_camino'): ?>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="marcar_en_sitio">
                                    <input type="hidden" name="asignacion_id" value="<?= $asignacion['id'] ?>">
                                    <button type="submit" class="btn btn-primary">Marcar en Sitio</button>
                                </form>
                                
                                <a href="navegacion.php?emergencia_id=<?= $asignacion['emergencia_id'] ?>" 
                                   class="btn btn-info">Navegar al Sitio</a>
                            <?php elseif ($asignacion['estado'] === 'en_sitio'): ?>
                                <button class="btn btn-danger" 
                                        onclick="mostrarModalCierre(<?= $asignacion['id'] ?>)">
                                    Cerrar Emergencia
                                </button>
                            <?php endif; ?>
                            
                            <button class="btn btn-outline" 
                                    onclick="agregarNota(<?= $asignacion['id'] ?>)">
                                Agregar Nota
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Historial de Asignaciones -->
        <div class="card">
            <h2>Historial de Intervenciones</h2>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Tipo</th>
                            <th>Descripción</th>
                            <th>Fecha Asignación</th>
                            <th>Fecha Cierre</th>
                            <th>Duración</th>
                            <th>Gravedad</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($historial as $asignacion): ?>
                        <tr>
                            <td><?= $asignacion['emergencia_id'] ?></td>
                            <td>
                                <span class="badge badge-<?= $asignacion['tipo'] ?>">
                                    <?= ucfirst($asignacion['tipo']) ?>
                                </span>
                            </td>
                            <td><?= substr($asignacion['descripcion'], 0, 50) ?>...</td>
                            <td><?= date('d/m/Y H:i', strtotime($asignacion['fecha_asignacion'])) ?></td>
                            <td><?= date('d/m/Y H:i', strtotime($asignacion['fecha_cierre'])) ?></td>
                            <td><?= $asignacion['duracion_minutos'] ?> min</td>
                            <td>
                                <span class="badge badge-<?= $asignacion['gravedad'] ?>">
                                    <?= ucfirst($asignacion['gravedad']) ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal para cerrar emergencia -->
    <div id="modalCierre" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h3>Cerrar Emergencia</h3>
            <form method="POST" id="formCierre">
                <input type="hidden" name="action" value="actualizar_estado">
                <input type="hidden" name="estado" value="completada">
                <input type="hidden" name="asignacion_id" id="cierreAsignacionId">
                
                <div class="form-group">
                    <label for="notasCierre">Notas de Intervención:</label>
                    <textarea name="notas" id="notasCierre" rows="5" 
                              placeholder="Describa las acciones realizadas, materiales utilizados, personas atendidas, etc."></textarea>
                </div>
                
                <button type="submit" class="btn btn-danger">Confirmar Cierre</button>
            </form>
        </div>
    </div>

    <!-- Modal para agregar nota -->
    <div id="modalNota" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h3>Agregar Nota de Intervención</h3>
            <form method="POST" id="formNota">
                <input type="hidden" name="action" value="actualizar_estado">
                <input type="hidden" name="asignacion_id" id="notaAsignacionId">
                
                <div class="form-group">
                    <label for="notasIntervencion">Notas:</label>
                    <textarea name="notas" id="notasIntervencion" rows="4" 
                              placeholder="Agregue observaciones, acciones realizadas, etc."></textarea>
                </div>
                
                <button type="submit" class="btn btn-primary">Guardar Nota</button>
            </form>
        </div>
    </div>

    <script>
        // Modales
        const modalCierre = document.getElementById('modalCierre');
        const modalNota = document.getElementById('modalNota');
        const closes = document.getElementsByClassName('close');

        function mostrarModalCierre(asignacionId) {
            document.getElementById('cierreAsignacionId').value = asignacionId;
            modalCierre.style.display = 'block';
        }

        function agregarNota(asignacionId) {
            document.getElementById('notaAsignacionId').value = asignacionId;
            modalNota.style.display = 'block';
        }

        // Cerrar modales
        for (let i = 0; i < closes.length; i++) {
            closes[i].onclick = function() {
                modalCierre.style.display = 'none';
                modalNota.style.display = 'none';
            }
        }

        window.onclick = function(event) {
            if (event.target == modalCierre) {
                modalCierre.style.display = 'none';
            }
            if (event.target == modalNota) {
                modalNota.style.display = 'none';
            }
        }

        // Actualizar automáticamente cada 30 segundos
        setInterval(function() {
            window.location.reload();
        }, 30000);
    </script>
</body>
</html>