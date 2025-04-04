/**
 * Clase para gestionar el generador de requisitos con IA
 */
class RequirementsGenerator {
    constructor() {
        this.baseUrl = base_url;
        this.endpoints = {
            getTemplate: `${this.baseUrl}/Levels/get_generator_template`,
            generate: `${this.baseUrl}/Levels/generate_requirements`,
            save: `${this.baseUrl}/Levels/save_generated_requirements`
        };

        this.state = {
            currentStep: 1,
            isLoading: false,
            provider: 'openai',
            language: 'es',
            context: '',
            numRequirements: 10,
            generatedRequirements: [],
            processingComplete: false
        };

        this.modal = null;
        this.tableManager = null;
        this.translations = {
            get: (key) => LanguageManager.getTranslation(`requirements_generator.${key}`)
        };
    }


    /**
     * Carga el template del generador mediante AJAX
     * @returns {Promise<string>} HTML del template
     */
    async loadGeneratorTemplate() {
        try {
            const response = await fetch(this.endpoints.getTemplate);

            if (!response.ok) {
                throw new Error(`Error ${response.status}: ${response.statusText}`);
            }

            return await response.text();
        } catch (error) {
            console.error('Error loading template:', error);
            return '<div class="error-container">Error al cargar el generador. Por favor, intente de nuevo.</div>';
        }
    }

    /**
     * Abre el modal del generador
     */
    async openGenerator_OLD() {
        // Resetear el estado
        this.resetState();

        try {
            // Cargar el template
            const template = await this.loadGeneratorTemplate();

            Swal.fire({
                title: '', // Sin título para usar todo el espacio
                html: template,
                showConfirmButton: false,
                showCloseButton: true,
                width: '90%',
                padding: 0,
                customClass: {
                    container: 'ai-generator-container',
                    popup: 'ai-generator-swal-popup',
                    closeButton: 'ai-generator-close-btn'
                },
                didOpen: () => {
                    // Inicializar eventos y componentes después de cargar el template
                    this.initializeGeneratorEvents();

                    // Inicializar tabla si Tabulator está disponible
                    if (typeof Tabulator !== 'undefined') {
                        this.initializeTable();
                    } else {
                        console.error('Tabulator.js no está cargado');
                        this.showErrorMessage('Error: Tabulator.js no está disponible');
                    }

                    // Aplicar traducciones después de cargar el template
                    if (typeof LanguageManager !== 'undefined' && LanguageManager.applyTranslations) {
                        LanguageManager.applyTranslations();
                    }
                }
            });
        } catch (error) {
            console.error('Error opening generator:', error);
            this.showErrorMessage('Error al abrir el generador de requisitos');
        }
    }

    async openGenerator() {
        // Resetear el estado
        this.resetState();

        try {
            // Cargar el template
            const template = await this.loadGeneratorTemplate();

            // Crear modal personalizado si no existe
            if (!this.modal) {
                this.modal = new CustomModal({
                    scrollable: false,
                    title: this.translations.get('modal.title') || 'Generador de Requisitos con IA',
                    closeOnClickOutside: true,
                    onClose: () => {
                        // Limpiar recursos al cerrar
                        if (this.tableManager) {
                            // Limpiar tabla si es necesario
                        }
                    }
                });
            }
            //modalConScroll.setScrollable(false); // Desactivar scroll
            // Establecer contenido
            this.modal.setContent(template);

            // Abrir modal
            this.modal.open();

            // Inicializar eventos y componentes después de abrir el modal
            this.initializeGeneratorEvents();

            // Inicializar tabla si Tabulator está disponible
            if (typeof Tabulator !== 'undefined') {
                this.initializeTable();
            } else {
                console.error('Tabulator.js no está cargado');
                this.showErrorMessage('Error: Tabulator.js no está disponible');
            }

            // Aplicar traducciones
            if (typeof LanguageManager !== 'undefined' && LanguageManager.applyTranslations) {
                LanguageManager.applyTranslations();
            }
        } catch (error) {
            console.error('Error opening generator:', error);
            this.showErrorMessage('Error al abrir el generador de requisitos');
        }
    }

    /**
     * Resetea el estado del generador
     */
    resetState() {
        this.state = {
            currentStep: 1,
            isLoading: false,
            provider: 'openai',
            language: 'es',
            context: '',
            numRequirements: 10,
            generatedRequirements: [],
            processingComplete: false
        };
    }

