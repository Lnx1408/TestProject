const TableModule = {
    // Configuración base
    config: {
        selectors: {
            mainContainer: '#main',
            headerToggle: '#header-toggle',
            tableId: '#tableJugadores',
            // ... otros selectores ...
        },
        endpoint: `${base_url}/Analytics/get_analiticas_jugadores_partida`,
        params: {
            id: null, // Parámetro que necesitamos enviar
            gameCode: null
        }
    },

    // Estado de la tabla
    state: {
        instance: null,
        isInitialized: false
    },

    translations: {
        get: (key) => LanguageManager.getTranslation(`game_analytics_classification.${key}`)
    },

    showColumnInfo(key) {
        const info = this.translations.get(`table.info.${key}`);
        if (info) {
            Swal.fire({
                title: info.title,
                text: info.description,
                icon: 'info',
                confirmButtonColor: '#1976D2',
                customClass: {
                    container: 'analytics-type-modal',
                    popup: 'analytics-modal-popup',
                }
            });
        }
    },

    initializeResponsiveHandling() {
        // Obtener referencias a los elementos
        const mainContainer = document.querySelector(this.config.selectors.mainContainer);
        const headerToggle = document.querySelector(this.config.selectors.headerToggle);

        if (!mainContainer || !this.state.instance) return;

        const responsiveColumnIndexes = [1]; // Puedes agregar más índices aquí

        const updateResponsiveColumns = () => {
            if (this.state.instance && this.state.instance.rows().any()) {  // Verificamos que existan filas
                try {

                    this.state.instance
                        .rows()
                        .every(function (rowIdx) {
                            // Actualizamos cada columna definida
                            responsiveColumnIndexes.forEach(colIdx => {
                                this.state.instance
                                    .cell(rowIdx, colIdx)
                                    .invalidate();
                            });
                        }.bind(this));

                    // Un solo draw al final
                    this.state.instance.draw(false);
                } catch (error) {
                    console.warn('Error al actualizar las columnas:', error);
                }
            }
        };

        // Crear ResizeObserver para el contenedor principal
        const resizeObserver = new ResizeObserver(entries => {
            if (this.state.instance) {
                // Ajustamos las columnas
                this.state.instance.columns.adjust();
                // llamamos a la función de actualización después de un tiempo
                setTimeout(() => {
                    //updateResponsiveColumns();
                    this.state.instance.responsive.recalc();
                }, 100);
            }
        });

        // Observar el contenedor principal
        resizeObserver.observe(mainContainer);

        // Manejar el evento del botón toggle
        /**
        if (headerToggle) {
            headerToggle.addEventListener('click', () => {
                // Esperar a que termine la transición del menú
                setTimeout(() => {
                    if (this.state.instance) {
                        this.state.instance.columns.adjust().responsive.recalc();
                    }
                }, 300); // Ajusta este tiempo según la duración de tu animación
            });
        }
         */

        // Opcional: Si quieres optimizar las llamadas para evitar múltiples actualizaciones
        /*
        this.debounceResize = this.debounce(() => {
            if (this.state.instance) {
                updateResponsiveColumns();
            }
        }, 250);

        window.addEventListener('resize', this.debounceResize);
        */
    },

    // Función de utilidad para debounce
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    },
    // Asegurarnos de que la tabla se ajuste después de cualquier cambio en el DOM
    refreshTable() {
        if (this.state.instance) {
            this.state.instance.columns.adjust().responsive.recalc();
        }
    },

    // Utilidad para formatear nombres
    utils: {
        formatName(nombres, apellidos, type = 'full') {
            const nombresArray = nombres.split(' ');
            const apellidosArray = apellidos.split(' ');

            if (type === 'short') {
                // Tomar solo el primer nombre y primer apellido
                return `${nombresArray[0]} ${apellidosArray[0]}`;
            }
            // Retornar nombre completo
            return `${nombres} ${apellidos}`;
        },

        formatTime(seconds) {
            const totalSeconds = parseInt(seconds);
            const minutes = Math.floor(totalSeconds / 60);
            const remainingSeconds = totalSeconds % 60;
            const formattedSeconds = remainingSeconds < 10 ? `0${remainingSeconds}` : remainingSeconds;

            return `${minutes}min ${formattedSeconds}s`;
        },

        formatDate(dateString) {
            return new Date(dateString).toLocaleDateString('es-ES', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric'
            });
        },

        getStatusInfo(status, status_text) {
            const statusMap = {
                '1': 'completed',
                '0': 'process',
                '-1': 'pending'
            };
            return {
                text: status_text,
                class: statusMap[status] || 'pending'
            };
        },
    },

    viewDetails(nombres, apellidos, id_jugador) {
        Swal.fire({
            title: this.translations.get('modals.view_details.title'),
            html: `${this.translations.get('modals.view_details.message')} <b>${nombres} ${apellidos}</b>`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#1976D2',
            cancelButtonColor: '#D32F2F',
            confirmButtonText: this.translations.get('modals.view_details.confirm'),
            cancelButtonText: this.translations.get('modals.view_details.cancel'),
            customClass: {
                container: 'analytics-type-modal',
                popup: 'analytics-modal-popup',
            },
        }).then((result) => {
            if (result.isConfirmed) {
                // Redirigir a la página de detalles
                window.location.href = `${base_url}/Analytics/details_user_clasification?gamecode=${encodeURIComponent(this.config.params.gameCode)}&Jugador=${encodeURIComponent(id_jugador)}`;
            }
        });
    },

    columnDefs: {
        // Definiciones detalladas de columnas
        columns: [
            {
                data: null,
                render: (data) => ''
            },
            {
                data: null,
                className: "dt-center",
                title: "Jugador",
                title: `<span data-i18n="game_analytics_classification.table.columns.player">Jugador</span> <i class='bx bx-info-circle info-icon' style='color: #666; cursor: pointer;' onclick='TableModule.showColumnInfo("player"); event.stopPropagation();'></i>`,
                //width: "50%",
                responsivePriority: 1, // Alta prioridad - siempre visible
                render: function (data, type, row) {
                    // Detectar si estamos en viewport móvil
                    const isMobile = window.innerWidth <= 480;
                    // Formatear el nombre del jugador
                    const displayName = isMobile ?
                        TableModule.utils.formatName(row.nombres, row.apellidos, 'short') :
                        TableModule.utils.formatName(row.nombres, row.apellidos, 'full');

                    return `
                        <div class="player-info">
                            <div class="avatar-circle" style="background-color: ${DashboardModule.AvatarUtils.getAvatarColor(displayName)}">
                                <span class="initials">${DashboardModule.AvatarUtils.getInitials(displayName)}</span>
                            </div>
                            <span class="player-name">${displayName}</span>
                        </div>
                    `;
                }.bind(this)
            },
            {
                data: 'tiempo_empleado',
                title: `<span data-i18n="game_analytics_classification.table.columns.time">Tiempo</span> <i class='bx bx-info-circle info-icon' style='color: #666; cursor: pointer;' onclick='TableModule.showColumnInfo("time"); event.stopPropagation();'></i>`,
                className: "dt-center",
                responsivePriority: 2,
                width: "15%",
            },
            {
                data: 'intentos',
                title: `<span data-i18n="game_analytics_classification.table.columns.attempts">Intentos</span> <i class='bx bx-info-circle info-icon' style='color: #666; cursor: pointer;' onclick='TableModule.showColumnInfo("attempts"); event.stopPropagation();'></i>`,
                className: "dt-center",
                width: "10%",
                responsivePriority: 2,
                type: 'string',
            },
            {
                data: 'fecha',
                title: `<span data-i18n="game_analytics_classification.table.columns.last_attempt">Fecha</span> <i class='bx bx-info-circle info-icon' style='color: #666; cursor: pointer;' onclick='TableModule.showColumnInfo("last_attempt"); event.stopPropagation();'></i>`,
                className: "dt-center",
                width: "15%",
                responsivePriority: 3,
                type: 'string',
            },
            {
                data: 'porcentaje',
                title: `<span data-i18n="game_analytics_classification.table.columns.progress">Avance</span> <i class='bx bx-info-circle info-icon' style='color: #666; cursor: pointer;' onclick='TableModule.showColumnInfo("progress"); event.stopPropagation();'></i>`,
                className: "dt-center",
                width: "15%",
                responsivePriority: 2,
                type: 'string',
                render: data => `${data}%`
            },
            {
                data: null,
                title: `<span data-i18n="game_analytics_classification.table.columns.status">Estado</span> <i class='bx bx-info-circle info-icon' style='color: #666; cursor: pointer;' onclick='TableModule.showColumnInfo("status"); event.stopPropagation();'></i>`,
                className: "dt-center",
                width: "25%",
                responsivePriority: 3,
                render: function (data, type, row) {
                    return `<span class="status ${row.estado.class}">${row.estado.text}</span>`;
                }
            },
            {
                data: null,
                title: "Acciones",
                title: `<span data-i18n="game_analytics_classification.table.columns.actions">Acciones</span>`,
                className: "dt-center",
                //width: "40%",
                responsivePriority: 1,
                orderable: false,
                render: function (data, type, row) {
                    return `
                    <div class="btn-group">
                        <button class="btn btn-info btn-sm"
                            onclick="TableModule.viewDetails('${row.nombres}', '${row.apellidos}', '${row.id_jugador}'); event.stopPropagation();">
                            <i class='bx bx-info-circle'></i>
                        </button>
                    </div>
                `;
                }
            }
        ]
    },


    // Configuración de DataTables
    getDataTableConfig() {
        return {
            ajax: this.getAjaxConfig(),
            columns: this.columnDefs.columns,
            language: this.getLanguageConfig(),
            responsive: {
                details: {
                    type: 'column',
                    target: 'tr'
                }
            },
            columnDefs: [
                {
                    // Columna del botón expand
                    className: 'dtr-control',
                    orderable: false,
                    targets: 0,
                    width: '2.5rem' // Ancho fijo para el botón
                }
            ],
            processing: true,
            serverSide: false,
            drawCallback: (settings) => {
                // Aquí puedes agregar lógica después de que se dibujen los datos
                console.log('Tabla actualizada');
            }
        };
    },

    // Configuración de Ajax
    getAjaxConfig() {
        return {
            url: this.config.endpoint,
            type: 'POST',
            contentType: 'application/json', // Especificamos que enviaremos JSON
            data: (d) => {
                const requestData = {
                    ...d,
                    gamecode: this.config.params.gameCode
                };
                return JSON.stringify({
                    encryptedData: CryptoModule.encrypt(requestData)
                });
            },
            dataSrc: (response) => {
                const decryptedResponse = CryptoModule.decrypt(response.data);
                // Verificamos si la respuesta es exitosa y tiene datos
                if (decryptedResponse.status && decryptedResponse.analytics) {
                    // Transformamos los datos al formato que necesita la tabla
                    return decryptedResponse.analytics.map(item => ({
                        nombres: item.nombres,     // Guardamos nombres completos
                        apellidos: item.apellidos, // Guardamos apellidos completos
                        tiempo_empleado: this.utils.formatTime(item.tiempo_total_jugador),
                        estado: this.utils.getStatusInfo(item.estado, item.estado_texto),
                        intentos: item.intentos,
                        porcentaje: item.porcentaje_avance_alt,
                        fecha: this.utils.formatDate(item.ultimo_intento),
                        id_jugador: item.id_jugador
                    }));
                }
                return [];
            },
            beforeSend: this.handleBeforeSend.bind(this),
            error: this.handleError.bind(this)
        };
    },

    // Configuración de lenguaje
    getLanguageConfig() {
        return {
            url: `${base_url}/Assets/js/plugins/datatables/es-ES.json`,
            paginate: {
                first: '«',
                last: '»',
                next: '›',
                previous: '‹'
            },
            loadingRecords: '<div class="spinner">Cargando...</div>',
            zeroRecords: 'No se encontraron registros'
        };
    },

    // Manejadores de eventos
    handleBeforeSend() {
        // Lógica antes de enviar la petición
        console.log('Iniciando petición...');
    },

    handleError(xhr, error, thrown) {
        console.error('Error en la petición:', error);
        // Manejo de errores
        const errorMessage = 'Ocurrió un error al cargar los datos. Por favor, intente nuevamente.';
    },

    // Métodos públicos
    initialize(id) {
        this.config.params.id = id;
        if (!this.state.isInitialized) {
            this.state.instance = $(this.config.selectors.tableId).DataTable(this.getDataTableConfig());
            this.state.isInitialized = true;
        }
    },

    reload() {
        if (this.state.instance) {
            this.state.instance.ajax.reload();
        }
    },

    updateParams(newParams) {
        this.config.params = { ...this.config.params, ...newParams };
        this.reload();
    },

    // Función para obtener parámetros de la URL
    getUrlParameter(name) {
        const params = new URLSearchParams(window.location.search);
        return params.get(name);
    },

    // Función para inicializar parámetros
    initializeParams() {
        const gameCode = this.getUrlParameter('gamecode');
        if (!gameCode) {
            console.error('No se encontró el código de juego en la URL');
            return false;
        }
        this.config.params.gameCode = gameCode;
        return true;
    },

    destroy() {
        if (this.state.instance) {
            this.state.instance.destroy();
            this.state.isInitialized = false;

            // Remover event listeners
            window.removeEventListener('resize', this.debounceResize);
        }
    }
};

