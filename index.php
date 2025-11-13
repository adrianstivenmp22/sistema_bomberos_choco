<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Formularios y Mapa Interactivo</title>
    <style>
        :root {
            --primary-color: #3498db;
            --secondary-color: #2c3e50;
            --accent-color: #e74c3c;
            --success-color: #27ae60;
            --warning-color: #f39c12;
            --light-color: #ecf0f1;
            --dark-color: #34495e;
            --gray-color: #95a5a6;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: var(--dark-color);
            line-height: 1.6;
            min-height: 100vh;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        header {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            text-align: center;
            backdrop-filter: blur(10px);
        }
        
        header h1 {
            color: var(--secondary-color);
            margin-bottom: 0.5rem;
            font-size: 2.5rem;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        header p {
            color: var(--gray-color);
            font-size: 1.1rem;
        }
        
        .main-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }
        
        @media (max-width: 968px) {
            .main-content {
                grid-template-columns: 1fr;
            }
        }
        
        .card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            padding: 2rem;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
        }
        
        .card h2 {
            color: var(--secondary-color);
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid var(--light-color);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .card h2 i {
            color: var(--primary-color);
        }
        
        .form-tabs {
            display: flex;
            margin-bottom: 1.5rem;
            border-bottom: 1px solid #e1e8ed;
            background: rgba(236, 240, 241, 0.5);
            border-radius: 10px;
            padding: 5px;
        }
        
        .tab {
            flex: 1;
            padding: 12px 20px;
            text-align: center;
            cursor: pointer;
            background: transparent;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            color: var(--gray-color);
            transition: all 0.3s ease;
        }
        
        .tab.active {
            background: white;
            color: var(--primary-color);
            box-shadow: 0 2px 8px rgba(52, 152, 219, 0.2);
        }
        
        .tab:hover:not(.active) {
            background: rgba(255, 255, 255, 0.7);
            color: var(--dark-color);
        }
        
        .tab-content {
            display: none;
            animation: fadeIn 0.5s ease;
        }
        
        .tab-content.active {
            display: block;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--dark-color);
        }
        
        .input-icon {
            position: relative;
        }
        
        .input-icon i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray-color);
        }
        
        .input-icon input,
        .input-icon select,
        .input-icon textarea {
            padding-left: 45px;
        }
        
        input, select, textarea {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e1e8ed;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: white;
        }
        
        input:focus, select:focus, textarea:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }
        
        input.error, select.error, textarea.error {
            border-color: var(--accent-color);
            box-shadow: 0 0 0 3px rgba(231, 76, 60, 0.1);
        }
        
        input.success, select.success, textarea.success {
            border-color: var(--success-color);
            box-shadow: 0 0 0 3px rgba(39, 174, 96, 0.1);
        }
        
        .error-message {
            color: var(--accent-color);
            font-size: 0.85rem;
            margin-top: 5px;
            display: block;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            background: linear-gradient(135deg, var(--primary-color), #2980b9);
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(52, 152, 219, 0.3);
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(52, 152, 219, 0.4);
        }
        
        .btn:active {
            transform: translateY(0);
        }
        
        .btn-block {
            display: flex;
            width: 100%;
        }
        
        .btn-success {
            background: linear-gradient(135deg, var(--success-color), #229954);
            box-shadow: 0 4px 15px rgba(39, 174, 96, 0.3);
        }
        
        .map-container {
            height: 400px;
            border-radius: 15px;
            overflow: hidden;
            margin-top: 1rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            border: 3px solid white;
        }
        
        #map {
            height: 100%;
            width: 100%;
            border-radius: 12px;
        }
        
        .puntos-interes {
            margin-top: 1.5rem;
        }
        
        .puntos-interes h3 {
            margin-bottom: 1rem;
            color: var(--secondary-color);
        }
        
        .puntos-lista {
            list-style: none;
            max-height: 200px;
            overflow-y: auto;
        }
        
        .punto-interes-item {
            background: rgba(236, 240, 241, 0.5);
            padding: 12px 15px;
            margin-bottom: 8px;
            border-radius: 8px;
            border-left: 4px solid var(--primary-color);
            transition: all 0.3s ease;
        }
        
        .punto-interes-item:hover {
            background: rgba(52, 152, 219, 0.1);
            transform: translateX(5px);
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        
        @media (max-width: 576px) {
            .form-row {
                grid-template-columns: 1fr;
            }
        }
        
        .radio-group, .checkbox-group {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        
        .radio-option, .checkbox-option {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .radio-option input, .checkbox-option input {
            width: auto;
        }
        
        .form-message {
            padding: 12px 15px;
            border-radius: 10px;
            margin-top: 1rem;
            display: none;
            animation: slideIn 0.5s ease;
        }
        
        @keyframes slideIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .success {
            background: rgba(39, 174, 96, 0.1);
            border: 1px solid var(--success-color);
            color: var(--success-color);
        }
        
        .error {
            background: rgba(231, 76, 60, 0.1);
            border: 1px solid var(--accent-color);
            color: var(--accent-color);
        }
        
        .loading-spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s ease-in-out infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        footer {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            padding: 1.5rem;
            text-align: center;
            color: var(--gray-color);
            backdrop-filter: blur(10px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .user-info {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: rgba(52, 152, 219, 0.1);
            padding: 10px 15px;
            border-radius: 10px;
            margin-bottom: 1rem;
        }
        
        .stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            margin-top: 1.5rem;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 1rem;
            border-radius: 10px;
            text-align: center;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            font-size: 0.9rem;
            opacity: 0.9;
        }
        
        /* Estilos para el mapa simulado */
        .mapa-simulado {
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #74b9ff, #0984e3);
            position: relative;
            border-radius: 12px;
            overflow: hidden;
        }
        
        .mapa-contenido {
            width: 100%;
            height: 100%;
            position: relative;
        }
        
        .mapa-overlay {
            position: absolute;
            top: 10px;
            left: 10px;
            background: rgba(255, 255, 255, 0.95);
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            z-index: 1000;
            max-width: 250px;
        }
        
        .controles-mapa {
            display: flex;
            gap: 5px;
            margin-top: 10px;
        }
        
        .btn-mapa {
            padding: 8px 12px;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
        }
        
        .puntos-interes-container {
            position: absolute;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            justify-content: center;
            max-width: 90%;
        }
        
        .punto-interes-marker {
            background: rgba(255, 255, 255, 0.95);
            padding: 8px 15px;
            border-radius: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            cursor: pointer;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }
        
        .punto-interes-marker.activo {
            border-color: var(--accent-color);
            background: #fff;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }
            
            header {
                padding: 1.5rem;
            }
            
            header h1 {
                font-size: 2rem;
            }
            
            .card {
                padding: 1.5rem;
            }
            
            .stats {
                grid-template-columns: 1fr;
            }
        }
    </style>
    <!-- Font Awesome para iconos -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <header>
            <h1><i class="fas fa-map-marked-alt"></i> Sistema de Formularios y Mapa</h1>
            <p>Gestiona tus registros, contactos y explora ubicaciones en el mapa interactivo</p>
            
            <div class="user-info">
                <div>
                    <i class="fas fa-user"></i>
                    <span id="nombre-usuario">Usuario Demo</span>
                </div>
                <div>
                    <i class="fas fa-calendar"></i>
                    <?php echo date('d/m/Y'); ?>
                </div>
            </div>
            
            <div class="stats">
                <div class="stat-card">
                    <div class="stat-number" id="total-registros">0</div>
                    <div class="stat-label">Registros Hoy</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" id="total-contactos">0</div>
                    <div class="stat-label">Contactos</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" id="total-puntos">5</div>
                    <div class="stat-label">Puntos de Interés</div>
                </div>
            </div>
        </header>
        
        <div class="main-content">
            <div class="card">
                <div class="form-tabs">
                    <button class="tab active" data-tab="registro">
                        <i class="fas fa-user-plus"></i> Registro
                    </button>
                    <button class="tab" data-tab="contacto">
                        <i class="fas fa-envelope"></i> Contacto
                    </button>
                    <button class="tab" data-tab="encuesta">
                        <i class="fas fa-poll"></i> Encuesta
                    </button>
                </div>
                
                <div id="registro" class="tab-content active">
                    <h2><i class="fas fa-user-edit"></i> Formulario de Registro</h2>
                    <form id="formRegistro">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="nombre"><i class="fas fa-id-card"></i> Nombre</label>
                                <div class="input-icon">
                                    <i class="fas fa-user"></i>
                                    <input type="text" id="nombre" name="nombre" placeholder="Ingresa tu nombre" required>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="apellido"><i class="fas fa-id-card"></i> Apellido</label>
                                <div class="input-icon">
                                    <i class="fas fa-user"></i>
                                    <input type="text" id="apellido" name="apellido" placeholder="Ingresa tu apellido" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="email"><i class="fas fa-envelope"></i> Email</label>
                            <div class="input-icon">
                                <i class="fas fa-at"></i>
                                <input type="email" id="email" name="email" placeholder="tu@email.com" required>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="password"><i class="fas fa-lock"></i> Contraseña</label>
                                <div class="input-icon">
                                    <i class="fas fa-key"></i>
                                    <input type="password" id="password" name="password" placeholder="Mínimo 6 caracteres" required>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="confirmPassword"><i class="fas fa-lock"></i> Confirmar Contraseña</label>
                                <div class="input-icon">
                                    <i class="fas fa-key"></i>
                                    <input type="password" id="confirmPassword" name="confirmPassword" placeholder="Repite tu contraseña" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="pais"><i class="fas fa-globe-americas"></i> País</label>
                            <div class="input-icon">
                                <i class="fas fa-flag"></i>
                                <select id="pais" name="pais">
                                    <option value="">Seleccione un país</option>
                                    <option value="es">España</option>
                                    <option value="mx">México</option>
                                    <option value="ar">Argentina</option>
                                    <option value="co">Colombia</option>
                                    <option value="pe">Perú</option>
                                    <option value="cl">Chile</option>
                                    <option value="ve">Venezuela</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="telefono"><i class="fas fa-phone"></i> Teléfono</label>
                            <div class="input-icon">
                                <i class="fas fa-mobile-alt"></i>
                                <input type="tel" id="telefono" name="telefono" placeholder="+34 123 456 789">
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-block">
                            <i class="fas fa-user-plus"></i> Registrarse
                        </button>
                        
                        <div id="messageRegistro" class="form-message"></div>
                    </form>
                </div>
                
                <div id="contacto" class="tab-content">
                    <h2><i class="fas fa-headset"></i> Formulario de Contacto</h2>
                    <form id="formContacto">
                        <div class="form-group">
                            <label for="nombreContacto"><i class="fas fa-user"></i> Nombre Completo</label>
                            <div class="input-icon">
                                <i class="fas fa-user"></i>
                                <input type="text" id="nombreContacto" name="nombreContacto" placeholder="Tu nombre completo" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="emailContacto"><i class="fas fa-envelope"></i> Email</label>
                            <div class="input-icon">
                                <i class="fas fa-at"></i>
                                <input type="email" id="emailContacto" name="emailContacto" placeholder="tu@email.com" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="asunto"><i class="fas fa-tag"></i> Asunto</label>
                            <div class="input-icon">
                                <i class="fas fa-heading"></i>
                                <input type="text" id="asunto" name="asunto" placeholder="Motivo de tu contacto" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="departamento"><i class="fas fa-building"></i> Departamento</label>
                            <div class="input-icon">
                                <i class="fas fa-sitemap"></i>
                                <select id="departamento" name="departamento" required>
                                    <option value="">Selecciona un departamento</option>
                                    <option value="ventas">Ventas</option>
                                    <option value="soporte">Soporte Técnico</option>
                                    <option value="administracion">Administración</option>
                                    <option value="gerencia">Gerencia</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="mensaje"><i class="fas fa-comment-dots"></i> Mensaje</label>
                            <div class="input-icon">
                                <i class="fas fa-edit"></i>
                                <textarea id="mensaje" name="mensaje" rows="5" placeholder="Describe tu consulta o mensaje..." required></textarea>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-block btn-success">
                            <i class="fas fa-paper-plane"></i> Enviar Mensaje
                        </button>
                        
                        <div id="messageContacto" class="form-message"></div>
                    </form>
                </div>
                
                <div id="encuesta" class="tab-content">
                    <h2><i class="fas fa-clipboard-check"></i> Encuesta de Satisfacción</h2>
                    <form id="formEncuesta">
                        <div class="form-group">
                            <label><i class="fas fa-star"></i> ¿Cómo calificaría nuestro servicio?</label>
                            <div class="radio-group">
                                <div class="radio-option">
                                    <input type="radio" id="excelente" name="calificacion" value="excelente">
                                    <label for="excelente">Excelente <i class="fas fa-smile-beam" style="color: #27ae60;"></i></label>
                                </div>
                                <div class="radio-option">
                                    <input type="radio" id="bueno" name="calificacion" value="bueno">
                                    <label for="bueno">Bueno <i class="fas fa-smile" style="color: #3498db;"></i></label>
                                </div>
                                <div class="radio-option">
                                    <input type="radio" id="regular" name="calificacion" value="regular">
                                    <label for="regular">Regular <i class="fas fa-meh" style="color: #f39c12;"></i></label>
                                </div>
                                <div class="radio-option">
                                    <input type="radio" id="malo" name="calificacion" value="malo">
                                    <label for="malo">Malo <i class="fas fa-frown" style="color: #e74c3c;"></i></label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-check-circle"></i> ¿Qué servicios ha utilizado?</label>
                            <div class="checkbox-group">
                                <div class="checkbox-option">
                                    <input type="checkbox" id="servicio1" name="servicios[]" value="web">
                                    <label for="servicio1">Desarrollo Web</label>
                                </div>
                                <div class="checkbox-option">
                                    <input type="checkbox" id="servicio2" name="servicios[]" value="movil">
                                    <label for="servicio2">Aplicaciones Móviles</label>
                                </div>
                                <div class="checkbox-option">
                                    <input type="checkbox" id="servicio3" name="servicios[]" value="consultoria">
                                    <label for="servicio3">Consultoría</label>
                                </div>
                                <div class="checkbox-option">
                                    <input type="checkbox" id="servicio4" name="servicios[]" value="soporte">
                                    <label for="servicio4">Soporte Técnico</label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="sugerencias"><i class="fas fa-lightbulb"></i> ¿Tiene alguna sugerencia para mejorar?</label>
                            <div class="input-icon">
                                <i class="fas fa-edit"></i>
                                <textarea id="sugerencias" name="sugerencias" rows="4" placeholder="Tus sugerencias son muy valiosas para nosotros..."></textarea>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="recomendacion"><i class="fas fa-share-alt"></i> ¿Recomendaría nuestros servicios?</label>
                            <div class="input-icon">
                                <i class="fas fa-thumbs-up"></i>
                                <select id="recomendacion" name="recomendacion">
                                    <option value="">Seleccione una opción</option>
                                    <option value="si">Sí, definitivamente</option>
                                    <option value="probable">Probablemente</option>
                                    <option value="talvez">Tal vez</option>
                                    <option value="no">No</option>
                                </select>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-block">
                            <i class="fas fa-paper-plane"></i> Enviar Encuesta
                        </button>
                        
                        <div id="messageEncuesta" class="form-message"></div>
                    </form>
                </div>
            </div>
            
            <div class="card">
                <h2><i class="fas fa-map-marked-alt"></i> Mapa Interactivo</h2>
                <p>Explora nuestras ubicaciones y puntos de interés en el mapa.</p>
                
                <div class="map-container">
                    <div id="map">
                        <!-- El mapa se cargará aquí mediante JavaScript -->
                    </div>
                </div>
                
                <div class="puntos-interes">
                    <h3><i class="fas fa-map-marker-alt"></i> Puntos de Interés</h3>
                    <ul class="puntos-lista" id="puntos-interes">
                        <!-- Los puntos de interés se cargarán dinámicamente -->
                    </ul>
                </div>
            </div>
        </div>
        
        <footer>
            <p>&copy; <?php echo date('Y'); ?> Sistema de Formularios y Mapa Interactivo. Todos los derechos reservados.</p>
            <p>Desarrollado con <i class="fas fa-heart" style="color: #e74c3c;"></i> usando PHP, JavaScript y CSS3</p>
        </footer>
    </div>

    <!-- Scripts JavaScript -->
    <script src="formularios.js"></script>
    <script src="mapa.js"></script>
    <script src="main.js"></script>
    
    <script>
        // Inicialización adicional para la interfaz
        document.addEventListener('DOMContentLoaded', function() {
            // Actualizar estadísticas
            actualizarEstadisticas();
            
            // Configurar fecha actual
            document.getElementById('fecha-actual').textContent = new Date().toLocaleDateString('es-ES', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
        });
        
        function actualizarEstadisticas() {
            // Simular datos de estadísticas
            document.getElementById('total-registros').textContent = Math.floor(Math.random() * 50) + 10;
            document.getElementById('total-contactos').textContent = Math.floor(Math.random() * 100) + 25;
        }
        
        // Simular datos para puntos de interés
        const puntosInteres = [
            {
                nombre: 'Oficina Central',
                direccion: 'Plaza Mayor, 1, Madrid',
                horario: 'L-V: 9:00-18:00'
            },
            {
                nombre: 'Sucursal Norte',
                direccion: 'Diagonal, 123, Barcelona',
                horario: 'L-V: 8:00-17:00'
            },
            {
                nombre: 'Sucursal Sur',
                direccion: 'Avenida de la Constitución, 45, Sevilla',
                horario: 'L-V: 9:30-18:30'
            },
            {
                nombre: 'Centro de Distribución',
                direccion: 'Calle del Mar, 78, Valencia',
                horario: 'L-D: 24 horas'
            },
            {
                nombre: 'Punto de Atención',
                direccion: 'Gran Vía, 25, Bilbao',
                horario: 'L-S: 10:00-20:00'
            }
        ];
        
        // Cargar puntos de interés en la lista
        document.addEventListener('DOMContentLoaded', function() {
            const lista = document.getElementById('puntos-interes');
            puntosInteres.forEach(punto => {
                const li = document.createElement('li');
                li.className = 'punto-interes-item';
                li.innerHTML = `
                    <strong>${punto.nombre}</strong><br>
                    <small>${punto.direccion}</small><br>
                    <em>${punto.horario}</em>
                `;
                lista.appendChild(li);
            });
        });
    </script>
</body>
</html>