    /**
     * Inicializa los eventos del generador
     */
    initializeGeneratorEvents() {
        // Radio buttons para proveedor de IA
        const providerOptions = document.querySelectorAll('#ai-provider-options .radio-option');
        providerOptions.forEach(option => {
            option.addEventListener('click', () => {
                providerOptions.forEach(opt => opt.classList.remove('selected'));
                option.classList.add('selected');
                const input = option.querySelector('input[type="radio"]');
                input.checked = true;
                this.state.provider = input.value;
            });
        });

        // Radio buttons para idioma
        const languageOptions = document.querySelectorAll('#language-options .radio-option');
        languageOptions.forEach(option => {
            option.addEventListener('click', () => {
                languageOptions.forEach(opt => opt.classList.remove('selected'));
                option.classList.add('selected');
                const input = option.querySelector('input[type="radio"]');
                input.checked = true;
                this.state.language = input.value;
            });
        });

        // Vincular input de número y rango
        const numInput = document.getElementById('num-requirements');
        const rangeInput = document.getElementById('num-range');

        numInput.addEventListener('input', () => {
            let value = parseInt(numInput.value);
            if (isNaN(value)) value = 10;
            if (value < 5) value = 5;
            if (value > 20) value = 20;

            numInput.value = value;
            rangeInput.value = value;
            this.state.numRequirements = value;
        });

        rangeInput.addEventListener('input', () => {
            const value = rangeInput.value;
            numInput.value = value;
            this.state.numRequirements = parseInt(value);
        });

        // Campo de contexto
        const contextInput = document.getElementById('context');
        contextInput.addEventListener('input', () => {
            this.state.context = contextInput.value;
        });

        // Botones de navegación y acción
        document.getElementById('btn-generate').addEventListener('click', () => this.generateRequirements());
        document.getElementById('btn-back-step1').addEventListener('click', () => this.goToStep(1));
        document.getElementById('btn-to-step3').addEventListener('click', () => this.goToStep(3));
        document.getElementById('btn-back-step2').addEventListener('click', () => this.goToStep(2));
        document.getElementById('btn-save-requirements').addEventListener('click', () => this.saveRequirements());
        document.getElementById('btn-regenerate').addEventListener('click', () => this.regenerateRequirements());
    }

    /**
     * Inicializa la tabla de requisitos con Tabulator
     */
    initializeTable() {
        const tableElement = document.getElementById('requirements-table');
        if (!tableElement) return;

        // Asegurar que el contenedor de la tabla tiene ancho completo
        tableElement.style.width = '100%';

        this.tableManager = new RequirementsTableManager('requirements-table', {
            editable: true,
            selectable: true
        });

        // Configurar callbacks
        this.tableManager.setOnDeleteCallback((rowData) => {
            this.confirmDeleteRequirement(rowData);
        });

        this.tableManager.setOnEditCallback((rowData) => {
            this.showEditRequirementModal(rowData);
        });

        // Agregar un listener para el cambio de tamaño del modal
        document.addEventListener('modal:open', () => {
            setTimeout(() => {
                if (this.tableManager) {
                    this.tableManager.adjustTableSize();
                }
            }, 300);
        });

        // También reajustar la tabla cuando se cambia entre pasos
        document.querySelectorAll('[id^="btn-"]').forEach(button => {
            button.addEventListener('click', () => {
                setTimeout(() => {
                    if (this.tableManager) {
                        this.tableManager.adjustTableSize();
                    }
                }, 300);
            });
        });
    }

    /**
     * Navega a un paso específico
     * @param {number} step - Número del paso
     */
    goToStep(step) {
        // Validar paso actual antes de cambiar
        if (step > this.state.currentStep && !this.validateCurrentStep()) {
            return;
        }

        // Ocultar todos los paneles de pasos
        document.querySelectorAll('.step-content-panel').forEach(panel => {
            panel.style.display = 'none';
        });

        // Mostrar el panel del paso solicitado
        document.getElementById(`step-panel-${step}`).style.display = 'block';

        // Actualizar los estados de los ítems del stepper
        document.querySelectorAll('.step-item').forEach(item => {
            const itemStep = parseInt(item.getAttribute('data-step'));
            item.classList.remove('active', 'completed');

            if (itemStep === step) {
                item.classList.add('active');
            } else if (itemStep < step) {
                item.classList.add('completed');
            }
        });

        // Actualizar estado
        this.state.currentStep = step;

        // Si estamos yendo al paso 3, actualizar el resumen
        if (step === 3) {
            this.updateSummary();
        }
    }

