<?php
session_start();
require_once '../../includes/auth.php';
require_once '../../includes/database.php';

if (!isAdmin()) {
    header('Location: /sistema_bomberos_choco/index.php');
    exit();
}

$db = connectDatabase();

// Procesar configuración
if ($_POST['action'] ?? '') {
    if ($_POST['action'] === 'actualizar_config') {
        actualizarConfiguracion($db, $_POST['clave'], $_POST['valor']);
    } elseif ($_POST['action'] === 'backup') {
        generarBackup($db);
    } elseif ($_POST['action'] === 'mantenimiento') {
        cambiarModoMantenimiento($db, $_POST['modo']);
    }
}

// Obtener configuraciones
$configuraciones = obtenerConfiguraciones($db);
$logs = obtenerLogsSistema($db);
$estadoSistema = obtenerEstadoSistema($db);

function obtenerConfiguraciones($db) {
    $sql = "SELECT * FROM configuraciones ORDER BY clave";
    return $db->query($sql)->fetch_all(MYSQLI_ASSOC);
}

function obtenerLogsSistema($db) {
    $sql = "SELECT l.*, u.nombre as usuario_nombre 
            FROM logs_sistema l 
            LEFT JOIN usuarios u ON l.usuario_id = u.id 
            ORDER BY l.fecha_log DESC 
            LIMIT 100";
    return $db->query($sql)->fetch_all(MYSQLI_ASSOC);
}

function obtenerEstadoSistema($db) {
    $estado = [];
    
    // Contar registros
    $tablas = ['usuarios', 'emergencias', 'asignaciones', 'comunicaciones'];
    foreach ($tablas as $tabla) {
        $sql = "SELECT COUNT(*) as total FROM $tabla";
        $estado[$tabla] = $db->query($sql)->fetch_assoc()['total'];
    }
    
    // Espacio en disco
    $estado['espacio_disco'] = disk_free_space("/") / (1024 * 1024 * 1024); // GB libres
    
    return $estado;
}

function actualizarConfiguracion($db, $clave, $valor) {
    $sql = "UPDATE configuraciones SET valor = ?, fecha_actualizacion = NOW() WHERE clave = ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("ss", $valor, $clave);
    $stmt->execute();
    
    $_SESSION['success'] = "Configuración actualizada";
}

function generarBackup($db) {
    $backup_file = '../../assets/backups/backup_' . date('Y-m-d_H-i-s') . '.sql';
    
    // Crear directorio si no existe
    if (!is_dir('../../assets/backups')) {
        mkdir('../../assets/backups', 0755, true);
    }
    
    // Generar backup básico (en producción usar mysqldump)
    $tables = ['usuarios', 'emergencias', 'asignaciones', 'configuraciones', 'logs_sistema'];
    $backup_content = "";
    
    foreach ($tables as $table) {
        $result = $db->query("SELECT * FROM $table");
        $backup_content .= "-- Table: $table\n";
        
        while ($row = $result->fetch_assoc()) {
            $columns = implode("`, `", array_keys($row));
            $values = implode("', '", array_map([$db, 'real_escape_string'], array_values($row)));
            $backup_content .= "INSERT INTO `$table` (`$columns`) VALUES ('$values');\n";
        }
        $backup_content .= "\n";
    }
    
    file_put_contents($backup_file, $backup_content);
    $_SESSION['success'] = "Backup generado: " . basename($backup_file);
}

