<?php
session_start();
require_once '../../includes/auth.php';
require_once '../../includes/database.php';

if (!isCiudadano()) {
    header('Location: /sistema_bomberos_choco/index.php');
    exit();
}

$db = connectDatabase();

// Procesar alerta SOS
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $latitud = $_POST['latitud'] ?? '';
    $longitud = $_POST['longitud'] ?? '';
    
    if (empty($latitud) || empty($longitud)) {
        $error = "No se pudo obtener la ubicación automáticamente. Por favor intenta nuevamente.";
    } else {
        // Crear emergencia SOS (máxima prioridad)
        $sql = "INSERT INTO emergencias (ciudadano_id, tipo, descripcion, direccion, latitud, longitud, gravedad) 
                VALUES (?, 'rescate', 'ALERTA SOS - EMERGENCIA CRÍTICA', 'Ubicación automática SOS', ?, ?, 'critica')";
        $stmt = $db->prepare($sql);
        $stmt->bind_param("idd", $_SESSION['user_id'], $latitud, $longitud);
        
        if ($stmt->execute()) {
            $emergencia_id = $db->insert_id;
            
            // Registrar log
            registrarLog('alerta_sos', 'ciudadano', "Alerta SOS enviada: Emergencia $emergencia_id");
            
            // Éxito - mostrar confirmación
            $success = true;
            $numero_caso = $emergencia_id;
        } else {
            $error = "Error al enviar la alerta SOS. Por favor intenta nuevamente.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alerta SOS - Sistema Bomberos Chocó</title>
    <link rel="stylesheet" href="/sistema_bomberos_choco/css/styles.css">
    <style>
        .sos-container {
            text-align: center;
            padding: 40px 20px;
        }
        .sos-alert {
            background: linear-gradient(45deg, #dc3545, #ff6b7a);
            color: white;
            padding: 30px;
            border-radius: 15px;
            margin: 20px 0;
            animation: pulse-sos 1.5s infinite;
        }
        @keyframes pulse-sos {
            0% { transform: scale(1); box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.7); }
            70% { transform: scale(1.05); box-shadow: 0 0 0 20px rgba(220, 53, 69, 0); }
            100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(220, 53, 69, 0); }
        }
        .sos-button {
            background: #dc3545;
            color: white;
            border: none;
            padding: 30px;
            font-size: 2em;
            border-radius: 50%;
            width: 150px;
            height: 150px;
            cursor: pointer;
            margin: 20px auto;
            display: block;
            animation: pulse 2s infinite;
        }
        .location-status {
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
            font-weight: bold;
        }
        .success-message {
            background: #d4edda;
            color: #155724;
            border: 2px solid #c3e6cb;
            padding: 30px;
            border-radius: 10px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <?php include '../../includes/header.php'; ?>
    
    <div class="container">
        <div class="sos-container">
            
            <?php if (isset($success) && $success): ?>
                <!-- Confirmación de éxito -->
                <div class="success-message">
                    <h1>✅ ALERTA SOS ENVIADA</h1>
                    <h2>Número de Caso: #<?= $numero_caso ?></h2>
                    <p>Los equipos de emergencia han sido alertados y están en camino a tu ubicación.</p>
                    <div class="instructions">
                        <h3>📞 MANTÉN LA CALMA Y:</h3>
                        <ul style="text-align: left; display: inline-block;">
                            <li>Mantén tu teléfono encendido y con volumen</li>
                            <li>Si es seguro, espera en un lugar visible</li>
                            <li>Prepara tu identificación</li>
                            <li>No te muevas a menos que sea peligroso quedarte</li>
                        </ul>
                    </div>
                    <div class="contact-info">
                        <p><strong>Si el peligro es inminente, llama directamente:</strong></p>
                        <p style="font-size: 1.5em; margin: 10px 0;">📞 <strong>123</strong> (Bomberos)</p>
                        <p style="font-size: 1.5em; margin: 10px 0;">📞 <strong>112</strong> (Policía)</p>
                    </div>
                    <a href="historial.php" class="btn btn-primary">Ver Mi Historial</a>
                </div>
                
            <?php else: ?>
                <!-- Formulario SOS -->
                <h1>🚨 BOTÓN DE EMERGENCIA SOS</h1>
                
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger">
                        <?= $error ?>
                    </div>
                <?php endif; ?>
                
                <div class="sos-alert">
                    <h2>⚠️ SOLO PARA EMERGENCIAS CRÍTICAS</h2>
                    <p>Usa este botón solo si tu vida o la de otros está en peligro inminente</p>
                </div>

                <form method="POST" id="sosForm">
                    <input type="hidden" name="latitud" id="latitud">
                    <input type="hidden" name="longitud" id="longitud">
                    
                    <div id="locationStatus" class="location-status">
                        📍 Obteniendo tu ubicación...
                    </div>
                    
                    <button type="button" class="sos-button" onclick="enviarSOS()" id="sosBtn">
                        SOS
                    </button>
                    
                    <p class="text-muted">
                        Al presionar el botón, se enviará tu ubicación automáticamente<br>
                        a todos los equipos de emergencia disponibles.
                    </p>
                </form>

                <div class="safety-tips">
                    <h3>💡 Consejos de Seguridad:</h3>
                    <ul style="text-align: left; display: inline-block;">
                        <li>Mantén la calma y evalúa la situación</li>
                        <li>Busca un lugar seguro si es posible</li>
                        <li>Ten a mano tu identificación</li>
                        <li>Prepara información médica importante si aplica</li>
                    </ul>
                </div>
                
                <div class="alternative-actions">
                    <p>¿No es una emergencia crítica?</p>
                    <a href="reporte.php" class="btn btn-outline">Usar Reporte Normal</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        let ubicacionObtenida = false;
        let latitud, longitud;

        // Obtener ubicación al cargar la página
        window.addEventListener('load', function() {
            obtenerUbicacion();
        });

        function obtenerUbicacion() {
            const statusElement = document.getElementById('locationStatus');
            
            if (!navigator.geolocation) {
                statusElement.innerHTML = '❌ Tu navegador no soporta geolocalización';
                return;
            }

            navigator.geolocation.getCurrentPosition(
                (position) => {
                    latitud = position.coords.latitude;
                    longitud = position.coords.longitude;
                    
                    document.getElementById('latitud').value = latitud;
                    document.getElementById('longitud').value = longitud;
                    
                    ubicacionObtenida = true;
                    statusElement.innerHTML = '✅ Ubicación obtenida correctamente';
                    statusElement.style.background = '#d4edda';
                    statusElement.style.color = '#155724';
                    
                },
                (error) => {
                    let mensaje = '';
                    switch(error.code) {
                        case error.PERMISSION_DENIED:
                            mensaje = '❌ Permiso de ubicación denegado. Por favor habilita la ubicación.';
                            break;
                        case error.POSITION_UNAVAILABLE:
                            mensaje = '❌ No se pudo obtener la ubicación.';
                            break;
                        case error.TIMEOUT:
                            mensaje = '❌ Tiempo de espera agotado.';
                            break;
                        default:
                            mensaje = '❌ Error al obtener ubicación.';
                    }
                    statusElement.innerHTML = mensaje;
                    statusElement.style.background = '#f8d7da';
                    statusElement.style.color = '#721c24';
                },
                {
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 0
                }
            );
        }

        function enviarSOS() {
            if (!ubicacionObtenida) {
                alert('No se pudo obtener tu ubicación. Por favor intenta nuevamente.');
                obtenerUbicacion();
                return;
            }

            if (confirm('¿ESTÁS EN PELIGRO INMINENTE?\n\nEsta alerta enviará tu ubicación a todos los equipos de emergencia como PRIORIDAD MÁXIMA.')) {
                const sosBtn = document.getElementById('sosBtn');
                sosBtn.disabled = true;
                sosBtn.innerHTML = '⏳ ENVIANDO...';
                sosBtn.style.background = '#6c757d';
                
                // Enviar formulario
                document.getElementById('sosForm').submit();
            }
        }

        // Reintentar obtener ubicación cada 10 segundos
        setInterval(() => {
            if (!ubicacionObtenida) {
                obtenerUbicacion();
            }
        }, 10000);
    </script>
</body>
</html>