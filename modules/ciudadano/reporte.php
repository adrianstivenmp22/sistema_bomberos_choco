<?php
session_start();
require_once '../../includes/auth.php';
require_once '../../includes/database.php';

if (!isCiudadano()) {
    header('Location: /sistema_bomberos_choco/index.php');
    exit();
}

$db = connectDatabase();
$success = false;
$error = '';

// Procesar reporte de emergencia
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tipo = $_POST['tipo'] ?? '';
    $descripcion = $_POST['descripcion'] ?? '';
    $direccion = $_POST['direccion'] ?? '';
    $latitud = $_POST['latitud'] ?? '';
    $longitud = $_POST['longitud'] ?? '';
    
    // Validar campos requeridos
    if (empty($tipo) || empty($descripcion) || empty($direccion) || empty($latitud) || empty($longitud)) {
        $error = "Todos los campos son obligatorios, incluyendo la ubicación.";
    } else {
        // Crear reporte de emergencia
        $sql = "INSERT INTO emergencias (ciudadano_id, tipo, descripcion, direccion, latitud, longitud, gravedad) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $db->prepare($sql);
        
        // Determinar gravedad automáticamente basado en el tipo
        $gravedad = determinarGravedad($tipo);
        
        $stmt->bind_param("isssdds", $_SESSION['user_id'], $tipo, $descripcion, $direccion, $latitud, $longitud, $gravedad);
        
        if ($stmt->execute()) {
            $emergencia_id = $db->insert_id;
            
            // Procesar multimedia si se subió
            if (!empty($_FILES['multimedia']['name'][0])) {
                procesarMultimedia($db, $emergencia_id, $_FILES['multimedia']);
            }
            
            // Registrar log
            registrarLog('reporte_emergencia', 'ciudadano', "Emergencia $emergencia_id reportada: $tipo");
            
            $success = true;
            $_SESSION['success'] = "¡Emergencia reportada exitosamente! Número de caso: #$emergencia_id";
            
            // Redirigir para evitar reenvío del formulario
            header('Location: reporte.php?success=1');
            exit();
        } else {
            $error = "Error al reportar la emergencia: " . $db->error;
        }
    }
}

function determinarGravedad($tipo) {
    $gravedades = [
        'incendio' => 'alta',
        'accidente' => 'alta', 
        'medica' => 'alta',
        'rescate' => 'critica',
        'inundacion' => 'media',
        'otro' => 'media'
    ];
    return $gravedades[$tipo] ?? 'media';
}

function procesarMultimedia($db, $emergencia_id, $archivos) {
    $upload_dir = '../../assets/uploads/';
    
    // Crear directorio si no existe
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    for ($i = 0; $i < count($archivos['name']); $i++) {
        if ($archivos['error'][$i] === UPLOAD_ERR_OK) {
            $nombre_archivo = uniqid() . '_' . basename($archivos['name'][$i]);
            $ruta_archivo = $upload_dir . $nombre_archivo;
            
            // Mover archivo
            if (move_uploaded_file($archivos['tmp_name'][$i], $ruta_archivo)) {
                // Determinar tipo de archivo
                $tipo_archivo = determinarTipoArchivo($archivos['type'][$i]);
                
                // Guardar en base de datos
                $sql = "INSERT INTO emergencia_multimedia (emergencia_id, tipo, nombre_archivo, ruta_archivo) 
                        VALUES (?, ?, ?, ?)";
                $stmt = $db->prepare($sql);
                $ruta_relativa = 'assets/uploads/' . $nombre_archivo;
                $stmt->bind_param("isss", $emergencia_id, $tipo_archivo, $archivos['name'][$i], $ruta_relativa);
                $stmt->execute();
            }
        }
    }
}

