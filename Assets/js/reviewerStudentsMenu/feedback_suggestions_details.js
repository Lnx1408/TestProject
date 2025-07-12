class RequirementSuggestion {
  constructor() {
    this.config = {
      selectors: {
        mainContainer: "#main",
      },
      endpoints: {
        feedback_suggestions_details: `${base_url}/ReviewerStudentsMenu/get_feedback_suggestions_details`,
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
          data: "feedback_description",
          responsivePriority: 1,
          title: `<span>Feedback del Docente</span>`,
          className: "dt-left",
        },
        {
          data: "docente_revisor",
          title: `<span>Docente Revisor</span>`,
          className: "wrap-cell",
          responsivePriority: 2,
          render: function (data, type, row) {
            return `<div class="ambiguous-state">${data}</div>`;
          },
        },
        {
          data: "descripcion",
          //title: this.translations.get('main_table.columns.description'),
          title: `<span>Mi sugerencia</span>`,
          responsivePriority: 4,
        },
      ],
    };

    this.initializeParams();
    this.initializeTables();
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
        url: this.config.endpoints.feedback_suggestions_details,
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

            console.log(decryptedData);
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
          width: "75%",
        },
      ],
      processing: true,
      serverSide: false,
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
    }).then((result) => {
      if (result.isConfirmed) {
        location.reload(); // Recargar la página al confirmar
      }
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
    }).then((result) => {
      if (result.isConfirmed) {
        location.reload(); // Recargar la página al confirmar
      }
    });
  }
}

// Inicialización cuando el DOM está listo
document.addEventListener("DOMContentLoaded", async () => {
  window.requirementSuggestion = new RequirementSuggestion();
});