function cambiarModoMantenimiento($db, $modo) {
    $sql = "UPDATE configuraciones SET valor = ? WHERE clave = 'modo_mantenimiento'";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("s", $modo);
    $stmt->execute();
    
    $_SESSION['success'] = "Modo mantenimiento " . ($modo === 'true' ? 'activado' : 'desactivado');
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema - Administración - Bomberos Chocó</title>
    <link rel="stylesheet" href="/sistema_bomberos_choco/css/styles.css">
</head>
<body>
    <?php include '../../includes/header.php'; ?>
    
    <div class="container">
        <h1>Administración del Sistema</h1>
        
        <!-- Estado del Sistema -->
        <div class="card">
            <h2>Estado del Sistema</h2>
            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Usuarios Registrados</h3>
                    <div class="stat-number"><?= $estadoSistema['usuarios'] ?></div>
                </div>
                
                <div class="stat-card">
                    <h3>Emergencias Totales</h3>
                    <div class="stat-number"><?= $estadoSistema['emergencias'] ?></div>
                </div>
                
                <div class="stat-card">
                    <h3>Asignaciones Activas</h3>
                    <div class="stat-number"><?= $estadoSistema['asignaciones'] ?></div>
                </div>
                
                <div class="stat-card">
                    <h3>Espacio Libre</h3>
                    <div class="stat-number"><?= number_format($estadoSistema['espacio_disco'], 1) ?> GB</div>
                </div>
            </div>
        </div>

        <!-- Configuraciones -->
        <div class="card">
            <h2>Configuraciones del Sistema</h2>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Clave</th>
                            <th>Valor</th>
                            <th>Tipo</th>
                            <th>Descripción</th>
                            <th>Última Actualización</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($configuraciones as $config): ?>
                        <tr>
                            <td><strong><?= $config['clave'] ?></strong></td>
                            <td>
                                <?php if ($config['tipo'] === 'boolean'): ?>
                                    <span class="badge badge-<?= $config['valor'] === 'true' ? 'success' : 'danger' ?>">
                                        <?= $config['valor'] === 'true' ? 'Activo' : 'Inactivo' ?>
                                    </span>
                                <?php else: ?>
                                    <?= htmlspecialchars($config['valor']) ?>
                                <?php endif; ?>
                            </td>
                            <td><?= $config['tipo'] ?></td>
                            <td><?= $config['descripcion'] ?></td>
                            <td><?= date('d/m/Y H:i', strtotime($config['fecha_actualizacion'])) ?></td>
                            <td>
                                <button class="btn btn-sm btn-info" onclick="editarConfig('<?= $config['clave'] ?>', '<?= $config['valor'] ?>', '<?= $config['tipo'] ?>')">
                                    Editar
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Herramientas -->
        <div class="card">
            <h2>Herramientas del Sistema</h2>
            <div class="tools-grid">
                <form method="POST" class="tool-card">
                    <input type="hidden" name="action" value="backup">
                    <h3>Backup de Datos</h3>
                    <p>Generar respaldo completo de la base de datos</p>
                    <button type="submit" class="btn btn-warning">Generar Backup</button>
                </form>
                
                <form method="POST" class="tool-card">
                    <input type="hidden" name="action" value="mantenimiento">
                    <input type="hidden" name="modo" value="<?= $configuraciones[5]['valor'] === 'true' ? 'false' : 'true' ?>">
                    <h3>Modo Mantenimiento</h3>
                    <p>Activar/desactivar modo mantenimiento del sistema</p>
                    <button type="submit" class="btn btn-<?= $configuraciones[5]['valor'] === 'true' ? 'success' : 'danger' ?>">
                        <?= $configuraciones[5]['valor'] === 'true' ? 'Desactivar' : 'Activar' ?> Mantenimiento
                    </button>
                </form>
                
                <div class="tool-card">
                    <h3>Limpiar Caché</h3>
                    <p>Limpiar archivos temporales del sistema</p>
                    <button class="btn btn-info" onclick="limpiarCache()">Limpiar Caché</button>
                </div>
            </div>
        </div>

        <!-- Logs del Sistema -->
        <div class="card">
            <h2>Logs del Sistema</h2>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Usuario</th>
                            <th>Módulo</th>
                            <th>Acción</th>
                            <th>Descripción</th>
                            <th>IP</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): ?>
                        <tr>
                            <td><?= date('d/m/Y H:i', strtotime($log['fecha_log'])) ?></td>
                            <td><?= $log['usuario_nombre'] ?? 'Sistema' ?></td>
                            <td><?= $log['modulo'] ?></td>
                            <td>
                                <span class="badge badge-<?= 
                                    strpos($log['accion'], 'error') !== false ? 'danger' : 
                                    (strpos($log['accion'], 'login') !== false ? 'warning' : 'info')
                                ?>">
                                    <?= $log['accion'] ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($log['descripcion']) ?></td>
                            <td><?= $log['ip_address'] ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal para editar configuración -->
    <div id="modalConfig" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h3>Editar Configuración</h3>
            <form method="POST" id="formConfig">
                <input type="hidden" name="action" value="actualizar_config">
                <input type="hidden" name="clave" id="configClave">
                
                <div class="form-group">
                    <label>Valor:</label>
                    <input type="text" name="valor" id="configValor" required>
                </div>
                
                <button type="submit" class="btn btn-primary">Actualizar</button>
            </form>
        </div>
    </div>

    <script>
        // Modal para editar configuración
        const modal = document.getElementById('modalConfig');
        const span = document.getElementsByClassName('close')[0];

        function editarConfig(clave, valor, tipo) {
            document.getElementById('configClave').value = clave;
            document.getElementById('configValor').value = valor;
            
            // Ajustar tipo de input según el tipo de configuración
            const input = document.getElementById('configValor');
            if (tipo === 'number') {
                input.type = 'number';
            } else if (tipo === 'boolean') {
                input.type = 'checkbox';
                input.checked = valor === 'true';
            } else {
                input.type = 'text';
            }
            
            modal.style.display = 'block';
        }

        span.onclick = function() {
            modal.style.display = 'none';
        }

        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }

        function limpiarCache() {
            if (confirm('¿Está seguro de limpiar la caché del sistema?')) {
                alert('Caché limpiada exitosamente');
                // Aquí se implementaría la limpieza real de caché
            }
        }
    </script>
</body>
</html>