function determinarTipoArchivo($mime_type) {
    if (strpos($mime_type, 'image/') === 0) {
        return 'foto';
    } elseif (strpos($mime_type, 'video/') === 0) {
        return 'video';
    } else {
        return 'documento';
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportar Emergencia - Sistema Bomberos Chocó</title>
    <link rel="stylesheet" href="/sistema_bomberos_choco/css/styles.css">
    <style>
        #map {
            height: 300px;
            width: 100%;
            border-radius: 8px;
            margin: 10px 0;
            border: 2px solid #ddd;
        }
        .location-status {
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
            text-align: center;
        }
        .location-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .location-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .multimedia-preview {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 10px;
            margin: 10px 0;
        }
        .preview-item {
            position: relative;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 5px;
        }
        .preview-item img, .preview-item video {
            width: 100%;
            height: 100px;
            object-fit: cover;
        }
        .remove-file {
            position: absolute;
            top: 5px;
            right: 5px;
            background: red;
            color: white;
            border: none;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            cursor: pointer;
        }
        .sos-button {
            background: #dc3545;
            color: white;
            border: none;
            padding: 20px;
            font-size: 1.5em;
            border-radius: 50%;
            width: 100px;
            height: 100px;
            cursor: pointer;
            margin: 20px auto;
            display: block;
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }
    </style>
</head>
<body>
    <?php include '../../includes/header.php'; ?>
    
    <div class="container">
        <h1>Reportar Emergencia</h1>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <?= $_SESSION['success'] ?>
                <?php unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger">
                <?= $error ?>
            </div>
        <?php endif; ?>

        <!-- Botón SOS Rápido -->
        <div class="card text-center">
            <h2>🚨 EMERGENCIA CRÍTICA</h2>
            <p>Usa este botón solo para situaciones que requieren atención inmediata</p>
            <button type="button" class="sos-button" onclick="activarSOS()">
                SOS
            </button>
            <p class="text-muted">Se enviará tu ubicación automáticamente</p>
        </div>

        <!-- Formulario de Reporte Detallado -->
        <div class="card">
            <h2>Reporte Detallado de Emergencia</h2>
            <form method="POST" enctype="multipart/form-data" id="reporteForm">
                <!-- Tipo de Emergencia -->
                <div class="form-group">
                    <label for="tipo">Tipo de Emergencia *</label>
                    <select name="tipo" id="tipo" required onchange="actualizarDescripcion()">
                        <option value="">Selecciona el tipo de emergencia</option>
                        <option value="incendio">🔥 Incendio</option>
                        <option value="accidente">🚗 Accidente de Tránsito</option>
                        <option value="rescate">🆘 Rescate</option>
                        <option value="inundacion">🌊 Inundación</option>
                        <option value="medica">🏥 Emergencia Médica</option>
                        <option value="otro">❓ Otro</option>
                    </select>
                </div>

                <!-- Descripción -->
                <div class="form-group">
                    <label for="descripcion">Descripción Detallada *</label>
                    <textarea name="descripcion" id="descripcion" rows="4" 
                              placeholder="Describe la situación con el mayor detalle posible..." required></textarea>
                    <small id="descripcionHelp" class="form-text"></small>
                </div>

                <!-- Ubicación -->
                <div class="form-group">
                    <label>Ubicación del Incidente *</label>
                    <div id="locationStatus" class="location-status location-error">
                        📍 Esperando acceso a la ubicación...
                    </div>
                    
                    <div id="map"></div>
                    
                    <div class="form-group">
                        <label for="direccion">Dirección Específica *</label>
                        <input type="text" name="direccion" id="direccion" 
                               placeholder="Ej: Calle 10 # 5-20, Barrio El Centro" required>
                    </div>
                    
                    <input type="hidden" name="latitud" id="latitud">
                    <input type="hidden" name="longitud" id="longitud">
                    
                    <button type="button" class="btn btn-outline" onclick="obtenerUbicacion()">
                        🔄 Actualizar Mi Ubicación
                    </button>
                </div>

                <!-- Multimedia -->
                <div class="form-group">
                    <label for="multimedia">Evidencia Multimedia (Opcional)</label>
                    <input type="file" name="multimedia[]" id="multimedia" 
                           multiple accept="image/*,video/*,.pdf,.doc,.docx"
                           onchange="previewFiles(this.files)">
                    <small class="form-text">
                        Puedes subir fotos, videos o documentos. Máximo 5 archivos, 10MB cada uno.
                    </small>
                    
                    <div id="multimediaPreview" class="multimedia-preview"></div>
                </div>

                <!-- Botones -->
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary" id="submitBtn">
                        📤 Reportar Emergencia
                    </button>
                    <button type="reset" class="btn btn-outline">🔄 Limpiar Formulario</button>
                </div>
            </form>
        </div>

        <!-- Información de Contacto -->
        <div class="card">
            <h2>📞 Contactos de Emergencia</h2>
            <div class="contactos-grid">
                <div class="contacto-item">
                    <h3>Bomberos Quibdó</h3>
                    <p>📞 <strong>123</strong> o <strong>672 1234</strong></p>
                </div>
                <div class="contacto-item">
                    <h3>Policía Nacional</h3>
                    <p>📞 <strong>112</strong> o <strong>672 5678</strong></p>
                </div>
                <div class="contacto-item">
                    <h3>CRUZ ROJA</h3>
                    <p>📞 <strong>132</strong> o <strong>672 9012</strong></p>
                </div>
                <div class="contacto-item">
                    <h3>Defensa Civil</h3>
                    <p>📞 <strong>672 3456</strong></p>
                </div>
            </div>
        </div>
    </div>

    <script>
        let mapa;
        let marcador;
        let ubicacionObtenida = false;

        // Inicializar mapa
        function initMap() {
            mapa = new google.maps.Map(document.getElementById('map'), {
                center: { lat: 5.6946, lng: -76.6610 }, // Quibdó
                zoom: 13
            });
            
            // Intentar obtener ubicación al cargar
            obtenerUbicacion();
        }

        // Obtener ubicación del usuario
        function obtenerUbicacion() {
            const statusElement = document.getElementById('locationStatus');
            
            if (!navigator.geolocation) {
                statusElement.innerHTML = '❌ Tu navegador no soporta geolocalización';
                statusElement.className = 'location-status location-error';
                return;
            }

            statusElement.innerHTML = '📍 Obteniendo ubicación...';
            statusElement.className = 'location-status';

            navigator.geolocation.getCurrentPosition(
                (position) => {
                    const lat = position.coords.latitude;
                    const lng = position.coords.longitude;
                    
                    // Actualizar campos ocultos
                    document.getElementById('latitud').value = lat;
                    document.getElementById('longitud').value = lng;
                    
                    // Actualizar mapa
                    const ubicacion = { lat: lat, lng: lng };
                    mapa.setCenter(ubicacion);
                    
                    if (marcador) {
                        marcador.setPosition(ubicacion);
                    } else {
                        marcador = new google.maps.Marker({
                            position: ubicacion,
                            map: mapa,
                            title: 'Tu ubicación actual',
                            draggable: true
                        });
                        
                        // Permitir arrastrar el marcador
                        marcador.addListener('dragend', function() {
                            const nuevaPos = marcador.getPosition();
                            document.getElementById('latitud').value = nuevaPos.lat();
                            document.getElementById('longitud').value = nuevaPos.lng();
                            actualizarDireccionDesdeCoordenadas(nuevaPos.lat(), nuevaPos.lng());
                        });
                    }
                    
                    // Obtener dirección
                    actualizarDireccionDesdeCoordenadas(lat, lng);
                    
                    ubicacionObtenida = true;
                    statusElement.innerHTML = '✅ Ubicación obtenida correctamente';
                    statusElement.className = 'location-status location-success';
                    
                },
                (error) => {
                    let mensaje = '';
                    switch(error.code) {
                        case error.PERMISSION_DENIED:
                            mensaje = '❌ Permiso de ubicación denegado. Por favor habilita la ubicación en tu navegador.';
                            break;
                        case error.POSITION_UNAVAILABLE:
                            mensaje = '❌ Información de ubicación no disponible.';
                            break;
                        case error.TIMEOUT:
                            mensaje = '❌ Tiempo de espera agotado para obtener la ubicación.';
                            break;
                        default:
                            mensaje = '❌ Error desconocido al obtener la ubicación.';
                    }
                    statusElement.innerHTML = mensaje;
                    statusElement.className = 'location-status location-error';
                },
                {
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 0
                }
            );
        }

        // Obtener dirección desde coordenadas (geocoding)
        function actualizarDireccionDesdeCoordenadas(lat, lng) {
            const geocoder = new google.maps.Geocoder();
            const latlng = { lat: lat, lng: lng };
            
            geocoder.geocode({ location: latlng }, (results, status) => {
                if (status === 'OK' && results[0]) {
                    document.getElementById('direccion').value = results[0].formatted_address;
                }
            });
        }

        // Vista previa de archivos multimedia
        function previewFiles(files) {
            const preview = document.getElementById('multimediaPreview');
            preview.innerHTML = '';
            
            for (let i = 0; i < Math.min(files.length, 5); i++) {
                const file = files[i];
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    const previewItem = document.createElement('div');
                    previewItem.className = 'preview-item';
                    
                    if (file.type.startsWith('image/')) {
                        previewItem.innerHTML = `
                            <img src="${e.target.result}" alt="Preview">
                            <button type="button" class="remove-file" onclick="removeFile(${i})">×</button>
                            <small>${file.name}</small>
                        `;
                    } else if (file.type.startsWith('video/')) {
                        previewItem.innerHTML = `
                            <video controls>
                                <source src="${e.target.result}" type="${file.type}">
                            </video>
                            <button type="button" class="remove-file" onclick="removeFile(${i})">×</button>
                            <small>${file.name}</small>
                        `;
                    } else {
                        previewItem.innerHTML = `
                            <div style="text-align: center; padding: 20px;">
                                📄<br>
                                <small>${file.name}</small>
                            </div>
                            <button type="button" class="remove-file" onclick="removeFile(${i})">×</button>
                        `;
                    }
                    
                    preview.appendChild(previewItem);
                };
                
                reader.readAsDataURL(file);
            }
        }

        // Remover archivo de la vista previa
        function removeFile(index) {
            const input = document.getElementById('multimedia');
            const dt = new DataTransfer();
            const files = input.files;
            
            for (let i = 0; i < files.length; i++) {
                if (i !== index) {
                    dt.items.add(files[i]);
                }
            }
            
            input.files = dt.files;
            previewFiles(input.files);
        }

        // Actualizar ayuda de descripción según el tipo
        function actualizarDescripcion() {
            const tipo = document.getElementById('tipo').value;
            const helpText = document.getElementById('descripcionHelp');
            const ejemplos = {
                'incendio': 'Ej: Edificio de 3 pisos en llamas, hay personas atrapadas...',
                'accidente': 'Ej: Choque entre camión y moto, 2 heridos graves...',
                'rescate': 'Ej: Persona atrapada en vehículo volcado...',
                'inundacion': 'Ej: Agua ha entrado a las casas, nivel 1 metro...',
                'medica': 'Ej: Persona inconsciente, dificultad para respirar...',
                'otro': 'Ej: Describe detalladamente la situación...'
            };
            
            document.getElementById('descripcion').placeholder = ejemplos[tipo] || 'Describe la situación...';
        }

        // Botón SOS
        function activarSOS() {
            if (!ubicacionObtenida) {
                alert('Primero debemos obtener tu ubicación. Por favor permite el acceso a la ubicación.');
                obtenerUbicacion();
                return;
            }
            
            if (confirm('¿ESTÁS EN PELIGRO INMINENTE? Este botón enviará una alerta de máxima prioridad.')) {
                // Rellenar automáticamente el formulario
                document.getElementById('tipo').value = 'rescate';
                document.getElementById('descripcion').value = 'EMERGENCIA SOS - NECESITO AYUDA INMEDIATA';
                document.getElementById('gravedad').value = 'critica';
                
                // Enviar formulario automáticamente
                document.getElementById('reporteForm').submit();
            }
        }

        // Validación antes de enviar
        document.getElementById('reporteForm').addEventListener('submit', function(e) {
            if (!ubicacionObtenida) {
                e.preventDefault();
                alert('Debes permitir el acceso a tu ubicación para reportar la emergencia.');
                obtenerUbicacion();
                return;
            }
            
            const submitBtn = document.getElementById('submitBtn');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '⏳ Enviando reporte...';
        });
    </script>

    <!-- Google Maps API -->
    <script async defer
        src="https://maps.googleapis.com/maps/api/js?key=TU_API_KEY&callback=initMap&libraries=places">
    </script>
</body>
</html>