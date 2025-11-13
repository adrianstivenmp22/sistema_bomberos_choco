-- =============================================
-- Base de Datos: sistema_bomberos_choco
-- =============================================

CREATE DATABASE IF NOT EXISTS sistema_bomberos_choco;
USE sistema_bomberos_choco;

-- =============================================
-- Tablas Principales
-- =============================================

-- Tabla de usuarios del sistema
CREATE TABLE usuarios (
    id INT PRIMARY KEY AUTO_INCREMENT,
    tipo ENUM('ciudadano', 'operador', 'bombero', 'admin') NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    telefono VARCHAR(20),
    direccion TEXT,
    fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP,
    activo BOOLEAN DEFAULT true,
    ultimo_acceso DATETIME,
    INDEX idx_tipo (tipo),
    INDEX idx_email (email)
);

-- Tabla de unidades bomberiles
CREATE TABLE unidades (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(100) NOT NULL,
    codigo VARCHAR(20) UNIQUE NOT NULL,
    tipo ENUM('estacion', 'movil', 'especializada') NOT NULL,
    ubicacion_base VARCHAR(200),
    latitud_base DECIMAL(10,8),
    longitud_base DECIMAL(11,8),
    activa BOOLEAN DEFAULT true,
    capacidad INT DEFAULT 1,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Tabla de bomberos (extiende usuarios)
CREATE TABLE bomberos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    usuario_id INT NOT NULL,
    unidad_id INT,
    numero_placa VARCHAR(20) UNIQUE,
    especialidad ENUM('incendios', 'rescate', 'medica', 'materiales_peligrosos', 'general'),
    fecha_ingreso DATE,
    activo BOOLEAN DEFAULT true,
    latitud_actual DECIMAL(10,8),
    longitud_actual DECIMAL(11,8),
    disponible BOOLEAN DEFAULT true,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (unidad_id) REFERENCES unidades(id),
    INDEX idx_disponible (disponible),
    INDEX idx_unidad (unidad_id)
);

-- Tabla de emergencias reportadas
CREATE TABLE emergencias (
    id INT PRIMARY KEY AUTO_INCREMENT,
    ciudadano_id INT NOT NULL,
    tipo ENUM('incendio', 'accidente', 'rescate', 'inundacion', 'medica', 'otro') NOT NULL,
    descripcion TEXT NOT NULL,
    direccion TEXT NOT NULL,
    latitud DECIMAL(10,8) NOT NULL,
    longitud DECIMAL(11,8) NOT NULL,
    gravedad ENUM('baja', 'media', 'alta', 'critica') DEFAULT 'media',
    estado ENUM('reportada', 'en_progreso', 'resuelta', 'cancelada') DEFAULT 'reportada',
    fecha_reporte DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_asignacion DATETIME,
    fecha_cierre DATETIME,
    notas_cierre TEXT,
    FOREIGN KEY (ciudadano_id) REFERENCES usuarios(id),
    INDEX idx_estado (estado),
    INDEX idx_tipo (tipo),
    INDEX idx_fecha_reporte (fecha_reporte),
    INDEX idx_gravedad (gravedad)
);

-- Tabla de multimedia de emergencias
CREATE TABLE emergencia_multimedia (
    id INT PRIMARY KEY AUTO_INCREMENT,
    emergencia_id INT NOT NULL,
    tipo ENUM('foto', 'video', 'documento') NOT NULL,
    nombre_archivo VARCHAR(255) NOT NULL,
    ruta_archivo VARCHAR(500) NOT NULL,
    fecha_subida DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (emergencia_id) REFERENCES emergencias(id) ON DELETE CASCADE,
    INDEX idx_emergencia (emergencia_id)
);

-- Tabla de asignaciones a bomberos
CREATE TABLE asignaciones (
    id INT PRIMARY KEY AUTO_INCREMENT,
    emergencia_id INT NOT NULL,
    bombero_id INT NOT NULL,
    operador_id INT NOT NULL,
    estado ENUM('asignada', 'aceptada', 'en_camino', 'en_sitio', 'completada') DEFAULT 'asignada',
    fecha_asignacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_aceptacion DATETIME,
    fecha_llegada DATETIME,
    fecha_cierre DATETIME,
    notas_operador TEXT,
    notas_bombero TEXT,
    FOREIGN KEY (emergencia_id) REFERENCES emergencias(id),
    FOREIGN KEY (bombero_id) REFERENCES bomberos(id),
    FOREIGN KEY (operador_id) REFERENCES usuarios(id),
    INDEX idx_estado (estado),
    INDEX idx_emergencia (emergencia_id),
    INDEX idx_bombero (bombero_id)
);

