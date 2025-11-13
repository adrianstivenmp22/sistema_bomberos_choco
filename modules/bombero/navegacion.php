<?php
session_start();
require_once '../../includes/auth.php';
require_once '../../includes/database.php';

if (!isBombero()) {
    header('Location: /sistema_bomberos_choco/index.php');
    exit();
}

$db = connectDatabase();
$emergencia_id = $_GET['emergencia_id'] ?? null;

if (!$emergencia_id) {
    header('Location: tareas.php');
    exit();
}

// Obtener información de la emergencia
$emergencia = obtenerEmergencia($db, $emergencia_id);
$multimedia = obtenerMultimediaEmergencia($db, $emergencia_id);

function obtenerEmergencia($db, $emergencia_id) {
    $sql = "SELECT e.*, u.nombre as ciudadano_nombre, u.telefono as ciudadano_telefono
            FROM emergencias e
            JOIN usuarios u ON e.ciudadano_id = u.id
            WHERE e.id = ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("i", $emergencia_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

function obtenerMultimediaEmergencia($db, $emergencia_id) {
    $sql = "SELECT * FROM emergencia_multimedia WHERE emergencia_id = ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("i", $emergencia_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Navegación - Bombero</title>
    <link rel="stylesheet" href="/sistema_bomberos_choco/css/styles.css">
    <style>
        #map {
            height: 500px;
            width: 100%;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .info-panel {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .multimedia-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 10px;
            margin-top: 15px;
        }
        .multimedia-item {
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 10px;
            text-align: center;
        }
        .multimedia-item img, .multimedia-item video {
            max-width: 100%;
            max-height: 150px;
        }
        .navigation-actions {
            display: flex;
            gap: 10px;
            margin: 20px 0;
            flex-wrap: wrap;
        }
    </style>
</head>
<body>
    <?php include '../../includes/header.php'; ?>
    
    <div class="container">
        <h1>Navegación a Emergencia</h1>
        
        <div class="card">
            <h2>Información del Incidente</h2>
            <div class="info-panel">
                <div class="emergency-header">
                    <h3>
                        <span class="badge badge-<?= $emergencia['tipo'] ?>">
                            <?= ucfirst($emergencia['tipo']) ?>
                        </span>
                        <span class="badge badge-<?= $emergencia['gravedad'] ?>">
                            Gravedad: <?= ucfirst($emergencia['gravedad']) ?>
                        </span>
                    </h3>
                </div>
                
                <div class="emergency-details">
                    <p><strong>Descripción:</strong> <?= htmlspecialchars($emergencia['descripcion']) ?></p>
                    <p><strong>Dirección:</strong> <?= htmlspecialchars($emergencia['direccion']) ?></p>
                    <p><strong>Coordenadas:</strong> <?= $emergencia['latitud'] ?>, <?= $emergencia['longitud'] ?></p>
                    <p><strong>Reportante:</strong> <?= htmlspecialchars($emergencia['ciudadano_nombre']) ?></p>
                    <p><strong>Teléfono:</strong> <?= $emergencia['ciudadano_telefono'] ?></p>
                    <p><strong>Reportada:</strong> <?= date('d/m/Y H:i', strtotime($emergencia['fecha_reporte'])) ?></p>
                </div>
            </div>

            <!-- Mapa de Navegación -->
            <h3>Navegación</h3>
            <div id="map"></div>
            
            <div class="navigation-actions">
                <button class="btn btn-primary" onclick="abrirGoogleMaps()">
                    Abrir en Google Maps
                </button>
                <button class="btn btn-success" onclick="abrirWaze()">
                    Abrir en Waze
                </button>
                <button class="btn btn-info" onclick="obtenerUbicacionActual()">
                    Actualizar Mi Ubicación
                </button>
                <a href="tareas.php" class="btn btn-outline">Volver a Tareas</a>
            </div>

            <!-- Multimedia -->
            <?php if (!empty($multimedia)): ?>
            <h3>Evidencia Multimedia</h3>
            <div class="multimedia-grid">
                <?php foreach ($multimedia as $media): ?>
                <div class="multimedia-item">
                    <?php if ($media['tipo'] === 'foto'): ?>
                        <img src="/sistema_bomberos_choco/<?= $media['ruta_archivo'] ?>" 
                             alt="Evidencia <?= $emergencia['id'] ?>">
                    <?php elseif ($media['tipo'] === 'video'): ?>
                        <video controls>
                            <source src="/sistema_bomberos_choco/<?= $media['ruta_archivo'] ?>" type="video/mp4">
                            Tu navegador no soporta el elemento video.
                        </video>
                    <?php else: ?>
                        <div class="documento">
                            <i class="icon-documento">📄</i>
                            <p><?= $media['nombre_archivo'] ?></p>
                        </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Coordenadas de la emergencia
        const emergenciaLat = <?= $emergencia['latitud'] ?>;
        const emergenciaLng = <?= $emergencia['longitud'] ?>;
        let mapa;
        let marcadorEmergencia;
        let marcadorUsuario;

        // Inicializar mapa
        function initMap() {
            mapa = new google.maps.Map(document.getElementById('map'), {
                center: { lat: emergenciaLat, lng: emergenciaLng },
                zoom: 15
            });

            // Marcador de la emergencia
            marcadorEmergencia = new google.maps.Marker({
                position: { lat: emergenciaLat, lng: emergenciaLng },
                map: mapa,
                title: 'Ubicación de la Emergencia',
                icon: {
                    url: 'http://maps.google.com/mapfiles/ms/icons/red-dot.png'
                }
            });

            // InfoWindow para la emergencia
            const infoWindow = new google.maps.InfoWindow({
                content: `
                    <div>
                        <h3>Emergencia <?= strtoupper($emergencia['tipo']) ?></h3>
                        <p><strong>Gravedad:</strong> <?= ucfirst($emergencia['gravedad']) ?></p>
                        <p><strong>Dirección:</strong> <?= htmlspecialchars($emergencia['direccion']) ?></p>
                        <p><strong>Reportante:</strong> <?= htmlspecialchars($emergencia['ciudadano_nombre']) ?></p>
                    </div>
                `
            });

            marcadorEmergencia.addListener('click', () => {
                infoWindow.open(mapa, marcadorEmergencia);
            });

            // Obtener ubicación actual del usuario
            obtenerUbicacionActual();
        }

        // Obtener ubicación actual
        function obtenerUbicacionActual() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        const userLat = position.coords.latitude;
                        const userLng = position.coords.longitude;

                        // Actualizar o crear marcador del usuario
                        if (marcadorUsuario) {
                            marcadorUsuario.setPosition({ lat: userLat, lng: userLng });
                        } else {
                            marcadorUsuario = new google.maps.Marker({
                                position: { lat: userLat, lng: userLng },
                                map: mapa,
                                title: 'Tu ubicación actual',
                                icon: {
                                    url: 'http://maps.google.com/mapfiles/ms/icons/blue-dot.png'
                                }
                            });
                        }

                        // Centrar mapa entre usuario y emergencia
                        const bounds = new google.maps.LatLngBounds();
                        bounds.extend(marcadorEmergencia.getPosition());
                        bounds.extend(marcadorUsuario.getPosition());
                        mapa.fitBounds(bounds);

                        // Dibujar ruta
                        dibujarRuta(userLat, userLng);

                        // Actualizar ubicación en base de datos
                        actualizarUbicacionBombero(userLat, userLng);

                    },
                    (error) => {
                        console.error('Error obteniendo ubicación:', error);
                        alert('No se pudo obtener tu ubicación. Asegúrate de tener el GPS activado.');
                    },
                    {
                        enableHighAccuracy: true,
                        timeout: 10000,
                        maximumAge: 0
                    }
                );
            } else {
                alert('La geolocalización no es soportada por este navegador.');
            }
        }

        // Dibujar ruta
        function dibujarRuta(userLat, userLng) {
            const directionsService = new google.maps.DirectionsService();
            const directionsRenderer = new google.maps.DirectionsRenderer();
            directionsRenderer.setMap(mapa);

            const request = {
                origin: { lat: userLat, lng: userLng },
                destination: { lat: emergenciaLat, lng: emergenciaLng },
                travelMode: 'DRIVING'
            };

            directionsService.route(request, (result, status) => {
                if (status === 'OK') {
                    directionsRenderer.setDirections(result);
                }
            });
        }

        // Actualizar ubicación en base de datos
        function actualizarUbicacionBombero(lat, lng) {
            fetch('../../includes/update_location.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    lat: lat,
                    lng: lng
                })
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    console.error('Error actualizando ubicación:', data.error);
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
        }

        // Abrir en Google Maps
        function abrirGoogleMaps() {
            const url = `https://www.google.com/maps/dir/?api=1&destination=${emergenciaLat},${emergenciaLng}&travelmode=driving`;
            window.open(url, '_blank');
        }

        // Abrir en Waze
        function abrirWaze() {
            const url = `https://www.waze.com/ul?ll=${emergenciaLat},${emergenciaLng}&navigate=yes`;
            window.open(url, '_blank');
        }

        // Actualizar ubicación cada 30 segundos
        setInterval(obtenerUbicacionActual, 30000);
    </script>

    <!-- Cargar Google Maps API -->
    <script async defer
        src="https://maps.googleapis.com/maps/api/js?key=TU_API_KEY&callback=initMap&libraries=geometry">
    </script>
</body>
</html>