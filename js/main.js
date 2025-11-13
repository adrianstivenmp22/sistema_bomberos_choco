// main.js - Archivo principal que coordina la aplicación
class Aplicacion {
    constructor() {
        this.formularios = null;
        this.mapa = null;
        this.datosUsuario = null;
        this.config = {
            apiBaseUrl: 'https://api.ejemplo.com',
            maxIntentosLogin: 3,
            tiempoSesion: 30 * 60 * 1000 // 30 minutos
        };

        this.inicializarAplicacion();
    }

    async inicializarAplicacion() {
        try {
            console.log('Inicializando aplicación...');
            
            // Mostrar pantalla de carga
            this.mostrarLoading();
            
            // Inicializar módulos
            await this.inicializarModulos();
            
            // Configurar eventos globales
            this.configurarEventosGlobales();
            
            // Verificar autenticación
            await this.verificarAutenticacion();
            
            // Cargar datos iniciales
            await this.cargarDatosIniciales();
            
            // Ocultar loading
            this.ocultarLoading();
            
            console.log('Aplicación inicializada correctamente');
            
        } catch (error) {
            console.error('Error al inicializar la aplicación:', error);
            this.mostrarError('Error al cargar la aplicación. Por favor, recargue la página.');
        }
    }

    async inicializarModulos() {
        // Inicializar módulo de formularios
        if (typeof Formularios !== 'undefined') {
            this.formularios = new Formularios();
            console.log('Módulo de formularios inicializado');
        } else {
            throw new Error('Módulo de formularios no disponible');
        }

        // Inicializar módulo de mapa
        if (typeof MapaInteractivo !== 'undefined') {
            this.mapa = window.mapaInteractivo;
            console.log('Módulo de mapa inicializado');
        }
    }

    configurarEventosGlobales() {
        // Configurar manejo de errores global
        window.addEventListener('error', (event) => {
            this.manejarErrorGlobal(event.error);
        });

        // Configurar eventos de navegación
        this.configurarNavegacion();
        
        // Configurar eventos de teclado
        this.configurarEventosTeclado();
        
        // Configurar manejo de sesión
        this.configurarManejoSesion();
    }

    configurarNavegacion() {
        // Navegación entre secciones
        const enlacesNavegacion = document.querySelectorAll('[data-navegacion]');
        enlacesNavegacion.forEach(enlace => {
            enlace.addEventListener('click', (e) => {
                e.preventDefault();
                const seccion = enlace.getAttribute('data-navegacion');
                this.navegarA(seccion);
            });
        });
    }

    configurarEventosTeclado() {
        document.addEventListener('keydown', (e) => {
            // Atajos de teclado
            switch(e.key) {
                case 'Escape':
                    this.cerrarModales();
                    break;
                case 'F1':
                    e.preventDefault();
                    this.mostrarAyuda();
                    break;
            }
        });
    }

    configurarManejoSesion() {
        // Verificar inactividad
        this.iniciarTemporizadorInactividad();
        
        // Configurar eventos de actividad
        ['click', 'mousemove', 'keypress'].forEach(evento => {
            document.addEventListener(evento, () => {
                this.reiniciarTemporizadorInactividad();
            });
        });
    }

    iniciarTemporizadorInactividad() {
        this.temporizadorInactividad = setTimeout(() => {
            this.manejarInactividad();
        }, this.config.tiempoSesion);
    }

    reiniciarTemporizadorInactividad() {
        if (this.temporizadorInactividad) {
            clearTimeout(this.temporizadorInactividad);
        }
        this.iniciarTemporizadorInactividad();
    }

    async manejarInactividad() {
        if (this.datosUsuario) {
            console.log('Sesión expirada por inactividad');
            this.cerrarSesion();
            this.mostrarNotificacion('Su sesión ha expirado por inactividad', 'warning');
        }
    }

    navegarA(seccion) {
        console.log('Navegando a:', seccion);
        
        // Ocultar todas las secciones
        document.querySelectorAll('.seccion').forEach(sec => {
            sec.style.display = 'none';
        });
        
        // Mostrar sección objetivo
        const seccionObjetivo = document.getElementById(seccion);
        if (seccionObjetivo) {
            seccionObjetivo.style.display = 'block';
            
            // Ejecutar acciones específicas de la sección
            this.ejecutarAccionesSeccion(seccion);
        }
    }

