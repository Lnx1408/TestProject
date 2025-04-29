/**
 * Clase para gestionar el historial de sesiones
 */
class SessionsManager {
    constructor() {
        this.config = {
            endpoints: {
                getActiveSessions: `${base_url}/Settings/getActiveSessions`,
                closeSession: `${base_url}/Settings/closeSession`,
                closeAllSessions: `${base_url}/Settings/closeAllSessions`
            },
            selectors: {
                sessionsList: '#sessionsList',
                closeAllSessionsBtn: '#closeAllSessionsBtn'
            }
        };

        // Sistema de traducciones específico para esta clase
        this.translations = {
            get: (key) => LanguageManager.getTranslation(`settings.sessions.${key}`)
        };

        this.state = {
            loading: false,
            sessions: [],
            currentSessionId: null
        };

        this.init();
    }

    /**
     * Inicializa el componente
     */
    async init() {
        this.bindEvents();
        await this.loadActiveSessions();

        // Añadir manejo de cambio de idioma
        document.addEventListener('languageChanged', () => {
            this.updateTranslations();
        });
    }

    /**
     * Actualiza las traducciones cuando cambia el idioma
     */
    updateTranslations() {
        // Si hay sesiones, volver a renderizarlas para actualizar textos
        if (this.state.sessions.length > 0) {
            this.renderSessions(this.state.sessions, this.state.currentSessionId);
        } else {
            // Si no hay sesiones, actualizar el estado vacío
            const sessionsList = document.querySelector(this.config.selectors.sessionsList);
            if (sessionsList) {
                sessionsList.innerHTML = this.createEmptyStateHTML();
            }
        }
    }

    /**
     * Crea el HTML para el estado vacío (sin sesiones)
     * @returns {string} HTML del estado vacío
     */
    createEmptyStateHTML() {
        return `
            <div class="empty-state">
                <div class="empty-state-icon">
                    <i class='bx bx-check-circle'></i>
                </div>
                <h3>${this.translations.get('empty_state.title') || 'No hay otras sesiones activas'}</h3>
                <p class="empty-state-message">${this.translations.get('empty_state.message') || 'Solo tienes esta sesión actualmente.'}</p>
            </div>
        `;
    }

    /**
     * Vincula eventos a elementos del DOM
     */
    bindEvents() {
        const closeAllBtn = document.querySelector(this.config.selectors.closeAllSessionsBtn);

        if (closeAllBtn) {
            closeAllBtn.addEventListener('click', () => this.handleCloseAllSessions());
        }
    }

    /**
     * Carga las sesiones activas
     */
    async loadActiveSessions() {
        try {
            this.setLoading(true);

            const response = await fetch(this.config.endpoints.getActiveSessions);
            const result = await response.json();

            if (!result.data) {
                throw new Error(this.translations.get('errors.no_data') || 'No se recibieron datos del servidor');
            }

            const decryptedData = CryptoModule.decrypt(result.data);

            if (!decryptedData.status) {
                throw new Error(decryptedData.msg || this.translations.get('errors.load_failed') || 'Error al cargar las sesiones');
            }

            this.renderSessions(decryptedData.sessions, decryptedData.currentSessionId);
            this.state.currentSessionId = decryptedData.currentSessionId;
            this.state.sessions = decryptedData.sessions;

            // Actualizar estado del botón de cerrar todas las sesiones
            this.updateCloseAllButton();

        } catch (error) {
            console.error('Error loading sessions:', error);
            this.showNotification('error', this.translations.get('errors.load_failed') || 'Error al cargar el historial de sesiones');
            this.renderError(error.message);
        } finally {
            this.setLoading(false);
        }
    }

    /**
     * Renderiza la lista de sesiones
     * @param {Array} sessions - Lista de sesiones activas
     * @param {string} currentSessionId - ID de la sesión actual
     */
    renderSessions(sessions, currentSessionId) {
        const sessionsListEl = document.querySelector(this.config.selectors.sessionsList);

        if (!sessionsListEl) return;

        // Limpiar contenido previo
        sessionsListEl.innerHTML = '';

        // Si no hay sesiones, mostrar estado vacío
        if (!sessions || sessions.length === 0) {
            sessionsListEl.innerHTML = this.config.templates.emptyState();
            return;
        }

        // Renderizar cada sesión
        sessions.forEach(session => {
            const isCurrent = session.id_sesion === currentSessionId;
            const sessionCard = this.createSessionCardElement(session, isCurrent);
            sessionsListEl.appendChild(sessionCard);
        });
    }

