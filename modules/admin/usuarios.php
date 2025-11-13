<?php
session_start();
require_once '../../includes/auth.php';
require_once '../../includes/database.php';

if (!isAdmin()) {
    header('Location: /sistema_bomberos_choco/index.php');
    exit();
}

$db = connectDatabase();

// Procesar acciones
if ($_POST['action'] ?? '') {
    $action = $_POST['action'];
    
    switch ($action) {
        case 'crear_usuario':
            crearUsuario($db, $_POST);
            break;
        case 'editar_usuario':
            editarUsuario($db, $_POST);
            break;
        case 'cambiar_estado':
            cambiarEstadoUsuario($db, $_POST['id'], $_POST['activo']);
            break;
    }
}

// Obtener usuarios
$usuarios = obtenerUsuarios($db);
$unidades = obtenerUnidades($db);

function obtenerUsuarios($db) {
    $sql = "SELECT u.*, b.numero_placa, b.especialidad, un.nombre as unidad_nombre 
            FROM usuarios u 
            LEFT JOIN bomberos b ON u.id = b.usuario_id 
            LEFT JOIN unidades un ON b.unidad_id = un.id 
            ORDER BY u.fecha_registro DESC";
    return $db->query($sql)->fetch_all(MYSQLI_ASSOC);
}

function obtenerUnidades($db) {
    $sql = "SELECT * FROM unidades WHERE activa = true";
    return $db->query($sql)->fetch_all(MYSQLI_ASSOC);
}

function crearUsuario($db, $data) {
    $tipo = $data['tipo'];
    $email = $data['email'];
    $password = password_hash($data['password'], PASSWORD_DEFAULT);
    $nombre = $data['nombre'];
    $telefono = $data['telefono'];
    
    $sql = "INSERT INTO usuarios (tipo, email, password, nombre, telefono) 
            VALUES (?, ?, ?, ?, ?)";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("sssss", $tipo, $email, $password, $nombre, $telefono);
    
    if ($stmt->execute()) {
        $usuario_id = $db->insert_id;
        
        // Si es bombero, crear registro adicional
        if ($tipo === 'bombero') {
            $sql_bombero = "INSERT INTO bomberos (usuario_id, numero_placa, especialidad, unidad_id, fecha_ingreso) 
                           VALUES (?, ?, ?, ?, ?)";
            $stmt_bombero = $db->prepare($sql_bombero);
            $stmt_bombero->bind_param("issss", $usuario_id, $data['numero_placa'], $data['especialidad'], $data['unidad_id'], $data['fecha_ingreso']);
            $stmt_bombero->execute();
        }
        
        $_SESSION['success'] = "Usuario creado exitosamente";
    } else {
        $_SESSION['error'] = "Error al crear usuario: " . $db->error;
    }
}

function editarUsuario($db, $data) {
    $sql = "UPDATE usuarios SET nombre = ?, telefono = ?, activo = ? WHERE id = ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("ssii", $data['nombre'], $data['telefono'], $data['activo'], $data['id']);
    $stmt->execute();
    
    $_SESSION['success'] = "Usuario actualizado exitosamente";
}

