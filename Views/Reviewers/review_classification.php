<?php headerGame($data); ?>

<main class="main container" id="main">
    <!--- INICIO CONTENIDO --->

    <div id="ai-generator-modal-izi" class="iziModal"></div>
    <div class="game-container">
        <div class="create-game-container">
            <!-- Header con título y contador -->
            <div class="header-section">
                <h1 class="page-title">Revisiones por estudiantes</h1>
            </div>

            <!-- Tabla principal -->
            <div class="bottom-data">
                <div class="data-info container-table">
                    <div class="header-table">
                        <i class='bx ri-user-community-fill'></i>
                        <h2 data-i18n="create_classification.main_table.title">Requisitos de la Partida</h2>
                    </div>
                    <table id="selectedRequirementsTable" class="nowrap">
                        <thead>
                            <tr>
                                <th></th>
                                <th data-i18n="create_classification.main_table.columns.description">Descripción</th>
                                <th data-i18n="create_classification.main_table.columns.type">Tipo</th>
                                <th data-i18n="create_classification.main_table.columns.is_ambiguous">Es Ambiguo</th>
                                <th data-i18n="create_classification.main_table.columns.feedback">Retroalimentación</th>
                                <th data-i18n="create_classification.main_table.columns.actions">Acciones</th>
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
<?php
if (!empty($data['page_functions_js'])) {
    foreach ($data['page_functions_js'] as $js) {
        echo '<script src="' . media() . '/js/' . $js . '"></script>';
    }
}
?>
<?php footerGame($data); ?>