    /**
     * Crea el elemento HTML para una tarjeta de sesión
     * @param {Object} session - Datos de la sesión
     * @param {boolean} isCurrent - Indica si es la sesión actual
     * @returns {HTMLElement} Elemento de la tarjeta
     */
    createSessionCardElement(session, isCurrent) {
        const sessionCard = document.createElement('div');
        sessionCard.className = `session-card${isCurrent ? ' current' : ''}`;
        sessionCard.setAttribute('data-session-id', session.id_sesion);
        sessionCard.innerHTML = this.createSessionCardHTML(session, isCurrent);

        // Agregar evento para cerrar la sesión
        const closeBtn = sessionCard.querySelector('.close-session-btn');
        if (closeBtn && !isCurrent) {
            closeBtn.addEventListener('click', () => this.handleCloseSession(session.id_sesion));
        }

        return sessionCard;
    }

    /**
     * Crea el HTML para una tarjeta de sesión
     * @param {Object} session - Datos de la sesión
     * @param {boolean} isCurrent - Indica si es la sesión actual
     * @returns {string} HTML de la tarjeta
     */
    createSessionCardHTML(session, isCurrent) {
        // Parsear la información del dispositivo si está disponible
        let deviceInfo = { user_agent: 'Desconocido' };
        if (session.info_dispositivo) {
            try {
                deviceInfo = JSON.parse(session.info_dispositivo);
            } catch (e) {
                console.error('Error parsing device info:', e);
            }
        }

        // Determinar el tipo de dispositivo
        const deviceType = this.getDeviceType(deviceInfo.user_agent);
        const deviceIcon = this.getDeviceIcon(deviceType);

        // Formatear fechas
        const creationDate = this.formatDate(session.fecha_creacion);
        const lastActivity = this.formatDate(session.ultima_actividad);

        return `
            <div class="device-icon">
                <i class='bx ${deviceIcon}'></i>
            </div>
            <span class="session-status status-active">${this.translations.get('status.active') || 'Activa'}</span>
            <div class="session-info">
                <h3>${deviceType}</h3>
                <div class="info-item">
                    <span class="info-label">${this.translations.get('ip') || 'IP'}:</span>
                    <span class="info-value">${session.ip_direccion}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">${this.translations.get('start') || 'Inicio'}:</span>
                    <span class="info-value">${creationDate}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">${this.translations.get('activity') || 'Actividad'}:</span>
                    <span class="info-value">${lastActivity}</span>
                </div>
            </div>
            <div class="session-actions">
                <button class="btn btn-sm btn-outline-danger close-session-btn" ${isCurrent ? 'disabled' : ''}>
                    <i class='bx bx-log-out'></i> ${this.translations.get('close_session') || 'Cerrar sesión'}
                </button>
            </div>
        `;
    }

    /**
     * Determina el tipo de dispositivo a partir del User-Agent
     * @param {string} userAgent - User-Agent del navegador
     * @returns {string} Tipo de dispositivo
     */
    getDeviceType(userAgent) {
        if (!userAgent) return this.translations.get('device_types.unknown') || 'Dispositivo desconocido';

        // Versión simple de detección de dispositivo
        if (/Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(userAgent)) {
            return this.translations.get('device_types.mobile') || 'Dispositivo móvil';
        } else if (/Tablet|iPad/i.test(userAgent)) {
            return this.translations.get('device_types.tablet') || 'Tablet';
        } else {
            return this.translations.get('device_types.computer') || 'Computadora';
        }
    }

    /**
     * Obtiene el icono correspondiente al tipo de dispositivo
     * @param {string} deviceType - Tipo de dispositivo
     * @returns {string} Clase del icono
     */
    getDeviceIcon(deviceType) {
        const translations = {
            [this.translations.get('device_types.mobile') || 'Dispositivo móvil']: 'bx-mobile-alt',
            [this.translations.get('device_types.tablet') || 'Tablet']: 'bx-tablet',
            [this.translations.get('device_types.computer') || 'Computadora']: 'bx-desktop'
        };

        return translations[deviceType] || 'bx-devices';
    }

