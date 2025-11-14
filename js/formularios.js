// formularios.js - Manejo de formularios y validaciones
class Formularios {
    constructor() {
        this.formularios = {};
        this.inicializarFormularios();
        this.configurarEventos();
    }

    inicializarFormularios() {
        // Configuración de formularios disponibles
        this.formularios = {
            registro: {
                id: 'formRegistro',
                campos: {
                    nombre: { requerido: true, tipo: 'texto', minLength: 2 },
                    apellido: { requerido: true, tipo: 'texto', minLength: 2 },
                    email: { requerido: true, tipo: 'email' },
                    password: { requerido: true, tipo: 'password', minLength: 6 },
                    confirmPassword: { requerido: true, tipo: 'password' },
                    pais: { requerido: false, tipo: 'select' }
                },
                validacionesPersonalizadas: ['validarPassword']
            },
            contacto: {
                id: 'formContacto',
                campos: {
                    nombreContacto: { requerido: true, tipo: 'texto', minLength: 2 },
                    emailContacto: { requerido: true, tipo: 'email' },
                    asunto: { requerido: true, tipo: 'texto', minLength: 5 },
                    mensaje: { requerido: true, tipo: 'texto', minLength: 10 }
                }
            },
            encuesta: {
                id: 'formEncuesta',
                campos: {
                    calificacion: { requerido: true, tipo: 'radio' },
                    sugerencias: { requerido: false, tipo: 'texto' },
                    recomendacion: { requerido: true, tipo: 'select' }
                }
            }
        };
    }

    configurarEventos() {
        // Configurar eventos para cada formulario
        Object.keys(this.formularios).forEach(tipo => {
            const formularioConfig = this.formularios[tipo];
            const formularioElement = document.getElementById(formularioConfig.id);
            
            if (formularioElement) {
                // Validación en tiempo real
                this.configurarValidacionEnTiempoReal(formularioElement, formularioConfig);
                
                // Evento de envío
                formularioElement.addEventListener('submit', (e) => {
                    this.manejarEnvio(e, tipo, formularioConfig);
                });
            }
        });

        // Configurar pestañas
        this.configurarPestanas();
    }

