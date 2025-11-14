// mapa.js - Manejo de mapas interactivos y ubicaciones
class MapaInteractivo {
    constructor(containerId, opciones = {}) {
        this.container = document.getElementById(containerId);
        this.mapa = null;
        this.marcadores = [];
        this.polilinea = null;
        this.opciones = {
            zoom: opciones.zoom || 12,
            centro: opciones.centro || [40.4168, -3.7038], // Madrid por defecto
            estilo: opciones.estilo || 'standard',
            ...opciones
        };

        this.puntosInteres = [];
        this.filtrosActivos = new Set();
        
        this.inicializarMapa();
        this.cargarPuntosInteres();
        this.configurarControles();
    }

    inicializarMapa() {
        if (!this.container) {
            console.error('Contenedor del mapa no encontrado');
            return;
        }

        // En un entorno real, aquí se inicializaría Leaflet o Google Maps
        // Por ahora simulamos el comportamiento
        this.simularInicializacionMapa();
        
        // Crear elementos del mapa simulado
        this.crearMapaSimulado();
    }

    simularInicializacionMapa() {
        console.log('Inicializando mapa en:', this.opciones.centro);
        console.log('Zoom:', this.opciones.zoom);
        console.log('Estilo:', this.opciones.estilo);
        
        // Simular carga de tiles
        this.simularCargaTiles();
    }

    simularCargaTiles() {
        // Simular progreso de carga
        let progreso = 0;
        const intervalo = setInterval(() => {
            progreso += 20;
            console.log(`Cargando mapa... ${progreso}%`);
            
            if (progreso >= 100) {
                clearInterval(intervalo);
                console.log('Mapa cargado completamente');
                this.onMapaCargado();
            }
        }, 200);
    }

    crearMapaSimulado() {
        // Crear interfaz visual del mapa
        this.container.innerHTML = `
            <div class="mapa-simulado">
                <div class="mapa-contenido">
                    <div class="mapa-overlay">
                        <div class="mapa-info">
                            <h3>Mapa Interactivo</h3>
                            <p>Centro: [${this.opciones.centro[0]}, ${this.opciones.centro[1]}]</p>
                            <p>Zoom: ${this.opciones.zoom}</p>
                            <div class="controles-mapa">
                                <button class="btn-mapa" id="zoom-in">+</button>
                                <button class="btn-mapa" id="zoom-out">-</button>
                                <button class="btn-mapa" id="reset-view">⟲</button>
                            </div>
                        </div>
                    </div>
                    <div class="puntos-interes-container" id="puntos-interes-map"></div>
                </div>
            </div>
        `;

        // Aplicar estilos
        this.aplicarEstilosMapa();
    }

    aplicarEstilosMapa() {
        const estilo = `
            <style>
                .mapa-simulado {
                    width: 100%;
                    height: 100%;
                    background: linear-gradient(135deg, #74b9ff, #0984e3);
                    position: relative;
                    border-radius: 8px;
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
                
                .mapa-info h3 {
                    margin: 0 0 10px 0;
                    color: #2d3436;
                }
                
                .mapa-info p {
                    margin: 5px 0;
                    font-size: 0.9rem;
                    color: #636e72;
                }
                
                .controles-mapa {
                    display: flex;
                    gap: 5px;
                    margin-top: 10px;
                }
                
                .btn-mapa {
                    padding: 8px 12px;
                    background: #3498db;
                    color: white;
                    border: none;
                    border-radius: 4px;
                    cursor: pointer;
                    font-weight: bold;
                }
                
                .btn-mapa:hover {
                    background: #2980b9;
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
                
                .punto-interes-marker:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 4px 8px rgba(0,0,0,0.3);
                }
                
                .punto-interes-marker.activo {
                    border-color: #e74c3c;
                    background: #fff;
                }
                
                .loading-mapa {
                    position: absolute;
                    top: 50%;
                    left: 50%;
                    transform: translate(-50%, -50%);
                    color: white;
                    font-size: 1.2rem;
                }
            </style>
        `;
        
        document.head.insertAdjacentHTML('beforeend', estilo);
    }

    onMapaCargado() {
        console.log('Mapa listo para interactuar');
        this.mostrarPuntosInteres();
    }

    cargarPuntosInteres() {
        // Datos de ejemplo - en una app real vendrían de una API
        this.puntosInteres = [
            {
                id: 1,
                nombre: 'Oficina Central',
                tipo: 'oficina',
                coordenadas: [40.4168, -3.7038],
                direccion: 'Plaza Mayor, 1, Madrid',
                horario: 'L-V: 9:00-18:00'
            },
            {
                id: 2,
                nombre: 'Sucursal Norte',
                tipo: 'sucursal',
                coordenadas: [41.3851, 2.1734],
                direccion: 'Diagonal, 123, Barcelona',
                horario: 'L-V: 8:00-17:00'
            },
            {
                id: 3,
                nombre: 'Sucursal Sur',
                tipo: 'sucursal',
                coordenadas: [37.3891, -5.9845],
                direccion: 'Avenida de la Constitución, 45, Sevilla',
                horario: 'L-V: 9:30-18:30'
            },
            {
                id: 4,
                nombre: 'Centro de Distribución',
                tipo: 'almacen',
                coordenadas: [39.4699, -0.3763],
                direccion: 'Calle del Mar, 78, Valencia',
                horario: 'L-D: 24 horas'
            },
            {
                id: 5,
                nombre: 'Punto de Atención',
                tipo: 'atencion',
                coordenadas: [43.2630, -2.9350],
                direccion: 'Gran Vía, 25, Bilbao',
                horario: 'L-S: 10:00-20:00'
            }
        ];
    }

