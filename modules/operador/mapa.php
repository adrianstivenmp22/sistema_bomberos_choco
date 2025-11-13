<?php
session_start();
require_once '../../includes/auth.php';
require_once '../../includes/database.php';

if (!isOperador()) {
    header('Location: /sistema_bomberos_choco/index.php');
    exit();
}

$db = connectDatabase();

// Obtener emergencias para el mapa
$emergencias = obtenerEmergenciasParaMapa($db);
$bomberos = obtenerBomberosParaMapa($db);
$unidades = obtenerUnidadesParaMapa($db);

function obtenerEmergenciasParaMapa($db) {
    $sql = "SELECT e.*, u.nombre as ciudadano_nombre, u.telefono,
                   TIMESTAMPDIFF(MINUTE, e.fecha_reporte, NOW()) as minutos_espera
            FROM emergencias e
            JOIN usuarios u ON e.ciudadano_id = u.id
            WHERE e.estado IN ('reportada', 'en_progreso')
            ORDER BY e.fecha_reporte DESC";
    return $db->query($sql)->fetch_all(MYSQLI_ASSOC);
}

function obtenerBomberosParaMapa($db) {
    $sql = "SELECT b.*, u.nombre, un.nombre as unidad_nombre,
                   (SELECT COUNT(*) FROM asignaciones a 
                    WHERE a.bombero_id = b.id AND a.estado != 'completada') as asignaciones_activas
            FROM bomberos b
            JOIN usuarios u ON b.usuario_id = u.id
            LEFT JOIN unidades un ON b.unidad_id = un.id
            WHERE b.activo = 1 AND b.latitud_actual IS NOT NULL AND b.longitud_actual IS NOT NULL";
    return $db->query($sql)->fetch_all(MYSQLI_ASSOC);
}

