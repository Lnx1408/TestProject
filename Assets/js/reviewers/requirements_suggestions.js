class RequirementSuggestion {
  constructor() {
    this.config = {
      selectors: {
        mainContainer: "#main",
      },
      endpoints: {
        get_requirements_suggestions: `${base_url}/Reviewers/get_requirements_suggestions`,
        get_original_requirement: `${base_url}/Reviewers/get_original_requirement`,
        update_original_requirement: `${base_url}/Reviewers/update_original_requirement`,
      },
      params: {
        // Parámetro que necesitamos enviar
        gameCode: null,
        requisito: null,
      },
    };

    this.translations = {
      get: (key) =>
        LanguageManager.getTranslation(`create_classification.${key}`),
    };

    // Referencias a elementos del DOM
    this.elements = {
      selectedTable: null, // Instancia DataTable de la tabla principal
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
          data: "es_funcional",
          responsivePriority: 3,
          //title: this.translations.get('main_table.columns.type'),
          title: `<span data-i18n="create_classification.main_table.columns.type">Tipo</span>`,
          render: (data) => this.renderRequirementType(data),
        },
        {
          data: "es_ambiguo",
          responsivePriority: 3,
          //title: this.translations.get('main_table.columns.is_ambiguous'),
          title: `<span data-i18n="create_classification.main_table.columns.is_ambiguous">Es Ambiguo</span>`,
          render: (data) => this.renderAmbiguousState(data),
        },
        {
          data: "revisor",
          title: `<span>Estudiante Revisor</span>`,
          className: "wrap-cell",
          responsivePriority: 2,
          render: function (data, type, row) {
            return `<div class="ambiguous-state">${data}</div>`;
          },
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

    this.initializeParams();
    this.initializeTables();
    this.obtenerRequisitoOriginal();
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
    const requisito = this.getUrlParameter("Requisito");
    if (!gameCode) {
      console.error("No se encontró el id requisito en la URL");
      return false;
    }
    this.config.params.gameCode = gameCode;
    this.config.params.requisito = requisito;
    return true;
  }

  initializeTables() {
    this.elements.selectedTable = $("#existingRequirementsTable").DataTable({
      ajax: {
        url: this.config.endpoints.get_requirements_suggestions,
        type: "POST",
        //dataSrc: 'data'
        data: (d) => {
          const requestData = {
            ...d,
            gamecode: this.config.params.gameCode,
            requisito: this.config.params.requisito,
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
    });
  }

  async obtenerRequisitoOriginal() {
    try {
      const response = await fetch(
        this.config.endpoints.get_original_requirement,
        {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
          },
          body: JSON.stringify({
            encryptedData: CryptoModule.encrypt({
              requisito: this.config.params.requisito,
            }),
          }),
        }
      );

      const data = await response.json();
      const decryptedData = CryptoModule.decrypt(data.data);

      console.log(this.config.params.requisito + ' | ' + decryptedData);

      if (!decryptedData.status) {
        throw new Error(decryptedData.message);
      }

      if (
        decryptedData &&
        decryptedData.data &&
        decryptedData.data.length > 0
      ) {
        const description = decryptedData.data[0].descripcion;
        document.getElementById("requisito-original").innerHTML = description;
      } else {
        console.warn("No description found in decryptedData.");
        // Optionally, set a default message or handle the empty state
        document.getElementById("requisito-original").innerHTML =
          "No se encontró la descripción del requisito.";
      }
      return decryptedData.data;
    } catch (error) {
      console.error("Error loading initial data:", error);
      this.showError(this.translations.get("errors.loading"));
    }
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
    <button title="Actualizar revisión" onclick="requirementSuggestion.updateRequerimentModal('${row.id_requisito}','${row.descripcion}', '${row.es_funcional}', '${row.es_ambiguo}' ); event.stopPropagation();" 
                            class="btn-action">
                        <i class='bx bx-edit'></i>
                    </button>
                    <button title="Dar Feedback" onclick="requirementSuggestion.viewDetails('${row.id_requisito}')" 
                            class="btn-action">
                        <i class='bx bx-message'></i>
                    </button>
                </div>`;
  }
  viewDetails(id_requisito) {
    // Redirigir a la página de detalles
    window.location.href = `${base_url}/Reviewers/requirements_suggestions?gamecode=${encodeURIComponent(
      this.config.params.gameCode
    )}&Requisito=${encodeURIComponent(id_requisito)}`;
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


  updateRequerimentModal(id_requisito, requisito, es_funcional, es_ambiguo) {

    Swal.fire({
      title: "Actualizar requisito",
      html: `¿Desea modificar el requisito original?`,
      icon: "question",
      showCancelButton: true,
      confirmButtonColor: "#1976D2",
      cancelButtonColor: "#D32F2F",
      confirmButtonText: "Sí, cambiar",
      cancelButtonText: "No, cancelar",
      customClass: {
        container: "analytics-type-modal",
        popup: "analytics-modal-popup",
      },
    }).then((result) => {
      if (result.isConfirmed) {
        console.log(`Actualizando requisito: ${requisito}`);
        this.UpdateRequeriment(
          id_requisito,
          requisito,
          es_funcional,
          es_ambiguo
        );
        location.reload();

      }
    });
  }

  async UpdateRequeriment(id_requisito, requisito, es_funcional, es_ambiguo) {
    try {
      const encryptedPayload = CryptoModule.encrypt({
        id_requisito: id_requisito,
        requisito: requisito,
        es_funcional: es_funcional,
        es_ambiguo: es_ambiguo,
      });

      const response = await fetch(
        this.config.endpoints.update_original_requirement,
        {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
          },
          body: JSON.stringify({
            encryptedData: encryptedPayload, // Enviar el payload ya cifrado
          }),
        }
      );

      if (!response.ok) {
        throw new Error(
          `Error de comunicación con el servidor: ${response.status}`
        );
      }

      const resultEncrypt = await response.json();

      if (!resultEncrypt.data) {
        throw new Error(
          "La respuesta del servidor no tiene el formato esperado."
        );
      }

      // --- PROCESAMIENTO DE LA RESPUESTA ---
      const decryptedString = CryptoModule.decrypt(resultEncrypt.data);

      // --- PASO DE DEPURACIÓN CRUCIAL ---
      console.log("Datos descifrados:", decryptedString);
      // el problema está en la función `encryptResponse` de tu PHP.
    } catch (error) {
      console.error("Error en UpdateReviewer:", error.message);
      // Muestra el error al usuario
      // this.showErrorMessage('Error: ' + error.message);
    }
  }
}

// Inicialización cuando el DOM está listo
document.addEventListener("DOMContentLoaded", async () => {
  window.requirementSuggestion = new RequirementSuggestion();
});