    mostrarPuntosInteres() {
        const container = document.getElementById('puntos-interes-map');
        if (!container) return;

        container.innerHTML = this.puntosInteres.map(punto => `
            <div class="punto-interes-marker" 
                 data-id="${punto.id}"
                 data-tipo="${punto.tipo}"
                 onclick="mapaInteractivo.seleccionarPuntoInteres(${punto.id})">
                📍 ${punto.nombre}
            </div>
        `).join('');

        // Actualizar también la lista lateral
        this.actualizarListaPuntosInteres();
    }

    actualizarListaPuntosInteres() {
        const listaContainer = document.getElementById('puntos-interes');
        if (!listaContainer) return;

        listaContainer.innerHTML = this.puntosInteres.map(punto => `
            <li class="punto-interes-item" data-id="${punto.id}">
                <strong>${punto.nombre}</strong><br>
                <small>${punto.direccion}</small><br>
                <em>${punto.horario}</em>
                <button onclick="mapaInteractivo.mostrarRuta(${punto.id})" 
                        style="margin-top: 5px; padding: 3px 8px; background: #3498db; color: white; border: none; border-radius: 3px; cursor: pointer;">
                    Cómo llegar
                </button>
            </li>
        `).join('');
    }

    seleccionarPuntoInteres(id) {
        const punto = this.puntosInteres.find(p => p.id === id);
        if (!punto) return;

        console.log('Punto seleccionado:', punto);

        // Resaltar en el mapa
        document.querySelectorAll('.punto-interes-marker').forEach(marker => {
            marker.classList.remove('activo');
        });
        
        const marker = document.querySelector(`.punto-interes-marker[data-id="${id}"]`);
        if (marker) {
            marker.classList.add('activo');
        }

        // Mostrar información detallada
        this.mostrarInfoPunto(punto);
    }

    mostrarInfoPunto(punto) {
        // Crear o actualizar ventana de información
        let infoWindow = document.querySelector('.info-window');
        
        if (!infoWindow) {
            infoWindow = document.createElement('div');
            infoWindow.className = 'info-window';
            infoWindow.style.cssText = `
                position: absolute;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                background: white;
                padding: 20px;
                border-radius: 10px;
                box-shadow: 0 5px 15px rgba(0,0,0,0.3);
                z-index: 1001;
                max-width: 300px;
            `;
            this.container.querySelector('.mapa-contenido').appendChild(infoWindow);
        }

        infoWindow.innerHTML = `
            <h3>${punto.nombre}</h3>
            <p><strong>Dirección:</strong> ${punto.direccion}</p>
            <p><strong>Horario:</strong> ${punto.horario}</p>
            <p><strong>Tipo:</strong> ${this.obtenerTipoTexto(punto.tipo)}</p>
            <button onclick="this.parentElement.remove()" 
                    style="margin-top: 10px; padding: 5px 10px; background: #e74c3c; color: white; border: none; border-radius: 3px; cursor: pointer;">
                Cerrar
            </button>
        `;
    }

    obtenerTipoTexto(tipo) {
        const tipos = {
            'oficina': 'Oficina Central',
            'sucursal': 'Sucursal',
            'almacen': 'Centro de Distribución',
            'atencion': 'Punto de Atención'
        };
        return tipos[tipo] || tipo;
    }

    mostrarRuta(idPuntoDestino) {
        const puntoDestino = this.puntosInteres.find(p => p.id === idPuntoDestino);
        if (!puntoDestino) return;

        // Simular cálculo de ruta
        console.log('Calculando ruta hacia:', puntoDestino.nombre);
        
        // En una app real, aquí se usaría la API de direcciones
        alert(`Ruta calculada hacia: ${puntoDestino.nombre}\n${puntoDestino.direccion}`);
    }

    configurarControles() {
        // Configurar eventos de los controles del mapa
        setTimeout(() => {
            const zoomIn = document.getElementById('zoom-in');
            const zoomOut = document.getElementById('zoom-out');
            const resetView = document.getElementById('reset-view');

            if (zoomIn) {
                zoomIn.addEventListener('click', () => this.zoom(1));
            }
            if (zoomOut) {
                zoomOut.addEventListener('click', () => this.zoom(-1));
            }
            if (resetView) {
                resetView.addEventListener('click', () => this.resetearVista());
            }
        }, 100);
    }