function obtenerUnidadesParaMapa($db) {
    $sql = "SELECT * FROM unidades 
            WHERE activa = 1 AND latitud_base IS NOT NULL AND longitud_base IS NOT NULL";
    return $db->query($sql)->fetch_all(MYSQLI_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mapa Interactivo - Operador</title>
    <link rel="stylesheet" href="/sistema_bomberos_choco/css/styles.css">
    <style>
        #map {
            height: 600px;
            width: 100%;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .map-controls {
            background: white;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .filters-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 10px;
            margin: 10px 0;
        }
        .legend {
            background: white;
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
            border: 1px solid #ddd;
        }
        .legend-item {
            display: flex;
            align-items: center;
            margin: 5px 0;
        }
        .legend-color {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            margin-right: 10px;
            border: 2px solid white;
        }
        .info-window {
            max-width: 300px;
        }
        .info-window h4 {
            margin: 0 0 10px 0;
            color: #333;
        }
        .info-window p {
            margin: 5px 0;
            font-size: 0.9em;
        }
    </style>
</head>
<body>
    <?php include '../../includes/header.php'; ?>
    
    <div class="container">
        <h1>🗺️ Mapa Interactivo de Emergencias</h1>
        
        <!-- Controles del Mapa -->
        <div class="map-controls">
            <div class="filters-grid">
                <div class="form-group">
                    <label>Filtrar por Tipo:</label>
                    <select id="filterTipo" onchange="filtrarMapa()">
                        <option value="">Todos los tipos</option>
                        <option value="incendio">Incendio</option>
                        <option value="accidente">Accidente</option>
                        <option value="rescate">Rescate</option>
                        <option value="inundacion">Inundación</option>
                        <option value="medica">Médica</option>
                        <option value="otro">Otro</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Filtrar por Estado:</label>
                    <select id="filterEstado" onchange="filtrarMapa()">
                        <option value="">Todos los estados</option>
                        <option value="reportada">Pendiente</option>
                        <option value="en_progreso">En Progreso</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Mostrar:</label>
                    <div>
                        <label><input type="checkbox" id="showEmergencies" checked onchange="filtrarMapa()"> Emergencias</label>
                        <label><input type="checkbox" id="showBomberos" checked onchange="filtrarMapa()"> Bomberos</label>
                        <label><input type="checkbox" id="showUnidades" checked onchange="filtrarMapa()"> Unidades</label>
                    </div>
                </div>
            </div>
            
            <button class="btn btn-outline" onclick="centrarMapa()">
                📍 Centrar en Quibdó
            </button>
            <button class="btn btn-info" onclick="actualizarUbicaciones()">
                🔄 Actualizar Ubicaciones
            </button>
        </div>

        <!-- Leyenda -->
        <div class="legend">
            <h4>Leyenda del Mapa:</h4>
            <div class="legend-item">
                <div class="legend-color" style="background: #dc3545;"></div>
                <span>Emergencia Crítica</span>
            </div>
            <div class="legend-item">
                <div class="legend-color" style="background: #fd7e14;"></div>
                <span>Emergencia Alta</span>
            </div>
            <div class="legend-item">
                <div class="legend-color" style="background: #ffc107;"></div>
                <span>Emergencia Media</span>
            </div>
            <div class="legend-item">
                <div class="legend-color" style="background: #20c997;"></div>
                <span>Emergencia Baja</span>
            </div>
            <div class="legend-item">
                <div class="legend-color" style="background: #007bff; border: 2px dashed blue;"></div>
                <span>Bombero Disponible</span>
            </div>
            <div class="legend-item">
                <div class="legend-color" style="background: #6f42c1;"></div>
                <span>Bombero Ocupado</span>
            </div>
            <div class="legend-item">
                <div class="legend-color" style="background: #28a745; border: 2px solid green;"></div>
                <span>Estación de Bomberos</span>
            </div>
        </div>

        <!-- Mapa -->
        <div id="map"></div>

        <!-- Estadísticas Rápidas -->
        <div class="stats-grid">
            <div class="stat-card">
                <h3>📍 Emergencias Activas</h3>
                <div class="stat-number"><?= count($emergencias) ?></div>
            </div>
            <div class="stat-card">
                <h3>👨‍🚒 Bomberos en Mapa</h3>
                <div class="stat-number"><?= count($bomberos) ?></div>
            </div>
            <div class="stat-card">
                <h3>🏢 Unidades Activas</h3>
                <div class="stat-number"><?= count($unidades) ?></div>
            </div>
        </div>
    </div>

    <script>
        let mapa;
        let marcadoresEmergencias = [];
        let marcadoresBomberos = [];
        let marcadoresUnidades = [];

        // Colores para gravedad
        const coloresGravedad = {
            'critica': '#dc3545',
            'alta': '#fd7e14', 
            'media': '#ffc107',
            'baja': '#20c997'
        };

        // Inicializar mapa
        function initMap() {
            // Centro en Quibdó, Chocó
            const quibdo = { lat: 5.6946, lng: -76.6610 };
            
            mapa = new google.maps.Map(document.getElementById('map'), {
                center: quibdo,
                zoom: 12,
                styles: [
                    {
                        featureType: 'poi',
                        elementType: 'labels',
                        stylers: [{ visibility: 'on' }]
                    }
                ]
            });

            // Cargar marcadores
            cargarEmergencias();
            cargarBomberos();
            cargarUnidades();
        }

        // Cargar emergencias en el mapa
        function cargarEmergencias() {
            const emergencias = <?= json_encode($emergencias) ?>;
            
            emergencias.forEach(emergencia => {
                const marcador = new google.maps.Marker({
                    position: { 
                        lat: parseFloat(emergencia.latitud), 
                        lng: parseFloat(emergencia.longitud) 
                    },
                    map: mapa,
                    title: `Emergencia #${emergencia.id}`,
                    icon: {
                        path: google.maps.SymbolPath.CIRCLE,
                        fillColor: coloresGravedad[emergencia.gravedad] || '#6c757d',
                        fillOpacity: 0.8,
                        strokeColor: '#ffffff',
                        strokeWeight: 2,
                        scale: 10
                    },
                    animation: emergencia.estado === 'reportada' ? google.maps.Animation.BOUNCE : null
                });

                const infoWindow = new google.maps.InfoWindow({
                    content: `
                        <div class="info-window">
                            <h4>
                                <span style="color: ${coloresGravedad[emergencia.gravedad]}">●</span>
                                Emergencia #${emergencia.id}
                            </h4>
                            <p><strong>Tipo:</strong> ${emergencia.tipo.toUpperCase()}</p>
                            <p><strong>Gravedad:</strong> <span style="color: ${coloresGravedad[emergencia.gravedad]}">${emergencia.gravedad.toUpperCase()}</span></p>
                            <p><strong>Estado:</strong> ${emergencia.estado.replace('_', ' ').toUpperCase()}</p>
                            <p><strong>Descripción:</strong> ${emergencia.descripcion.substring(0, 100)}...</p>
                            <p><strong>Ubicación:</strong> ${emergencia.direccion}</p>
                            <p><strong>Reportante:</strong> ${emergencia.ciudadano_nombre}</p>
                            <p><strong>Teléfono:</strong> ${emergencia.telefono}</p>
                            <p><strong>Tiempo espera:</strong> ${emergencia.minutos_espera} minutos</p>
                            <div style="margin-top: 10px;">
                                <a href="asignaciones.php?emergencia_id=${emergencia.id}" 
                                   style="background: #007bff; color: white; padding: 5px 10px; text-decoration: none; border-radius: 3px;">
                                    👨‍🚒 Asignar Recursos
                                </a>
                            </div>
                        </div>
                    `
                });

                marcador.addListener('click', () => {
                    infoWindow.open(mapa, marcador);
                });

                marcadoresEmergencias.push({
                    marcador: marcador,
                    tipo: emergencia.tipo,
                    estado: emergencia.estado,
                    gravedad: emergencia.gravedad
                });
            });
        }

        // Cargar bomberos en el mapa
        function cargarBomberos() {
            const bomberos = <?= json_encode($bomberos) ?>;
            
            bomberos.forEach(bombero => {
                if (!bombero.latitud_actual || !bombero.longitud_actual) return;
                
                const esDisponible = bombero.disponible && bombero.asignaciones_activas === 0;
                
                const marcador = new google.maps.Marker({
                    position: { 
                        lat: parseFloat(bombero.latitud_actual), 
                        lng: parseFloat(bombero.longitud_actual) 
                    },
                    map: mapa,
                    title: `Bombero ${bombero.nombre}`,
                    icon: {
                        url: esDisponible ? 
                             'http://maps.google.com/mapfiles/ms/icons/blue-dot.png' :
                             'http://maps.google.com/mapfiles/ms/icons/purple-dot.png',
                        scaledSize: new google.maps.Size(32, 32)
                    }
                });

                const infoWindow = new google.maps.InfoWindow({
                    content: `
                        <div class="info-window">
                            <h4>👨‍🚒 ${bombero.nombre}</h4>
                            <p><strong>Placa:</strong> ${bombero.numero_placa}</p>
                            <p><strong>Especialidad:</strong> ${bombero.especialidad || 'General'}</p>
                            <p><strong>Unidad:</strong> ${bombero.unidad_nombre || 'Sin unidad'}</p>
                            <p><strong>Estado:</strong> 
                                <span style="color: ${esDisponible ? 'green' : 'orange'}">
                                    ${esDisponible ? 'DISPONIBLE' : 'OCUPADO'}
                                </span>
                            </p>
                            <p><strong>Asignaciones activas:</strong> ${bombero.asignaciones_activas}</p>
                            <div style="margin-top: 10px;">
                                <button onclick="asignarBombero(${bombero.id})" 
                                        style="background: #28a745; color: white; border: none; padding: 5px 10px; border-radius: 3px; cursor: pointer;">
                                    ⚡ Asignar Emergencia
                                </button>
                            </div>
                        </div>
                    `
                });

                marcador.addListener('click', () => {
                    infoWindow.open(mapa, marcador);
                });

                marcadoresBomberos.push(marcador);
            });
        }

        // Cargar unidades en el mapa
        function cargarUnidades() {
            const unidades = <?= json_encode($unidades) ?>;
            
            unidades.forEach(unidad => {
                const marcador = new google.maps.Marker({
                    position: { 
                        lat: parseFloat(unidad.latitud_base), 
                        lng: parseFloat(unidad.longitud_base) 
                    },
                    map: mapa,
                    title: unidad.nombre,
                    icon: {
                        url: 'http://maps.google.com/mapfiles/ms/icons/green-dot.png',
                        scaledSize: new google.maps.Size(32, 32)
                    }
                });

                const infoWindow = new google.maps.InfoWindow({
                    content: `
                        <div class="info-window">
                            <h4>🏢 ${unidad.nombre}</h4>
                            <p><strong>Código:</strong> ${unidad.codigo}</p>
                            <p><strong>Tipo:</strong> ${unidad.tipo}</p>
                            <p><strong>Capacidad:</strong> ${unidad.capacidad} bomberos</p>
                            <p><strong>Estado:</strong> ${unidad.activa ? 'ACTIVA' : 'INACTIVA'}</p>
                            <p><strong>Ubicación:</strong> ${unidad.ubicacion_base}</p>
                        </div>
                    `
                });

                marcador.addListener('click', () => {
                    infoWindow.open(mapa, marcador);
                });

                marcadoresUnidades.push(marcador);
            });
        }

        // Filtrar mapa
        function filtrarMapa() {
            const filterTipo = document.getElementById('filterTipo').value;
            const filterEstado = document.getElementById('filterEstado').value;
            const showEmergencies = document.getElementById('showEmergencies').checked;
            const showBomberos = document.getElementById('showBomberos').checked;
            const showUnidades = document.getElementById('showUnidades').checked;

            // Filtrar emergencias
            marcadoresEmergencias.forEach(item => {
                const visible = showEmergencies && 
                              (filterTipo === '' || item.tipo === filterTipo) &&
                              (filterEstado === '' || item.estado === filterEstado);
                item.marcador.setVisible(visible);
            });

            // Filtrar bomberos
            marcadoresBomberos.forEach(marcador => {
                marcador.setVisible(showBomberos);
            });

            // Filtrar unidades
            marcadoresUnidades.forEach(marcador => {
                marcador.setVisible(showUnidades);
            });
        }

        // Centrar mapa en Quibdó
        function centrarMapa() {
            mapa.setCenter({ lat: 5.6946, lng: -76.6610 });
            mapa.setZoom(12);
        }

        // Actualizar ubicaciones
        function actualizarUbicaciones() {
            window.location.reload();
        }

        // Asignar bombero (función de ejemplo)
        function asignarBombero(bomberoId) {
            alert(`Funcionalidad de asignación rápida para bombero ${bomberoId}`);
            // Aquí se implementaría la asignación directa
        }

        // Auto-actualizar cada 60 segundos
        setInterval(actualizarUbicaciones, 60000);
    </script>

    <!-- Google Maps API -->
    <script async defer
        src="https://maps.googleapis.com/maps/api/js?key=TU_API_KEY&callback=initMap&libraries=geometry">
    </script>
</body>
</html>