    /**
     * Valida el paso actual antes de continuar
     * @returns {boolean} - Indica si el paso es válido
     */
    validateCurrentStep() {
        switch (this.state.currentStep) {
            case 1:
                // Validar formulario de configuración
                if (!this.state.context.trim()) {
                    this.showErrorMessage(this.translations.get('validation.context_required') || 'Por favor, ingrese el contexto del proyecto');
                    return false;
                }
                return true;

            case 2:
                // Validar que haya requisitos generados
                if (this.state.generatedRequirements.length === 0) {
                    this.showErrorMessage(this.translations.get('validation.no_requirements') || 'No hay requisitos para guardar');
                    return false;
                }
                return true;

            default:
                return true;
        }
    }

    /**
     * Genera los requisitos basados en la configuración
     */
    async generateRequirements() {
        if (!this.validateCurrentStep()) {
            return;
        }

        // Mostrar paso 2 con estado de carga
        this.goToStep(2);
        this.setLoading(true);

        try {
            const requestData = {
                provider: this.state.provider,
                language: this.state.language,
                context: this.state.context,
                num_requirements: this.state.numRequirements
            };

            const response = await fetch(this.endpoints.generate, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    encryptedData: CryptoModule.encrypt(requestData)
                })
            });

            const result = await response.json();
            const decryptedResponse = CryptoModule.decrypt(result.data);

            if (!decryptedResponse.success) {
                throw new Error(decryptedResponse.message || (this.translations.get('errors.generation_failed') || 'Error al generar requisitos'));
            }

            // Procesar los requisitos recibidos
            this.processGeneratedRequirements(decryptedResponse.requirements);

        } catch (error) {
            console.error('Error al generar requisitos:', error);
            this.showErrorMessage((this.translations.get('errors.generation_failed') || 'Error al generar requisitos') + ': ' + error.message);
        } finally {
            this.setLoading(false);
        }
    }

    /**
     * Regenera los requisitos con los mismos parámetros
     */
    regenerateRequirements() {
        // Confirmar antes de regenerar
        Swal.fire({
            title: this.translations.get('regenerate.title') || 'Regenerar requisitos',
            text: this.translations.get('regenerate.confirmation') || '¿Está seguro de que desea regenerar los requisitos? Los requisitos actuales serán reemplazados.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: this.translations.get('regenerate.confirm') || 'Regenerar',
            cancelButtonText: this.translations.get('regenerate.cancel') || 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                this.generateRequirements();
            }
        });
    }

    /**
     * Procesa los requisitos generados
     * @param {Array} requirements - Array de requisitos generados
     */
    processGeneratedRequirements(requirements) {
        // Agregar ID a cada requisito para manejarlos en la tabla
        const processedRequirements = requirements.map((req, index) => ({
            id: Date.now() + index, // Generar ID único
            description: req.description,
            is_functional: req.is_functional ? 1 : 0,
            is_ambiguous: req.is_ambiguous ? 1 : 0,
            feedback: req.feedback || ''
        }));

        // Actualizar estado
        this.state.generatedRequirements = processedRequirements;

        // Actualizar contador
        document.getElementById('requirements-count').textContent = processedRequirements.length;

        // Cargar datos en la tabla
        if (this.tableManager) {
            this.tableManager.setData(processedRequirements);
        }

        // Mostrar contenedor de resultados
        document.getElementById('results-container').style.display = 'block';
    }

    /**
     * Muestra/oculta el indicador de carga
     * @param {boolean} isLoading - Estado de carga
     */
    setLoading(isLoading) {
        this.state.isLoading = isLoading;
        const loadingIndicator = document.getElementById('loading-state');
        const resultsContainer = document.getElementById('results-container');

        if (isLoading) {
            loadingIndicator.style.display = 'flex';
            resultsContainer.style.display = 'none';
        } else {
            loadingIndicator.style.display = 'none';
            // No mostramos resultsContainer aquí, se hace en processGeneratedRequirements
        }
    }

    /**
     * Confirma la eliminación de un requisito
     * @param {Object} rowData - Datos del requisito a eliminar
     */
    confirmDeleteRequirement(rowData) {
        Swal.fire({
            title: this.translations.get('delete.title') || 'Eliminar requisito',
            text: this.translations.get('delete.confirmation') || '¿Está seguro de que desea eliminar este requisito?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: this.translations.get('delete.confirm') || 'Eliminar',
            cancelButtonText: this.translations.get('delete.cancel') || 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                this.deleteRequirement(rowData.id);
            }
        });
    }

    /**
     * Elimina un requisito de la tabla y del estado
     * @param {number|string} reqId - ID del requisito a eliminar
     */
    deleteRequirement(reqId) {
        // Eliminar de la tabla
        if (this.tableManager) {
            this.tableManager.deleteRow(reqId);
        }

        // Eliminar del estado
        this.state.generatedRequirements = this.state.generatedRequirements.filter(req => req.id !== reqId);

        // Actualizar contador
        document.getElementById('requirements-count').textContent = this.state.generatedRequirements.length;
    }

    /**
     * Muestra el modal para editar un requisito
     * @param {Object} rowData - Datos del requisito a editar
     */
    showEditRequirementModal(rowData) {
        Swal.fire({
            title: this.translations.get('edit.title') || 'Editar requisito',
            html: `
                <form id="edit-requirement-form">
                    <div class="form-group">
                        <label for="edit-description">${this.translations.get('edit.form.description') || 'Descripción'}</label>
                        <textarea id="edit-description" class="form-control" rows="3">${rowData.description}</textarea>
                    </div>
                    <div class="form-group">
                        <label>${this.translations.get('edit.form.type') || 'Tipo de requisito'}</label>
                        <div class="radio-group">
                            <div class="radio-option ${rowData.is_functional == 1 ? 'selected' : ''}">
                                <input type="radio" name="edit-type" id="edit-type-functional" value="1" ${rowData.is_functional == 1 ? 'checked' : ''}>
                                <label for="edit-type-functional">${this.translations.get('edit.form.functional') || 'Funcional'}</label>
                            </div>
                            <div class="radio-option ${rowData.is_functional == 0 ? 'selected' : ''}">
                                <input type="radio" name="edit-type" id="edit-type-non-functional" value="0" ${rowData.is_functional == 0 ? 'checked' : ''}>
                                <label for="edit-type-non-functional">${this.translations.get('edit.form.non_functional') || 'No Funcional'}</label>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>${this.translations.get('edit.form.ambiguous') || '¿Es ambiguo?'}</label>
                        <div class="radio-group">
                            <div class="radio-option ${rowData.is_ambiguous == 1 ? 'selected' : ''}">
                                <input type="radio" name="edit-ambiguous" id="edit-is-ambiguous" value="1" ${rowData.is_ambiguous == 1 ? 'checked' : ''}>
                                <label for="edit-is-ambiguous">${this.translations.get('edit.form.yes') || 'Sí'}</label>
                            </div>
                            <div class="radio-option ${rowData.is_ambiguous == 0 ? 'selected' : ''}">
                                <input type="radio" name="edit-ambiguous" id="edit-is-not-ambiguous" value="0" ${rowData.is_ambiguous == 0 ? 'checked' : ''}>
                                <label for="edit-is-not-ambiguous">${this.translations.get('edit.form.no') || 'No'}</label>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="edit-feedback">${this.translations.get('edit.form.feedback') || 'Retroalimentación'}</label>
                        <textarea id="edit-feedback" class="form-control" rows="3">${rowData.feedback}</textarea>
                    </div>
                </form>
            `,
            focusConfirm: false,
            showCancelButton: true,
            confirmButtonText: this.translations.get('edit.save') || 'Guardar',
            cancelButtonText: this.translations.get('edit.cancel') || 'Cancelar',
            customClass: {
                popup: 'edit-requirement-modal'
            },
            didOpen: () => {
                // Inicializar los radio buttons
                document.querySelectorAll('.edit-requirement-modal .radio-option').forEach(option => {
                    option.addEventListener('click', () => {
                        const radioName = option.querySelector('input[type="radio"]').name;
                        document.querySelectorAll(`.edit-requirement-modal .radio-option input[name="${radioName}"]`)
                            .forEach(input => input.parentElement.classList.remove('selected'));
                        option.classList.add('selected');
                        option.querySelector('input[type="radio"]').checked = true;
                    });
                });
            },
            preConfirm: () => {
                // Validar y obtener datos del formulario
                const description = document.getElementById('edit-description').value.trim();
                const isFunctional = document.querySelector('input[name="edit-type"]:checked').value;
                const isAmbiguous = document.querySelector('input[name="edit-ambiguous"]:checked').value;
                const feedback = document.getElementById('edit-feedback').value.trim();

                if (!description) {
                    Swal.showValidationMessage(this.translations.get('edit.validation.description_required') || 'La descripción es requerida');
                    return false;
                }

                if (!feedback) {
                    Swal.showValidationMessage(this.translations.get('edit.validation.feedback_required') || 'La retroalimentación es requerida');
                    return false;
                }

                return {
                    id: rowData.id,
                    description,
                    is_functional: isFunctional,
                    is_ambiguous: isAmbiguous,
                    feedback
                };
            }
        }).then((result) => {
            if (result.isConfirmed && result.value) {
                this.updateRequirement(result.value);
            }
        });
    }

    /**
     * Actualiza un requisito en la tabla y en el estado
     * @param {Object} data - Nuevos datos del requisito
     */
    updateRequirement(data) {
        // Actualizar en la tabla
        if (this.tableManager) {
            this.tableManager.updateRow(data.id, data);
        }

        // Actualizar en el estado
        this.state.generatedRequirements = this.state.generatedRequirements.map(req => {
            if (req.id === data.id) {
                return { ...data };
            }
            return req;
        });
    }

    /**
     * Actualiza el resumen en el paso 3
     */
    updateSummary() {
        // Actualizar valores del proveedor y lenguaje
        document.getElementById('summary-provider').textContent = this.state.provider === 'openai' ? 'OpenAI' : 'Gemini';
        document.getElementById('summary-language').textContent = this.state.language === 'es' ? 'Español' : 'Inglés';
        document.getElementById('summary-context').textContent = this.state.context;

        // Actualizar contadores de distribución
        const requirements = this.state.generatedRequirements;
        const functionalCount = requirements.filter(req => req.is_functional == 1).length;
        const nonFunctionalCount = requirements.length - functionalCount;
        const ambiguousCount = requirements.filter(req => req.is_ambiguous == 1).length;
        const nonAmbiguousCount = requirements.length - ambiguousCount;

        document.getElementById('summary-count').textContent = `${requirements.length} requisitos generados`;
        document.getElementById('functional-count').textContent = functionalCount;
        document.getElementById('non-functional-count').textContent = nonFunctionalCount;
        document.getElementById('ambiguous-count').textContent = ambiguousCount;
        document.getElementById('non-ambiguous-count').textContent = nonAmbiguousCount;
    }

    /**
     * Guarda los requisitos generados
     */
    async saveRequirements() {
        if (!this.validateCurrentStep()) {
            return;
        }

        try {
            // Obtener datos actualizados de la tabla
            let requirements = [];
            if (this.tableManager) {
                requirements = this.tableManager.getData();
            } else {
                requirements = this.state.generatedRequirements;
            }

            if (requirements.length === 0) {
                this.showErrorMessage(this.translations.get('validation.no_requirements') || 'No hay requisitos para guardar');
                return;
            }

            // Mostrar carga
            Swal.fire({
                title: this.translations.get('saving.title') || 'Guardando requisitos',
                text: this.translations.get('saving.message') || 'Por favor espere...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            // Preparar datos para enviar
            const requestData = {
                requirements: requirements
            };

            const response = await fetch(this.endpoints.save, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    encryptedData: CryptoModule.encrypt(requestData)
                })
            });

            const result = await response.json();
            const decryptedResponse = CryptoModule.decrypt(result.data);

            if (!decryptedResponse.success) {
                throw new Error(decryptedResponse.message || (this.translations.get('errors.save_failed') || 'Error al guardar requisitos'));
            }

            // Mostrar mensaje de éxito
            Swal.fire({
                title: this.translations.get('saving.success_title') || 'Requisitos guardados',
                text: this.translations.get('saving.success_message') || 'Los requisitos han sido guardados exitosamente',
                icon: 'success',
                confirmButtonText: this.translations.get('saving.success_button') || 'Aceptar'
            }).then(() => {
                Swal.close(); // Cerrar el modal del generador

                // Refrescar la página o actualizar la tabla principal de requisitos
                if (typeof createClassificationGame !== 'undefined' && createClassificationGame.loadRequirements) {
                    createClassificationGame.loadRequirements();
                } else {
                    // Alternativa si no podemos recargar dinámicamente
                    window.location.reload();
                }
            });

        } catch (error) {
            console.error('Error al guardar requisitos:', error);
            Swal.fire({
                title: this.translations.get('errors.title') || 'Error',
                text: (this.translations.get('errors.save_failed') || 'Error al guardar requisitos') + ': ' + error.message,
                icon: 'error',
                confirmButtonText: this.translations.get('errors.button') || 'Aceptar'
            });
        }
    }

    /**
     * Muestra un mensaje de error
     * @param {string} message - Mensaje de error
     */
    showErrorMessage(message) {
        Swal.fire({
            title: this.translations.get('errors.title') || 'Error',
            text: message,
            icon: 'error',
            confirmButtonText: this.translations.get('errors.button') || 'Aceptar'
        });
    }
}

// Inicialización de la instancia global
let requirementsGenerator;
document.addEventListener('DOMContentLoaded', () => {
    requirementsGenerator = new RequirementsGenerator();
});