const AnalyticsModule = {
    config: {
        endpoint: `${base_url}/Analytics/get_analiticas_generales_partida`,
        selectors: {
            precisionIntento: '#precision-primer-intento',
            tiempoPromedio: '#tiempo-promedio',
            totalJugadores: '#total-jugadores',
            promedioIntentos: '#promedio-intentos',
            primerIntento: '#primer-intento',
            procentajePrimerIntento: '#procentaje-primer-intento',
            dosATresIntentos: '#dos-tres-intentos',
            procentajedosATresIntentos: '#porcentaje-dos-tres-intentos',
            masTresIntentos: '#mas-tres-intentos',
            procentajemasTresIntentos: '#porcentaje-mas-tres-intentos'
        }
    },

    async fetchAnalytics(gameCode) {
        try {
            const requestData = {
                gamecode: gameCode
            };

            const response = await fetch(this.config.endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    encryptedData: CryptoModule.encrypt(requestData)
                })
            });

            const data = await response.json();
            const decryptedData = CryptoModule.decrypt(data.data);

            if (decryptedData.status) {
                this.updateDashboard(decryptedData);
            } else {
                console.error('Error en la respuesta:', decryptedData.message);
            }
        } catch (error) {
            console.error('Error obteniendo analytics:', error);
        }
    },

    updateDashboard(data) {
        if (!data || !data.analytics || !data.analytics[0]) return;
        
        const stats = data.analytics[0];
        
        // Función auxiliar para actualizar elementos
        const updateElement = (selector, value) => {
            const element = document.querySelector(selector);
            if (element) element.textContent = value;
        };

        // Actualizamos cada valor
        updateElement(
            this.config.selectors.precisionIntento, 
            `+${parseFloat(stats.porcentaje_promedio_aciertos_primer_intento).toFixed(1)}%`
        );
        
        updateElement(
            this.config.selectors.tiempoPromedio,
            this.formatTime(stats.tiempo_promedio_total_segundos)
        );

        updateElement(
            this.config.selectors.totalJugadores,
            stats.total_jugadores
        );

        updateElement(
            this.config.selectors.promedioIntentos,
            this.formatNumber(stats.promedio_intentos_necesarios)
        );

        updateElement(
            this.config.selectors.primerIntento, 
            this.formatNumber(stats.completaron_primer_intento)
        );

        updateElement(
            this.config.selectors.procentajePrimerIntento, 
            `${parseFloat(stats.porcentaje_completaron_primer_intento).toFixed(1)}%`
        );

        updateElement(
            this.config.selectors.dosATresIntentos, 
            this.formatNumber(stats.completaron_2_3_intentos)
        );

        updateElement(
            this.config.selectors.procentajedosATresIntentos, 
            `${parseFloat(stats.porcentaje_completaron_2_3_intentos).toFixed(1)}%`
        );

        updateElement(
            this.config.selectors.masTresIntentos, 
            this.formatNumber(stats.necesitaron_mas_3_intentos)
        );

        updateElement(
            this.config.selectors.procentajemasTresIntentos, 
            `${parseFloat(stats.porcentaje_necesitaron_mas_3_intentos).toFixed(1)}%`
        );

    },

    formatTime(seconds) {
        const totalSeconds = parseInt(seconds);
        if(totalSeconds < 60){
            return `${totalSeconds} seg`;
        }else{
            const minutes = Math.floor(totalSeconds / 60);
            //const remainingSeconds = totalSeconds % 60;
            //return `${minutes}:${remainingSeconds.toString().padStart(2, '0')}`;
            return `${minutes} min`;
        }
    },

    formatNumber(value) {
        const number = parseFloat(value);
        return number % 1 === 0 ? number.toString() : number.toFixed(2);
    },

    init(gameCode) {
        this.fetchAnalytics(gameCode);
    }
};


