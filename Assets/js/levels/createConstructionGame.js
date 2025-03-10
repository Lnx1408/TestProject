class ToastManager {
    constructor() {
        this.init();
    }

    init() {
        const container = document.createElement('div');
        container.className = 'toast-container';
        document.body.appendChild(container);
        this.container = container;
    }

    show(message, type = 'error', duration = 3000) {
        const toast = document.createElement('div');
        toast.className = `toast-notification ${type}`;
        toast.innerHTML = `
            <i class='bx ${type === 'error' ? 'bx-error-circle' : 'bx-info-circle'}'></i>
            <span>${message}</span>
        `;

        this.container.appendChild(toast);

        // Auto-remove después del tiempo especificado
        setTimeout(() => {
            toast.classList.add('fade-out');
            setTimeout(() => toast.remove(), 300);
        }, duration);
    }
}


class CreateConstructionGame {
    constructor() {
        this.config = {
            endpoints: {
                getExisting: `${base_url}/Levels/get_requirements_construction`,
                createRequirement: `${base_url}/Levels/create_requirement_construction`,
                createGame: `${base_url}/Levels/create_game_construction`,
                importMasiveData: `${base_url}/Levels/import_requirements_construction`
            },
            minRequirements: 5
        };

        this.state = {
            selectedRequirements: new Map(),
            requirementCount: 0
        };

        this.translations = {
            get: (key) => LanguageManager.getTranslation(`create_construction.${key}`)
        };

        this.elements = {
            selectedTable: null,
            selectModal: null,
            createGameBtn: document.getElementById('createGameBtn'),
            reqCountDisplay: document.getElementById('reqCount')
        };

        this.initializeTables();
        this.bindEvents();
        this.toastManager = new ToastManager();
    }

    bindEvents() {
        // Vincular evento del botón de selección de requisitos existentes
        document.getElementById('selectExistingBtn')
            .addEventListener('click', () => this.showSelectModal());

        // Vincular evento del botón de crear nuevo requisito
        document.getElementById('createNewBtn')
            .addEventListener('click', () => this.showCreateModal());

        // Vincular evento del botón de crear partida
        document.getElementById('createGameBtn')
            .addEventListener('click', () => this.createGame());

        const exportBtn = document.getElementById('exportReqBtn');
        if (exportBtn) {
            exportBtn.addEventListener('click', () => this.exportRequirements());
        }

        document.getElementById('importReqBtn')
            .addEventListener('click', () => this.showImportModal());


        // Manejar cambios en el idioma para actualizar tablas
        document.addEventListener('languageChanged', () => {
            if (this.elements.selectedTable) {
                this.elements.selectedTable.draw();
            }
            if (this.elements.selectModal) {
                this.elements.selectModal.draw();
            }
        });
    }

    showErrorMessage(message) {
        return Swal.fire({
            icon: 'error',
            title: this.translations.get('errors.server_error'),
            text: message,
            confirmButtonColor: '#1976D2',
            customClass: {
                container: 'game-type-modal',
                popup: 'game-levels-popup',
            },
        });
    }

    showSuccessMessage(message) {
        return Swal.fire({
            icon: 'success',
            title: message,
            confirmButtonColor: '#1976D2',
            customClass: {
                container: 'game-type-modal',
                popup: 'game-levels-popup',
            },
        });
    }

    getDataTableLanguage() {
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
    }

    handleCheckboxChange(checkbox, requirementId) {
        if (checkbox.checked) {
            this.state.temporarySelections.add(requirementId);
        } else {
            this.state.temporarySelections.delete(requirementId);
        }
        console.log('Selecciones temporales:', this.state.temporarySelections);
    }