    configurarPestanas() {
        const tabs = document.querySelectorAll('.tab');
        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                // Remover clase active de todas las pestañas y contenidos
                tabs.forEach(t => t.classList.remove('active'));
                document.querySelectorAll('.tab-content').forEach(content => {
                    content.classList.remove('active');
                });
                
                // Agregar clase active a la pestaña clickeada y su contenido
                tab.classList.add('active');
                const tabId = tab.getAttribute('data-tab');
                document.getElementById(tabId).classList.add('active');
            });
        });
    }

    configurarValidacionEnTiempoReal(formulario, config) {
        Object.keys(config.campos).forEach(campoNombre => {
            const campo = formulario.querySelector(`[name="${campoNombre}"]`);
            if (campo) {
                campo.addEventListener('blur', () => {
                    this.validarCampo(campo, config.campos[campoNombre]);
                });

                campo.addEventListener('input', () => {
                    this.limpiarError(campo);
                });
            }
        });
    }

    validarCampo(campo, config) {
        const valor = campo.value.trim();
        let esValido = true;
        let mensajeError = '';

        // Validación de campo requerido
        if (config.requerido && !valor) {
            esValido = false;
            mensajeError = 'Este campo es obligatorio';
        }

        // Validación de longitud mínima
        if (esValido && config.minLength && valor.length < config.minLength) {
            esValido = false;
            mensajeError = `Mínimo ${config.minLength} caracteres requeridos`;
        }

        // Validación de email
        if (esValido && config.tipo === 'email' && valor) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(valor)) {
                esValido = false;
                mensajeError = 'Formato de email inválido';
            }
        }

        // Validación de password
        if (esValido && config.tipo === 'password' && valor) {
            if (valor.length < 6) {
                esValido = false;
                mensajeError = 'La contraseña debe tener al menos 6 caracteres';
            }
        }

        if (!esValido) {
            this.mostrarError(campo, mensajeError);
        } else {
            this.mostrarExito(campo);
        }

        return esValido;
    }

    validarPassword(formulario) {
        const password = formulario.querySelector('[name="password"]').value;
        const confirmPassword = formulario.querySelector('[name="confirmPassword"]').value;
        
        if (password !== confirmPassword) {
            this.mostrarError(formulario.querySelector('[name="confirmPassword"]'), 'Las contraseñas no coinciden');
            return false;
        }
        return true;
    }

    mostrarError(campo, mensaje) {
        this.limpiarError(campo);
        
        campo.classList.add('error');
        const errorElement = document.createElement('div');
        errorElement.className = 'error-message';
        errorElement.textContent = mensaje;
        errorElement.style.color = '#e74c3c';
        errorElement.style.fontSize = '0.85rem';
        errorElement.style.marginTop = '5px';
        
        campo.parentNode.appendChild(errorElement);
    }

    mostrarExito(campo) {
        this.limpiarError(campo);
        campo.classList.add('success');
    }

    limpiarError(campo) {
        campo.classList.remove('error', 'success');
        const errorExistente = campo.parentNode.querySelector('.error-message');
        if (errorExistente) {
            errorExistente.remove();
        }
    }

    validarFormularioCompleto(formulario, config) {
        let esValido = true;
        
        Object.keys(config.campos).forEach(campoNombre => {
            const campo = formulario.querySelector(`[name="${campoNombre}"]`);
            if (campo && !this.validarCampo(campo, config.campos[campoNombre])) {
                esValido = false;
            }
        });

        // Validaciones personalizadas
        if (esValido && config.validacionesPersonalizadas) {
            config.validacionesPersonalizadas.forEach(validacion => {
                if (typeof this[validacion] === 'function') {
                    if (!this[validacion](formulario)) {
                        esValido = false;
                    }
                }
            });
        }

        return esValido;
    }

    async manejarEnvio(event, tipo, config) {
        event.preventDefault();
        const formulario = event.target;
        
        if (this.validarFormularioCompleto(formulario, config)) {
            // Mostrar loading
            this.mostrarLoading(formulario);
            
            try {
                // Simular envío al servidor
                await this.enviarDatos(formulario, tipo);
                this.mostrarMensajeExito(formulario, tipo);
                formulario.reset();
            } catch (error) {
                this.mostrarMensajeError(formulario, 'Error al enviar el formulario. Intente nuevamente.');
            } finally {
                this.ocultarLoading(formulario);
            }
        }
    }

    mostrarLoading(formulario) {
        const boton = formulario.querySelector('button[type="submit"]');
        const textoOriginal = boton.textContent;
        boton.innerHTML = '<div class="loading-spinner"></div> Enviando...';
        boton.disabled = true;
        
        // Guardar texto original para restaurarlo después
        boton.setAttribute('data-original-text', textoOriginal);
    }

    ocultarLoading(formulario) {
        const boton = formulario.querySelector('button[type="submit"]');
        const textoOriginal = boton.getAttribute('data-original-text');
        boton.innerHTML = textoOriginal;
        boton.disabled = false;
    }

    async enviarDatos(formulario, tipo) {
        // Simular envío a servidor
        return new Promise((resolve, reject) => {
            setTimeout(() => {
                // Simular éxito 90% del tiempo
                if (Math.random() > 0.1) {
                    resolve();
                } else {
                    reject(new Error('Error de servidor simulado'));
                }
            }, 1500);
        });
    }

    mostrarMensajeExito(formulario, tipo) {
        const mensajes = {
            registro: '¡Registro exitoso! Se ha enviado un correo de confirmación.',
            contacto: '¡Mensaje enviado correctamente! Nos pondremos en contacto pronto.',
            encuesta: '¡Gracias por completar nuestra encuesta! Su opinión es muy valiosa.'
        };

        this.mostrarMensaje(formulario, mensajes[tipo], 'success');
    }

    mostrarMensajeError(formulario, mensaje) {
        this.mostrarMensaje(formulario, mensaje, 'error');
    }

    mostrarMensaje(formulario, mensaje, tipo) {
        // Limpiar mensajes anteriores
        const mensajesAnteriores = formulario.querySelectorAll('.form-message');
        mensajesAnteriores.forEach(msg => msg.remove());

        const mensajeElement = document.createElement('div');
        mensajeElement.className = `form-message ${tipo}`;
        mensajeElement.textContent = mensaje;
        mensajeElement.style.padding = '0.8rem';
        mensajeElement.style.borderRadius = '5px';
        mensajeElement.style.marginTop = '1rem';
        mensajeElement.style.display = 'block';

        if (tipo === 'success') {
            mensajeElement.style.backgroundColor = 'rgba(46, 204, 113, 0.2)';
            mensajeElement.style.border = '1px solid #2ecc71';
            mensajeElement.style.color = '#2ecc71';
        } else {
            mensajeElement.style.backgroundColor = 'rgba(231, 76, 60, 0.2)';
            mensajeElement.style.border = '1px solid #e74c3c';
            mensajeElement.style.color = '#e74c3c';
        }

        formulario.appendChild(mensajeElement);

        // Auto-ocultar después de 5 segundos
        setTimeout(() => {
            mensajeElement.style.display = 'none';
        }, 5000);
    }
}

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
    new Formularios();
});

