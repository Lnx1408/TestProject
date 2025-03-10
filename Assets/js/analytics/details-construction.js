const TableIntentosModule = {
    config: {
        selectors: {
            mainContainer: '#main',
            headerToggle: '#header-toggle',
            tableId: '#tableIntentos'
        },
        detailsEndpoint: `${base_url}/Analytics/get_detalles_intento_construction`,
    },

    state: {
        instance: null,
        isInitialized: false
    },

    utils: {
        formatTime(seconds) {
            const totalSeconds = parseInt(seconds);
            const minutes = Math.floor(totalSeconds / 60);
            const remainingSeconds = totalSeconds % 60;
            return `${minutes}min ${remainingSeconds.toString().padStart(2, '0')}s`;
        },

        formatDate(dateString) {
            return new Date(dateString).toLocaleDateString('es-ES', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        },

        formatPrecision(value) {
            return `${parseFloat(value).toFixed(2)}%`;
        }
    },

    async fetchAttemptDetails(id_intento) {
        try {
            const requestData = {
                id_intento: id_intento,
                gamecode: DashboardModule.config.params.gameCode,
                id_jugador: DashboardModule.config.params.id
            };

            const response = await fetch(this.config.detailsEndpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    encryptedData: CryptoModule.encrypt(requestData)
                })
            });

            const data = await response.json();
            return CryptoModule.decrypt(data.data);
        } catch (error) {
            console.error('Error obteniendo detalles:', error);
            throw error;
        }
    },

    generateHeaderStatsHTML(headerDetails) {
        return `
            <div class="analyse stats-container grid grid-cols-3 gap-4 mb-6">
                <div class="stat-item bg-gray-100 p-4 rounded-lg text-center">
                    <i class='bx bx-time text-2xl mb-2 text-primary'></i>
                    <p class="text-sm text-gray-600">Tiempo</p>
                    <p class="text-lg font-bold">${this.utils.formatTime(headerDetails.tiempo)}</p>
                </div>
                <div class="stat-item bg-gray-100 p-4 rounded-lg text-center">
                    <i class='bx bx-check-circle text-2xl mb-2 text-success'></i>
                    <p class="text-sm text-gray-600">Precisión</p>
                    <p class="text-lg font-bold">${headerDetails.margen_presicion}%</p>
                </div>
                <div class="stat-item bg-gray-100 p-4 rounded-lg text-center">
                    <i class='bx bx-x-circle text-2xl mb-2 text-danger'></i>
                    <p class="text-sm text-gray-600">Movimientos</p>
                    <p class="text-lg font-bold">${headerDetails.movimientos}</p>
                </div>
            </div>
        `;
    },

    generateRequirementsHTML(requirements) {
        // Función auxiliar para construir el requisito completo
        const buildCompleteRequirement = (fragments) => {
            return fragments
                .filter(f => f.posicion_usada > 0)
                .sort((a, b) => a.posicion_usada - b.posicion_usada)
                .map(f => f.texto)
                .join(' ');
        };
    
        // Función para generar el HTML de cada fragmento
        const generateFragmentHTML = (fragment) => `
            <div class="fragment-detail ${fragment.es_correcto ? 'correct' : 'incorrect'}">
                <div class="fragment-content">
                    <span class="fragment-text">${fragment.texto}</span>
                    <i class='bx ${fragment.es_correcto ? 'bx-check' : 'bx-x'}'></i>
                </div>
                <div class="fragment-stats">
                    <span class="stat-item">
                        <i class='bx bx-move'></i> ${fragment.cantidad_movimientos} ${LanguageManager.getTranslation('details_construction.modal.movements')}
                    </span>
                    <span class="stat-item">
                        <i class='bx bx-time'></i> ${fragment.tiempo_colocacion}s
                    </span>
                    <span class="stat-item">
                        <i class='bx bx-target-lock'></i> ${LanguageManager.getTranslation('details_construction.modal.position')} ${fragment.posicion_usada}
                    </span>
                </div>
            </div>
        `;
    
        // Organizamos los fragmentos
        const userConstruction = requirements.attemptDetails
            .filter(f => f.posicion_usada > 0)
            .sort((a, b) => a.posicion_usada - b.posicion_usada);
        const decoyFragments = requirements.attemptDetails.filter(f => f.es_señuelo);
        const unusedFragments = requirements.attemptDetails.filter(f => !f.es_señuelo && f.posicion_usada === 0);
    
        return `
            <div class="attempt-details-container">
                <div class="requirement-result">
                    <h3 class="section-title">${LanguageManager.getTranslation('details_construction.modal.constructed_requirement')}</h3>
                    <div class="final-requirement">
                        ${buildCompleteRequirement(requirements.attemptDetails)}
                    </div>
                </div>
    
                <div class="fragments-summary">
                    <div class="stats-overview">
                        <div class="stat-box">
                            <i class='bx bx-check-circle'></i>
                            <span>${LanguageManager.getTranslation('details_construction.modal.correct_fragments')}: ${requirements.headerDetails.fragmentos_correctos}</span>
                        </div>
                        <div class="stat-box">
                            <i class='bx bx-time'></i>
                            <span>${LanguageManager.getTranslation('details_construction.modal.total_time')}: ${requirements.headerDetails.tiempo}s</span>
                        </div>
                        <div class="stat-box">
                            <i class='bx bx-move'></i>
                            <span>${LanguageManager.getTranslation('details_construction.modal.total_movements')}: ${requirements.headerDetails.movimientos}</span>
                        </div>
                    </div>
                </div>
    
                <div class="fragments-section">
                    <h4 class="section-subtitle">${LanguageManager.getTranslation('details_construction.modal.user_construction')}</h4>                
                    <div class="fragments-list">
                        ${userConstruction.map(generateFragmentHTML).join('')}
                    </div>
                </div>
    
                ${decoyFragments.length > 0 ? `
                    <div class="fragments-section decoy-fragments">
                        <h4 class="section-subtitle">${LanguageManager.getTranslation('details_construction.modal.decoys_used')} (${decoyFragments.length})</h4>
                        <div class="fragments-list">
                            ${decoyFragments.map(generateFragmentHTML).join('')}
                        </div>
                    </div>
                ` : ''}
    
                ${unusedFragments.length > 0 ? `
                    <div class="fragments-section unused-fragments">
                        <h4 class="section-subtitle">${LanguageManager.getTranslation('details_construction.modal.unused_fragments')} (${unusedFragments.length})</h4>
                        <div class="fragments-list">
                            ${unusedFragments.map(generateFragmentHTML).join('')}
                        </div>
                    </div>
                ` : ''}
            </div>
        `;
    },

    async viewAttemptDetails(id_intento) {
        Swal.fire({
            title: `${LanguageManager.getTranslation('details_construction.modal.loading')}`,
            //allowOutsideClick: false,
            allowOutsideClick: true, // Permitir cerrar al hacer clic fuera
            allowEscapeKey: true,    // Permitir cerrar con tecla ESC
            width: 'auto',           // Auto para que tome el porcentaje que definiremos
            showConfirmButton: false, // No mostrar botón en el loading
            customClass: {
                container: 'analytics-type-modal',
                popup: 'analytics-modal-popup',
            },
            didOpen: async () => {
                try {
                    Swal.showLoading();
                    const details = await this.fetchAttemptDetails(id_intento);

                    if (details.status) {
                        const contentHTML = `
                            <div class="attempt-details-container">
                                ${this.generateHeaderStatsHTML(details.headerDetails)}
                                ${this.generateRequirementsHTML(details)}
                            </div>
                        `;

                        Swal.hideLoading(); // Ocultar explícitamente el loading
                        Swal.update({
                            title: `${LanguageManager.getTranslation('details_construction.modal.title')}`,
                            html: contentHTML,
                            showConfirmButton: true,
                            confirmButtonText: `${LanguageManager.getTranslation('details_construction.modal.close')}`,
                            customClass: {
                                container: 'attempt-details-modal',
                                popup: 'attempt-details-popup',
                                content: 'attempt-details-content'
                            }
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'No se pudieron cargar los detalles',
                            customClass: {
                                container: 'analytics-type-modal',
                                popup: 'analytics-modal-popup',
                            },
                        });
                    }
                } catch (error) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Ocurrió un error al cargar los detalles',
                        customClass: {
                            container: 'analytics-type-modal',
                            popup: 'analytics-modal-popup',
                        },
                    });
                }
            }
        });
    },

    updateTableTranslations() {
        if (this.state.instance) {
            this.state.instance.columns().header().each(function(header) {
                // Buscar elementos con data-i18n dentro del header
                const translatable = header.querySelector('[data-i18n]');
                if (translatable) {
                    const key = translatable.getAttribute('data-i18n');
                    translatable.textContent = LanguageManager.getTranslation(key) || key;
                }
            });
        }
    },

    showColumnInfo(key) {
        const info = LanguageManager.getTranslation(`details_construction.table.${key}`);
        if (info) {
            Swal.fire({
                title: info.title_modal,
                text: info.description,
                icon: 'info',
                confirmButtonColor: '#1976D2',
                customClass: {
                    container: 'analytics-type-modal',
                    popup: 'analytics-modal-popup',
                },
            });
        }
    },

    columnDefs: {
        columns: [
            {
                data: null,
                render: (data) => ''
            },
            {
                type: 'string',
                data: 'numero_intento',
                title: `<span data-i18n="details_construction.table.attempt.title">Intento</span> <i class='bx bx-info-circle info-icon' style='color: #666; cursor: pointer;' onclick='TableIntentosModule.showColumnInfo("attempt"); event.stopPropagation();'></i>`,
                className: "dt-center",
                responsivePriority: 1,
                width: "10%"
            },
            {
                type: 'string',
                data: 'id_requisito',
                title: "Id Requisito",
                className: "dt-center",
                responsivePriority: 1,
                width: "10%"
            },
            {
                data: 'tiempo_intento',
                title: `<span data-i18n="details_construction.table.time.title">Tiempo</span> <i class='bx bx-info-circle info-icon' style='color: #666; cursor: pointer;' onclick='TableIntentosModule.showColumnInfo("time"); event.stopPropagation();'></i>`,
                className: "dt-center",
                responsivePriority: 2,
                width: "15%",
                render: (data) => TableIntentosModule.utils.formatTime(data)
            },
            {
                type: 'string',
                data: 'cantidad_movimientos',
                title: `<span data-i18n="details_construction.table.movements.title">Movimientos</span> <i class='bx bx-info-circle info-icon' style='color: #666; cursor: pointer;' onclick='TableIntentosModule.showColumnInfo("movements"); event.stopPropagation();'></i>`,
                className: "dt-center",
                responsivePriority: 2,
                width: "15%"
            },
            {
                data: 'fragmentos_correctos',
                title: `<span data-i18n="details_construction.table.correct_fragments.title">Fragmentos Correctos</span> <i class='bx bx-info-circle info-icon' style='color: #666; cursor: pointer;' onclick='TableIntentosModule.showColumnInfo("correct_fragments"); event.stopPropagation();'></i>`,
                className: "dt-center",
                responsivePriority: 2,
                type: 'string',
                width: "10%"
            },
            {
                data: 'fragmentos_incorrectos',
                title: `<span data-i18n="details_construction.table.incorrect_fragments.title">Fragmentos Incorrectos</span> <i class='bx bx-info-circle info-icon' style='color: #666; cursor: pointer;' onclick='TableIntentosModule.showColumnInfo("incorrect_fragments"); event.stopPropagation();'></i>`,
                className: "dt-center",
                responsivePriority: 4,
                type: 'string',
                width: "10%"
            },
            {
                type: 'string',
                data: 'señuelos_usados',
                title: `<span data-i18n="details_construction.table.decoys_used.title">Señuelos Usados</span> <i class='bx bx-info-circle info-icon' style='color: #666; cursor: pointer;' onclick='TableIntentosModule.showColumnInfo("decoys_used"); event.stopPropagation();'></i>`,
                responsivePriority: 3,
                className: "dt-center",
                width: "10%"
            },
            {
                type: 'string',
                data: 'precision_construccion',
                title: `<span data-i18n="details_construction.table.progressive_accuracy.title">Precisión Progresiva</span> <i class='bx bx-info-circle info-icon' style='color: #666; cursor: pointer;' onclick='TableIntentosModule.showColumnInfo("progressive_accuracy"); event.stopPropagation();'></i>`,
                className: "dt-center",
                responsivePriority: 2,
                width: "15%",
                render: data => TableIntentosModule.utils.formatPrecision(data)
            },
            {
                data: 'fecha_intento',
                title: `<span data-i18n="details_construction.table.date.title">Fecha</span> <i class='bx bx-info-circle info-icon' style='color: #666; cursor: pointer;' onclick='TableIntentosModule.showColumnInfo("date"); event.stopPropagation();'></i>`,
                className: "dt-center",
                responsivePriority: 3,
                width: "15%",
                render: data => TableIntentosModule.utils.formatDate(data)
            },
            {
                data: null,
                title: "Ver",
                className: "dt-center",
                width: "20%",
                responsivePriority: 1,
                orderable: false,
                render: function (data, type, row) {
                    return `
                        <div class="btn-group">
                            <button class="btn btn-info btn-sm" onclick="TableIntentosModule.viewAttemptDetails(${row.id_intento}); event.stopPropagation();">
                                <i class='bx ri-eye-line'></i>
                            </button>
                        </div>
                    `;
                }
            }
        ]
    },

    initializeWithData(attemptsData) {
        if (!this.state.isInitialized) {
            this.state.instance = $(this.config.selectors.tableId).DataTable({
                data: attemptsData,
                columns: this.columnDefs.columns,
                language: this.getLanguageConfig(),
                responsive: {
                    details: {
                        type: 'column',
                        target: 'tr'
                    }
                },
                order: [[2, 'asc']], // Ordenar por la columna de requisito (índice 2)
                columnDefs: [
                    {
                        className: 'dtr-control',
                        orderable: false,
                        targets: 0,
                        width: '2.5rem'
                    },
                    {
                        // Ocultar la columna de id_requisito ya que ahora se muestra en el grupo
                        targets: 2,
                        visible: false
                    }
                ],
                rowGroup: {
                    dataSrc: 'id_requisito', // Agrupar por id_requisito
                    startRender: (rows, group) => {
                        // Obtener el requisito_completo de la primera fila del grupo
                        const requisitoCompleto = rows.data()[0].requisito_completo;
                        return `
                            <tr class="group-header">
                                <td colspan="${this.columnDefs.columns.length}">
                                    <div><strong>Requisito:</strong> ${requisitoCompleto}</div>
                                </td>
                            </tr>
                        `;
                    }
                },
                order: [[2, 'asc']], // Orden inicial por id_requisito
                processing: true,
                serverSide: false
            });
            this.state.isInitialized = true;
        }
    },

    getLanguageConfig() {
        const currentLanguage = LanguageManager.currentLang;
        // Definir el archivo según el idioma
        const languageFile = currentLanguage === 'es' ? 'es-ES.json' : 'en-GB.json';
        return {
            url: `${base_url}/Assets/js/plugins/datatables/${languageFile}`,
            paginate: {
                first: '«',
                last: '»',
                next: '›',
                previous: '‹'
            }
        };
    },

    initializeResponsiveHandling() {
        // Obtener referencias a los elementos
        const mainContainer = document.querySelector(this.config.selectors.mainContainer);

        if (!mainContainer || !this.state.instance) return;

        // Crear ResizeObserver para el contenedor principal
        const resizeObserver = new ResizeObserver(entries => {
            if (this.state.instance) {
                // Ajustamos las columnas
                this.state.instance.columns.adjust();
                // llamamos a la función de actualización después de un tiempo
                setTimeout(() => {
                    this.state.instance.responsive.recalc();
                }, 100);
            }
        });

        // Observar el contenedor principal
        resizeObserver.observe(mainContainer);
    },

    adjustTableAfterLanguageChange() {
        if (this.state.instance) {
            this.state.instance.columns.adjust();
            this.state.instance.responsive.recalc();
        }
    },

    destroy() {
        if (this.state.instance) {
            this.state.instance.destroy();
            this.state.isInitialized = false;
        }
    }
};