    ejecutarAccionesSeccion(seccion) {
        switch(seccion) {
            case 'mapa':
                if (this.mapa) {
                    this.mapa.resetearVista();
                }
                break;
            case 'formularios':
                // Reiniciar formularios si es necesario
                break;
        }
    }

    async verificarAutenticacion() {
        const token = localStorage.getItem('authToken');
        if (token) {
            try {
                // Verificar token con el servidor
                const usuario = await this.verificarToken(token);
                this.datosUsuario = usuario;
                this.actualizarUIautenticado();
            } catch (error) {
                console.error('Token inválido:', error);
                this.cerrarSesion();
            }
        } else {
            this.actualizarUINoAutenticado();
        }
    }

    async verificarToken(token) {
        // Simular verificación de token
        return new Promise((resolve) => {
            setTimeout(() => {
                resolve({
                    id: 1,
                    nombre: 'Usuario Demo',
                    email: 'demo@ejemplo.com',
                    rol: 'usuario'
                });
            }, 500);
        });
    }

    actualizarUIautenticado() {
        // Actualizar interfaz para usuario autenticado
        const elementosAutenticado = document.querySelectorAll('.usuario-autenticado');
        const elementosNoAutenticado = document.querySelectorAll('.usuario-no-autenticado');
        
        elementosAutenticado.forEach(el => el.style.display = 'block');
        elementosNoAutenticado.forEach(el => el.style.display = 'none');
        
        // Actualizar información del usuario
        const nombreUsuario = document.getElementById('nombre-usuario');
        if (nombreUsuario && this.datosUsuario) {
            nombreUsuario.textContent = this.datosUsuario.nombre;
        }
    }

    actualizarUINoAutenticado() {
        // Actualizar interfaz para usuario no autenticado
        const elementosAutenticado = document.querySelectorAll('.usuario-autenticado');
        const elementosNoAutenticado = document.querySelectorAll('.usuario-no-autenticado');
        
        elementosAutenticado.forEach(el => el.style.display = 'none');
        elementosNoAutenticado.forEach(el => el.style.display = 'block');
    }

    async cargarDatosIniciales() {
        try {
            // Cargar datos necesarios para la aplicación
            await Promise.all([
                this.cargarConfiguracion(),
                this.cargarDatosGeograficos(),
                this.cargarPreferenciasUsuario()
            ]);
            
            console.log('Datos iniciales cargados correctamente');
        } catch (error) {
            console.warn('Error al cargar algunos datos iniciales:', error);
        }
    }

    async cargarConfiguracion() {
        // Simular carga de configuración
        return new Promise((resolve) => {
            setTimeout(() => {
                this.configuracion = {
                    tema: 'claro',
                    idioma: 'es',
                    notificaciones: true
                };
                resolve();
            }, 300);
        });
    }

    async cargarDatosGeograficos() {
        // Simular carga de datos geográficos
        return new Promise((resolve) => {
            setTimeout(() => {
                resolve();
            }, 200);
        });
    }

    async cargarPreferenciasUsuario() {
        if (this.datosUsuario) {
            // Cargar preferencias del usuario
            const preferencias = localStorage.getItem(`preferencias_${this.datosUsuario.id}`);
            if (preferencias) {
                this.preferenciasUsuario = JSON.parse(preferencias);
            }
        }
    }