// Módulo principal de funcionalidades
const DashboardModule = {
    // Configuración inicial
    config: {
        cardsToShow: 4,
        selectors: {
            showMoreBtn: '#showMoreBtn',
            cardItem: '.card-item',
            analyse: '.analyse',
            tableRows: '#tableJugadores tbody tr',
            playersTable: '#tableJugadores'
        }
    },

    // Estado de la aplicación
    state: {
        isShowingAll: false
    },

    translations: {
        get: (key) => LanguageManager.getTranslation(`game_analytics_classification.${key}`)
    },

    showStatsInfo(key) {
        const info = this.translations.get(`cards.${key}.info`);
        if (info) {
            Swal.fire({
                title: info.title,
                text: info.description,
                icon: 'info',
                confirmButtonColor: '#1976D2',
                customClass: {
                    container: 'analytics-type-modal',
                    popup: 'analytics-modal-popup',
                }
            });
        }
    },

    initializeTable(id) {
        //TableModule.initializeParams();
        if (!TableModule.initializeParams()) {
            // Manejar el caso cuando no hay código de juego
            return;
        }
        TableModule.initialize(id);
        TableModule.initializeResponsiveHandling();
    },

    // Inicialización
    init() {
        this.bindElements();
        this.setupEventListeners();
        this.initializeCards();
        this.initializeAvatars();
        this.initializeTable('tu_id_aqui');
        // Obtenemos el gameCode de la URL
        const gameCode = TableModule.getUrlParameter('gamecode');
        if (gameCode) {
            AnalyticsModule.init(gameCode);
        } else {
            console.error('No se encontró el código de juego');
        }
    },

    // Vinculación de elementos del DOM
    bindElements() {
        this.elements = {
            showMoreBtn: document.querySelector(this.config.selectors.showMoreBtn),
            hiddenCards: document.querySelectorAll(`${this.config.selectors.cardItem}.hidden`),
            analyseSection: document.querySelector(this.config.selectors.analyse)
        };
    },

    // Configuración de event listeners
    setupEventListeners() {
        if (this.elements.showMoreBtn) {
            this.elements.showMoreBtn.addEventListener('click', () => this.handleShowMoreClick());
        }
    },

    // Inicialización de las cards
    initializeCards() {
        if (this.elements.hiddenCards.length === 0 && this.elements.showMoreBtn) {
            this.elements.showMoreBtn.style.display = 'none';
        }
    },

    // Manejador del botón "Ver más/menos"
    handleShowMoreClick() {
        this.state.isShowingAll ? this.hideExtraCards() : this.showMoreCards();
    },

    // Mostrar más cards
    showMoreCards() {
        const stillHidden = document.querySelectorAll(`${this.config.selectors.cardItem}.hidden`);

        for (let i = 0; i < this.config.cardsToShow && i < stillHidden.length; i++) {
            this.animateCardReveal(stillHidden[i], i);
        }

        if (stillHidden.length <= this.config.cardsToShow) {
            this.updateShowMoreButton(true);
        }
    },

    // Ocultar cards extras
    hideExtraCards() {
        const allCards = document.querySelectorAll(this.config.selectors.cardItem);
        allCards.forEach((card, index) => {
            if (index >= this.config.cardsToShow) {
                card.classList.add('hidden');
            }
        });

        this.updateShowMoreButton(false);
        this.scrollToTop();
    },

    // Animación de revelar card
    animateCardReveal(card, index) {
        card.classList.remove('hidden');
        setTimeout(() => {
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 100);
    },

    // Actualizar estado del botón
    updateShowMoreButton(isShowingAll) {
        this.state.isShowingAll = isShowingAll;
        this.elements.showMoreBtn.innerHTML = isShowingAll
            ? `<i class="bx bx-minus"></i> <span data-i18n="game_analytics_classification.buttons.show_less">${this.translations.get('buttons.show_less')}</span>`
            : `<i class="bx bx-plus"></i> <span data-i18n="game_analytics_classification.buttons.show_more">${this.translations.get('buttons.show_more')}</span>`;    
    },

    // Scroll suave hacia arriba
    scrollToTop() {
        window.scrollTo({
            top: this.elements.analyseSection.offsetTop,
            behavior: 'smooth'
        });
    },

    // Utilidades para los avatares
    AvatarUtils: {
        getInitials(name) {
            const words = name.split(' ');
            return words.length === 1
                ? words[0].substring(0, 2).toUpperCase()
                : (words[0][0] + words[words.length - 1][0]).toUpperCase();
        },

        getAvatarColor(name) {
            const colors = [
                'var(--primary)',
                'var(--success)',
                'var(--warning)',
                'var(--danger)',
                '#7E57C2',
                '#26A69A',
                '#FF7043'
            ];

            let hash = 0;
            for (let i = 0; i < name.length; i++) {
                hash = name.charCodeAt(i) + ((hash << 5) - hash);
            }

            return colors[Math.abs(hash) % colors.length];
        },

        createAvatarElement(name) {
            const initials = this.getInitials(name);
            const color = this.getAvatarColor(name);

            const avatar = document.createElement('div');
            avatar.className = 'avatar-circle';
            avatar.style.backgroundColor = color;
            avatar.innerHTML = `<span class="initials">${initials}</span>`;

            return avatar;
        }
    },

    // Inicialización de avatares
    initializeAvatars() {
        document.querySelectorAll(this.config.selectors.tableRows).forEach(row => {
            const nameElement = row.querySelector('td:first-child p');
            if (!nameElement) return;

            const name = nameElement.textContent;
            const avatar = this.AvatarUtils.createAvatarElement(name);

            const imgElement = row.querySelector('td:first-child img');
            if (imgElement) {
                imgElement.replaceWith(avatar);
            }
        });
    }
};


const ReportModule = {
    init() {
        this.bindEvents();
    },

    bindEvents() {
        const reportBtn = document.getElementById('generateReportBtn');
        if (reportBtn) {
            reportBtn.addEventListener('click', () => this.showReportOptions());
        }
    },

    async showReportOptions() {
        const result = await Swal.fire({
            title: this.translations.get('report.modal.title'),
            icon: 'question',
            html: `
                <div class="report-options">
                    <p>${this.translations.get('report.modal.select_option')}</p>
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: this.translations.get('report.modal.preview'),
            cancelButtonText: this.translations.get('report.modal.cancel'),
            confirmButtonColor: '#1976D2',
            customClass: {
                container: 'report-modal',
                popup: 'analytics-modal-popup',
                actions: 'report-modal-actions',
                confirmButton: 'btn-preview',
                denyButton: 'btn-print'
            },
        });

        if (result.isConfirmed) {
            //PREVISUALIZACION Y GUARDADO
            this.downloadReport();
        }
    },

    async downloadReport() {
        const gameCode = this.getGameCode();
        const url = `${base_url}/Analytics/downloadReportGeneralClassification?gamecode=${encodeURIComponent(gameCode)}`;
        window.open(url, '_blank');
    },

    getGameCode() {
        // Obtener el código del juego de la URL
        const urlParams = new URLSearchParams(window.location.search);
        return urlParams.get('gamecode');
    },

    translations: {
        get: (key) => LanguageManager.getTranslation(`analytics.${key}`)
    }
};

// Inicialización cuando el DOM está listo
document.addEventListener('DOMContentLoaded', () => {
    initializeModules();
});

function initializeModules() {
    // Inicializar módulos principales
    DashboardModule.init();
    ReportModule.init();
}