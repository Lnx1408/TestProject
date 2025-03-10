class CreateClassificationGame {
    constructor() {
        this.config = {
            selectors: {
                mainContainer: '#main',
                headerToggle: '#header-toggle',
            },
            endpoints: {
                getExisting: `${base_url}/Levels/get_requirements_clasification`,
                createRequirement: `${base_url}/Levels/create_requirement_clasification`,
                createGame: `${base_url}/Levels/create_game_clasification`,
                importMasiveData: `${base_url}/Levels/import_requirements`
            },
            minRequirements: 5
        };

        this.debug = {
            columnStates: [] // Variable para almacenar las columnas y sus títulos
        };

        // Estado de la aplicación
        this.state = {
            selectedRequirements: new Map(), // Requisitos ya agregados a la tabla principal
            temporarySelections: new Set(),  // Para mantener las selecciones en el modal
            requirementCount: 0
        };

        this.translations = {
            get: (key) => LanguageManager.getTranslation(`create_classification.${key}`)
        };

        // Referencias a elementos del DOM
        this.elements = {
            selectedTable: null,  // Instancia DataTable de la tabla principal
            selectModal: null,    // Instancia DataTable del modal de selección
            createGameBtn: document.getElementById('createGameBtn'),
            reqCountDisplay: document.getElementById('reqCount')
        };

        this.columnDefs = {
            columns: [
                {
                    data: null,
                    title: '*',
                    render: (data) => ''
                },
                {
                    data: 'description',
                    //title: this.translations.get('main_table.columns.description'),
                    title: `<span data-i18n="create_classification.main_table.columns.description">Descripción</span>`,
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
                    data: 'is_functional',
                    responsivePriority: 3,
                    //title: this.translations.get('main_table.columns.type'),
                    title: `<span data-i18n="create_classification.main_table.columns.type">Tipo</span>`,
                    render: (data) => this.renderRequirementType(data)
                },
                {
                    data: 'is_ambiguous',
                    responsivePriority: 2,
                    //title: this.translations.get('main_table.columns.is_ambiguous'),
                    title: `<span data-i18n="create_classification.main_table.columns.is_ambiguous">Es Ambiguo</span>`,
                    render: (data) => this.renderAmbiguousState(data)
                },
                {
                    data: 'feedback',
                    responsivePriority: 3,
                    title: `<span data-i18n="create_classification.main_table.columns.feedback">Retroalimentación</span>`
                },
                {
                    data: null,
                    responsivePriority: 1,
                    title: `<span data-i18n="create_classification.main_table.columns.actions">Acciones</span>`,
                    className: "dt-center",
                    render: (data, type, row) => this.renderActions(row)
                }
            ]
        };

        this.importConfig = {
            requiredColumns: ['descripcion', 'es_ambiguo', 'es_funcional', 'retroalimentacion'],
            maxFileSize: 1024 * 1024, // 1MB
            allowedExtensions: ['csv']
        };

        this.aiGenerator = null;

        this.initializeTables();
        this.initializeResponsiveHandling();
        this.bindEvents();
    }

    initializeResponsiveHandling() {
        // Obtener referencias a los elementos
        const mainContainer = document.querySelector(this.config.selectors.mainContainer);

        if (!mainContainer || !this.elements.selectedTable) return;

        // Crear ResizeObserver para el contenedor principal
        const resizeObserver = new ResizeObserver(entries => {
            if (this.elements.selectedTable) {
                // Ajustamos las columnas
                this.elements.selectedTable.columns.adjust();

                // llamamos a la función de actualización después de un tiempo
                setTimeout(() => {
                    this.elements.selectedTable.responsive.recalc();
                }, 100);
            }
        });

        // Observar el contenedor principal
        resizeObserver.observe(mainContainer);
    }

    adjustTableAfterLanguageChange() {
        if (this.elements.selectedTable) {
            this.elements.selectedTable.columns.adjust();
            this.elements.selectedTable.responsive.recalc();
        }
    }

    initializeTables() {
        this.elements.selectedTable = $('#selectedRequirementsTable').DataTable({
            responsive: true,
            columns: this.columnDefs.columns,
            language: this.getDataTableLanguage(),
            responsive: {
                details: {
                    type: 'column',
                    target: 'tr',
                    renderer: (api, rowIdx, columns) => {
                        const data = api.row(rowIdx).data();
                        let html = '<div class="expanded-details">';

                        const hiddenColumns = columns.filter(col => col.hidden);
                        if (hiddenColumns.length > 0) {
                            html += `
                                <div class="hidden-columns-section">
                                    <div class="hidden-columns-content">
                                        <ul class="dtr-details">
                                        ${hiddenColumns.map(col => {
                                // Obtener el título de la configuración de columnas si está disponible
                                const columnDef = this.columnDefs.columns[col.columnIndex];
                                const titleCol = api.settings()[0].aoColumns[col.columnIndex].sTitle;
                                let title;
                                if (!titleCol || titleCol === 'undefined') {
                                    // Si titleCol es inválido, usar directamente columnDef.title
                                    title = columnDef.title;
                                } else if (columnDef && columnDef.title) {
                                    // Si ambos son válidos, actualizar el contenido del span
                                    title = columnDef.title.replace(
                                        />([^<]+)<\/span>/, // Busca el contenido entre > y </span>
                                        `>${titleCol}</span>` // Reemplaza con el nuevo titleCol
                                    );
                                } else {
                                    // Si no hay columnDef válido, usar titleCol
                                    title = titleCol;
                                }

                                return `
                                                <li class="detail-row">
                                                    <span class="dtr-title">${title}:</span>
                                                    <span class="dtr-data">${col.data}</span>
                                                </li>
                                            `;
                            }).join('')}
                                        </ul>
                                    </div>
                                </div>
                            `;
                        }
                        html += '</div>';
                        return html;
                    }

                }
            },
            columnDefs: [
                {
                    className: 'dtr-control',
                    orderable: false,
                    targets: 0,
                    width: '2.5rem'
                },
                {
                    targets: [1],
                    width: '50%',
                }
            ],
            processing: true,
            serverSide: false,
            initComplete: () => {
                // Verificar títulos después de la inicialización
                setTimeout(() => {
                    this.elements.selectedTable.columns().header().each((header, index) => {
                        const title = $(header).text();
                        this.elements.selectedTable.settings()[0].aoColumns[index].sTitle = title;
                        if (!title || title === 'undefined') {
                            const originalTitle = this.columnDefs.columns[index].title || `T ${index + 1}`;
                            $(header).text(originalTitle);
                        }
                    });
                }, 350);
            }
        });
    }

    renderRequirementType(isFunctional) {
        const typeClass = isFunctional ? 'functional' : 'non-functional';
        const typeText = isFunctional ? 'Funcional' : 'No Funcional';
        return `<span class="requirement-type ${typeClass}">${typeText}</span>`;
    }

    renderAmbiguousState(isAmbiguous) {
        const stateClass = isAmbiguous ? 'yes' : 'no';
        const icon = isAmbiguous ? 'bx-check' : 'bx-x';
        return `<span class="ambiguous-state ${stateClass}">
                    <i class='bx ${icon}'></i>
                    ${isAmbiguous ? 'Sí' : 'No'}
                </span>`;
    }

    renderActions(row) {
        return `<div class="table-actions">
                    <button onclick="createClassificationGame.removeRequirement('${row.id}')" 
                            class="btn-action">
                        <i class='bx bx-trash'></i>
                    </button>
                </div>`;
    }

    showSelectModal() {
        // Limpiar selecciones temporales al abrir el modal
        this.state.temporarySelections.clear();

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
                            <th>${this.translations.get('main_table.columns.description')}</th>
                            <th>${this.translations.get('main_table.columns.is_ambiguous')}</th>
                            <th>${this.translations.get('main_table.columns.type')}</th>
                        </tr>
                    </thead>
                </table>`;
    }

    showCreateModal() {
        Swal.fire({
            title: this.translations.get('create_modal.title'),
            html: this.createFormModalContent(),
            width: '600px',
            showCancelButton: true,
            confirmButtonText: this.translations.get('create_modal.buttons.create'),
            cancelButtonText: this.translations.get('create_modal.buttons.cancel'),
            customClass: {
                container: 'game-type-modal',
                popup: 'game-levels-popup',
            },
            preConfirm: () => this.validateAndGetFormData()
        }).then((result) => {
            if (result.isConfirmed) {
                this.createNewRequirement(result.value);
            }
        });
    }

    createFormModalContent() {
        return `
            <form id="createRequirementForm" class="requirement-form">
                <div class="form-group">
                    <label>${this.translations.get('create_modal.form.description')}</label>
                    <textarea id="reqDescription" class="form-control" rows="3"></textarea>
                </div>
                <div class="form-group">
                    <label class="checkbox-container">
                        ${this.translations.get('create_modal.form.is_ambiguous')}
                        <input type="checkbox" id="reqIsAmbiguous">
                    </label>
                </div>
                <div class="form-group">
                    <label class="checkbox-container">
                        ${this.translations.get('create_modal.form.is_functional')}
                        <input type="checkbox" id="reqIsFunctional">
                    </label>
                </div>
                <div class="form-group">
                    <label>${this.translations.get('create_modal.form.feedback')}</label>
                    <textarea id="reqFeedback" class="form-control" rows="3"></textarea>
                </div>
            </form>
        `;
    }

    // Método para manejar el cambio en los checkboxes
    handleCheckboxChange(checkbox, requirementId) {
        if (checkbox.checked) {
            this.state.temporarySelections.add(requirementId);
        } else {
            this.state.temporarySelections.delete(requirementId);
        }
        console.log('Selecciones temporales:', this.state.temporarySelections);
    }

    async initializeSelectTable() {
        const table = $('#existingRequirementsTable').DataTable({
            ajax: {
                url: this.config.endpoints.getExisting,
                type: 'GET',
                //dataSrc: 'data'
                dataSrc: (response) => {
                    try {
                        if (!response.data) {
                            throw new Error(this.translations.get('errors.invalid_data'));
                        }

                        const decryptedData = CryptoModule.decrypt(response.data);

                        if (!decryptedData) {
                            throw new Error(this.translations.get('errors.messages.error_loading'));
                        }

                        if (!decryptedData.success) {
                            throw new Error(decryptedData.message || this.translations.get('errors.server_error'));
                        }

                        return decryptedData.data || [];
                    } catch (error) {
                        console.error('Error processing data:', error);
                        this.showErrorMessage(this.translations.get('errors.general'));
                        return [];
                    }
                },
                error: (xhr, error, thrown) => {
                    console.error('Ajax error:', error);
                    this.showErrorMessage(this.translations.get('errors.connection_error'));
                }
            },
            processing: true,
            serverSide: false,
            columns: [
                {
                    data: null,
                    responsivePriority: 1,
                    render: (data) => {
                        const isDisabled = this.state.selectedRequirements.has(data.id);
                        //<div class="checkbox-wrapper" onclick="event.stopPropagation();" onmousedown="event.stopPropagation();">
                        return `
                            <div class="checkbox-wrapper" onclick="event.stopPropagation();">
                                <input type="checkbox" 
                                    class="req-checkbox" 
                                    value="${data.id}"
                                    onchange="createClassificationGame.handleCheckboxChange(this, ${data.id})"
                                    ${isDisabled ? 'checked disabled' : ''}>
                            </div>
                        `;
                    },
                    orderable: false
                },
                {
                    data: 'description',
                    className: 'wrap-cell', // Nueva clase para permitir wrap
                    responsivePriority: 1,  // Máxima prioridad - nunca se ocultará
                    render: function (data, type, row) {
                        if (type === 'display') {
                            return `<div class="requirement-description">${data}</div>`;
                        }
                        return data;
                    }
                },
                {
                    data: 'is_ambiguous',
                    responsivePriority: 2,
                    render: (data) => this.renderAmbiguousState(data)
                },
                {
                    data: 'is_functional',
                    responsivePriority: 3,
                    render: (data) => this.renderRequirementType(data)
                }
            ],
            columnDefs: [
                {
                    targets: [1], // Índice de la columna descripción
                    width: '50%', // Dar un ancho fijo a la columna
                }
            ],
            language: this.getDataTableLanguage(),
            select: {
                style: 'multi',
                selector: 'td:first-child input:not(:disabled)'
            },
            rowCallback: (row, data) => {
                if (this.state.selectedRequirements.has(data.id)) {
                    $(row).addClass('selected-requirement');
                }
            }
        });

        this.elements.selectModal = table;

        // Agregar manejador de errores global para la tabla
        table.on('error.dt', (e, settings, techNote, message) => {
            console.error('DataTables error:', message);
            this.showErrorMessage(this.translations.get('errors.table_error'));
        });
    }

    updateExportButtonState() {
        const exportBtn = document.getElementById('exportSelectedBtn');
        if (exportBtn) {
            exportBtn.disabled = this.state.selectedRequirements.size === 0;
        }
    }

    exportRequirements() {
        const selectedRequirements = Array.from(this.state.selectedRequirements).map(reqId => {
            const requirement = this.findRequirementById(reqId);
            if (!requirement) return null;

            return {
                descripcion: requirement.description,
                es_ambiguo: requirement.is_ambiguous ? 1 : 0,
                es_funcional: requirement.is_functional === 'funcional' ? 1 : 0,
                retroalimentacion: requirement.feedback
            };
        }).filter(req => req !== null);

        if (selectedRequirements.length === 0) {
            this.showErrorMessage(this.translations.get('messages.no_requirements_selected'));
            return;
        }

        // Configuracion de encabezados del CSV
        const headers = {
            descripcion: 'descripcion',
            es_ambiguo: 'es_ambiguo',
            es_funcional: 'es_funcional',
            retroalimentacion: 'retroalimentacion'
        };

        const csv = Papa.unparse({
            fields: Object.values(headers),
            data: selectedRequirements.map(req => Object.values(req))
        }, {
            quotes: true,
            delimiter: ";",
        });

        // Crear y descargar el archivo
        const blob = new Blob(["\ufeff", csv], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        const url = URL.createObjectURL(blob);
        const timestamp = new Date().toISOString().replace(/[:.]/g, '-');

        link.setAttribute('href', url);
        link.setAttribute('download', `requisitos_${timestamp}.csv`);
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        URL.revokeObjectURL(url);
    }

    findRequirementById(reqId) {
        const allData = this.elements.selectModal.data().toArray();
        return allData.find(row => row.id === parseInt(reqId));
    }

    validateAndGetFormData() {
        const description = document.getElementById('reqDescription').value.trim();
        const feedback = document.getElementById('reqFeedback').value.trim();
        const isAmbiguous = document.getElementById('reqIsAmbiguous').checked;
        const isFunctional = document.getElementById('reqIsFunctional').checked;

        if (!description) {
            Swal.showValidationMessage(this.translations.get('create_modal.validation.description_required'));
            return false;
        }

        if (!feedback) {
            Swal.showValidationMessage(this.translations.get('create_modal.validation.feedback_required'));
            return false;
        }

        return {
            description,
            feedback,
            isAmbiguous,
            isFunctional
        };
    }

    async createNewRequirement(data) {
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

            const resultEncript = await response.json();
            const result = CryptoModule.decrypt(resultEncript.data);

            if (result.success && Array.isArray(result.requirement) && result.requirement.length > 0) {
                this.addRequirementToTable(result.requirement[0]);
                this.showSuccessMessage(this.translations.get('messages.requirement_created'));
            } else {
                throw new Error(result.message || 'An unknown error occurred.');
            }
        } catch (error) {
            console.log('Error:', error.message);
            this.showErrorMessage(this.translations.get('messages.error_message'));
        }
    }

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
            showCancelButton: true,
            confirmButtonText: this.translations.get('import_modal.import'),
            cancelButtonText: this.translations.get('import_modal.cancel'),
            confirmButtonColor: '#1976D2',
            showConfirmButton: false,
            width: '600px',
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

    downloadTemplate() {
        const templateData = [
            {
                descripcion: 'El sistema debe procesar las transacciones en menos de 2 segundos',
                es_ambiguo: '0',
                es_funcional: '1',
                retroalimentacion: 'Este requisito es específico y medible'
            },
            {
                descripcion: 'El sistema debe ser rápido',
                es_ambiguo: '1',
                es_funcional: '1',
                retroalimentacion: 'Este requisito es ambiguo porque no especifica qué significa rápido'
            }
        ];

        const csv = Papa.unparse(templateData, { delimiter: ";" });
        const blob = new Blob(["\ufeff", csv], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = 'plantilla_requisitos.csv';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }

    handleFileSelection(event) {
        const file = event.target.files[0];
        if (!file) return;

        const extension = file.name.split('.').pop().toLowerCase();
        if (!this.importConfig.allowedExtensions.includes(extension)) {
            this.showErrorMessage(this.translations.get('import_modal.errors.invalid_extension'));
            return;
        }

        if (file.size > this.importConfig.maxFileSize) {
            this.showErrorMessage(this.translations.get('import_modal.errors.file_too_large'));
            return;
        }

        Papa.parse(file, {
            complete: (results) => this.validateAndPreviewData(results),
            header: true,
            skipEmptyLines: true,
            encoding: 'UTF-8'
        });
    }

    validateAndPreviewData(results) {
        const validationResult = this.validateCSVData(results.data);
        if (!validationResult.isValid) {
            this.showErrorMessage(validationResult.errors.join('\n'));
            return;
        }

        //this.showDataPreview(results.data);
        this.updateImportModal(results.data);
    }

    validateCSVData(data) {
        const errors = [];
        const requiredColumns = this.importConfig.requiredColumns;

        // Validar columnas
        const headers = Object.keys(data[0] || {});
        const missingColumns = requiredColumns.filter(col => !headers.includes(col));

        if (missingColumns.length > 0) {
            errors.push(`Columnas faltantes: ${missingColumns.join(', ')}`);
        }

        // Validar datos
        data.forEach((row, index) => {
            if (!row.descripcion?.trim()) {
                errors.push(`Fila ${index + 1}: Descripción vacía`);
            }
            if (!['0', '1'].includes(row.es_ambiguo?.toString())) {
                errors.push(`Fila ${index + 1}: Valor inválido para es_ambiguo`);
            }
            if (!['0', '1'].includes(row.es_funcional?.toString())) {
                errors.push(`Fila ${index + 1}: Valor inválido para es_funcional`);
            }
            if (!row.retroalimentacion?.trim()) {
                errors.push(`Fila ${index + 1}: Retroalimentación vacía`);
            }
        });

        return {
            isValid: errors.length === 0,
            errors
        };
    }

    showDataPreview(data) {
        const previewDiv = document.getElementById('importPreview');
        const previewContent = previewDiv.querySelector('.preview-content');

        const previewHtml = `
            <table class="preview-table">
                <thead>
                    <tr>
                        ${this.importConfig.requiredColumns.map(col =>
            `<th>${col}</th>`).join('')}
                    </tr>
                </thead>
                <tbody>
                    ${data.slice(0, 5).map(row => `
                        <tr>
                            ${this.importConfig.requiredColumns.map(col =>
                `<td>${row[col]}</td>`).join('')}
                        </tr>
                    `).join('')}
                </tbody>
            </table>
            ${data.length > 5 ? `<p>... y ${data.length - 5} requisitos más</p>` : ''}
        `;

        previewContent.innerHTML = previewHtml;
        previewDiv.style.display = 'block';
    }

    updateImportModal(data) {
        Swal.update({
            showConfirmButton: true,
            confirmButtonText: `${this.translations.get('import_modal.import')} (${data.length})`,
        });

        // Actualizar el handler del botón de confirmar
        const confirmButton = Swal.getConfirmButton();
        confirmButton.onclick = () => this.processImport(data);
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

    addSelectedRequirements() {
        const allData = this.elements.selectModal.data().toArray();
        // Convertir los datos de DataTables a un array ALTERNATIVA 2
        //const allData = Array.from(this.elements.selectModal.data());
        // Procesar cada selección temporal
        this.state.temporarySelections.forEach(reqId => {
            const requirement = allData.find(row => row.id === reqId);
            if (requirement && !this.state.selectedRequirements.has(reqId)) {
                this.addRequirementToTable(requirement);
            }
        });

        // Limpiar selecciones temporales
        this.state.temporarySelections.clear();
    }

    addRequirementToTable(requirement) {
        if (!this.state.selectedRequirements.has(requirement.id)) {
            this.state.selectedRequirements.set(requirement.id, requirement);
            this.elements.selectedTable.row.add(requirement).draw();
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
            }
        }).then((result) => {
            if (result.isConfirmed) {
                // Eliminar del Map de requisitos seleccionados
                this.state.selectedRequirements.delete(parseInt(requirementId));

                // Encontrar y eliminar la fila directamente usando una función de búsqueda
                const table = this.elements.selectedTable;
                table.rows(function (idx, data) {
                    return data.id === parseInt(requirementId);
                }).remove().draw();

                this.updateRequirementCount();
                // También podríamos querer actualizar el estado del checkbox en el modal de selección
                if (this.elements.selectModal) {
                    this.elements.selectModal.draw(false); // false para mantener la página actual
                }
            }
        });
    }

    updateRequirementCount() {
        this.state.requirementCount = this.state.selectedRequirements.size;
        this.elements.reqCountDisplay.textContent = this.state.requirementCount;

        // Actualizar estado del botón de crear partida
        this.elements.createGameBtn.disabled =
            this.state.requirementCount < this.config.minRequirements;

        this.updateExportButtonState();
    }

    async createGame() {
        if (this.state.requirementCount < this.config.minRequirements) {
            this.showErrorMessage(this.translations.get('messages.min_requirements'));
            return;
        }

        try {
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
                await this.showSuccessModal(decryptedResponse.gameCode);
            } else {
                throw new Error(result.message);
            }
        } catch (error) {
            this.showErrorMessage(this.translations.get('errors.general'));
        }
    }

    async showSuccessModal(gameCode) {
        let shouldRedirect = false;

        // Función para mostrar el modal principal
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
                    infoIcon.addEventListener('click', async () => {
                        // Cerrar temporalmente el modal principal
                        Swal.close();

                        // Mostrar el modal de información
                        await Swal.fire({
                            title: this.translations.get('create_modal.game_code_info.title'),
                            html: `
                                <div class="game-code-info">
                                    <p>${this.translations.get('create_modal.game_code_info.description')}</p>
                                    <ul>
                                        <li>${this.translations.get('create_modal.game_code_info.point1')}</li>
                                        <li>${this.translations.get('create_modal.game_code_info.point2')}</li>
                                        <li>${this.translations.get('create_modal.game_code_info.point3')}</li>
                                    </ul>
                                </div>
                            `,
                            icon: 'info',
                            confirmButtonColor: '#1976D2',
                            customClass: {
                                container: 'game-type-modal',
                                popup: 'game-levels-popup',
                            },
                        });

                        // Volver a mostrar el modal principal
                        return showMainModal();
                    });
                }
            });

            // Actualizar el estado de redirección
            if (result.isConfirmed) {
                shouldRedirect = true;
                window.location.href = `${base_url}/Analytics`;
            }

            return result;
        };

        // Mostrar el modal principal por primera vez
        await showMainModal();
    }

    bindEvents() {
        document.addEventListener('languageChanged', () => {
            setTimeout(() => {
                //this.updateTableTranslations();
                this.adjustTableAfterLanguageChange();
            }, 100);
        });

        document.getElementById('selectExistingBtn')
            .addEventListener('click', () => this.showSelectModal());

        document.getElementById('createNewBtn')
            .addEventListener('click', () => this.showCreateModal());

        document.getElementById('createGameBtn')
            .addEventListener('click', () => this.createGame());

        document.getElementById('importReqBtn')
            .addEventListener('click', () => this.showImportModal());

        const exportBtn = document.getElementById('exportSelectedBtn');
        if (exportBtn) {
            exportBtn.addEventListener('click', () => {
                this.exportRequirements();
            });
        }

        const generateWithAiBtn = document.getElementById('generateWithAiBtn');
        if (generateWithAiBtn) {
            generateWithAiBtn.addEventListener('click', () => {
                if (typeof RequirementsGenerator !== 'undefined') {
                    // Si la clase del generador está cargada, usarla
                    if (!this.aiGenerator) {
                        this.aiGenerator = new RequirementsGenerator();
                    }

                    // Iniciar el proceso de generación
                    this.aiGenerator.openGenerator();
                } else {
                    // Si no está cargada, mostrar error
                    this.showErrorMessage(this.translations.get('errors.ai_generator_not_loaded'));
                }
            });
        }
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

    showErrorMessage(message) {
        return Swal.fire({
            icon: 'error',
            title: this.translations.get('messages.error'),
            text: message,
            confirmButtonColor: '#1976D2',
            customClass: {
                container: 'game-type-modal',
                popup: 'game-levels-popup',
            },
        });
    }
}

// Inicialización cuando el DOM está listo
document.addEventListener('DOMContentLoaded', async () => {
    window.createClassificationGame = new CreateClassificationGame();
});