// Exportar para uso en otros módulos
if (typeof module !== 'undefined' && module.exports) {
    module.exports = Formularios;
}
// formularios.js - Sistema de Bomberos
class FormulariosBomberos {
    constructor() {
        this.formularios = {};
        this.inicializarFormularios();
        this.configurarEventos();
    }

    inicializarFormularios() {
        this.formularios = {
            incidente: {
                id: 'formIncidente',
                campos: {
                    tipo_emergencia: { requerido: true, tipo: 'select' },
                    ubicacion: { requerido: true, tipo: 'texto', minLength: 5 },
                    descripcion: { requerido: true, tipo: 'texto', minLength: 10 },
                    prioridad: { requerido: true, tipo: 'select' },
                    unidades_asignadas: { requerido: false, tipo: 'checkbox' }
                }
            },
            intervencion: {
                id: 'formIntervencion',
                campos: {
                    bombero_id: { requerido: true, tipo: 'select' },
                    equipo_utilizado: { requerido: true, tipo: 'texto' },
                    tiempo_intervencion: { requerido: true, tipo: 'number' },
                    observaciones: { requerido: false, tipo: 'texto' }
                }
            },
            equipo: {
                id: 'formEquipo',
                campos: {
                    tipo_equipo: { requerido: true, tipo: 'select' },
                    estado: { requerido: true, tipo: 'select' },
                    mantenimiento: { requerido: false, tipo: 'date' }
                }
            }
        };
    }

    configurarEventos() {
        // Configuración específica para bomberos
        this.configurarFormularioIncidente();
        this.configurarFormularioIntervencion();
    }

    configurarFormularioIncidente() {
        const form = document.getElementById('formIncidente');
        if (form) {
            form.addEventListener('submit', (e) => {
                this.procesarIncidente(e);
            });
        }
    }

    async procesarIncidente(event) {
        event.preventDefault();
        const formData = new FormData(event.target);
        
        // Simular envío a sistema de bomberos
        try {
            await this.enviarIncidenteSistema(formData);
            this.mostrarMensaje('Incidente reportado correctamente', 'success');
        } catch (error) {
            this.mostrarMensaje('Error al reportar incidente', 'error');
        }
    }

    async enviarIncidenteSistema(data) {
        // Simular API de bomberos
        return new Promise((resolve) => {
            setTimeout(() => {
                console.log('Incidente enviado al sistema:', data);
                resolve();
            }, 2000);
        });
    }
}

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
    new FormulariosBomberos();
});