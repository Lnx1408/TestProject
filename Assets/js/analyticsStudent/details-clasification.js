const TableIntentosModule = {
    config: {
        selectors: {
            mainContainer: '#main',
            headerToggle: '#header-toggle',
            tableId: '#tableIntentos'
        },
        detailsEndpoint: `${base_url}/AnalyticsStudent/get_detalles_intento`
    },

    state: {
        instance: null,
        isInitialized: false
    },

    translations: {
        get: (key) => LanguageManager.getTranslation(`details_classification.${key}`)
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
                    container: 'column-info-modal',
                    popup: 'analytics-modal-popup',
                }
            });
        }
    },

    utils: {
        formatTime(seconds) {
            if (isNaN(seconds) || seconds === null || seconds === undefined || seconds < 0) {
                return "0min 00s";
            }
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
                gamecode: DashboardModule.config.params.gameCode
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
        const titles = this.translations.get('modals.attempt_details.headers');
        return `
            <div class="analyse stats-container grid grid-cols-3 gap-4 mb-6">
                <div class="stat-item bg-gray-100 p-4 rounded-lg text-center">
                    <i class='bx bx-time text-2xl mb-2 text-primary'></i>
                    <p class="text-sm text-gray-600">${titles.time}</p>
                    <p class="text-lg font-bold">${this.utils.formatTime(headerDetails.tiempo)}</p>
                </div>
                <div class="stat-item bg-gray-100 p-4 rounded-lg text-center">
                    <i class='bx bx-check-circle text-2xl mb-2 text-success'></i>
                    <p class="text-sm text-gray-600">${titles.success}</p>
                    <p class="text-lg font-bold">${headerDetails.margen_aciertos}%</p>
                </div>
                <div class="stat-item bg-gray-100 p-4 rounded-lg text-center">
                    <i class='bx bx-x-circle text-2xl mb-2 text-danger'></i>
                    <p class="text-sm text-gray-600">${titles.errors}</p>
                    <p class="text-lg font-bold">${headerDetails.margen_errores}%</p>
                </div>
            </div>
        `;
    },

    generateRequirementsHTML(requirements) {
        const texts = this.translations.get('modals.attempt_details.requirements');

        return `
            <div class="requirements-container">
                <h3 class="requirements-title">${texts.title}</h3>
                <div class="requirements-list">
                    ${requirements.map(req => `
                        <div class="requirement-item ${req.es_correcto ? 'requirement-correct' : 'requirement-incorrect'}">
                            <div class="requirement-header">
                                <p class="requirement-description"><strong> ${req.descripcion} </strong></p>
                                <i class='bx ${req.es_correcto ? 'bx-check' : 'bx-x'}'></i>
                            </div>
                            <p class="requirement-feedback"><em> ${req.retroalimentacion} </em></p>
                            <div class="requirement-moves">
                               <strong>${texts.movements}: ${req.cantidad_movimientos}</strong>
                            </div>
                        </div>
                    `).join('')}
                </div>
            </div>
        `;
    },

    async viewAttemptDetails(id_intento) {
        const modalTexts = this.translations.get('modals.attempt_details');
        Swal.fire({
            title: modalTexts.loading,
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
                                ${this.generateRequirementsHTML(details.attemptDetails)}
                            </div>
                        `;

                        Swal.hideLoading(); // Ocultar explícitamente el loading
                        Swal.update({
                            title: modalTexts.title,
                            html: contentHTML,
                            showConfirmButton: true,
                            confirmButtonText: modalTexts.close,
                            customClass: {
                                container: 'attempt-details-modal',
                                popup: 'attempt-details-popup',
                                content: 'attempt-details-content'
                            }
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: this.translations.get('errors.title'),
                            text: this.translations.get('errors.load_details'),
                            customClass: {
                                container: 'analytics-type-modal',
                                popup: 'analytics-modal-popup',
                            },
                        });
                    }
                } catch (error) {
                    Swal.fire({
                        icon: 'error',
                        title: this.translations.get('errors.title'),
                        text: this.translations.get('errors.general'),
                        customClass: {
                            container: 'analytics-type-modal',
                            popup: 'analytics-modal-popup',
                        },
                    });
                }
            }
        });
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
                title: `<span data-i18n="details_classification.table.columns.attempt">Intento</span> <i class='bx bx-info-circle info-icon' style='color: #666; cursor: pointer;' onclick='TableIntentosModule.showColumnInfo("attempt"); event.stopPropagation();'></i>`,
                className: "dt-center",
                responsivePriority: 1,
                width: "10%"
            },
            {
                data: 'tiempo_intento',
                title: `<span data-i18n="details_classification.table.columns.time">Tiempo</span> <i class='bx bx-info-circle info-icon' style='color: #666; cursor: pointer;' onclick='TableIntentosModule.showColumnInfo("time"); event.stopPropagation();'></i>`,
                className: "dt-center",
                responsivePriority: 2,
                width: "15%",
                render: (data) => TableIntentosModule.utils.formatTime(data)
            },
            {
                type: 'string',
                data: 'cantidad_movimientos',
                title: `<span data-i18n="details_classification.table.columns.movements">Movimientos</span> <i class='bx bx-info-circle info-icon' style='color: #666; cursor: pointer;' onclick='TableIntentosModule.showColumnInfo("movements"); event.stopPropagation();'></i>`,
                className: "dt-center",
                responsivePriority: 2,
                width: "15%"
            },
            {
                data: 'total_requisitos',
                title: `<span data-i18n="details_classification.table.columns.requirements">Requisitos</span> <i class='bx bx-info-circle info-icon' style='color: #666; cursor: pointer;' onclick='TableIntentosModule.showColumnInfo("requirements"); event.stopPropagation();'></i>`,
                className: "dt-center",
                responsivePriority: 2,
                type: 'string',
                width: "10%"
            },
            {
                data: 'requisitos_correctos',
                title: `<span data-i18n="details_classification.table.columns.correct">Correctos</span> <i class='bx bx-info-circle info-icon' style='color: #666; cursor: pointer;' onclick='TableIntentosModule.showColumnInfo("correct"); event.stopPropagation();'></i>`,
                className: "dt-center",
                responsivePriority: 2,
                type: 'string',
                width: "10%"
            },
            {
                type: 'string',
                data: 'requisitos_incorrectos',
                title: `<span data-i18n="details_classification.table.columns.incorrect">Incorrectos</span> <i class='bx bx-info-circle info-icon' style='color: #666; cursor: pointer;' onclick='TableIntentosModule.showColumnInfo("incorrect"); event.stopPropagation();'></i>`,
                responsivePriority: 2,
                className: "dt-center",
                width: "10%"
            },
            {
                data: 'precision_aciertos',
                title: `<span data-i18n="details_classification.table.columns.success_precision">Precisión Aciertos</span> <i class='bx bx-info-circle info-icon' style='color: #666; cursor: pointer;' onclick='TableIntentosModule.showColumnInfo("success_precision"); event.stopPropagation();'></i>`,
                className: "dt-center",
                responsivePriority: 3,
                width: "15%",
                render: data => TableIntentosModule.utils.formatPrecision(data)
            },
            {
                data: 'precision_errores',
                title: `<span data-i18n="details_classification.table.columns.error_precision">Precisión Errores</span> <i class='bx bx-info-circle info-icon' style='color: #666; cursor: pointer;' onclick='TableIntentosModule.showColumnInfo("error_precision"); event.stopPropagation();'></i>`,
                className: "dt-center",
                responsivePriority: 3,
                width: "15%",
                render: data => TableIntentosModule.utils.formatPrecision(data)
            },
            {
                type: 'string',
                data: 'precision_progresiva',
                title: `<span data-i18n="details_classification.table.columns.progressive_precision">Precisión Progresiva</span> <i class='bx bx-info-circle info-icon' style='color: #666; cursor: pointer;' onclick='TableIntentosModule.showColumnInfo("progressive_precision"); event.stopPropagation();'></i>`,
                className: "dt-center",
                responsivePriority: 2,
                width: "15%",
                render: data => TableIntentosModule.utils.formatPrecision(data)
            },
            {
                data: 'fecha_intento',
                title: `<span data-i18n="details_classification.table.columns.date">Fecha</span> <i class='bx bx-info-circle info-icon' style='color: #666; cursor: pointer;' onclick='TableIntentosModule.showColumnInfo("date"); event.stopPropagation();'></i>`,
                className: "dt-center",
                responsivePriority: 3,
                width: "15%",
                render: data => TableIntentosModule.utils.formatDate(data)
            },
            {
                data: null,
                title: `<span data-i18n="details_classification.table.columns.actions">Ver</span>`,
                className: "dt-center",
                width: "10%",
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
                columnDefs: [
                    {
                        className: 'dtr-control',
                        orderable: false,
                        targets: 0,
                        width: '2.5rem'
                    }
                ],
                processing: true,
                serverSide: false
            });
            this.state.isInitialized = true;
        }
    },

    getLanguageConfig() {
        return {
            url: `${base_url}/Assets/js/plugins/datatables/es-ES.json`,
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
        endpoint: `${base_url}/AnalyticsStudent/get_intentos_jugador`,
    },

    // Estado de la aplicación
    state: {
        isShowingAll: false
    },

    translations: {
        get: (key) => LanguageManager.getTranslation(`details_classification.${key}`)
    },

    showStatsInfo(key) {
        const info = this.translations.get(`stats.${key}.info`);
        if (info) {
            Swal.fire({
                title: info.title,
                text: info.description,
                icon: 'info',
                confirmButtonColor: '#1976D2',
                customClass: {
                    container: 'stats-info-modal',
                    popup: 'analytics-modal-popup',
                }
            });
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
        if (isNaN(seconds) || seconds === null || seconds === undefined || seconds < 0) {
            return "0min 00s";
        }
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


    initializeTable(id) {
        TableModule.initialize(id);
        TableModule.initializeResponsiveHandling();
    },

    // Inicialización
    init() {
        if (this.initializeParams()) {
            this.fetchPlayerDetails(); // Obtener detalles del jugador
            //this.initializeTable(this.config.selectors.Table);
        }
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
        const url = `${base_url}/AnalyticsStudent/downloadReportClass?gamecode=${encodeURIComponent(gameCode)}`;
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