function cambiarEstadoUsuario($db, $id, $activo) {
    $sql = "UPDATE usuarios SET activo = ? WHERE id = ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("ii", $activo, $id);
    $stmt->execute();
    
    $_SESSION['success'] = "Estado de usuario actualizado";
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios - Sistema Bomberos Chocó</title>
    <link rel="stylesheet" href="/sistema_bomberos_choco/css/styles.css">
</head>
<body>
    <?php include '../../includes/header.php'; ?>
    
    <div class="container">
        <h1>Gestión de Usuarios</h1>
        
        <!-- Formulario de creación de usuarios -->
        <div class="card">
            <h2>Crear Nuevo Usuario</h2>
            <form method="POST" class="form-grid">
                <input type="hidden" name="action" value="crear_usuario">
                
                <div class="form-group">
                    <label>Tipo de Usuario:</label>
                    <select name="tipo" id="tipoUsuario" required>
                        <option value="">Seleccionar...</option>
                        <option value="ciudadano">Ciudadano</option>
                        <option value="operador">Operador</option>
                        <option value="bombero">Bombero</option>
                        <option value="admin">Administrador</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Email:</label>
                    <input type="email" name="email" required>
                </div>
                
                <div class="form-group">
                    <label>Contraseña:</label>
                    <input type="password" name="password" required>
                </div>
                
                <div class="form-group">
                    <label>Nombre Completo:</label>
                    <input type="text" name="nombre" required>
                </div>
                
                <div class="form-group">
                    <label>Teléfono:</label>
                    <input type="tel" name="telefono">
                </div>
                
                <!-- Campos específicos para bomberos -->
                <div id="camposBombero" style="display: none;">
                    <div class="form-group">
                        <label>Número de Placa:</label>
                        <input type="text" name="numero_placa">
                    </div>
                    
                    <div class="form-group">
                        <label>Especialidad:</label>
                        <select name="especialidad">
                            <option value="general">General</option>
                            <option value="incendios">Incendios</option>
                            <option value="rescate">Rescate</option>
                            <option value="medica">Médica</option>
                            <option value="materiales_peligrosos">Materiales Peligrosos</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Unidad:</label>
                        <select name="unidad_id">
                            <?php foreach ($unidades as $unidad): ?>
                                <option value="<?= $unidad['id'] ?>"><?= $unidad['nombre'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Fecha de Ingreso:</label>
                        <input type="date" name="fecha_ingreso">
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary">Crear Usuario</button>
            </form>
        </div>

        <!-- Lista de usuarios -->
        <div class="card">
            <h2>Lista de Usuarios</h2>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Email</th>
                            <th>Tipo</th>
                            <th>Teléfono</th>
                            <th>Estado</th>
                            <th>Registro</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($usuarios as $usuario): ?>
                        <tr>
                            <td><?= $usuario['id'] ?></td>
                            <td><?= htmlspecialchars($usuario['nombre']) ?></td>
                            <td><?= htmlspecialchars($usuario['email']) ?></td>
                            <td>
                                <span class="badge badge-<?= $usuario['tipo'] ?>">
                                    <?= ucfirst($usuario['tipo']) ?>
                                </span>
                                <?php if ($usuario['numero_placa']): ?>
                                    <br><small>Placa: <?= $usuario['numero_placa'] ?></small>
                                <?php endif; ?>
                            </td>
                            <td><?= $usuario['telefono'] ?></td>
                            <td>
                                <span class="badge badge-<?= $usuario['activo'] ? 'success' : 'danger' ?>">
                                    <?= $usuario['activo'] ? 'Activo' : 'Inactivo' ?>
                                </span>
                            </td>
                            <td><?= date('d/m/Y H:i', strtotime($usuario['fecha_registro'])) ?></td>
                            <td>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="cambiar_estado">
                                    <input type="hidden" name="id" value="<?= $usuario['id'] ?>">
                                    <input type="hidden" name="activo" value="<?= $usuario['activo'] ? '0' : '1' ?>">
                                    <button type="submit" class="btn btn-sm btn-<?= $usuario['activo'] ? 'warning' : 'success' ?>">
                                        <?= $usuario['activo'] ? 'Desactivar' : 'Activar' ?>
                                    </button>
                                </form>
                                <button class="btn btn-sm btn-info" onclick="editarUsuario(<?= $usuario['id'] ?>)">Editar</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        // Mostrar/ocultar campos de bombero
        document.getElementById('tipoUsuario').addEventListener('change', function() {
            const camposBombero = document.getElementById('camposBombero');
            camposBombero.style.display = this.value === 'bombero' ? 'block' : 'none';
        });

        function editarUsuario(id) {
            // Implementar modal de edición
            alert('Editar usuario ID: ' + id);
        }
    </script>
</body>
</html>