-- Tabla de historial de estados de emergencias
CREATE TABLE historial_estados (
    id INT PRIMARY KEY AUTO_INCREMENT,
    emergencia_id INT NOT NULL,
    estado_anterior ENUM('reportada', 'en_progreso', 'resuelta', 'cancelada'),
    estado_nuevo ENUM('reportada', 'en_progreso', 'resuelta', 'cancelada') NOT NULL,
    usuario_id INT NOT NULL,
    fecha_cambio DATETIME DEFAULT CURRENT_TIMESTAMP,
    notas TEXT,
    FOREIGN KEY (emergencia_id) REFERENCES emergencias(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
    INDEX idx_emergencia (emergencia_id),
    INDEX idx_fecha (fecha_cambio)
);

-- Tabla de comunicaciones del sistema
CREATE TABLE comunicaciones (
    id INT PRIMARY KEY AUTO_INCREMENT,
    emergencia_id INT NOT NULL,
    usuario_id INT NOT NULL,
    tipo ENUM('notificacion', 'mensaje', 'alerta') NOT NULL,
    mensaje TEXT NOT NULL,
    leido BOOLEAN DEFAULT false,
    fecha_envio DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (emergencia_id) REFERENCES emergencias(id),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
    INDEX idx_emergencia (emergencia_id),
    INDEX idx_usuario (usuario_id),
    INDEX idx_leido (leido)
);

-- Tabla de configuración del sistema
CREATE TABLE configuraciones (
    id INT PRIMARY KEY AUTO_INCREMENT,
    clave VARCHAR(100) UNIQUE NOT NULL,
    valor TEXT,
    tipo ENUM('string', 'number', 'boolean', 'json') DEFAULT 'string',
    descripcion TEXT,
    fecha_actualizacion DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabla de logs del sistema
CREATE TABLE logs_sistema (
    id INT PRIMARY KEY AUTO_INCREMENT,
    usuario_id INT,
    accion VARCHAR(100) NOT NULL,
    modulo VARCHAR(50) NOT NULL,
    descripcion TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    fecha_log DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_usuario (usuario_id),
    INDEX idx_accion (accion),
    INDEX idx_fecha (fecha_log)
);

-- =============================================
-- Datos Iniciales
-- =============================================

-- Insertar usuario administrador por defecto
INSERT INTO usuarios (tipo, email, password, nombre, telefono, activo) VALUES
('admin', 'admin@bomberoschoco.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrador Principal', '+573001234567', true);

-- Insertar unidades bomberiles básicas
INSERT INTO unidades (nombre, codigo, tipo, ubicacion_base, capacidad) VALUES
('Estación Central Quibdó', 'EC-001', 'estacion', 'Calle 15 # 10-25, Quibdó', 20),
('Unidad Móvil Rescate', 'UM-R-001', 'movil', 'Estación Central Quibdó', 6),
('Unidad Médica Avanzada', 'UM-M-001', 'movil', 'Estación Central Quibdó', 4),
('Equipo Especial Incendios', 'EE-I-001', 'especializada', 'Estación Central Quibdó', 8);

-- Insertar operadores por defecto
INSERT INTO usuarios (tipo, email, password, nombre, telefono, activo) VALUES
('operador', 'operador1@bomberoschoco.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Operador Centro Control', '+573001234568', true),
('operador', 'operador2@bomberoschoco.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Operador Turno Noche', '+573001234569', true);

-- Insertar bomberos de ejemplo
INSERT INTO usuarios (tipo, email, password, nombre, telefono, activo) VALUES
('bombero', 'bombero1@bomberoschoco.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Carlos Rodríguez', '+573001234570', true),
('bombero', 'bombero2@bomberoschoco.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'María González', '+573001234571', true),
('bombero', 'bombero3@bomberoschoco.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'José Pérez', '+573001234572', true);

-- Asignar bomberos a unidades
INSERT INTO bomberos (usuario_id, unidad_id, numero_placa, especialidad, fecha_ingreso, disponible) VALUES
(3, 1, 'BP-001', 'incendios', '2023-01-15', true),
(4, 1, 'BP-002', 'medica', '2023-02-20', true),
(5, 2, 'BP-003', 'rescate', '2023-03-10', true);

-- Configuraciones del sistema
INSERT INTO configuraciones (clave, valor, tipo, descripcion) VALUES
('sistema_nombre', 'Sistema Integral de Emergencias - Bomberos del Chocó', 'string', 'Nombre del sistema'),
('sistema_version', '1.0.0', 'string', 'Versión del sistema'),
('tiempo_maximo_respuesta', '15', 'number', 'Tiempo máximo en minutos para respuesta'),
('radio_asignacion_km', '10', 'number', 'Radio en km para asignación automática'),
('notificaciones_activas', 'true', 'boolean', 'Activar/desactivar notificaciones'),
('modo_mantenimiento', 'false', 'boolean', 'Modo mantenimiento del sistema'),
('coordenadas_centrales', '{"lat": 5.6946, "lng": -76.6610}', 'json', 'Coordenadas centrales del departamento'),
('tipos_emergencia', '["incendio", "accidente", "rescate", "inundacion", "medica", "otro"]', 'json', 'Tipos de emergencia permitidos');

-- =============================================
-- Vistas Útiles
-- =============================================

-- Vista para dashboard del operador
CREATE VIEW vista_emergencias_activas AS
SELECT 
    e.id,
    e.tipo,
    e.descripcion,
    e.direccion,
    e.latitud,
    e.longitud,
    e.gravedad,
    e.estado,
    e.fecha_reporte,
    u.nombre as ciudadano_nombre,
    u.telefono as ciudadano_telefono,
    COUNT(a.id) as asignaciones_activas
FROM emergencias e
LEFT JOIN usuarios u ON e.ciudadano_id = u.id
LEFT JOIN asignaciones a ON e.id = a.emergencia_id AND a.estado != 'completada'
WHERE e.estado IN ('reportada', 'en_progreso')
GROUP BY e.id;

-- Vista para bomberos disponibles
CREATE VIEW vista_bomberos_disponibles AS
SELECT 
    b.id as bombero_id,
    u.nombre,
    b.numero_placa,
    b.especialidad,
    u.telefono,
    un.nombre as unidad_nombre,
    b.latitud_actual,
    b.longitud_actual
FROM bomberos b
JOIN usuarios u ON b.usuario_id = u.id
LEFT JOIN unidades un ON b.unidad_id = un.id
WHERE b.disponible = true AND b.activo = true AND u.activo = true;

-- =============================================
-- Procedimientos Almacenados
-- =============================================

DELIMITER //

-- Procedimiento para asignar emergencia automáticamente
CREATE PROCEDURE asignar_emergencia_automatica(IN emergencia_id INT)
BEGIN
    DECLARE bombero_id INT;
    DECLARE operador_id INT;
    
    -- Encontrar bombero disponible más cercano
    SELECT b.id INTO bombero_id
    FROM bomberos b
    JOIN emergencias e ON e.id = emergencia_id
    WHERE b.disponible = true 
    AND b.activo = true
    ORDER BY (
        6371 * acos(
            cos(radians(e.latitud)) * cos(radians(b.latitud_actual)) *
            cos(radians(b.longitud_actual) - radians(e.longitud)) +
            sin(radians(e.latitud)) * sin(radians(b.latitud_actual))
        )
    ) ASC
    LIMIT 1;
    
    -- Encontrar operador activo
    SELECT id INTO operador_id 
    FROM usuarios 
    WHERE tipo = 'operador' AND activo = true 
    LIMIT 1;
    
    -- Crear asignación
    IF bombero_id IS NOT NULL AND operador_id IS NOT NULL THEN
        INSERT INTO asignaciones (emergencia_id, bombero_id, operador_id, estado)
        VALUES (emergencia_id, bombero_id, operador_id, 'asignada');
        
        -- Actualizar estado de la emergencia
        UPDATE emergencias 
        SET estado = 'en_progreso', fecha_asignacion = NOW() 
        WHERE id = emergencia_id;
        
        -- Marcar bombero como no disponible
        UPDATE bomberos SET disponible = false WHERE id = bombero_id;
    END IF;
END//

-- Procedimiento para cerrar emergencia
CREATE PROCEDURE cerrar_emergencia(IN emergencia_id INT, IN usuario_id INT, IN notas TEXT)
BEGIN
    -- Actualizar estado de la emergencia
    UPDATE emergencias 
    SET estado = 'resuelta', fecha_cierre = NOW(), notas_cierre = notas
    WHERE id = emergencia_id;
    
    -- Registrar en historial
    INSERT INTO historial_estados (emergencia_id, estado_anterior, estado_nuevo, usuario_id, notas)
    SELECT emergencia_id, estado, 'resuelta', usuario_id, notas
    FROM emergencias WHERE id = emergencia_id;
    
    -- Liberar bomberos asignados
    UPDATE bomberos b
    JOIN asignaciones a ON b.id = a.bombero_id
    SET b.disponible = true
    WHERE a.emergencia_id = emergencia_id AND a.estado != 'completada';
    
    -- Completar asignaciones
    UPDATE asignaciones 
    SET estado = 'completada', fecha_cierre = NOW()
    WHERE emergencia_id = emergencia_id AND estado != 'completada';
END//

DELIMITER ;

-- =============================================
-- Índices Adicionales para Optimización
-- =============================================

CREATE INDEX idx_emergencias_ubicacion ON emergencias(latitud, longitud);
CREATE INDEX idx_bomberos_ubicacion ON bomberos(latitud_actual, longitud_actual);
CREATE INDEX idx_asignaciones_fechas ON asignaciones(fecha_asignacion, fecha_cierre);
CREATE INDEX idx_emergencias_estado_fecha ON emergencias(estado, fecha_reporte);

-- =============================================
-- Comentarios Finales
-- =============================================

-- Contraseñas por defecto: "password" (hasheadas con bcrypt)
-- Para producción, cambiar todas las contraseñas por defecto

SELECT 'Base de datos sistema_bomberos_choco creada exitosamente!' as mensaje;