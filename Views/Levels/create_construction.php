<?php headerGame($data); ?>

<main class="main container" id="main">
    <!--- INICIO CONTENIDO --->
    <div class="game-container">
        <div class="create-game-container">
            <!-- Header con título y contador -->
            <div class="header-section">
                <h1 class="page-title" data-i18n="create_construction.title">Crear Partida de Construcción</h1>
            </div>

            <!-- Área de acciones -->
            <div class="actions-section">
                <button id="selectExistingBtn" class="btn btn-primary">
                    <i class='bx bx-list-plus'></i>
                    <span data-i18n="create_construction.buttons.select_existing">Seleccionar Requisitos Existentes</span>
                </button>
                <button id="createNewBtn" class="btn btn-primary">
                    <i class='bx bx-plus-circle'></i>
                    <span data-i18n="create_construction.buttons.create_new">Crear Nuevo Requisito</span>
                </button>
                <button id="importReqBtn" class="btn btn-primary">
                    <i class='bx bx-export'></i>
                    <span data-i18n="create_construction.buttons.import">Importar Requisitos</span>
                </button>
            </div>

            <!-- Botón crear partida y contador -->
            <div class="footer-section">
                <div class="requirements-counter">
                    <span data-i18n="create_construction.counter.title">Requisitos Seleccionados</span>:
                    <span id="reqCount">0</span>
                    <small data-i18n="create_construction.counter.minimum">(Mínimo requerido: 5)</small>
                </div>
                <button id="createGameBtn" class="btn btn-success" disabled>
                    <i class='bx bx-game'></i>
                    <span data-i18n="create_construction.buttons.create_game">Crear Partida</span>
                </button>
            </div>

            <!-- Tabla Principal con Row Grouping -->
            <div class="bottom-data">
                <div class="data-info container-table">
                    <div class="header-table">
                        <i class='bx ri-user-community-fill'></i>
                        <h2 data-i18n="create_construction.main_table.title">Requisitos de la Partida</h2>
                    </div>
                    <div class="bottom-actions">
                        <button id="exportReqBtn" class="btn btn-outline-primary" disabled>
                            <i class='bx bx-export'></i>
                            <span data-i18n="create_construction.buttons.export">Exportar Requisitos</span>
                        </button>
                    </div>
                    <table id="selectedRequirementsTable" class="nowrap">
                        <thead>
                            <tr>
                                <th></th>
                                <th data-i18n="create_construction.main_table.columns.requirement">Requisito</th>
                                <th data-i18n="create_construction.main_table.columns.fragments">Fragmentos</th>
                                <th data-i18n="create_construction.main_table.columns.decoys">Señuelos</th>
                                <th data-i18n="create_construction.main_table.columns.actions">Acciones</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <!--- FIN CONTENIDO --->
</main>

<script>
    const base_url = "<?= base_url(); ?>";
</script>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<?php
if (!empty($data['page_functions_js'])) {
    foreach ($data['page_functions_js'] as $js) {
        echo '<script src="' . media() . '/js/' . $js . '"></script>';
    }
}
?>
<?php footerGame($data); ?>