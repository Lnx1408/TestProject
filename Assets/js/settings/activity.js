/**
 * Clase para gestionar el historial de actividad
 */
class ActivityManager {
    constructor() {
        this.config = {
            endpoints: {
                getActivityHistory: `${base_url}/Settings/getActivityHistory`
            },
            selectors: {
                activityList: '#activityList',
                periodFilter: '#periodFilter',
                exportBtn: '#exportBtn',
                loadMoreBtn: '#loadMoreBtn'
            },
            defaultPeriod: 90,
            itemsPerPage: 20
        };
        
        this.state = {
            loading: false,
            period: this.config.defaultPeriod,
            currentOffset: 0,
            hasMoreData: false,
            activityData: [],
            fullName: '' // Se asignará el nombre del usuario actual
        };
        
        this.init();
    }
    
    /**
     * Inicializa el componente
     */
    async init() {
        // Inicializar datos del usuario
        const userData = this.getUserData();
        this.state.fullName = userData ? userData.fullName : 'Usuario';
        
        this.bindEvents();
        await this.loadActivityHistory();
    }
    
    /**
     * Obtiene datos básicos del usuario de la sesión
     * @returns {Object|null} Datos del usuario o null
     */
    getUserData() {
        // Opción 1: Si inicializamos la información del usuario en la vista
        if (window.userInfo) {
            return window.userInfo;
        }
        
        // Opción 2: Si no tenemos los datos, obtenemos el nombre del elemento HTML de perfil
        const avatarElement = document.getElementById('avatar-info');
        if (avatarElement) {
            const username = avatarElement.getAttribute('data-username');
            return { fullName: username || 'Usuario' };
        }
        
        // Si no hay datos disponibles, usar un valor por defecto
        return { fullName: 'Usuario' };
    }
    
    /**
     * Vincula eventos a elementos del DOM
     */
    bindEvents() {
        const periodFilter = document.querySelector(this.config.selectors.periodFilter);
        const exportBtn = document.querySelector(this.config.selectors.exportBtn);
        const loadMoreBtn = document.querySelector(this.config.selectors.loadMoreBtn);
        
        if (periodFilter) {
            periodFilter.addEventListener('change', (e) => this.handlePeriodChange(e));
        }
        
        if (exportBtn) {
            exportBtn.addEventListener('click', () => this.exportActivityToCSV());
        }
        
        if (loadMoreBtn) {
            loadMoreBtn.addEventListener('click', () => this.loadMoreActivity());
        }
        
        // Registrar evento de cambio de idioma
        document.addEventListener('languageChanged', () => {
            // Actualizar textos dinámicos cuando cambie el idioma
            this.updateUITranslations();
        });
    }
    
    /**
     * Maneja el cambio en el filtro de período
     * @param {Event} e - Evento change
     */
    async handlePeriodChange(e) {
        const newPeriod = parseInt(e.target.value, 10);
        if (newPeriod !== this.state.period) {
            this.state.period = newPeriod;
            this.state.currentOffset = 0;
            this.state.activityData = [];
            await this.loadActivityHistory();
        }
    }
    
    /**
     * Carga el historial de actividad desde el servidor
     */
    async loadActivityHistory() {
        try {
            this.setLoading(true);
            
            // Construir URL con parámetros
            const url = new URL(this.config.endpoints.getActivityHistory, window.location.origin);
            url.searchParams.append('period', this.state.period);
            url.searchParams.append('offset', this.state.currentOffset);
            url.searchParams.append('limit', this.config.itemsPerPage);
            
            const response = await fetch(url);
            const result = await response.json();
            
            if (!result.data) {
                throw new Error('No se recibieron datos del servidor');
            }
            
            const decryptedData = CryptoModule.decrypt(result.data);
            
            if (!decryptedData.status) {
                throw new Error(decryptedData.msg || 'Error al cargar el historial de actividad');
            }
            
            // Asignar o añadir datos según sea primera carga o carga adicional
            if (this.state.currentOffset === 0) {
                this.state.activityData = decryptedData.history || [];
            } else {
                this.state.activityData = [...this.state.activityData, ...(decryptedData.history || [])];
            }
            
            // Actualizar estado de paginación
            this.state.hasMoreData = decryptedData.hasMore || false;
            this.state.currentOffset += this.config.itemsPerPage;
            
            // Renderizar datos
            this.renderActivityHistory();
            
        } catch (error) {
            console.error('Error loading activity history:', error);
            this.showNotification('error', error.message || 'Error al cargar el historial de actividad');
            this.renderError();
        } finally {
            this.setLoading(false);
        }
    }
    
