const TableModule = {
  // Configuración base
  config: {
    selectors: {
      mainContainer: "#main",
      headerToggle: "#header-toggle",
      tableId: "#tableJugadores",
      // ... otros selectores ...
    },
    endpoint: `${base_url}/Reviewers/get_reviewers_partida_clasificacion`,
    endpointReviewer: `${base_url}/Reviewers/update_reviewer`,
    params: {
      id: null, // Parámetro que necesitamos enviar
      gameCode: null,
    },
  },

  // Estado de la tabla
  state: {
    instance: null,
    isInitialized: false,
  },

  translations: {
    get: (key) =>
      LanguageManager.getTranslation(`game_analytics_classification.${key}`),
  },

  showColumnInfo(key) {
    const info = this.translations.get(`table.info.${key}`);
    if (info) {
      Swal.fire({
        title: info.title,
        text: info.description,
        icon: "info",
        confirmButtonColor: "#1976D2",
        customClass: {
          container: "analytics-type-modal",
          popup: "analytics-modal-popup",
        },
      });
    }
  },

  initializeResponsiveHandling() {
    // Obtener referencias a los elementos
    const mainContainer = document.querySelector(
      this.config.selectors.mainContainer
    );
    const headerToggle = document.querySelector(
      this.config.selectors.headerToggle
    );

    if (!mainContainer || !this.state.instance) return;

    const responsiveColumnIndexes = [1]; // Puedes agregar más índices aquí

    const updateResponsiveColumns = () => {
      if (this.state.instance && this.state.instance.rows().any()) {
        // Verificamos que existan filas
        try {
          this.state.instance.rows().every(
            function (rowIdx) {
              // Actualizamos cada columna definida
              responsiveColumnIndexes.forEach((colIdx) => {
                this.state.instance.cell(rowIdx, colIdx).invalidate();
              });
            }.bind(this)
          );

          // Un solo draw al final
          this.state.instance.draw(false);
        } catch (error) {
          console.warn("Error al actualizar las columnas:", error);
        }
      }
    };

    // Crear ResizeObserver para el contenedor principal
    const resizeObserver = new ResizeObserver((entries) => {
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
    formatName(nombres, apellidos, type = "full") {
      const nombresArray = nombres.split(" ");
      const apellidosArray = apellidos.split(" ");

      if (type === "short") {
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
      const formattedSeconds =
        remainingSeconds < 10 ? `0${remainingSeconds}` : remainingSeconds;

      return `${minutes}min ${formattedSeconds}s`;
    },

    formatDate(dateString) {
      return new Date(dateString).toLocaleDateString("es-ES", {
        day: "2-digit",
        month: "2-digit",
        year: "numeric",
      });
    },

    getStatusInfo(status, status_text) {
      const statusMap = {
        1: "completed",
        0: "process",
        "-1": "pending",
      };
      return {
        text: status_text,
        class: statusMap[status] || "pending",
      };
    },
  },

  viewDetails(nombres, apellidos, id_jugador) {
    Swal.fire({
      title: this.translations.get("modals.view_details.title"),
      html: `${this.translations.get(
        "modals.view_details.message"
      )} <b>${nombres} ${apellidos}</b>`,
      icon: "question",
      showCancelButton: true,
      confirmButtonColor: "#1976D2",
      cancelButtonColor: "#D32F2F",
      confirmButtonText: this.translations.get("modals.view_details.confirm"),
      cancelButtonText: this.translations.get("modals.view_details.cancel"),
      customClass: {
        container: "analytics-type-modal",
        popup: "analytics-modal-popup",
      },
    }).then((result) => {
      if (result.isConfirmed) {
        // Redirigir a la página de detalles
        window.location.href = `${base_url}/Analytics/details_user_clasification?gamecode=${encodeURIComponent(
          this.config.params.gameCode
        )}&Jugador=${encodeURIComponent(id_jugador)}`;
      }
    });
  },
  updateToReviewer(nombres, apellidos, id_jugador, rol_estudiante) {
    let rol_estudianteTexto = "";
    let rol_value = 0;
    if (rol_estudiante === "ESTUDIANTE") {
      rol_estudianteTexto = "ESTUDIANTE REVISOR";
      rol_value = 1; // Asignar el valor correspondiente para revisor
    }else {
      rol_estudianteTexto = "ESTUDIANTE";
    }
    Swal.fire({
      title: "Cambiar rol",
      html: `¿Desea cambiar el rol de <b>${nombres} ${apellidos}</b> al rol de <b>${rol_estudianteTexto}</b>?`,
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
        console.log(
          `Cambiando rol a: ${rol_estudianteTexto}`
        );
        this.UpdateReviewer(this.config.params.gameCode, id_jugador, rol_value);
        location.reload();
        
      }
    });
  },

  columnDefs: {
    // Definiciones detalladas de columnas
    columns: [
      {
        data: null,
        render: (data) => "",
      },
      {
        data: null,
        className: "dt-center",
        title: "Jugador",
        title: `<span >Estudiante</span>`,
        //width: "50%",
        responsivePriority: 1, // Alta prioridad - siempre visible
        render: function (data, type, row) {
          // Detectar si estamos en viewport móvil
          const isMobile = window.innerWidth <= 480;
          // Formatear el nombre del jugador
          const displayName = isMobile
            ? TableModule.utils.formatName(row.nombres, row.apellidos, "short")
            : TableModule.utils.formatName(row.nombres, row.apellidos, "full");

          return `
                        <div class="player-info">
                            <div class="avatar-circle" style="background-color: ${DashboardModule.AvatarUtils.getAvatarColor(
                              displayName
                            )}">
                                <span class="initials">${DashboardModule.AvatarUtils.getInitials(
                                  displayName
                                )}</span>
                            </div>
                            <span class="player-name">${displayName}</span>
                        </div>
                    `;
        }.bind(this),
      },
      {
        data: "usuario",
        title: `<span>Nombre Usuario</span>`,
        className: "dt-center",
        responsivePriority: 3,
        width: "15%",
      },
      {
        data: "correo",
        title: `<span>Correo</span>`,
        className: "dt-center",
        width: "20%",
        responsivePriority: 3,
        type: "string",
      },
      {
        data: "fecha",
        title: `<span>Fecha Subscripción</span>`,
        className: "dt-center",
        width: "15%",
        responsivePriority: 4,
        type: "string",
      },
      {
        data: "estado_partida",
        title: `<span>Estado de Partida</span> `,
        className: "dt-center",
        width: "15%",
        responsivePriority: 3,
        type: "string",
        render: (data) => `${data}`,
      },
      {
        data: null,
        title: `<span>Estado</span> `,
        className: "dt-center",
        width: "15%",
        responsivePriority: 2,
        render: function (data, type, row) {
          return `<span class="status ${row.estado.class}">${row.estado.text}</span>`;
        },
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
                        <button class="btn-sm" title="Ver detalles"
                            onclick="TableModule.viewDetails('${row.nombres}', '${row.apellidos}', '${row.id_jugador}'); event.stopPropagation();">
                            <i class='bx bx-info-circle'></i>
                        </button>
                        <button class="btn-sm" title="Cambiar rol a revisor"
                            onclick="TableModule.updateToReviewer('${row.nombres}', '${row.apellidos}', '${row.id_jugador}', '${row.estado.text}'); event.stopPropagation();">
                            <i class='bx bx-user-check' hint="123-45-678"></i>
                        </button>
                    </div>
                `;
        },
      },
    ],
  },

  // Configuración de DataTables
  getDataTableConfig() {
    return {
      ajax: this.getAjaxConfig(),
      columns: this.columnDefs.columns,
      language: this.getLanguageConfig(),
      responsive: {
        details: {
          type: "column",
          target: "tr",
        },
      },
      columnDefs: [
        {
          // Columna del botón expand
          className: "dtr-control",
          orderable: false,
          targets: 0,
          width: "2.5rem", // Ancho fijo para el botón
        },
      ],
      processing: true,
      serverSide: false,
      drawCallback: (settings) => {
        // Aquí puedes agregar lógica después de que se dibujen los datos
        console.log("Tabla actualizada");
      },
    };
  },

  // Configuración de Ajax
  getAjaxConfig() {
    return {
      url: this.config.endpoint,
      type: "POST",
      contentType: "application/json", // Especificamos que enviaremos JSON
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
        const decryptedResponse = CryptoModule.decrypt(response.data);
        // Verificamos si la respuesta es exitosa y tiene datos
        if (decryptedResponse.status && decryptedResponse.analytics) {
          // Transformamos los datos al formato que necesita la tabla
          return decryptedResponse.analytics.map((item) => ({
            nombres: item.nombres, // Guardamos nombres completos
            apellidos: item.apellidos, // Guardamos apellidos completos
            usuario: item.usuario,
            estado: this.utils.getStatusInfo(item.estado, item.estado_texto),
            correo: item.correo,
            estado_partida: item.porcentaje_avance_alt,
            fecha: item.fecha_registro,
            id_jugador: item.id_jugador,
          }));
        }
        return [];
      },
      beforeSend: this.handleBeforeSend.bind(this),
      error: this.handleError.bind(this),
    };
  },
  async UpdateReviewer(codigoPartida, id_jugador, rol_estudiante) {
    try {

        const encryptedPayload = CryptoModule.encrypt({
            codigoPartida: codigoPartida,
            id_jugador: id_jugador,
            rol: rol_estudiante,
        });
        

        const response = await fetch(this.config.endpointReviewer, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                encryptedData: encryptedPayload // Enviar el payload ya cifrado
            })
        });

        if (!response.ok) {
            throw new Error(`Error de comunicación con el servidor: ${response.status}`);
        }

        const resultEncrypt = await response.json();

        if (!resultEncrypt.data) {
             throw new Error("La respuesta del servidor no tiene el formato esperado.");
        }

        // --- PROCESAMIENTO DE LA RESPUESTA ---
        const decryptedString = CryptoModule.decrypt(resultEncrypt.data);
        
        // --- PASO DE DEPURACIÓN CRUCIAL ---
        console.log("Datos descifrados:", decryptedString);
        // el problema está en la función `encryptResponse` de tu PHP.

    } catch (error) {
        console.error('Error en UpdateReviewer:', error.message);
        // Muestra el error al usuario
        // this.showErrorMessage('Error: ' + error.message);
    }
},

  // Configuración de lenguaje
  getLanguageConfig() {
    return {
      url: `${base_url}/Assets/js/plugins/datatables/es-ES.json`,
      paginate: {
        first: "«",
        last: "»",
        next: "›",
        previous: "‹",
      },
      loadingRecords: '<div class="spinner">Cargando...</div>',
      zeroRecords: "No se encontraron registros",
    };
  },

  // Manejadores de eventos
  handleBeforeSend() {
    // Lógica antes de enviar la petición
    console.log("Iniciando petición...");
  },

  handleError(xhr, error, thrown) {
    console.error("Error en la petición:", error);
    // Manejo de errores
    const errorMessage =
      "Ocurrió un error al cargar los datos. Por favor, intente nuevamente.";
  },

  // Métodos públicos
  initialize(id) {
    this.config.params.id = id;
    if (!this.state.isInitialized) {
      this.state.instance = $(this.config.selectors.tableId).DataTable(
        this.getDataTableConfig()
      );
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
    const gameCode = this.getUrlParameter("gamecode");
    if (!gameCode) {
      console.error("No se encontró el código de juego en la URL");
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
      window.removeEventListener("resize", this.debounceResize);
    }
  },
};

// Módulo principal de funcionalidades
const DashboardModule = {
  // Configuración inicial
  config: {
    cardsToShow: 4,
    selectors: {
      showMoreBtn: "#showMoreBtn",
      cardItem: ".card-item",
      analyse: ".analyse",
      tableRows: "#tableJugadores tbody tr",
      playersTable: "#tableJugadores",
    },
  },

  // Estado de la aplicación
  state: {
    isShowingAll: false,
  },

  translations: {
    get: (key) =>
      LanguageManager.getTranslation(`game_analytics_classification.${key}`),
  },

  showStatsInfo(key) {
    const info = this.translations.get(`cards.${key}.info`);
    if (info) {
      Swal.fire({
        title: info.title,
        text: info.description,
        icon: "info",
        confirmButtonColor: "#1976D2",
        customClass: {
          container: "analytics-type-modal",
          popup: "analytics-modal-popup",
        },
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
    this.initializeAvatars();
    this.initializeTable("tu_id_aqui");
  },

  // Vinculación de elementos del DOM
  bindElements() {
    this.elements = {};
  },

  // Configuración de event listeners
  setupEventListeners() {
    if (this.elements.showMoreBtn) {
      this.elements.showMoreBtn.addEventListener("click", () =>
        this.handleShowMoreClick()
      );
    }
  },

  // Scroll suave hacia arriba
  scrollToTop() {
    window.scrollTo({
      top: this.elements.analyseSection.offsetTop,
      behavior: "smooth",
    });
  },

  // Utilidades para los avatares
  AvatarUtils: {
    getInitials(name) {
      const words = name.split(" ");
      return words.length === 1
        ? words[0].substring(0, 2).toUpperCase()
        : (words[0][0] + words[words.length - 1][0]).toUpperCase();
    },

    getAvatarColor(name) {
      const colors = [
        "var(--primary)",
        "var(--success)",
        "var(--warning)",
        "var(--danger)",
        "#7E57C2",
        "#26A69A",
        "#FF7043",
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

      const avatar = document.createElement("div");
      avatar.className = "avatar-circle";
      avatar.style.backgroundColor = color;
      avatar.innerHTML = `<span class="initials">${initials}</span>`;

      return avatar;
    },
  },

  // Inicialización de avatares
  initializeAvatars() {
    document
      .querySelectorAll(this.config.selectors.tableRows)
      .forEach((row) => {
        const nameElement = row.querySelector("td:first-child p");
        if (!nameElement) return;

        const name = nameElement.textContent;
        const avatar = this.AvatarUtils.createAvatarElement(name);

        const imgElement = row.querySelector("td:first-child img");
        if (imgElement) {
          imgElement.replaceWith(avatar);
        }
      });
  },
};

// Inicialización cuando el DOM está listo
document.addEventListener("DOMContentLoaded", async () => {
  initializeModules();
  modificarTituloPagina();
});

function modificarTituloPagina() {
  const params = new URLSearchParams(window.location.search);
  const gameCode = params.get("gamecode");
    if (!gameCode) {
      console.error("No se encontró el código de juego en la URL");
      return false;
    }
    document.getElementById("page-title-r").innerHTML = "Asignar Estudiante Revisor a: <b>" + gameCode + "</b>";
    return true;
  }
function initializeModules() {
  // Inicializar módulos principales
  DashboardModule.init();
}
