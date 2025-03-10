/**
 * Configuración de Tabulator.js para la tabla de requisitos generados por IA
 */
class RequirementsTableManager {
    constructor(containerId, options = {}) {
        this.containerId = containerId;
        this.options = {
            editable: true,
            selectable: true,
            ...options
        };
        this.table = null;
        this.onDeleteCallback = null;
        this.onEditCallback = null;
        this.initialize();
    }

    /**
     * Inicializa la tabla de Tabulator
     */
    initialize() {
        const container = document.getElementById(this.containerId);
        if (!container) return;
        
        // Asegurarse de que el contenedor tenga ancho completo
        container.style.width = '100%';

        this.table = new Tabulator(`#${this.containerId}`, {
            layout: "fitColumns",
            responsiveLayout: "collapse",
            layoutColumnsOnNewData: true, // Recalcular el layout al recibir nuevos datos
            height: "auto",          // Altura automática basada en contenido
            pagination: "local",
            paginationSize: 10,
            paginationSizeSelector: [5, 10, 15, 20],
            movableColumns: true,
            placeholder: "No hay requisitos disponibles",
            columns: this.getColumnsDefinition(),
            selectable: this.options.selectable,
            tooltips: true,
            tooltipsHeader: true,
            headerSortElement: '<i class="ri-arrow-up-down-line"></i>',
            locale: true,
            resizableColumns: false,  // Permitir que el usuario ajuste el tamaño de las columnas
            langs: {
                "es-es": {
                    "pagination": {
                        "first": "Primero",
                        "first_title": "Primera Página",
                        "last": "Último",
                        "last_title": "Última Página",
                        "prev": "Anterior",
                        "prev_title": "Página Anterior",
                        "next": "Siguiente",
                        "next_title": "Página Siguiente",
                    },
                    "headerFilters": {
                        "default": "filtrar columna...",
                    },
                    "data": {
                        "loading": "Cargando",
                        "error": "Error",
                    },
                }
            }
        });
         // Observe Resize - recalcular cuando cambie el tamaño
         this.setupResizeObserver();
    }

    /**
     * Configurar un Observer para detectar cambios de tamaño
     */
    setupResizeObserver() {
        if (typeof ResizeObserver === 'undefined') return;
        
        const container = document.getElementById(this.containerId);
        if (!container) return;
        
        const observer = new ResizeObserver(() => {
            if (this.table) {
                setTimeout(() => {
                    this.table.redraw(true);
                }, 100);
            }
        });
        
        observer.observe(container);
    }

