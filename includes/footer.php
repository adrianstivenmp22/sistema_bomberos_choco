<?php
// includes/footer.php

/**
 * Footer del sistema - Bomberos del Chocó
 */

// Obtener estadísticas rápidas si está disponible la base de datos
$stats = [];
try {
    $db = connectDatabase();
    $stats = getSystemStats($db);
} catch (Exception $e) {
    // Silenciar error en footer
}

?>
    </main> <!-- Cierre del container principal -->

    <!-- Footer -->
    <footer style="background: var(--dark-color); color: white; padding: var(--spacing-xl) 0; margin-top: var(--spacing-xl);">
        <div class="container">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: var(--spacing-lg);">
                
                <!-- Información de contacto -->
                <div>
                    <h3 style="color: white; margin-bottom: var(--spacing-md);">🚒 Bomberos del Chocó</h3>
                    <p>📍 Quibdó, Chocó, Colombia</p>
                    <p>📞 <strong>123</strong> - Línea de emergencias</p>
                    <p>📞 <strong>672 1234</strong> - Central</p>
                    <p>📧 <strong>contacto@bomberoschoco.gov.co</strong></p>
                </div>
                
                <!-- Enlaces rápidos -->
                <div>
                    <h3 style="color: white; margin-bottom: var(--spacing-md);">🔗 Enlaces Rápidos</h3>
                    <ul style="list-style: none; padding: 0;">
                        <li style="margin-bottom: var(--spacing-sm);">
                            <a href="/sistema_bomberos_choco/index.php" style="color: #ccc; text-decoration: none;">
                                🏠 Página de Inicio
                            </a>
                        </li>
                        <li style="margin-bottom: var(--spacing-sm);">
                            <a href="/sistema_bomberos_choco/modules/ciudadano/reporte.php" style="color: #ccc; text-decoration: none;">
                                🚨 Reportar Emergencia
                            </a>
                        </li>
                        <li style="margin-bottom: var(--spacing-sm);">
                            <a href="/sistema_bomberos_choco/modules/ciudadano/sos.php" style="color: #ccc; text-decoration: none;">
                                🆘 Botón SOS
                            </a>
                        </li>
                        <?php if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin'): ?>
                        <li style="margin-bottom: var(--spacing-sm);">
                            <a href="/sistema_bomberos_choco/modules/admin/sistema.php" style="color: #ccc; text-decoration: none;">
                                ⚙️ Administración
                            </a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </div>
                
                <!-- Estadísticas del sistema -->
                <div>
                    <h3 style="color: white; margin-bottom: var(--spacing-md);">📊 Estadísticas</h3>
                    <?php if (!empty($stats)): ?>
                        <p>👥 Usuarios activos: 
                            <?php 
                            $total_usuarios = 0;
                            foreach ($stats['usuarios'] as $usuario) {
                                $total_usuarios += $usuario['total'];
                            }
                            echo $total_usuarios;
                            ?>
                        </p>
                        <p>🚨 Emergencias hoy: <?php echo $stats['emergencias_hoy']['total'] ?? 0; ?></p>
                        <p>👨‍🚒 Bomberos disp.: <?php echo $stats['bomberos']['disponibles'] ?? 0; ?></p>
                        <p>⏱️ Tiempo respuesta: <?php echo round($stats['tiempos']['asignacion'] ?? 0); ?> min</p>
                    <?php else: ?>
                        <p>Sistema en funcionamiento</p>
                        <p>Versión <?php echo APP_VERSION; ?></p>
                    <?php endif; ?>
                </div>
                
                <!-- Información del sistema -->
                <div>
                    <h3 style="color: white; margin-bottom: var(--spacing-md);">ℹ️ Sistema</h3>
                    <p>🕒 Hora servidor: <?php echo date('H:i:s'); ?></p>
                    <p>📅 Fecha: <?php echo date('d/m/Y'); ?></p>
                    <p>🌐 Zona horaria: <?php echo APP_TIMEZONE; ?></p>
                    <p>⚡ Entorno: <?php echo ENVIRONMENT; ?></p>
                </div>
            </div>
            
            <!-- Línea separadora -->
            <hr style="border-color: #555; margin: var(--spacing-lg) 0;">
            
            <!-- Copyright y información legal -->
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: var(--spacing-md);">
                <div>
                    <p style="margin: 0; color: #ccc;">
                        &copy; <?php echo date('Y'); ?> Bomberos del Chocó. 
                        <span style="font-size: 0.9em;">Todos los derechos reservados.</span>
                    </p>
                </div>
                
                <div style="display: flex; gap: var(--spacing-lg);">
                    <a href="#" style="color: #ccc; text-decoration: none; font-size: 0.9em;">📄 Términos</a>
                    <a href="#" style="color: #ccc; text-decoration: none; font-size: 0.9em;">🔒 Privacidad</a>
                    <a href="#" style="color: #ccc; text-decoration: none; font-size: 0.9em;">📞 Contacto</a>
                </div>
                
                <div>
                    <p style="margin: 0; color: #ccc; font-size: 0.8em;">
                        <?php echo APP_NAME; ?> v<?php echo APP_VERSION; ?> 
                        - <?php echo ENVIRONMENT === 'development' ? '🔧 Desarrollo' : '🚀 Producción'; ?>
                    </p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Scripts comunes -->
    <script>
        // Funciones globales del sistema
        const SystemConfig = {
            appName: '<?php echo APP_NAME; ?>',
            appVersion: '<?php echo APP_VERSION; ?>',
            environment: '<?php echo ENVIRONMENT; ?>',
            autoRefresh: <?php echo AUTO_REFRESH_INTERVAL; ?>,
            baseUrl: '<?php echo APP_URL; ?>'
        };

        // Mostrar notificaciones toast
        function showToast(message, type = 'info') {
            const toast = document.createElement('div');
            toast.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 15px 20px;
                border-radius: 5px;
                color: white;
                z-index: 10000;
                animation: slideIn 0.3s ease;
                max-width: 300px;
            `;
            
            const colors = {
                success: '#28a745',
                error: '#dc3545',
                warning: '#ffc107',
                info: '#17a2b8'
            };
            
            toast.style.background = colors[type] || colors.info;
            toast.innerHTML = message;
            
            document.body.appendChild(toast);
            
            setTimeout(() => {
                toast.style.animation = 'slideOut 0.3s ease';
                setTimeout(() => {
                    document.body.removeChild(toast);
                }, 300);
            }, 5000);
        }

        // Auto-refrescar páginas que lo necesiten
        function setupAutoRefresh() {
            const refreshElements = document.querySelectorAll('[data-auto-refresh]');
            refreshElements.forEach(element => {
                const interval = parseInt(element.getAttribute('data-auto-refresh')) || SystemConfig.autoRefresh;
                setInterval(() => {
                    window.location.reload();
                }, interval);
            });
        }

        // Confirmación para acciones peligrosas
        function confirmAction(message, callback) {
            if (confirm(message)) {
                callback();
            }
        }

        // Formatear fecha
        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('es-CO') + ' ' + date.toLocaleTimeString('es-CO');
        }

        // Inicializar cuando el DOM esté listo
        document.addEventListener('DOMContentLoaded', function() {
            setupAutoRefresh();
            
            // Agregar estilos para animaciones
            const style = document.createElement('style');
            style.textContent = `
                @keyframes slideIn {
                    from { transform: translateX(100%); opacity: 0; }
                    to { transform: translateX(0); opacity: 1; }
                }
                @keyframes slideOut {
                    from { transform: translateX(0); opacity: 1; }
                    to { transform: translateX(100%); opacity: 0; }
                }
            `;
            document.head.appendChild(style);
            
            // Mostrar hora actual en elementos con data-show-time
            const timeElements = document.querySelectorAll('[data-show-time]');
            timeElements.forEach(element => {
                element.textContent = new Date().toLocaleTimeString('es-CO');
            });
            
            // Actualizar hora cada segundo
            setInterval(() => {
                timeElements.forEach(element => {
                    element.textContent = new Date().toLocaleTimeString('es-CO');
                });
            }, 1000);
        });

        // Manejar errores globales
        window.addEventListener('error', function(e) {
            console.error('Error global:', e.error);
            
            if (SystemConfig.environment === 'development') {
                showToast('❌ Error: ' + e.error.message, 'error');
            }
        });

        // Prevenir envíos duplicados de formularios
        document.addEventListener('submit', function(e) {
            const form = e.target;
            const submitBtn = form.querySelector('button[type="submit"], input[type="submit"]');
            
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '⏳ Procesando...';
                
                // Re-enable después de 10 segundos por si hay error
                setTimeout(() => {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = submitBtn.getAttribute('data-original-text') || 'Enviar';
                }, 10000);
            }
        });

        // Guardar texto original de botones de envío
        document.addEventListener('DOMContentLoaded', function() {
            const submitBtns = document.querySelectorAll('button[type="submit"], input[type="submit"]');
            submitBtns.forEach(btn => {
                btn.setAttribute('data-original-text', btn.innerHTML);
            });
        });
    </script>

    <!-- Scripts específicos de la página -->
    <?php if (isset($page_scripts)): ?>
        <?php foreach ($page_scripts as $script): ?>
            <script src="<?php echo $script; ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>

</body>
</html>