// Módulo principal de funcionalidades
const DashboardModule = {
    // Configuración inicial
    config: {
        selectors: {
            analyse: '.analyse',
            tableRows: '#tableIntentos tbody tr',
            Table: '#tableIntentos',
            playerProfile: {
                avatar: '.player-profile .avatar-circle',
                initials: '.player-profile .initials',
                name: '.player-profile .player-name',
                email: '.player-profile .player-email',
                tiempoTotal: '#tiempo-total',
                numeroIntentos: '#numero-intentos'
            }
        },
        params: {
            id: null, // Parámetro que necesitamos enviar
            gameCode: null
        },
        endpoint: `${base_url}/Analytics/get_intentos_jugador_construction`,
    },

    // Estado de la aplicación
    state: {
        isShowingAll: false
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
        const id_jugador = this.getUrlParameter('Jugador');
        if (!id_jugador) {
            console.error('No se encontró el código de Jugador en la URL');
            return false;
        }
        this.config.params.id = id_jugador;
        return true;
    },


    async fetchPlayerDetails() {
        try {
            const requestData = {
                gamecode: this.getUrlParameter('gamecode'),
                id_jugador: this.getUrlParameter('Jugador')
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
                this.updatePlayerProfile(decryptedData.details);
                if (decryptedData.attempts) {
                    TableIntentosModule.initializeWithData(decryptedData.attempts);
                    TableIntentosModule.initializeResponsiveHandling();
                }
            } else {
                console.error('Error en la respuesta:', decryptedData.message);
            }
        } catch (error) {
            console.error('Error obteniendo detalles del jugador:', error);
        }
    },

    showStatsInfo(key) {
        const translationKey = `details_construction.stats.${key}`;
        const info = LanguageManager.getTranslation(translationKey);
        if (info) {
            Swal.fire({
                title: info.title,
                text: info.description,
                icon: 'info',
                confirmButtonColor: '#1976D2',
                customClass: {
                    container: 'analytics-type-modal',
                    popup: 'analytics-modal-popup',
                },
            });
        }
    },

    updatePlayerProfile(details) {
        const selectors = this.config.selectors.playerProfile;
        const fullName = `${this.capitalizeFullName(details.nombres)} ${this.capitalizeFullName(details.apellidos)}`;

        // Actualizar avatar
        const avatarElement = document.querySelector(selectors.avatar);
        if (avatarElement) {
            avatarElement.style.backgroundColor = this.AvatarUtils.getAvatarColor(fullName);
        }

        // Actualizar iniciales
        const initialsElement = document.querySelector(selectors.initials);
        if (initialsElement) {
            initialsElement.textContent = this.AvatarUtils.getInitials(fullName);
        }

        // Actualizar nombre y correo
        const nameElement = document.querySelector(selectors.name);
        if (nameElement) {
            nameElement.textContent = fullName;
        }

        const emailElement = document.querySelector(selectors.email);
        if (emailElement) {
            emailElement.textContent = `${details.email}`;
        }

        // Actualizar estadísticas
        const tiempoElement = document.querySelector(selectors.tiempoTotal);
        if (tiempoElement) {
            tiempoElement.textContent = this.formatTime(details.tiempo_total);
        }

        const intentosElement = document.querySelector(selectors.numeroIntentos);
        if (intentosElement) {
            intentosElement.textContent = details.total_intentos;
        }
    },

    formatTime(seconds) {
        const totalSeconds = parseInt(seconds);
        const minutes = Math.floor(totalSeconds / 60);
        const remainingSeconds = totalSeconds % 60;
        return `${minutes}min ${remainingSeconds.toString().padStart(2, '0')}s`;
    },

    capitalizeFullName(fullName) {
        return fullName.toLowerCase() // Convierte todo a minúsculas
            .split(' ') // Divide el nombre por palabras
            .map(word => word.charAt(0).toUpperCase() + word.slice(1)) // Capitaliza la primera letra de cada palabra
            .join(' '); // Une las palabras normalizadas
    },

    setupEventListeners() {
        // Event listener para cambios de idioma
        document.addEventListener('languageChanged', () => {
            setTimeout(() => {
                TableIntentosModule.adjustTableAfterLanguageChange();
            }, 100);
        });
    },

    // Inicialización
    init() {
        if (this.initializeParams()) {
            this.fetchPlayerDetails(); // Obtener detalles del jugador
            //this.initializeTable(this.config.selectors.Table);
        }
        this.setupEventListeners();
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
        const player = this.getPlayer();
        const url = `${base_url}/Analytics/downloadReportConstruction?gamecode=${encodeURIComponent(gameCode)}&Jugador=${encodeURIComponent(player)}`;
        window.open(url, '_blank');
    },

    getGameCode() {
        // Obtener el código del juego de la URL
        const urlParams = new URLSearchParams(window.location.search);
        return urlParams.get('gamecode');
    },

    getPlayer() {
        // Obtener el código del juego de la URL
        const urlParams = new URLSearchParams(window.location.search);
        return urlParams.get('Jugador');
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