    /**
     * Carga más datos de actividad (paginación)
     */
    async loadMoreActivity() {
        if (this.state.hasMoreData && !this.state.loading) {
            await this.loadActivityHistory();
        }
    }
    
    /**
     * Renderiza el historial de actividad
     */
    renderActivityHistory() {
        const activityListEl = document.querySelector(this.config.selectors.activityList);
        const loadMoreBtn = document.querySelector(this.config.selectors.loadMoreBtn);
        
        if (!activityListEl) return;
        
        // Limpiar contenido si es primera carga
        if (this.state.currentOffset === this.config.itemsPerPage) {
            activityListEl.innerHTML = '';
        }
        
        // Si no hay datos, mostrar estado vacío
        if (this.state.activityData.length === 0) {
            activityListEl.innerHTML = this.createEmptyStateHTML();
            if (loadMoreBtn) loadMoreBtn.style.display = 'none';
            return;
        }
        
        // Agrupar eventos por fecha
        const groupedByDate = this.groupActivityByDate(this.state.activityData);
        
        // Renderizar cada grupo de fecha
        for (const [date, events] of Object.entries(groupedByDate)) {
            const dateGroup = document.createElement('div');
            dateGroup.className = 'day-group';
            dateGroup.innerHTML = `
                <div class="date-header">
                    <div class="date-badge">${date}</div>
                    <div class="line"></div>
                </div>
                <div class="event-list"></div>
            `;
            
            const eventList = dateGroup.querySelector('.event-list');
            
            // Renderizar cada evento del grupo
            events.forEach(event => {
                const eventItem = document.createElement('div');
                eventItem.className = `event-item ${event.type} ${event.status || 'success'}`;
                eventItem.innerHTML = this.createEventItemHTML(event);
                eventList.appendChild(eventItem);
            });
            
            activityListEl.appendChild(dateGroup);
        }
        
        // Actualizar estado del botón "Cargar más"
        if (loadMoreBtn) {
            loadMoreBtn.style.display = this.state.hasMoreData ? 'flex' : 'none';
        }
    }
    
    /**
     * Agrupa los eventos de actividad por fecha
     * @param {Array} activities - Lista de actividades
     * @returns {Object} Actividades agrupadas por fecha
     */
    groupActivityByDate(activities) {
        const grouped = {};
        
        activities.forEach(activity => {
            const date = this.formatDate(activity.fecha);
            
            if (!grouped[date]) {
                grouped[date] = [];
            }
            
            grouped[date].push(activity);
        });
        
        return grouped;
    }
    
    /**
     * Formatea una fecha para mostrarla como cabecera de grupo
     * @param {string} dateString - Fecha en formato string
     * @returns {string} Fecha formateada
     */
    formatDate(dateString) {
        try {
            const date = new Date(dateString);
            // Usar opciones de internacionalización según el idioma actual
            const options = { 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric' 
            };
            
            // Usar el idioma del sistema si está disponible en LanguageManager
            const language = window.LanguageManager ? 
                window.LanguageManager.currentLang : 
                navigator.language || 'es';
                
            return date.toLocaleDateString(language === 'en' ? 'en-US' : 'es-ES', options);
        } catch (e) {
            console.error('Error formatting date:', e);
            return dateString;
        }
    }
    
