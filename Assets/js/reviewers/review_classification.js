class ReviewClassification {
  constructor() {
    this.config = {
      selectors: {
        mainContainer: "#main",
        headerToggle: "#header-toggle",
      },
      endpoints: {
        getRequirement: `${base_url}/Reviewers/get_requisitos_review`,
      },
      params: {
        id: null, // Parámetro que necesitamos enviar
        gameCode: null,
      },
      minRequirements: 5,
    };

    this.debug = {
      columnStates: [], // Variable para almacenar las columnas y sus títulos
    };

    // Estado de la aplicación
    this.state = {
      selectedRequirements: new Map(), // Requisitos ya agregados a la tabla principal
      temporarySelections: new Set(), // Para mantener las selecciones en el modal
      requirementCount: 0,
    };

    this.translations = {
      get: (key) =>
        LanguageManager.getTranslation(`create_classification.${key}`),
    };

    // Referencias a elementos del DOM
    this.elements = {
      selectedTable: null, // Instancia DataTable de la tabla principal
      selectModal: null, // Instancia DataTable del modal de selección
    };

    this.columnDefs = {
      columns: [
        {
          data: null,
          title: "*",
          render: (data) => "",
        },
        {
          data: "descripcion",
          //title: this.translations.get('main_table.columns.description'),
          title: `<span data-i18n="create_classification.main_table.columns.description">Descripción</span>`,
          className: "wrap-cell",
          responsivePriority: 1,
          render: function (data, type, row) {
            if (type === "display") {
              return `<div class="requirement-description">${data}</div>`;
            }
            return data;
          },
        },
        {
          data: "is_functional",
          responsivePriority: 3,
          //title: this.translations.get('main_table.columns.type'),
          title: `<span data-i18n="create_classification.main_table.columns.type">Tipo</span>`,
          render: (data) => this.renderRequirementType(data),
        },
        {
          data: "is_ambiguous",
          responsivePriority: 2,
          //title: this.translations.get('main_table.columns.is_ambiguous'),
          title: `<span data-i18n="create_classification.main_table.columns.is_ambiguous">Es Ambiguo</span>`,
          render: (data) => this.renderAmbiguousState(data),
        },
        {
          data: "retroalimentacion",
          responsivePriority: 3,
          title: `<span data-i18n="create_classification.main_table.columns.feedback">Retroalimentación</span>`,
        },
        {
          data: null,
          responsivePriority: 1,
          title: `<span data-i18n="create_classification.main_table.columns.actions">Acciones</span>`,
          className: "dt-center",
          render: (data, type, row) => this.renderActions(row),
        },
      ],
    };

    this.importConfig = {
      requiredColumns: [
        "descripcion",
        "es_ambiguo",
        "es_funcional",
        "retroalimentacion",
      ],
      maxFileSize: 1024 * 1024, // 1MB
      allowedExtensions: ["csv"],
    };

    this.aiGenerator = null;

    this.initializeTables();
    this.initializeParams();
    this.initializeResponsiveHandling();
    //this.initializeSelectTable();
  }

  initializeResponsiveHandling() {
    // Obtener referencias a los elementos
    const mainContainer = document.querySelector(
      this.config.selectors.mainContainer
    );

    if (!mainContainer || !this.elements.selectedTable) return;

    // Crear ResizeObserver para el contenedor principal
    const resizeObserver = new ResizeObserver((entries) => {
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
  getUrlParameter(name) {
    const params = new URLSearchParams(window.location.search);
    return params.get(name);
  }

  // Función para inicializar parámetros
  initializeParams() {
    const gameCode = this.getUrlParameter("gamecode");
    if (!gameCode) {
      console.error("No se encontró el código de juego en la URL");
      return false;
    }
    this.config.params.gameCode = gameCode;
    return true;
  }

  initializeTables() {
    this.elements.selectedTable = $("#existingRequirementsTable").DataTable({
      ajax: {
        url: this.config.endpoints.getRequirement,
        type: "POST",
        //dataSrc: 'data'
        data: (d) => {
          const requestData = {
            ...d,
            gamecode: this.config.params.gameCode,
          };
          return JSON.stringify({
            encryptedData: CryptoModule.encrypt(requestData),
          });
        },
        dataSrc: (response) => {
          try {
            if (!response.data) {
                
                console.error("Invalid response data:", response);
              throw new Error(this.translations.get("errors.invalid_data"));
            }

            const decryptedData = CryptoModule.decrypt(response.data);
            
            console.log(this.config.params.gameCode);
            console.log("Decrypted Data:", decryptedData);

            if (!decryptedData) {
              throw new Error(
                this.translations.get("errors.messages.error_loading")
              );
            }

            if (!decryptedData.status) {
              throw new Error(
                decryptedData.message ||
                  this.translations.get("errors.server_error")
              );
            }

            return decryptedData.analytics || [];
          } catch (error) {
            console.error("Error processing data:", error);
            this.showErrorMessage(this.translations.get("errors.general"));
            return [];
          }
        },
        error: (xhr, error, thrown) => {
          console.error("Ajax error:", error);
          this.showErrorMessage(
            this.translations.get("errors.connection_error")
          );
        },
      },
      responsive: true,
      columns: this.columnDefs.columns,
      language: this.getDataTableLanguage(),
      responsive: {
        details: {
          type: "column",
          target: "tr",
          renderer: (api, rowIdx, columns) => {
            const data = api.row(rowIdx).data();
            let html = '<div class="expanded-details">';

            const hiddenColumns = columns.filter((col) => col.hidden);
            if (hiddenColumns.length > 0) {
              html += `
                                <div class="hidden-columns-section">
                                    <div class="hidden-columns-content">
                                        <ul class="dtr-details">
                                        ${hiddenColumns
                                          .map((col) => {
                                            // Obtener el título de la configuración de columnas si está disponible
                                            const columnDef =
                                              this.columnDefs.columns[
                                                col.columnIndex
                                              ];
                                            const titleCol =
                                              api.settings()[0].aoColumns[
                                                col.columnIndex
                                              ].sTitle;
                                            let title;
                                            if (
                                              !titleCol ||
                                              titleCol === "undefined"
                                            ) {
                                              // Si titleCol es inválido, usar directamente columnDef.title
                                              title = columnDef.title;
                                            } else if (
                                              columnDef &&
                                              columnDef.title
                                            ) {
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
                                          })
                                          .join("")}
                                        </ul>
                                    </div>
                                </div>
                            `;
            }
            html += "</div>";
            return html;
          },
        },
      },
      columnDefs: [
        {
          className: "dtr-control",
          orderable: false,
          targets: 0,
          width: "2.5rem",
        },
        {
          targets: [1],
          width: "50%",
        },
      ],
      processing: true,
      serverSide: false,
      initComplete: () => {
        // Verificar títulos después de la inicialización
        setTimeout(() => {
          this.elements.selectedTable
            .columns()
            .header()
            .each((header, index) => {
              const title = $(header).text();
              this.elements.selectedTable.settings()[0].aoColumns[
                index
              ].sTitle = title;
              if (!title || title === "undefined") {
                const originalTitle =
                  this.columnDefs.columns[index].title || `T ${index + 1}`;
                $(header).text(originalTitle);
              }
            });
        }, 350);
      },
    });
  }

  renderRequirementType(isFunctional) {
    const typeClass = isFunctional ? "functional" : "non-functional";
    const typeText = isFunctional ? "Funcional" : "No Funcional";
    return `<span class="requirement-type ${typeClass}">${typeText}</span>`;
  }

  renderAmbiguousState(isAmbiguous) {
    const stateClass = isAmbiguous ? "yes" : "no";
    const icon = isAmbiguous ? "bx-check" : "bx-x";
    return `<span class="ambiguous-state ${stateClass}">
                    <i class='bx ${icon}'></i>
                    ${isAmbiguous ? "Sí" : "No"}
                </span>`;
  }

  renderActions(row) {
    return `<div class="table-actions">
                    <button onclick="createClassificationGame.editRequirement('${row.id}')" 
                            class="btn-action">
                        <i class='bx bx-edit'></i>
                    </button>
                    <button onclick="createClassificationGame.removeRequirement('${row.id}')" 
                            class="btn-action">
                        <i class='bx bx-trash'></i>
                    </button>
                </div>`;
  }

  createFormModalContent() {
    return `
            <form id="createRequirementForm" class="requirement-form">
                <div class="form-group">
                    <label>${this.translations.get(
                      "create_modal.form.description"
                    )}</label>
                    <textarea id="reqDescription" class="form-control" rows="3"></textarea>
                </div>
                <div class="form-group">
                    <label class="checkbox-container">
                        ${this.translations.get(
                          "create_modal.form.is_ambiguous"
                        )}
                        <input type="checkbox" id="reqIsAmbiguous">
                    </label>
                </div>
                <div class="form-group">
                    <label class="checkbox-container">
                        ${this.translations.get(
                          "create_modal.form.is_functional"
                        )}
                        <input type="checkbox" id="reqIsFunctional">
                    </label>
                </div>
                <div class="form-group">
                    <label>${this.translations.get(
                      "create_modal.form.feedback"
                    )}</label>
                    <textarea id="reqFeedback" class="form-control" rows="3"></textarea>
                </div>
            </form>
        `;
  }

  createEditFormModalContent(requirement) {
    return `
            <form id="editRequirementForm" class="requirement-form">
                <input type="hidden" id="reqId" value="${requirement.id}">
                <div class="form-group">
                    <label>${
                      this.translations.get("create_modal.form.description") ||
                      "Descripción del Requisito"
                    }</label>
                    <textarea id="reqDescription" class="form-control" rows="3">${
                      requirement.description
                    }</textarea>
                </div>
                <div class="form-group">
                    <label class="checkbox-container">
                        ${
                          this.translations.get(
                            "create_modal.form.is_ambiguous"
                          ) || "¿Es ambiguo?"
                        }
                        <input type="checkbox" id="reqIsAmbiguous" ${
                          requirement.is_ambiguous ? "checked" : ""
                        }>
                    </label>
                </div>
                <div class="form-group">
                    <label class="checkbox-container">
                        ${
                          this.translations.get(
                            "create_modal.form.is_functional"
                          ) || "¿Es funcional?"
                        }
                        <input type="checkbox" id="reqIsFunctional" ${
                          requirement.is_functional ? "checked" : ""
                        }>
                    </label>
                </div>
                <div class="form-group">
                    <label>${
                      this.translations.get("create_modal.form.feedback") ||
                      "Retroalimentación"
                    }</label>
                    <textarea id="reqFeedback" class="form-control" rows="3">${
                      requirement.feedback
                    }</textarea>
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
    console.log("Selecciones temporales:", this.state.temporarySelections);
  }

  async initializeSelectTable() {
    const table = $("#existingRequirementsTabl").DataTable({
      ajax: {
        url: this.config.endpoints.getRequirement,
        type: "GET",
        //dataSrc: 'data'
        dataSrc: (response) => {
          try {
            if (!response.data) {
              throw new Error(this.translations.get("errors.invalid_data"));
            }

            const decryptedData = CryptoModule.decrypt(response.data);

            if (!decryptedData) {
              throw new Error(
                this.translations.get("errors.messages.error_loading")
              );
            }

            if (!decryptedData.success) {
              throw new Error(
                decryptedData.message ||
                  this.translations.get("errors.server_error")
              );
            }

            return decryptedData.data || [];
          } catch (error) {
            console.error("Error processing data:", error);
            this.showErrorMessage(this.translations.get("errors.general"));
            return [];
          }
        },
        error: (xhr, error, thrown) => {
          console.error("Ajax error:", error);
          this.showErrorMessage(
            this.translations.get("errors.connection_error")
          );
        },
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
                                    onchange="createClassificationGame.handleCheckboxChange(this, ${
                                      data.id
                                    })"
                                    ${isDisabled ? "checked disabled" : ""}>
                            </div>
                        `;
          },
          orderable: false,
        },
        {
          data: "description",
          className: "wrap-cell", // Nueva clase para permitir wrap
          responsivePriority: 1, // Máxima prioridad - nunca se ocultará
          render: function (data, type, row) {
            if (type === "display") {
              return `<div class="requirement-description">${data}</div>`;
            }
            return data;
          },
        },
        {
          data: "is_ambiguous",
          responsivePriority: 2,
          render: (data) => this.renderAmbiguousState(data),
        },
        {
          data: "is_functional",
          responsivePriority: 3,
          render: (data) => this.renderRequirementType(data),
        },
      ],
      columnDefs: [
        {
          targets: [1], // Índice de la columna descripción
          width: "50%", // Dar un ancho fijo a la columna
        },
      ],
      language: this.getDataTableLanguage(),
      select: {
        style: "multi",
        selector: "td:first-child input:not(:disabled)",
      },
      rowCallback: (row, data) => {
        if (this.state.selectedRequirements.has(data.id)) {
          $(row).addClass("selected-requirement");
        }
      },
    });

    this.elements.selectModal = table;

    // Agregar manejador de errores global para la tabla
    table.on("error.dt", (e, settings, techNote, message) => {
      console.error("DataTables error:", message);
      this.showErrorMessage(this.translations.get("errors.table_error"));
    });
  }

  validateAndGetFormData(isEditing = false) {
    const description = document.getElementById("reqDescription").value.trim();
    const feedback = document.getElementById("reqFeedback").value.trim();
    const isAmbiguous = document.getElementById("reqIsAmbiguous").checked;
    const isFunctional = document.getElementById("reqIsFunctional").checked;

    if (!description) {
      Swal.showValidationMessage(
        this.translations.get("create_modal.validation.description_required")
      );
      return false;
    }

    if (!feedback) {
      Swal.showValidationMessage(
        this.translations.get("create_modal.validation.feedback_required")
      );
      return false;
    }

    const data = {
      description,
      feedback,
      isAmbiguous,
      isFunctional,
    };

    // Si estamos editando, añadimos el ID
    if (isEditing && document.getElementById("reqId")) {
      data.id = document.getElementById("reqId").value;
    }

    return data;
  }

  addSelectedRequirements() {
    const allData = this.elements.selectModal.data().toArray();
    // Convertir los datos de DataTables a un array ALTERNATIVA 2
    //const allData = Array.from(this.elements.selectModal.data());
    // Procesar cada selección temporal
    this.state.temporarySelections.forEach((reqId) => {
      const requirement = allData.find((row) => row.id === reqId);
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

  editRequirement(requirementId) {
    // Convertir a número para comparaciones
    const reqId = parseInt(requirementId);
    // Obtener el requisito del Map de requisitos seleccionados
    const requirement = this.state.selectedRequirements.get(reqId);

    if (!requirement) {
      this.showErrorMessage(this.translations.get("messages.error_loading"));
      return;
    }

    // Mostrar modal de edición con los datos del requisito
    Swal.fire({
      title: this.translations.get("edit_modal.title") || "Editar Requisito",
      html: this.createEditFormModalContent(requirement),
      width: "600px",
      showCancelButton: true,
      confirmButtonText:
        this.translations.get("edit_modal.buttons.save") || "Guardar Cambios",
      cancelButtonText:
        this.translations.get("edit_modal.buttons.cancel") || "Cancelar",
      customClass: {
        container: "game-type-modal",
        popup: "game-levels-popup",
      },
      preConfirm: () => this.validateAndGetFormData(true),
    }).then((result) => {
      if (result.isConfirmed) {
        this.updateRequirement(reqId, result.value);
      }
    });
  }

  async updateRequirement(reqId, data) {
    try {
      const response = await fetch(this.config.endpoints.updateRequirement, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          encryptedData: CryptoModule.encrypt(data),
        }),
      });

      const resultEncrypt = await response.json();
      const result = CryptoModule.decrypt(resultEncrypt.data);

      if (
        result.success &&
        Array.isArray(result.requirement) &&
        result.requirement.length > 0
      ) {
        // Actualizar en el Map
        this.state.selectedRequirements.set(reqId, result.requirement[0]);

        // Actualizar en la tabla
        this.elements.selectedTable.rows().every((rowIdx) => {
          const rowData = this.elements.selectedTable.row(rowIdx).data();
          if (rowData && rowData.id === reqId) {
            this.elements.selectedTable
              .row(rowIdx)
              .data(result.requirement[0])
              .draw();
            return false; // Salir del bucle
          }
          return true;
        });

        this.showSuccessMessage(
          this.translations.get("messages.requirement_updated") ||
            "Requisito actualizado exitosamente"
        );
      } else {
        throw new Error(result.message || "Un error desconocido ocurrió.");
      }
    } catch (error) {
      console.error("Error:", error.message);
      this.showErrorMessage(
        this.translations.get("messages.error_message") ||
          "Error al actualizar el requisito"
      );
    }
  }

  async showSuccessModal(gameCode) {
    let shouldRedirect = false;

    // Función para mostrar el modal principal
    const showMainModal = async () => {
      const result = await Swal.fire({
        icon: "success",
        title: this.translations.get("messages.game_created"),
        html: `
                    <div class="game-code-container">
                        <p>${this.translations.get("messages.game_code")}:</p>
                        <div class="code-display">
                            <span class="game-code">${gameCode}</span>
                            <i class='bx bx-info-circle info-icon' 
                               id="gameCodeInfo"
                               style="cursor: pointer; margin-left: 8px; color: #666;">
                            </i>
                        </div>
                    </div>
                `,
        confirmButtonColor: "#1976D2",
        confirmButtonText: this.translations.get("buttons.continue"),
        allowOutsideClick: false,
        customClass: {
          container: "game-type-modal",
          popup: "game-levels-popup",
        },
        didOpen: (modalElement) => {
          const infoIcon = modalElement.querySelector("#gameCodeInfo");
          infoIcon.addEventListener("click", async () => {
            // Cerrar temporalmente el modal principal
            Swal.close();

            // Mostrar el modal de información
            await Swal.fire({
              title: this.translations.get("create_modal.game_code_info.title"),
              html: `
                                <div class="game-code-info">
                                    <p>${this.translations.get(
                                      "create_modal.game_code_info.description"
                                    )}</p>
                                    <ul>
                                        <li>${this.translations.get(
                                          "create_modal.game_code_info.point1"
                                        )}</li>
                                        <li>${this.translations.get(
                                          "create_modal.game_code_info.point2"
                                        )}</li>
                                        <li>${this.translations.get(
                                          "create_modal.game_code_info.point3"
                                        )}</li>
                                    </ul>
                                </div>
                            `,
              icon: "info",
              confirmButtonColor: "#1976D2",
              customClass: {
                container: "game-type-modal",
                popup: "game-levels-popup",
              },
            });

            // Volver a mostrar el modal principal
            return showMainModal();
          });
        },
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

  getDataTableLanguage() {
    const currentLanguage = LanguageManager.currentLang;
    // Definir el archivo según el idioma
    const languageFile = currentLanguage === "es" ? "es-ES.json" : "en-GB.json";

    return {
      url: `${base_url}/Assets/js/plugins/datatables/${languageFile}`,
      paginate: {
        first: "«",
        last: "»",
        next: "›",
        previous: "‹",
      },
    };
  }

  showSuccessMessage(message) {
    return Swal.fire({
      icon: "success",
      title: message,
      confirmButtonColor: "#1976D2",
      customClass: {
        container: "game-type-modal",
        popup: "game-levels-popup",
      },
    });
  }

  showErrorMessage(message) {
    return Swal.fire({
      icon: "error",
      title: this.translations.get("messages.error"),
      text: message,
      confirmButtonColor: "#1976D2",
      customClass: {
        container: "game-type-modal",
        popup: "game-levels-popup",
      },
    });
  }
}

// Inicialización cuando el DOM está listo
document.addEventListener("DOMContentLoaded", async () => {
  window.ReviewClassification = new ReviewClassification();
});