    /**
     * Define las columnas de la tabla
     */
    getColumnsDefinition() {
        return [
            {
                formatter: "responsiveCollapse", 
                width: 30, 
                minWidth: 30, 
                hozAlign: "center", 
                resizable: false, 
                headerSort: false,
                responsive: 0,
            },
            {
                title: "Descripción",
                field: "description",
                widthGrow: 3,        // Mayor prioridad de crecimiento
                minWidth: 250,       // Ancho mínimo para mantener legibilidad
                headerFilter: "input",
                editor: this.options.editable ? "textarea" : false,
                responsive: 0,
                formatter: function(cell) {
                    return cell.getValue();
                }
            },
            {
                title: "Tipo",
                field: "is_functional",
                widthGrow: 1,
                minWidth: 120,
                headerFilter: "list",
                headerFilterParams: {
                    values: {"": "Todos", "1": "Funcional", "0": "No Funcional"}
                },
                formatter: function(cell) {
                    const value = cell.getValue();
                    const isFunctional = value === 1 || value === "1" || value === true;
                    const chipClass = isFunctional ? "chip-functional" : "chip-non-functional";
                    const label = isFunctional ? "Funcional" : "No Funcional";
                    return `<span class="chip ${chipClass}">${label}</span>`;
                },
                
                editor: this.options.editable ? "list" : false,
                editorParams: {
                    values: {"1": "Funcional", "0": "No Funcional"},
                    listOnEmpty: true
                }, 
                cellEdited: function(cell) {
                    // Si el valor está vacío, restaurar el valor original
                    const value = cell.getValue();
                    if (value === "" || value === undefined || value === null) {
                        cell.restoreOldValue();
                    }
                },
                responsive: 1
            },
            {
                title: "Ambigüedad",
                field: "is_ambiguous",
                widthGrow: 1,
                minWidth: 120,
                headerFilter: "list",
                headerFilterParams: {
                    values: {"": "Todos", "1": "Ambiguo", "0": "No Ambiguo"}
                },
                formatter: function(cell) {
                    const value = cell.getValue();
                    const isAmbiguous = value === 1 || value === "1" || value === true;
                    const chipClass = isAmbiguous ? "chip-ambiguous" : "chip-non-ambiguous";
                    const label = isAmbiguous ? "Ambiguo" : "No Ambiguo";
                    return `<span class="chip ${chipClass}">${label}</span>`;
                },
                editor: this.options.editable ? "list" : false,
                editorParams: {
                    values: {"1": "Ambiguo", "0": "No Ambiguo"},
                    listOnEmpty: true
                },
                cellEdited: function(cell) {
                    // Si el valor está vacío, restaurar el valor original
                    const value = cell.getValue();
                    if (value === "" || value === undefined || value === null) {
                        cell.restoreOldValue();
                    }
                },
                responsive: 2
            },
            {
                title: "Retroalimentación",
                field: "feedback",
                widthGrow: 2,
                minWidth: 200,
                headerFilter: "input",
                editor: this.options.editable ? "textarea" : false,
                //visible: false,
                responsive: 3
            },
            {
                title: "Acciones",
                formatter: (cell) => {
                    const rowData = cell.getRow().getData();
                    return `<div class="requirements-actions">
                                <button class="action-button edit-button" data-req-id="${rowData.id}" title="Editar">
                                    <i class="ri-edit-line"></i>
                                </button>
                                <button class="action-button delete-button" data-req-id="${rowData.id}" title="Eliminar">
                                    <i class="ri-delete-bin-line"></i>
                                </button>
                            </div>`;
                },
                widthGrow: 0.5,
                minWidth: 80,
                headerSort: false,
                hozAlign: "center",
                responsive: 0,
                cellClick: (e, cell) => {
                    const target = e.target.closest('button');
                    if (!target) return;
                    
                    const reqId = target.getAttribute('data-req-id');
                    const rowData = cell.getRow().getData();
                    
                    if (target.classList.contains('edit-button')) {
                        if (this.onEditCallback) {
                            this.onEditCallback(rowData);
                        }
                    } else if (target.classList.contains('delete-button')) {
                        if (this.onDeleteCallback) {
                            this.onDeleteCallback(rowData);
                        }
                    }
                }
            }
        ];
    }

     /**
     * Ajusta la tabla al contenedor
     * Útil para llamar cuando cambia el tamaño del contenedor
     */
     adjustTableSize() {
        if (!this.table) return;
        this.table.redraw(true);
    }


    /**
     * Establece los datos de la tabla
     * @param {Array} data - Array de requisitos
     */
    setData(data) {
        if (!this.table) return;
        this.table.setData(data);

        // Ajustar la tabla después de establecer los datos
        setTimeout(() => {
            this.adjustTableSize();
        }, 200);
    }

    /**
     * Obtiene todos los datos de la tabla
     * @returns {Array} - Array de requisitos
     */
    getData() {
        if (!this.table) return [];
        return this.table.getData();
    }

    /**
     * Obtiene los datos de los requisitos seleccionados
     * @returns {Array} - Array de requisitos seleccionados
     */
    getSelectedData() {
        if (!this.table) return [];
        return this.table.getSelectedData();
    }

    /**
     * Añade un nuevo requisito a la tabla
     * @param {Object} requirementData - Datos del requisito
     */
    addRow(requirementData) {
        if (!this.table) return;
        this.table.addRow(requirementData);
    }

    /**
     * Elimina un requisito de la tabla
     * @param {String|Number} id - ID del requisito a eliminar
     */
    deleteRow(id) {
        if (!this.table) return;
        const row = this.table.getRow(id);
        if (row) {
            row.delete();
        }
    }

    /**
     * Actualiza un requisito en la tabla
     * @param {String|Number} id - ID del requisito a actualizar
     * @param {Object} data - Nuevos datos del requisito
     */
    updateRow(id, data) {
        if (!this.table) return;
        const row = this.table.getRow(id);
        if (row) {
            row.update(data);
        }
    }

    /**
     * Establece el callback para la acción de eliminar
     * @param {Function} callback - Función a ejecutar al eliminar
     */
    setOnDeleteCallback(callback) {
        this.onDeleteCallback = callback;
    }

    /**
     * Establece el callback para la acción de editar
     * @param {Function} callback - Función a ejecutar al editar
     */
    setOnEditCallback(callback) {
        this.onEditCallback = callback;
    }

    /**
     * Limpia todos los datos de la tabla
     */
    clearData() {
        if (!this.table) return;
        this.table.clearData();
    }

    /**
     * Redibuja la tabla
     */
    redraw() {
        if (!this.table) return;
        this.table.redraw(true);
    }
}