    /**
     * Formatea una hora para mostrarla
     * @param {string} dateString - Fecha completa
     * @returns {string} Hora formateada
     */
    formatTime(dateString) {
        try {
            const date = new Date(dateString);
            // Formato de 24 horas con segundos
            return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });
        } catch (e) {
            console.error('Error formatting time:', e);
            return '';
        }
    }
    
    /**
     * Crea el HTML para un elemento de evento
     * @param {Object} event - Datos del evento
     * @returns {string} HTML del elemento
     */
    createEventItemHTML(event) {
        const isLogin = event.type === 'login';
        const isFailed = event.status === 'failed';
        
        // Localizar textos según el idioma
        const eventTitle = this.getTranslation(
            isLogin ? 'settings.activity.events.login' : 'settings.activity.events.logout'
        );
        const ipLabel = this.getTranslation('settings.activity.fields.ip_address');
        const deviceLabel = this.getTranslation('settings.activity.fields.device');
        const failedBadgeText = this.getTranslation('settings.activity.status.failed');
        const failedAlertText = this.getTranslation('settings.activity.alerts.failed_login');
        
        // Crear HTML del elemento
        let html = `
            <div class="event-icon">
                <i class='bx ${isLogin ? 'bx-log-in-circle' : 'bx-log-out-circle'}'></i>
            </div>
            
            <div class="event-details">
                <div class="event-header">
                    <h3>
                        ${eventTitle}
                        ${isFailed ? `<span class="event-status-badge failed">${failedBadgeText}</span>` : ''}
                    </h3>
                    <span class="event-timestamp">${this.formatTime(event.fecha)}</span>
                </div>
                
                <div class="event-user">${this.state.fullName}</div>
                
                <div class="event-metadata">
                    <div class="metadata-item">
                        <i class='bx bx-globe'></i>
                        <span>${ipLabel}: <b>${event.ip_direccion || '-'}</b></span>
                    </div>
                    <div class="metadata-item">
                        <i class='bx bx-devices'></i>
                        <span>${deviceLabel}: <b>${this.parseDeviceInfo(event.info_dispositivo)}</b></span>
                    </div>
                </div>
        `;
        
        // Si es un intento fallido de inicio de sesión, añadir alerta
        if (isFailed) {
            html += `
                <div class="event-alert">
                    <i class='bx bx-error-circle'></i>
                    ${failedAlertText}
                </div>
            `;
        }
        
        html += `</div>`;
        
        return html;
    }
    
    /**
     * Crea el HTML para el estado vacío
     * @returns {string} HTML del estado vacío
     */
    createEmptyStateHTML() {
        const title = this.getTranslation('settings.activity.empty_state.title');
        const message = this.getTranslation('settings.activity.empty_state.message');
        
        return `
            <div class="empty-state">
                <i class='bx bx-history'></i>
                <h3>${title}</h3>
                <p>${message}</p>
            </div>
        `;
    }
    
    /**
     * Renderiza un mensaje de error
     */
    renderError() {
        const activityListEl = document.querySelector(this.config.selectors.activityList);
        const loadMoreBtn = document.querySelector(this.config.selectors.loadMoreBtn);
        
        if (!activityListEl) return;
        
        // Mensaje de error
        const errorTitle = this.getTranslation('settings.activity.error_state.title');
        const errorMessage = this.getTranslation('settings.activity.error_state.message');
        
        activityListEl.innerHTML = `
            <div class="empty-state">
                <i class='bx bx-error-circle' style="color: var(--danger);"></i>
                <h3>${errorTitle}</h3>
                <p>${errorMessage}</p>
            </div>
        `;
        
        // Ocultar botón de cargar más
        if (loadMoreBtn) {
            loadMoreBtn.style.display = 'none';
        }
    }
    
    /**
     * Analiza la información del dispositivo desde JSON
     * @param {string} deviceInfoStr - Información del dispositivo en formato JSON
     * @returns {string} Información formateada
     */
    parseDeviceInfo(deviceInfoStr) {
        try {
            if (!deviceInfoStr) return 'Desconocido';
            
            const deviceInfo = typeof deviceInfoStr === 'string' 
                ? JSON.parse(deviceInfoStr) 
                : deviceInfoStr;
            
            // Si hay información de navegador y sistema
            if (deviceInfo.browser && deviceInfo.os) {
                return `${deviceInfo.browser} en ${deviceInfo.os}`;
            }
            
            // Si solo hay user agent
            if (deviceInfo.user_agent) {
                const ua = deviceInfo.user_agent.toLowerCase();
                
                // Detección simple de dispositivo/navegador
                if (ua.includes('firefox')) {
                    return 'Firefox';
                } else if (ua.includes('chrome')) {
                    return 'Chrome';
                } else if (ua.includes('safari')) {
                    return 'Safari';
                } else if (ua.includes('edge') || ua.includes('edg')) {
                    return 'Edge';
                } else if (ua.includes('opera') || ua.includes('opr')) {
                    return 'Opera';
                } else {
                    return 'Navegador web';
                }
            }
            
            return 'Desconocido';
            
        } catch (error) {
            console.error('Error parsing device info:', error);
            return 'Desconocido';
        }
    }
    
    /**
     * Exporta el historial de actividad a CSV
     */
    exportActivityToCSV() {
        try {
            if (this.state.activityData.length === 0) {
                this.showNotification('info', this.getTranslation('settings.activity.messages.no_data_export'));
                return;
            }
            
            // Preparar datos para CSV
            const headers = [
                this.getTranslation('settings.activity.csv.date'),
                this.getTranslation('settings.activity.csv.time'),
                this.getTranslation('settings.activity.csv.event_type'),
                this.getTranslation('settings.activity.csv.status'),
                this.getTranslation('settings.activity.csv.ip_address'),
                this.getTranslation('settings.activity.csv.device')
            ];
            
            const csvData = this.state.activityData.map(event => [
                this.formatDate(event.fecha),
                this.formatTime(event.fecha),
                event.type === 'login' 
                    ? this.getTranslation('settings.activity.events.login') 
                    : this.getTranslation('settings.activity.events.logout'),
                event.status === 'failed' 
                    ? this.getTranslation('settings.activity.status.failed') 
                    : this.getTranslation('settings.activity.status.success'),
                event.ip_direccion || '-',
                this.parseDeviceInfo(event.info_dispositivo)
            ]);
            
            // Generar CSV
            let csvContent = headers.join(';') + '\n';
            csvData.forEach(row => {
                csvContent += row.join(';') + '\n';
            });
            
            // Crear y descargar el archivo
            const blob = new Blob(["\ufeff", csvContent], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            const url = URL.createObjectURL(blob);
            const timestamp = new Date().toISOString().replace(/[:.]/g, '-');
            
            link.setAttribute('href', url);
            link.setAttribute('download', `historial_actividad_${timestamp}.csv`);
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            URL.revokeObjectURL(url);
            
            this.showNotification('success', this.getTranslation('settings.activity.messages.export_success'));
            
        } catch (error) {
            console.error('Error exporting activity:', error);
            this.showNotification('error', this.getTranslation('settings.activity.messages.export_error'));
        }
    }
    
    /**
     * Actualiza las traducciones en la UI después de un cambio de idioma
     */
    updateUITranslations() {
        // Si hay datos cargados, volver a renderizar con las nuevas traducciones
        if (this.state.activityData.length > 0) {
            const activityListEl = document.querySelector(this.config.selectors.activityList);
            if (activityListEl) {
                activityListEl.innerHTML = '';
                this.renderActivityHistory();
            }
        }
    }
    
    /**
     * Obtiene una traducción según la clave
     * @param {string} key - Clave de traducción
     * @returns {string} Texto traducido o la clave
     */
    getTranslation(key) {
        return LanguageManager.getTranslation(key) || key
    }
    
    /**
     * Establece el estado de carga
     * @param {boolean} isLoading - Estado de carga
     */
    setLoading(isLoading) {
        this.state.loading = isLoading;
        
        const activityListEl = document.querySelector(this.config.selectors.activityList);
        const loadMoreBtn = document.querySelector(this.config.selectors.loadMoreBtn);
        
        // Si está cargando y no hay datos aún, mostrar spinner
        if (isLoading && this.state.activityData.length === 0) {
            activityListEl.innerHTML = `
                <div class="loading-container">
                    <div class="spinner"></div>
                    <p>${this.getTranslation('settings.activity.loading') || 'Loading'}</p>
                </div>
            `;
        }
        
        // Actualizar el botón de cargar más
        if (loadMoreBtn) {
            if (isLoading) {
                loadMoreBtn.disabled = true;
                loadMoreBtn.innerHTML = `
                    <div class="spinner" style="width: 1rem; height: 1rem; margin: 0;"></div>
                    <span>${this.getTranslation('settings.activity.buttons.loading')}</span>
                `;
            } else {
                loadMoreBtn.disabled = false;
                loadMoreBtn.innerHTML = `
                    <i class='bx bx-history'></i>
                    <span>${this.getTranslation('settings.activity.buttons.load_more')}</span>
                `;
            }
        }
    }
    
    /**
     * Muestra una notificación
     * @param {string} type - Tipo de notificación (success, error, info)
     * @param {string} message - Mensaje a mostrar
     */
    showNotification(type, message) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: type,
                title: type === 'success' 
                    ? this.getTranslation('settings.notifications.success_title')
                    : type === 'error'
                        ? this.getTranslation('settings.notifications.error_title')
                        : this.getTranslation('settings.notifications.info_title'),
                text: message,
                timer: type === 'success' || type === 'info' ? 3000 : undefined,
                timerProgressBar: type === 'success' || type === 'info',
                showConfirmButton: true,
                confirmButtonColor: 'var(--primary)'
            });
        } else {
            // Fallback a alertas nativas si SweetAlert no está disponible
            if (type === 'error') {
                alert(`Error: ${message}`);
            } else {
                alert(message);
            }
        }
    }
}

// Inicializar el módulo cuando el DOM esté completamente cargado
document.addEventListener('DOMContentLoaded', () => {
    window.activityManager = new ActivityManager();
});