    // Métodos de utilidad
    mostrarLoading(mensaje = 'Cargando...') {
        // Crear o mostrar overlay de loading
        let loading = document.getElementById('loading-overlay');
        
        if (!loading) {
            loading = document.createElement('div');
            loading.id = 'loading-overlay';
            loading.innerHTML = `
                <div class="loading-contenido">
                    <div class="spinner"></div>
                    <p>${mensaje}</p>
                </div>
            `;
            loading.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.7);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 9999;
                color: white;
            `;
            
            document.body.appendChild(loading);
        }
        
        loading.style.display = 'flex';
    }

    ocultarLoading() {
        const loading = document.getElementById('loading-overlay');
        if (loading) {
            loading.style.display = 'none';
        }
    }

    mostrarError(mensaje) {
        this.mostrarNotificacion(mensaje, 'error');
    }

    mostrarExito(mensaje) {
        this.mostrarNotificacion(mensaje, 'success');
    }

    mostrarNotificacion(mensaje, tipo = 'info') {
        // Crear notificación
        const notificacion = document.createElement('div');
        notificacion.className = `notificacion notificacion-${tipo}`;
        notificacion.innerHTML = `
            <div class="notificacion-contenido">
                <span class="notificacion-mensaje">${mensaje}</span>
                <button class="notificacion-cerrar" onclick="this.parentElement.parentElement.remove()">×</button>
            </div>
        `;
        
        // Estilos básicos
        notificacion.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: ${this.obtenerColorNotificacion(tipo)};
            color: white;
            padding: 15px 20px;
            border-radius: 5px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            z-index: 10000;
            max-width: 400px;
            animation: slideInRight 0.3s ease;
        `;
        
        document.body.appendChild(notificacion);
        
        // Auto-eliminar después de 5 segundos
        setTimeout(() => {
            if (notificacion.parentElement) {
                notificacion.remove();
            }
        }, 5000);
    }

    obtenerColorNotificacion(tipo) {
        const colores = {
            success: '#27ae60',
            error: '#e74c3c',
            warning: '#f39c12',
            info: '#3498db'
        };
        return colores[tipo] || colores.info;
    }

    manejarErrorGlobal(error) {
        console.error('Error global capturado:', error);
        
        // En producción, enviar error a servicio de monitoreo
        if (this.config.monitoringEnabled) {
            this.enviarErrorMonitoreo(error);
        }
        
        // Mostrar error al usuario si es crítico
        if (this.esErrorCritico(error)) {
            this.mostrarError('Ha ocurrido un error inesperado. Por favor, recargue la página.');
        }
    }

    esErrorCritico(error) {
        // Determinar si el error es crítico
        const erroresNoCriticos = [
            'NetworkError',
            'TimeoutError'
        ];
        
        return !erroresNoCriticos.some(tipo => error.name.includes(tipo));
    }

    enviarErrorMonitoreo(error) {
        // Simular envío a servicio de monitoreo
        console.log('Enviando error a servicio de monitoreo:', error);
    }

    cerrarModales() {
        // Cerrar todos los modales abiertos
        document.querySelectorAll('.modal, .info-window').forEach(modal => {
            modal.remove();
        });
    }

    mostrarAyuda() {
        alert('Ayuda de la aplicación:\n\n• Use las pestañas para navegar entre formularios\n• Haga clic en los puntos del mapa para ver información\n• Use ESC para cerrar ventanas emergentes');
    }

    cerrarSesion() {
        localStorage.removeItem('authToken');
        this.datosUsuario = null;
        this.actualizarUINoAutenticado();
        this.mostrarNotificacion('Sesión cerrada correctamente', 'info');
    }

    // Métodos para integración entre módulos
    sincronizarDatosFormularioMapa(datosFormulario) {
        if (this.mapa && datosFormulario.ubicacion) {
            // Agregar punto de interés basado en datos del formulario
            const nuevoPunto = {
                id: Date.now(),
                nombre: datosFormulario.nombre || 'Nueva ubicación',
                tipo: 'personalizado',
                coordenadas: datosFormulario.ubicacion,
                direccion: datosFormulario.direccion || 'Dirección no especificada',
                horario: 'Horario no especificado'
            };
            
            this.mapa.agregarPuntoInteres(nuevoPunto);
        }
    }

    // Métodos de persistencia
    guardarPreferencias() {
        if (this.datosUsuario) {
            localStorage.setItem(
                `preferencias_${this.datosUsuario.id}`, 
                JSON.stringify(this.preferenciasUsuario)
            );
        }
    }

    // Métodos de limpieza
    destruir() {
        if (this.temporizadorInactividad) {
            clearTimeout(this.temporizadorInactividad);
        }
        
        // Limpiar event listeners
        window.removeEventListener('error', this.manejarErrorGlobal);
        
        console.log('Aplicación destruida');
    }
}

// Inicializar aplicación cuando el DOM esté listo
let aplicacion;

document.addEventListener('DOMContentLoaded', () => {
    aplicacion = new Aplicacion();
});

// Exportar para uso en otros módulos
if (typeof module !== 'undefined' && module.exports) {
    module.exports = Aplicacion;
}