    zoom(direccion) {
        this.opciones.zoom += direccion;
        
        // Limitar zoom
        this.opciones.zoom = Math.max(1, Math.min(18, this.opciones.zoom));
        
        console.log('Zoom actualizado:', this.opciones.zoom);
        
        // Actualizar display
        const zoomDisplay = this.container.querySelector('.mapa-info p:nth-child(3)');
        if (zoomDisplay) {
            zoomDisplay.textContent = `Zoom: ${this.opciones.zoom}`;
        }
    }

    resetearVista() {
        this.opciones.zoom = 12;
        console.log('Vista resetada al zoom por defecto');
        
        // Actualizar display
        const zoomDisplay = this.container.querySelector('.mapa-info p:nth-child(3)');
        if (zoomDisplay) {
            zoomDisplay.textContent = `Zoom: ${this.opciones.zoom}`;
        }
    }

    // Métodos para integración con otros módulos
    agregarPuntoInteres(punto) {
        this.puntosInteres.push(punto);
        this.mostrarPuntosInteres();
    }

    eliminarPuntoInteres(id) {
        this.puntosInteres = this.puntosInteres.filter(p => p.id !== id);
        this.mostrarPuntosInteres();
    }

    filtrarPorTipo(tipo) {
        if (this.filtrosActivos.has(tipo)) {
            this.filtrosActivos.delete(tipo);
        } else {
            this.filtrosActivos.add(tipo);
        }
        this.aplicarFiltros();
    }

    aplicarFiltros() {
        const puntosFiltrados = this.filtrosActivos.size === 0 
            ? this.puntosInteres 
            : this.puntosInteres.filter(p => this.filtrosActivos.has(p.tipo));
        
        // Actualizar visualización
        this.mostrarPuntosInteresFiltrados(puntosFiltrados);
    }

    mostrarPuntosInteresFiltrados(puntos) {
        // Implementar lógica de filtrado visual
        console.log('Mostrando puntos filtrados:', puntos);
    }
}

// Inicializar mapa cuando el DOM esté listo
let mapaInteractivo;

document.addEventListener('DOMContentLoaded', () => {
    mapaInteractivo = new MapaInteractivo('map', {
        zoom: 6,
        centro: [40.4168, -3.7038],
        estilo: 'standard'
    });
});

// Exportar para uso en otros módulos
if (typeof module !== 'undefined' && module.exports) {
    module.exports = MapaInteractivo;
}
// mapa.js - Sistema de Bomberos
class MapaBomberos {
    constructor(containerId) {
        this.container = document.getElementById(containerId);
        this.emergencias = [];
        this.unidades = [];
        this.inicializarMapa();
    }

    inicializarMapa() {
        // Simulación de mapa para bomberos
        this.crearMapaInteractivo();
        this.cargarEmergenciasActivas();
        this.cargarUnidadesActivas();
    }

    crearMapaInteractivo() {
        this.container.innerHTML = `
            <div class="mapa-bomberos">
                <div class="controles-mapa">
                    <button onclick="mapaBomberos.actualizarVista()">Actualizar</button>
                    <button onclick="mapaBomberos.mostrarTodasEmergencias()">Ver Emergencias</button>
                    <button onclick="mapaBomberos.mostrarTodasUnidades()">Ver Unidades</button>
                </div>
                <div class="vista-mapa">
                    <!-- Aquí iría el mapa real con Leaflet/Google Maps -->
                    <div class="mapa-simulado">
                        <h3>Mapa Operativo - Sistema de Bomberos</h3>
                        <div id="puntos-mapa"></div>
                    </div>
                </div>
            </div>
        `;
    }

    cargarEmergenciasActivas() {
        // Simular datos de emergencias
        this.emergencias = [
            { id: 1, tipo: 'Incendio', ubicacion: 'Zona Centro', prioridad: 'Alta' },
            { id: 2, tipo: 'Accidente', ubicacion: 'Av. Principal', prioridad: 'Media' }
        ];
        this.mostrarEmergenciasMapa();
    }

    cargarUnidadesActivas() {
        // Simular datos de unidades
        this.unidades = [
            { id: 1, tipo: 'URB', ubicacion: 'Estación Central', estado: 'Activa' },
            { id: 2, tipo: 'Escalera', ubicacion: 'Zona Norte', estado: 'En camino' }
        ];
        this.mostrarUnidadesMapa();
    }

    mostrarEmergenciasMapa() {
        const contenedor = document.getElementById('puntos-mapa');
        this.emergencias.forEach(emergencia => {
            const punto = document.createElement('div');
            punto.className = 'punto-emergencia';
            punto.innerHTML = `🚨 ${emergencia.tipo} - ${emergencia.ubicacion}`;
            contenedor.appendChild(punto);
        });
    }
}

// Inicializar mapa de bomberos
let mapaBomberos;
document.addEventListener('DOMContentLoaded', () => {
    mapaBomberos = new MapaBomberos('map');
});