    /**
     * Formatea una fecha para mostrarla en la interfaz
     * @param {string} dateString - Fecha en formato string
     * @returns {string} Fecha formateada
     */
    formatDate(dateString) {
        if (!dateString) return this.translations.get('date_unavailable') || 'No disponible';

        const date = new Date(dateString);

        // Si la fecha es inválida
        if (isNaN(date.getTime())) return this.translations.get('date_invalid') || 'Fecha inválida';

        // Calcular tiempo relativo
        const now = new Date();
        const diffMs = now - date;
        const diffSec = Math.floor(diffMs / 1000);
        const diffMin = Math.floor(diffSec / 60);
        const diffHour = Math.floor(diffMin / 60);
        const diffDay = Math.floor(diffHour / 24);

        // Formato relativo para fechas recientes
        if (diffDay < 1) {
            if (diffHour < 1) {
                if (diffMin < 1) {
                    return this.translations.get('date_moments_ago') || 'Hace un momento';
                }
                return `${this.translations.get('date_ago_prefix') || 'Hace'} ${diffMin} ${diffMin === 1 ?
                    this.translations.get('date_minute') || 'minuto' :
                    this.translations.get('date_minutes') || 'minutos'}`;
            }
            return `${this.translations.get('date_ago_prefix') || 'Hace'} ${diffHour} ${diffHour === 1 ?
                this.translations.get('date_hour') || 'hora' :
                this.translations.get('date_hours') || 'horas'}`;
        } else if (diffDay < 7) {
            return `${this.translations.get('date_ago_prefix') || 'Hace'} ${diffDay} ${diffDay === 1 ?
                this.translations.get('date_day') || 'día' :
                this.translations.get('date_days') || 'días'}`;
        }

        // Para fechas más antiguas, mostrar fecha completa
        return date.toLocaleDateString('es-ES', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    /**
     * Maneja el cierre de una sesión específica
     * @param {string} sessionId - ID de la sesión a cerrar
     */
    async handleCloseSession(sessionId) {
        try {
            // Confirmar antes de cerrar
            const result = await Swal.fire({
                title: this.translations.get('confirm_close.title') || '¿Cerrar sesión?',
                text: this.translations.get('confirm_close.text') || '¿Estás seguro de que deseas cerrar esta sesión?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: this.translations.get('confirm_close.confirm') || 'Sí, cerrar',
                cancelButtonText: this.translations.get('confirm_close.cancel') || 'Cancelar',
                confirmButtonColor: 'var(--danger)',
                customClass: {
                    container: 'settings-confirmation-modal',
                    popup: 'settings-confirmation-popup',
                }
            });

            if (!result.isConfirmed) return;

            this.setLoading(true);

            const response = await fetch(this.config.endpoints.closeSession, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    encryptedData: CryptoModule.encrypt({ sessionId })
                })
            });

            const responseData = await response.json();
            const decryptedData = CryptoModule.decrypt(responseData.data);

            if (!decryptedData.status) {
                throw new Error(decryptedData.msg || this.translations.get('errors.close_failed') || 'Error al cerrar la sesión');
            }

            // Actualizar la lista de sesiones
            await this.loadActiveSessions();

            // Mostrar mensaje de éxito
            this.showNotification('success', this.translations.get('close_success') || 'Sesión cerrada correctamente');

        } catch (error) {
            console.error('Error closing session:', error);
            this.showNotification('error', error.message || this.translations.get('errors.close_failed') || 'Error al cerrar la sesión');
        } finally {
            this.setLoading(false);
        }
    }

    /**
     * Maneja el cierre de todas las sesiones excepto la actual
     */
    async handleCloseAllSessions() {
        try {
            // Confirmar antes de cerrar todas
            const result = await Swal.fire({
                title: this.translations.get('confirm_close_all.title') || '¿Cerrar todas las sesiones?',
                text: this.translations.get('confirm_close_all.text') || '¿Estás seguro de que deseas cerrar todas las sesiones excepto la actual?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: this.translations.get('confirm_close_all.confirm') || 'Sí, cerrar todas',
                cancelButtonText: this.translations.get('confirm_close_all.cancel') || 'Cancelar',
                confirmButtonColor: 'var(--danger)',
                customClass: {
                    container: 'settings-confirmation-modal',
                    popup: 'settings-confirmation-popup',
                }
            });

            if (!result.isConfirmed) return;

            this.setLoading(true);

            const response = await fetch(this.config.endpoints.closeAllSessions, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    encryptedData: CryptoModule.encrypt({})
                })
            });

            const responseData = await response.json();
            const decryptedData = CryptoModule.decrypt(responseData.data);

            if (!decryptedData.status) {
                throw new Error(decryptedData.msg || this.translations.get('errors.close_all_failed') || 'Error al cerrar las sesiones');
            }

            // Actualizar la lista de sesiones
            await this.loadActiveSessions();

            // Mostrar mensaje de éxito
            const sessionsClosedCount = decryptedData.sesiones_cerradas || 0;
            const message = sessionsClosedCount > 0
                ? `${this.translations.get('close_all_success_prefix') || 'Se'} ${sessionsClosedCount === 1 ? this.translations.get('close_all_success_singular') || 'ha cerrado 1 sesión' : `${this.translations.get('close_all_success_plural_prefix') || 'han cerrado'} ${sessionsClosedCount} ${this.translations.get('close_all_success_plural_suffix') || 'sesiones'}`}`
                : this.translations.get('close_all_none') || 'No había otras sesiones activas para cerrar';


            this.showNotification('success', message);

        } catch (error) {
            console.error('Error closing all sessions:', error);
            this.showNotification('error', error.message || this.translations.get('errors.close_all_failed') || 'Error al cerrar las sesiones');
        } finally {
            this.setLoading(false);
        }
    }

    /**
     * Actualiza el estado del botón de cerrar todas las sesiones
     */
    updateCloseAllButton() {
        const closeAllBtn = document.querySelector(this.config.selectors.closeAllSessionsBtn);
        if (!closeAllBtn) return;

        // Contar sesiones que no son la actual
        const otherSessionsCount = this.state.sessions.filter(
            session => session.id_sesion !== this.state.currentSessionId
        ).length;

        // Deshabilitar el botón si no hay otras sesiones
        closeAllBtn.disabled = otherSessionsCount === 0;
    }

    /**
     * Renderiza un mensaje de error
     * @param {string} message - Mensaje de error
     */
    renderError(message) {
        const sessionsListEl = document.querySelector(this.config.selectors.sessionsList);
        if (!sessionsListEl) return;

        sessionsListEl.innerHTML = `
            <div class="error-state">
                <div class="error-icon">
                    <i class='bx bx-error-circle'></i>
                </div>
                <h3>${this.translations.get('error_state.title') || 'Error al cargar las sesiones'}</h3>
                <p class="error-message">${message || this.translations.get('error_state.default_message') || 'No se pudieron cargar las sesiones activas'}</p>
            </div>
        `;
    }

    /**
     * Establece el estado de carga
     * @param {boolean} isLoading - Estado de carga
     */
    setLoading(isLoading) {
        this.state.loading = isLoading;

        // Actualizar UI según estado de carga
        const closeAllBtn = document.querySelector(this.config.selectors.closeAllSessionsBtn);
        if (closeAllBtn) {
            closeAllBtn.disabled = isLoading;
        }

        const sessionsListEl = document.querySelector(this.config.selectors.sessionsList);
        if (sessionsListEl && isLoading) {
            sessionsListEl.innerHTML = `
                <div class="loading-container">
                    <div class="spinner"></div>
                    <p>${this.translations.get('loading') || 'Cargando sesiones...'}</p>
                </div>
            `;
        }
    }

    /**
     * Muestra una notificación
     * @param {string} type - Tipo de notificación (success, error)
     * @param {string} message - Mensaje a mostrar
     */
    showNotification(type, message) {
        Swal.fire({
            icon: type,
            title: type === 'success'
                ? LanguageManager.getTranslation('settings.notifications.success_title') || '¡Éxito!'
                : LanguageManager.getTranslation('settings.notifications.error_title') || 'Error',
            text: message,
            timer: type === 'success' ? 5000 : undefined,
            timerProgressBar: type === 'success',
            showConfirmButton: true,
            confirmButtonColor: 'var(--primary)',
            customClass: {
                container: 'settings-confirmation-modal',
                popup: 'settings-confirmation-popup',
            }
        });
    }
}

// Inicializar el módulo cuando el DOM esté completamente cargado
document.addEventListener('DOMContentLoaded', () => {
    window.sessionsManager = new SessionsManager();
});