    initializeTables() {
        // Obtener los títulos de las columnas del HTML
        const columnTitles = Array.from(document.querySelectorAll('#selectedRequirementsTable th'))
            .map(th => th.textContent);

        this.elements.selectedTable = $('#selectedRequirementsTable').DataTable({
            responsive: true,
            responsive: {
                details: {
                    type: 'column',
                    renderer: (api, rowIdx, columns) => {
                        const data = api.row(rowIdx).data();
                        let html = '<div class="expanded-details">';

                        // Sección de columnas ocultas por responsive

                        const hiddenColumns = columns.filter(col => col.hidden);
                        if (hiddenColumns.length > 0) {
                            html += `
                                <div class="hidden-columns-section">
                                    <h4>${this.translations.get('main_table.details.additional_info')}</h4>
                                    <div class="hidden-columns-content">
                                        ${hiddenColumns.map(col => `
                                            <div class="detail-row">
                                                <span class="detail-label">${columnTitles[col.columnIndex]}:</span>
                                                <span class="detail-value">${col.data}</span>
                                            </div>
                                        `).join('')}
                                    </div>
                                </div>
                            `;
                        }
                        html += this.formatFragmentsPreview(data);
                        html += '</div>';
                        return html;
                    },
                    className: 'collapsed', // Forzar la clase
                    target: 'tr'
                }
            },
            breakpoints: [
                { name: 'desktop', width: Infinity }
            ],
            columns: [
                {
                    className: 'dt-control',
                    orderable: false,
                    data: null,
                    responsivePriority: 1,
                    defaultContent: ''
                },
                { 
                    data: 'requisito_completo',
                    className: 'wrap-cell', 
                    responsivePriority: 1, 
                    render: function (data, type, row) {
                        if (type === 'display') {
                            return `<div class="requirement-description">${data}</div>`;
                        }
                        return data;
                    }

                 },
                {
                    data: 'fragmentos',
                    responsivePriority: 2,
                    render: data => data.filter(f => !f.es_señuelo).length
                },
                {
                    data: 'fragmentos',
                    responsivePriority: 3,
                    render: data => data.filter(f => f.es_señuelo).length
                },
                {
                    data: null,
                    responsivePriority: 1,
                    title: this.translations.get('main_table.columns.actions'),
                    render: (data, type, row) => this.renderActions(row)
                }
            ],
            columnDefs: [
                {
                    targets: 0,
                    orderable: false,
                    className: 'dtr-control',
                    width: '2.5rem'
                },
                {
                    targets: [1],
                    width: '70%',
                }
            ],
            language: this.getDataTableLanguage()
        });

        // Referencia a la tabla DOM
        const table = $('#selectedRequirementsTable');
        // Agregar la clase inicialmente
        table.addClass('collapsed');

        // Crear un MutationObserver para monitorear cambios en las clases
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                    if (!table.hasClass('collapsed')) {
                        table.addClass('collapsed');
                    }
                }
            });
        });

        // Configurar el observer
        observer.observe(table[0], {
            attributes: true,
            attributeFilter: ['class']
        });

        // También agregar la clase después de cualquier redibujado de la tabla
        this.elements.selectedTable.on('draw.dt', function () {
            table.addClass('collapsed');
        });

        // Y después de cualquier reordenamiento
        this.elements.selectedTable.on('order.dt', function () {
            table.addClass('collapsed');
        });

        // Y después de cualquier cambio en el responsive
        this.elements.selectedTable.on('responsive-resize.dt', function () {
            table.addClass('collapsed');
        });
    }

    formatFragments(data) {
        const mainFragments = data.fragmentos.filter(f => !f.es_señuelo)
            .sort((a, b) => a.posicion_correcta - b.posicion_correcta);
        const decoyFragments = data.fragmentos.filter(f => f.es_señuelo);

        return `
            <div class="fragments-detail">
                <div class="main-fragments">
                    <h4>Fragmentos Principales</h4>
                    <div class="fragments-list">
                        ${mainFragments.map(f => `
                            <div class="fragment">
                                <span class="position">${f.posicion_correcta}</span>
                                <span class="text">${f.texto}</span>
                            </div>
                        `).join('')}
                    </div>
                </div>
                <div class="decoy-fragments">
                    <h4>Señuelos</h4>
                    <div class="fragments-list">
                        ${decoyFragments.map(f => `
                            <div class="fragment">
                                <span class="text">${f.texto}</span>
                            </div>
                        `).join('')}
                    </div>
                </div>
            </div>
        `;
    }

    async showSelectModal() {
        // Limpiar selecciones temporales al abrir el modal
        this.state.temporarySelections = new Set();

        Swal.fire({
            title: this.translations.get('select_modal.title'),
            html: this.createSelectModalContent(),
            width: '80%',
            showCancelButton: true,
            confirmButtonText: this.translations.get('select_modal.confirm'),
            cancelButtonText: this.translations.get('select_modal.cancel'),
            customClass: {
                container: 'game-type-modal',
                popup: 'game-levels-popup',
            },
            didOpen: () => {
                this.initializeSelectTable();
            }
        }).then((result) => {
            if (result.isConfirmed) {
                this.addSelectedRequirements();
            }
        });
    }

    createSelectModalContent() {
        return `
            <table id="existingRequirementsTable" class="table display responsive nowrap">
                <thead>
                    <tr>
                        <th></th>
                        <th>${this.translations.get('main_table.columns.requirement')}</th>
                        <th>${this.translations.get('main_table.columns.fragments')}</th>
                        <th>${this.translations.get('main_table.columns.decoys')}</th>
                    </tr>
                </thead>
            </table>
        `;
    }

    updateExportButtonState() {
        const exportBtn = document.getElementById('exportReqBtn');
        if (exportBtn) {
            exportBtn.disabled = this.state.requirementCount === 0;
        }
    }

    exportRequirements() {
        // Obtener los requisitos seleccionados
        const requirements = Array.from(this.state.selectedRequirements.values()).map(req => {
            const fragments = req.fragmentos.map(fragment => {
                return `${fragment.texto}|${fragment.posicion_correcta || 0}|${fragment.es_señuelo ? 1 : 0}`;
            }).join('¬');

            return {
                requisito_completo: req.requisito_completo,
                fragmentos: fragments
            };
        });

        if (requirements.length === 0) {
            this.showErrorMessage(this.translations.get('messages.no_requirements_selected'));
            return;
        }

        const csv = Papa.unparse(requirements, {
            quotes: true, // Usar comillas en todos los campos
            delimiter: ";",
            header: true
        });

        const blob = new Blob(["\ufeff", csv], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        const url = URL.createObjectURL(blob);
        link.setAttribute('href', url);
        link.setAttribute('download', `requisitos_construccion_${new Date().toISOString()}.csv`);
        link.style.visibility = 'hidden';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }

    getRequirementById(reqId) {
        const allData = this.elements.selectModal.data().toArray();
        return allData.find(row => row.id === parseInt(reqId));
    }

    async initializeSelectTable() {
        const table = $('#existingRequirementsTable').DataTable({
            ajax: {
                url: this.config.endpoints.getExisting,
                type: 'GET',
                dataSrc: (response) => {
                    try {
                        if (!response.data) {
                            throw new Error(this.translations.get('errors.invalid_data'));
                        }

                        const decryptedData = CryptoModule.decrypt(response.data);

                        if (!decryptedData) {
                            throw new Error(this.translations.get('errors.decryption_failed'));
                        }

                        if (!decryptedData.success) {
                            throw new Error(decryptedData.message || this.translations.get('errors.server_error'));
                        }

                        return decryptedData.data || [];
                    } catch (error) {
                        console.error('Error processing data:', error);
                        this.showErrorMessage(error.message);
                        return [];
                    }
                },
                error: (xhr, error, thrown) => {
                    console.error('Ajax error:', error);
                    this.showErrorMessage(this.translations.get('errors.connection_error'));
                }
            },
            responsive: true,
            responsive: {
                details: {
                    type: 'column',
                    renderer: (api, rowIdx, columns) => {
                        const data = api.row(rowIdx).data();
                        let html = '<div class="expanded-details">';

                        // Sección de columnas ocultas por responsive
                        const hiddenColumns = columns.filter(col => col.hidden);
                        if (hiddenColumns.length > 0) {
                            html += `
                                <div class="hidden-columns-section">
                                    <h4>${this.translations.get('main_table.details.additional_info')}</h4>
                                    <div class="hidden-columns-content">
                                        ${hiddenColumns.map(col => `
                                            <div class="detail-row">
                                                <span class="detail-label">${col.title}:</span>
                                                <span class="detail-value">${col.data}</span>
                                            </div>
                                        `).join('')}
                                    </div>
                                </div>
                            `;
                        }

                        // Sección de fragmentos
                        html += this.formatFragmentsPreview(data);
                        html += '</div>';
                        return html;
                    }
                }
            },
            processing: true,
            serverSide: false,
            columns: [
                {
                    data: null,
                    className: 'dt-control',
                    responsivePriority: 1, 
                    orderable: false,
                    render: (data) => {
                        const isDisabled = this.state.selectedRequirements.has(data.id);
                        return `
                            <div class="checkbox-wrapper" onclick="event.stopPropagation();">
                                <input type="checkbox" 
                                    class="req-checkbox" 
                                    value="${data.id}"
                                    onchange="createConstructionGame.handleCheckboxChange(this, ${data.id})"
                                    ${isDisabled ? 'checked disabled' : ''}>
                            </div>
                        `;
                    }
                },
                {
                    data: 'requisito_completo',
                    //className: 'requirement-text'
                    className: 'wrap-cell', 
                    responsivePriority: 1, 
                    render: function (data, type, row) {
                        if (type === 'display') {
                            return `<div class="requirement-description">${data}</div>`;
                        }
                        return data;
                    }
                },
                {
                    data: 'fragmentos',
                    className: 'text-center',
                    responsivePriority: 2, 
                    render: fragments => fragments.filter(f => !f.es_señuelo).length
                },
                {
                    data: 'fragmentos',
                    className: 'text-center',
                    responsivePriority: 3,
                    render: fragments => fragments.filter(f => f.es_señuelo).length
                }
            ],
            columnDefs: [
                {
                    targets: [1],
                    width: '70%',
                }
            ],
            language: this.getDataTableLanguage(),
            rowCallback: (row, data) => {
                if (this.state.selectedRequirements.has(data.id)) {
                    $(row).addClass('selected-requirement');
                }
            }
        });
        // Referencia a la tabla DOM
        const tableElement = $('#existingRequirementsTable');
        // Agregar la clase inicialmente
        tableElement.addClass('collapsed');

        // Crear un MutationObserver para monitorear cambios en las clases
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                    if (!tableElement.hasClass('collapsed')) {
                        tableElement.addClass('collapsed');
                    }
                }
            });
        });

        // Configurar el observer
        observer.observe(tableElement[0], {
            attributes: true,
            attributeFilter: ['class']
        });

        // Agregar la clase después de eventos relevantes
        table.on('draw.dt', () => {
            tableElement.addClass('collapsed');
        });

        table.on('order.dt', () => {
            tableElement.addClass('collapsed');
        });

        table.on('responsive-resize.dt', () => {
            tableElement.addClass('collapsed');
        });

        this.elements.selectModal = table;
    }

    formatFragmentsPreview(data) {
        const mainFragments = data.fragmentos.filter(f => !f.es_señuelo)
            .sort((a, b) => a.posicion_correcta - b.posicion_correcta);
        const decoyFragments = data.fragmentos.filter(f => f.es_señuelo);

        return `
            <div class="fragments-section">
                <div class="main-fragments">
                    <h4>${this.translations.get('main_table.details.main_fragments')}</h4>
                    <div class="fragments-list">
                        ${mainFragments.map(f => `
                            <div class="fragment-item">
                                <span class="position-badge">${f.posicion_correcta}</span>
                                <span class="fragment-text">${f.texto}</span>
                            </div>
                        `).join('')}
                    </div>
                    </div>
                        <div class="decoy-fragments">
                            <h4>${this.translations.get('main_table.details.decoys')}</h4>
                            <div class="fragments-list">
                                ${decoyFragments.map(f => `
                                    <div class="fragment-item decoy">
                                        <span class="fragment-text">${f.texto}</span>
                                    </div>
                                `).join('')}
                            </div>
                        </div>
                </div>
        `;
    }

    addSelectedRequirements() {
        //const tableData = this.elements.selectModal.data();
        const tableData = this.elements.selectModal.data().toArray();

        // Procesar cada selección temporal
        this.state.temporarySelections.forEach(reqId => {
            const requirement = tableData.find(row => row.id === reqId);
            if (requirement && !this.state.selectedRequirements.has(reqId)) {
                this.addRequirementToTable(requirement);
            }
        });

        // Limpiar selecciones temporales
        this.state.temporarySelections.clear();
    }

    addRequirementToTable(requirement) {
        if (!this.state.selectedRequirements.has(requirement.id)) {
            // Guardar en el Map de requisitos seleccionados
            this.state.selectedRequirements.set(requirement.id, requirement);

            // Agregar a la tabla
            this.elements.selectedTable.row.add({
                id: requirement.id,
                requisito_completo: requirement.requisito_completo,
                fragmentos: requirement.fragmentos
            }).draw();

            // Actualizar contador
            this.updateRequirementCount();
        }
    }

    removeRequirement(requirementId) {
        Swal.fire({
            title: this.translations.get('messages.confirm_remove'),
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: this.translations.get('buttons.yes'),
            cancelButtonText: this.translations.get('buttons.no'),
            customClass: {
                container: 'game-type-modal',
                popup: 'game-levels-popup',
            },
        }).then((result) => {
            if (result.isConfirmed) {
                console.log('Removiendo requisito:', requirementId);

                // Eliminar del Map de requisitos seleccionados
                this.state.selectedRequirements.delete(parseInt(requirementId));

                // Encontrar y eliminar la fila directamente usando una función de búsqueda
                const table = this.elements.selectedTable;
                table.rows(function (idx, data) {
                    return data.id === parseInt(requirementId);
                }).remove().draw();

                console.log('Requisito removido exitosamente');
                this.updateRequirementCount();

                // Actualizar el modal de selección si está presente
                if (this.elements.selectModal) {
                    this.elements.selectModal.draw(false);
                }
            }
        });
    }

    updateRequirementCount() {
        this.state.requirementCount = this.state.selectedRequirements.size;
        this.elements.reqCountDisplay.textContent = this.state.requirementCount;

        // Actualizar estado del botón de crear partida
        if (this.elements.createGameBtn) {
            this.elements.createGameBtn.disabled =
                this.state.requirementCount < this.config.minRequirements;
        }
        this.updateExportButtonState();
    }

    renderActions(row) {
        return `
            <div class="table-actions">
                <button class="btn btn-danger btn-sm" 
                        onclick="createConstructionGame.removeRequirement(${row.id})">
                    <i class='bx bx-trash'></i>
                </button>
            </div>
        `;
    }

    /** SECCION DE IMPORTACION MASIVA */
    showImportModal() {
        Swal.fire({
            title: this.translations.get('import_modal.title'),
            html: `
                <div class="import-container">
                    <div class="import-instructions">
                        <p>${this.translations.get('import_modal.instructions')}</p>
                        <ul>
                            <li>${this.translations.get('import_modal.format_info')}</li>
                            <li>${this.translations.get('import_modal.columns_info')}</li>
                        </ul>
                    </div>
                    <div class="import-actions">
                        <button class="btn btn-secondary" id="downloadTemplateBtn">
                            <i class='bx bx-download'></i>
                            ${this.translations.get('import_modal.download_template')}
                        </button>
                        <input type="file" 
                               id="csvFileInput" 
                               accept=".csv" 
                               style="display: none">
                        <button class="btn btn-primary" id="selectFileBtn">
                            <i class='bx bx-upload'></i>
                            ${this.translations.get('import_modal.select_file')}
                        </button>
                    </div>
                    <div id="importPreview" class="import-preview" style="display: none">
                        <h4>${this.translations.get('import_modal.preview_title')}</h4>
                        <div class="preview-content"></div>
                    </div>
                </div>
            `,
            width: '800px',
            showCancelButton: true,
            confirmButtonText: this.translations.get('import_modal.import'),
            cancelButtonText: this.translations.get('import_modal.cancel'),
            confirmButtonColor: '#1976D2',
            showConfirmButton: false,
            customClass: {
                container: 'construction-import-modal',
            },
            didOpen: () => {
                this.initializeImportHandlers();
            }
        });
    }

    initializeImportHandlers() {
        const fileInput = document.getElementById('csvFileInput');
        const selectFileBtn = document.getElementById('selectFileBtn');
        const downloadTemplateBtn = document.getElementById('downloadTemplateBtn');

        selectFileBtn.addEventListener('click', () => fileInput.click());
        downloadTemplateBtn.addEventListener('click', () => this.downloadTemplate());
        fileInput.addEventListener('change', (e) => this.handleFileSelection(e));
    }

    handleFileSelection(event) {
        const file = event.target.files[0];
        if (!file) return;

        // Validar extensión y tamaño
        if (!this.validateFile(file)) return;

        // Leer y procesar el archivo
        Papa.parse(file, {
            complete: (results) => this.validateAndPreviewData(results),
            header: true,
            skipEmptyLines: true,
            encoding: 'UTF-8'
        });
    }

    validateFile(file) {
        // Validar extensión
        if (!file.name.toLowerCase().endsWith('.csv')) {
            this.showErrorMessage(this.translations.get('import_modal.errors.invalid_extension'));
            return false;
        }

        // Validar tamaño (1MB máximo)
        if (file.size > 1024 * 1024) {
            this.showErrorMessage(this.translations.get('import_modal.errors.file_too_large'));
            return false;
        }

        return true;
    }

    validateAndPreviewData(results) {
        const validationResult = this.validateCSVData(results.data);
        if (!validationResult.isValid) {
            this.showErrorMessage(validationResult.errors.join('\n'));
            return;
        }

        const processedData = this.processImportData(results.data);
        //this.showDataPreview(processedData);
        this.updateImportModal(processedData);
    }

    validateCSVData(data) {
        const errors = [];

        if (!Array.isArray(data) || data.length === 0) {
            return { isValid: false, errors: ['Archivo vacío o inválido'] };
        }

        data.forEach((row, index) => {
            // Validar estructura básica
            if (!row.requisito_completo || !row.fragmentos) {
                errors.push(`Fila ${index + 1}: Faltan columnas requeridas`);
                return;
            }

            // Validar fragmentos
            try {
                const fragments = row.fragmentos.split('¬');
                fragments.forEach((fragment, fragIndex) => {
                    const [texto, posicion, esSeñuelo] = fragment.split('|');

                    if (!texto) {
                        errors.push(`Fila ${index + 1}, Fragmento ${fragIndex + 1}: Texto vacío`);
                    }

                    if (isNaN(posicion) || (parseInt(posicion) < 0)) {
                        errors.push(`Fila ${index + 1}, Fragmento ${fragIndex + 1}: Posición inválida`);
                    }

                    if (!['0', '1'].includes(esSeñuelo)) {
                        errors.push(`Fila ${index + 1}, Fragmento ${fragIndex + 1}: Valor de señuelo inválido`);
                    }
                });

                // Validar que haya al menos un fragmento principal y un señuelo
                const mainFragments = fragments.filter(f => f.split('|')[2] === '0');
                const decoyFragments = fragments.filter(f => f.split('|')[2] === '1');

                if (mainFragments.length === 0) {
                    errors.push(`Fila ${index + 1}: Debe tener al menos un fragmento principal`);
                }
                if (decoyFragments.length === 0) {
                    errors.push(`Fila ${index + 1}: Debe tener al menos un señuelo`);
                }

            } catch (e) {
                errors.push(`Fila ${index + 1}: Error en formato de fragmentos`);
            }
        });

        return {
            isValid: errors.length === 0,
            errors
        };
    }


    processImportData(data) {
        return data.map(row => {
            // Procesar fragmentos del string delimitado
            const fragmentsArray = row.fragmentos.split('¬').map(fragmentStr => {
                const [texto, posicion, esSeñuelo] = fragmentStr.split('|');
                return {
                    texto: texto.trim(),
                    posicion: parseInt(posicion),
                    es_señuelo: esSeñuelo === '1'
                };
            });

            // Ordenar fragmentos principales por posición
            fragmentsArray.sort((a, b) => {
                // Si ambos son señuelos o ambos son principales, mantener el orden
                if (a.es_señuelo === b.es_señuelo) {
                    return a.posicion - b.posicion;
                }
                // Colocar señuelos al final
                return a.es_señuelo ? 1 : -1;
            });

            return {
                requisito_completo: row.requisito_completo.trim(),
                fragmentos: row.fragmentos, // Mantener string original para el backend
                fragmentosProcesados: fragmentsArray, // Array procesado para la vista previa
                totalFragmentos: fragmentsArray.length,
                totalPrincipales: fragmentsArray.filter(f => !f.es_señuelo).length,
                totalSeñuelos: fragmentsArray.filter(f => f.es_señuelo).length
            };
        });
    }

    showDataPreview(data) {
        const modalContainer = Swal.getHtmlContainer();
        if (!modalContainer) return;

        const previewDiv = modalContainer.querySelector('#importPreview');
        const previewContent = modalContainer.querySelector('.preview-content');
        if (!previewDiv || !previewContent) return;

        let html = '<div class="preview-requirements">';

        data.slice(0, 3).forEach((req, index) => {
            html += `
                <div class="preview-requirement">
                    <div class="requirement-header">
                        <span class="requirement-number">#${index + 1}</span>
                        <span class="requirement-text">${req.requisito_completo}</span>
                    </div>
                    <div class="fragments-section">
                        <div class="main-fragments">
                            <h5>Fragmentos Principales</h5>
                            ${req.fragmentosProcesados.filter(f => !f.es_señuelo)
                    .map(f => `<div class="fragment">
                                    <span class="position">${f.posicion}</span>
                                    <span class="text">${f.texto}</span>
                                </div>`).join('')}
                        </div>
                        <div class="decoy-fragments">
                            <h5>Señuelos</h5>
                            ${req.fragmentosProcesados.filter(f => f.es_señuelo)
                    .map(f => `<div class="fragment">
                                    <span class="text">${f.texto}</span>
                                </div>`).join('')}
                        </div>
                    </div>
                </div>
            `;
        });

        if (data.length > 3) {
            html += `<div class="more-indicator">... y ${data.length - 3} requisitos más</div>`;
        }

        html += '</div>';
        previewContent.innerHTML = html;
        previewDiv.style.display = 'block';

        // Actualizar el modal sin reemplazar el contenido
        this.updateImportModal(data);
    }

    updateImportModal(data) {
        // Obtener el modal actual
        const modalContainer = Swal.getHtmlContainer();
        if (!modalContainer) return;

        // Actualizar solo los elementos necesarios
        const confirmButton = Swal.getConfirmButton();
        if (confirmButton) {
            confirmButton.style.display = 'inline-flex';
            confirmButton.textContent = `${this.translations.get('import_modal.import')} (${data.length})`;
        }

        // Almacenar los datos procesados para usar en la importación
        this.importData = data;

        // Actualizar el handler del botón de confirmar
        if (confirmButton) {
            // Remover handlers previos si existen
            const newButton = confirmButton.cloneNode(true);
            confirmButton.parentNode.replaceChild(newButton, confirmButton);

            // Añadir nuevo handler
            newButton.addEventListener('click', () => {
                this.processImport(this.importData);
            });
        }
    }

    async processImport(data) {
        try {
            const response = await fetch(this.config.endpoints.importMasiveData, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    encryptedData: CryptoModule.encrypt({ requirements: data })
                })
            });

            const result = await response.json();
            const decryptedResult = CryptoModule.decrypt(result.data);

            if (decryptedResult.success) {
                await this.showSuccessMessage(this.translations.get('import_modal.success'));
                // Recargar la tabla de requisitos
                //this.loadRequirements();
            } else {
                throw new Error(decryptedResult.message);
            }
        } catch (error) {
            this.showErrorMessage(error.message);
        }
    }


    /*** SECCION DE CREACION NUEVO REQUISITO */
    showValidationErrors(errors) {
        // Limpiar errores previos
        this.clearValidationErrors();

        errors.forEach(error => {
            // Mostrar toast
            this.toastManager.show(error.message, 'error');

            // Resaltar sección con error
            if (error.section) {
                const section = document.querySelector(`.form-section[data-section="${error.section}"]`);
                if (section) {
                    section.classList.add('has-error');
                }
            }

            // Resaltar input específico si existe
            if (error.elementId) {
                const element = document.getElementById(error.elementId);
                if (element) {
                    element.classList.add('input-error');
                }
            }
        });
    }

    clearValidationErrors() {
        // Remover clases de error de las secciones
        document.querySelectorAll('.form-section.has-error').forEach(section => {
            section.classList.remove('has-error');
        });

        // Remover clases de error de los inputs
        document.querySelectorAll('.input-error').forEach(input => {
            input.classList.remove('input-error');
        });
    }

    async showCreateModal() {
        try {
            const modalContent = `
            <form id="createRequirementForm" class="requirement-form">
                <!-- Sección del Requisito Completo -->
                <div class="form-section" data-section="requirement">
                    <h3>${this.translations.get('create_modal.sections.requirement')}</h3>
                    <div class="form-group">
                        <label for="requirementText">
                            ${this.translations.get('create_modal.form.requirement.label')}
                        </label>
                        <textarea 
                            id="requirementText" 
                            class="form-control" 
                            rows="3"
                            placeholder="${this.translations.get('create_modal.form.requirement.placeholder')}"
                        ></textarea>
                    </div>
                </div>
    
                <!-- Sección de Fragmentos -->
                <div class="form-section" data-section="fragments">
                    <h3>${this.translations.get('create_modal.sections.fragments')}</h3>
                    <div class="generation-buttons">
                        <button type="button" class="btn btn-primary" id="generateFragmentsBtn">
                            <i class='bx bx-wand-2'></i>
                            ${this.translations.get('buttons.generate_fragments')}
                        </button>
                        <button type="button" class="btn btn-secondary" id="createManuallyBtn">
                            <i class='bx bx-edit'></i>
                            ${this.translations.get('buttons.create_manually')}
                        </button>
                    </div>
                    <div id="fragmentsContainer" class="fragments-container">
                        <!-- Los fragmentos se agregarán aquí -->
                    </div>
                </div>

                <!-- Sección de Señuelos -->
                <div class="form-section" data-section="decoys">
                    <h3>${this.translations.get('create_modal.sections.decoys')}</h3>
                    <div class="decoys-container">
                        <div id="decoysContainer">
                            <!-- Los señuelos se agregarán aquí -->
                        </div>
                    </div>
                </div> 
    
            </form>
        `;

            const result = await Swal.fire({
                title: this.translations.get('create_modal.title'),
                html: modalContent,
                width: '800px',
                showCancelButton: true,
                confirmButtonText: this.translations.get('buttons.save'),
                cancelButtonText: this.translations.get('buttons.cancel'),
                customClass: {
                    container: 'game-type-modal',
                    popup: 'game-levels-popup',
                },
                didOpen: () => {
                    try {
                        this.initializeCreateModalEvents();
                        this.initializeDragAndDrop();
                        this.initializeDecoys();
                    } catch (error) {
                        console.error('Error initializing modal components:', error);
                        this.showErrorMessage(this.translations.get('errors.initialization_error'));
                    }
                },
                preConfirm: async () => {
                    try {
                        return await this.validateAndCollectFormData();
                    } catch (error) {
                        Swal.showValidationMessage(error.message);
                        return false;
                    }
                }
            });

            if (result.isConfirmed && result.value) {
                await this.saveRequirement(result.value);
            }
        } catch (error) {
            console.error('Error showing create modal:', error);
            await this.showErrorMessage(this.translations.get('errors.modal_error'));
        }
    }

    initializeCreateModalEvents() {
        // Evento para generación automática
        document.getElementById('generateFragmentsBtn')
            .addEventListener('click', () => this.generateFragments());

        // Evento para creación manual
        document.getElementById('createManuallyBtn')
            .addEventListener('click', () => this.enableManualCreation());

        // Evento para actualización en tiempo real del requisito
        document.getElementById('requirementText')
            .addEventListener('input', () => this.updateFragmentsPreview());
    }

    // Método para actualizar la vista previa de fragmentos
    updateFragmentsPreview() {
        // Este método se llama cuando el texto del requisito cambia
        const requirementText = document.getElementById('requirementText').value.trim();
        if (requirementText) {
            // Limpiar cualquier mensaje de error previo
            this.clearValidationErrors();

            // Mostrar botones de generación/creación manual si hay texto
            document.getElementById('generateFragmentsBtn').disabled = false;
            document.getElementById('createManuallyBtn').disabled = false;
        } else {
            // Si no hay texto, deshabilitar botones
            document.getElementById('generateFragmentsBtn').disabled = true;
            document.getElementById('createManuallyBtn').disabled = true;
        }
    }

    async generateFragments() {
        this.clearValidationErrors();
        const errors = [];
        const requirementText = document.getElementById('requirementText').value.trim();

        if (!requirementText) {
            errors.push({
                message: this.translations.get('create_modal.validation.requirement_required'),
                elementId: 'requirementText'
            });
            this.showValidationErrors(errors);
            return;
        }

        try {
            // Mostrar loading
            const fragmentsContainer = document.getElementById('fragmentsContainer');
            fragmentsContainer.innerHTML = `
                <div class="loading-fragments">
                    <i class='bx bx-loader-alt bx-spin'></i>
                    <p>${this.translations.get('messages.generating_fragments')}</p>
                </div>
            `;

            // Generar fragmentos
            const fragments = this.autoGenerateFragments(requirementText);

            // Preparar contenedor
            fragmentsContainer.innerHTML = `
                <div class="fragments-list"></div>
            `;
            // Agregar cada fragmento usando el método existente
            fragments.forEach(fragment => this.addFragmentToList(fragment));
            // Inicializar drag & drop
            this.initializeDragAndDrop();
        } catch (error) {
            errors.push({
                message: this.translations.get('messages.error_generating')
            });
            this.showValidationErrors(errors);
        }
    }

    autoGenerateFragments(text) {
        // Reglas básicas de separación
        const fragments = text
            // Separar en oraciones si hay puntos
            .split('.')
            .filter(sentence => sentence.trim())
            .flatMap(sentence => {
                // Separar por conjunciones comunes
                const parts = sentence.split(/\s+(?:y|e|o|u|ni)\s+/);
                return parts.map(part => {
                    // Separar en fragmentos más pequeños por comas o conectores
                    const subParts = part.split(/,|\s+(?:que|para|cuando|donde)\s+/);
                    return subParts.map(p => p.trim()).filter(p => p);
                }).flat();
            })
            .filter(fragment => fragment.length > 0);

        // Convertir a formato de fragmentos
        return fragments.map((text, index) => ({
            texto: text.trim(),
            posicion_correcta: index + 1,
            es_señuelo: false
        }));
    }

    enableManualCreation() {
        const fragmentsContainer = document.getElementById('fragmentsContainer');
        fragmentsContainer.innerHTML = `
            <div class="add-fragment-form">
                <div class="form-group">
                    <input type="text" 
                           id="fragmentText" 
                           class="form-control" 
                           placeholder="${this.translations.get('create_modal.form.fragments.fragment_text')}">
                </div>
                <div class="form-group">
                    <input type="number" 
                           id="fragmentPosition" 
                           class="form-control" 
                           placeholder="${this.translations.get('create_modal.form.fragments.position')}">
                </div>
                <button type="button" class="btn btn-primary" onclick="createConstructionGame.addFragment()">
                    ${this.translations.get('create_modal.form.fragments.add')}
                </button>
            </div>
            <div class="fragments-list"></div>
        `;

        // Inicializar drag & drop después de crear la estructura
        this.initializeDragAndDrop();
    }

    addFragment() {
        const fragmentText = document.getElementById('fragmentText').value.trim();
        const position = parseInt(document.getElementById('fragmentPosition').value);

        if (!this.validateFragmentInput(fragmentText, position)) {
            return;
        }

        const fragment = {
            texto: fragmentText,
            posicion_correcta: position,
            es_señuelo: false
        };

        this.addFragmentToList(fragment);
        // Limpiar campos
        document.getElementById('fragmentText').value = '';
        document.getElementById('fragmentPosition').value = '';
    }

    validateFragmentInput(text, position) {
        this.clearValidationErrors();
        const errors = [];
        if (!text) {
            errors.push({
                message: this.translations.get('create_modal.validation.fragment_text_required'),
                elementId: 'fragmentText'
            });
        }

        if (isNaN(position) || position < 1) {
            errors.push({
                message: this.translations.get('create_modal.validation.position_required'),
                elementId: 'fragmentPosition'
            });
        }

        // Verificar si ya existe un fragmento en esa posición
        const existingFragments = this.getExistingFragments();
        if (existingFragments.some(f => f.posicion_correcta === position)) {
            errors.push({
                message: this.translations.get('create_modal.validation.position_duplicate'),
                elementId: 'fragmentPosition'
            });
        }

        if (errors.length > 0) {
            this.showValidationErrors(errors);
            return false;
        }

        return true;
    }

    addFragmentToList(fragment) {
        const fragmentsList = document.querySelector('.fragments-list');
        if (!fragmentsList) return;

        const fragmentElement = document.createElement('div');
        fragmentElement.className = 'fragment-item';
        fragmentElement.innerHTML = `
            <i class='bx bx-menu fragment-handle'></i>
            <span class="position-badge">${fragment.posicion_correcta}</span>
            <span class="fragment-text">${fragment.texto}</span>
            <i class='bx bx-x fragment-delete' onclick="createConstructionGame.removeFragment(this)"></i>
        `;
        fragmentsList.appendChild(fragmentElement);
    }

    initializeDragAndDrop() {
        const fragmentsList = document.querySelector('.fragments-list');
        if (fragmentsList) {
            new Sortable(fragmentsList, {
                animation: 150,
                handle: '.fragment-handle',
                ghostClass: 'fragment-ghost',
                onEnd: (evt) => {
                    this.updatePositionsAfterDrag();
                }
            });
        }
    }

    updatePositionsAfterDrag() {
        const fragments = document.querySelectorAll('.fragment-item');
        fragments.forEach((fragment, index) => {
            const positionBadge = fragment.querySelector('.position-badge');
            if (positionBadge) {
                positionBadge.textContent = index + 1;
            }
        });
    }

    removeFragment(element) {
        const fragmentItem = element.closest('.fragment-item');
        if (fragmentItem) {
            fragmentItem.remove();
        }
    }

    getExistingFragments() {
        const fragmentsList = document.querySelector('.fragments-list');
        if (!fragmentsList) return [];

        return Array.from(fragmentsList.children).map(item => ({
            texto: item.querySelector('.fragment-text').textContent,
            posicion_correcta: parseInt(item.querySelector('.position-badge').textContent),
            es_señuelo: false
        }));
    }

    /***SECCION DE SENIUELOS */
    initializeDecoys() {
        const decoysContainer = document.getElementById('decoysContainer');
        decoysContainer.innerHTML = `
            <div class="decoys-list" id="decoysList"></div>
            <div class="add-decoy-form">
                <div class="form-group">
                    <input type="text" 
                           id="decoyText" 
                           class="form-control" 
                           placeholder="${this.translations.get('create_modal.form.decoys.text')}">
                </div>
                <button type="button" class="btn btn-outline-primary" onclick="createConstructionGame.addDecoy()">
                    <i class='bx bx-plus'></i>
                    ${this.translations.get('create_modal.form.decoys.add')}
                </button>
            </div>
        `;

        // Inicializar drag & drop para señuelos
        new Sortable(document.getElementById('decoysList'), {
            animation: 150,
            handle: '.decoy-handle',
            ghostClass: 'decoy-ghost'
        });
    }

    addDecoy() {
        this.clearValidationErrors();
        const errors = [];
        const decoyText = document.getElementById('decoyText').value.trim();

        if (!decoyText) {
            errors.push({
                message: this.translations.get('create_modal.validation.decoy_text_required'),
                section: 'decoys'
            });
            this.showValidationErrors(errors);
            return;
        }

        const decoyElement = document.createElement('div');
        decoyElement.className = 'decoy-item';
        decoyElement.innerHTML = `
            <i class='bx bx-menu decoy-handle'></i>
            <span class="decoy-text">${decoyText}</span>
            <i class='bx bx-x decoy-delete' onclick="createConstructionGame.removeDecoy(this)"></i>
        `;

        document.getElementById('decoysList').appendChild(decoyElement);
        document.getElementById('decoyText').value = '';
    }

    removeDecoy(element) {
        const decoyItem = element.closest('.decoy-item');
        if (decoyItem) {
            decoyItem.remove();
        }
    }

    getDecoys() {
        const decoysList = document.getElementById('decoysList');
        if (!decoysList) return [];

        return Array.from(decoysList.children).map(item => ({
            texto: item.querySelector('.decoy-text').textContent,
            es_señuelo: true
        }));
    }

    /** SECIOON DE CREACION INTEGRAL */
    async validateAndCollectFormData() {
        try {
            const errors = [];
            const requirementText = document.getElementById('requirementText').value.trim();
            const fragments = this.getExistingFragments();
            const decoys = this.getDecoys();

            // Validaciones
            if (!requirementText) {
                errors.push({
                    message: this.translations.get('create_modal.validation.requirement_required'),
                    section: 'requirement',
                    elementId: 'requirementText'
                });
            }

            if (fragments.length === 0) {
                errors.push({
                    message: this.translations.get('create_modal.validation.fragments_required'),
                    section: 'fragments'
                });
            }

            // Validar que los fragmentos formen el requisito completo
            const fragmentsText = fragments
                .sort((a, b) => a.posicion_correcta - b.posicion_correcta)
                .map(f => f.texto)
                .join(' ')
                .trim();

            if (fragmentsText !== requirementText) {
                errors.push({
                    message: this.translations.get('create_modal.validation.fragments_match')
                });
            }

            // Validar que haya al menos un señuelo
            if (decoys.length === 0) {
                errors.push({
                    message: this.translations.get('create_modal.validation.decoys_required'),
                    section: 'decoys'
                });
            }

            if (errors.length > 0) {
                this.showValidationErrors(errors);
                return false;
            }

            // Retornar datos estructurados
            return {
                requisito_completo: requirementText,
                fragmentos: [
                    ...fragments,
                    ...decoys
                ]
            };
        } catch (error) {
            const errors = [];
            console.error('Error:', error);
            errors.push({
                message: this.translations.get('errors.modal_error')
            });
            if (errors.length > 0) {
                this.showValidationErrors(errors);
                return false;
            }
        }
    }

    async saveRequirement(data) {
        try {
            const response = await fetch(this.config.endpoints.createRequirement, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    encryptedData: CryptoModule.encrypt(data)
                })
            });

            const resultData = await response.json();
            const decryptedResponse = CryptoModule.decrypt(resultData.data);

            if (!decryptedResponse.success) {
                throw new Error(decryptedResponse.message || this.translations.get('errors.server_error'));
            }

            // Si la creación fue exitosa
            await this.showSuccessMessage(this.translations.get('messages.requirement_created'));

            // Agregar el nuevo requisito a la tabla principal
            if (decryptedResponse.requirement) {
                this.addRequirementToTable(decryptedResponse.requirement);
            }

        } catch (error) {
            console.error('Error saving requirement:', error);
            await this.showErrorMessage(this.translations.get('errors.creation_error'));
            //throw error; // Re-lanzar para manejo superior si es necesario
        }
    }

    /**CREACION DEL JUEGO FINAL */

    async createGame() {
        try {
            if (this.state.requirementCount < this.config.minRequirements) {
                this.toastManager.show(
                    this.translations.get('messages.min_requirements'),
                    'error'
                );
                return;
            }

            // Obtener solo los IDs de los requisitos seleccionados
            const requirementIds = Array.from(this.state.selectedRequirements.keys());

            const gameData = {
                requirements: requirementIds
            };

            const response = await fetch(this.config.endpoints.createGame, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    encryptedData: CryptoModule.encrypt(gameData)
                })
            });

            const data = await response.json();
            const decryptedResponse = CryptoModule.decrypt(data.data);

            if (decryptedResponse.success) {
                await this.showGameCreatedModal(decryptedResponse.gameCode);
            } else {
                throw new Error(decryptedResponse.message || this.translations.get('errors.server_error'));
            }
        } catch (error) {
            console.error('Error creating game:', error);
            this.toastManager.show(
                error.message || this.translations.get('errors.game_creation_error'),
                'error'
            );
        }
    }

    async showGameCreatedModal(gameCode) {
        let shouldRedirect = false;

        const showMainModal = async () => {
            const result = await Swal.fire({
                icon: 'success',
                title: this.translations.get('messages.game_created'),
                html: `
                    <div class="game-code-container">
                        <p>${this.translations.get('messages.game_code')}:</p>
                        <div class="code-display">
                            <span class="game-code">${gameCode}</span>
                            <i class='bx bx-info-circle info-icon' 
                               id="gameCodeInfo"
                               style="cursor: pointer; margin-left: 8px; color: #666;">
                            </i>
                        </div>
                    </div>
                `,
                confirmButtonColor: '#1976D2',
                confirmButtonText: this.translations.get('buttons.continue'),
                allowOutsideClick: false,
                customClass: {
                    container: 'game-type-modal',
                    popup: 'game-levels-popup',
                },
                didOpen: (modalElement) => {
                    const infoIcon = modalElement.querySelector('#gameCodeInfo');
                    infoIcon.addEventListener('click', async (e) => {
                        Swal.close();

                        await Swal.fire({
                            title: this.translations.get('modals.game_code_info.title'),
                            html: `
                                <div class="game-code-info">
                                    <p>${this.translations.get('modals.game_code_info.description')}</p>
                                    <ul>
                                        <li>${this.translations.get('modals.game_code_info.point1')}</li>
                                        <li>${this.translations.get('modals.game_code_info.point2')}</li>
                                        <li>${this.translations.get('modals.game_code_info.point3')}</li>
                                    </ul>
                                </div>
                            `,
                            icon: 'info',
                            confirmButtonColor: '#1976D2',
                            customClass: {
                                container: 'game-type-modal',
                                popup: 'game-levels-popup',
                            }
                        });

                        return showMainModal();
                    });
                }
            });

            if (result.isConfirmed) {
                shouldRedirect = true;
                window.location.href = `${base_url}/Analytics`;
            }

            return result;
        };

        return showMainModal();
    }
}

// Inicialización cuando el DOM está listo
document.addEventListener('DOMContentLoaded', () => {
    window.createConstructionGame = new CreateConstructionGame();
});