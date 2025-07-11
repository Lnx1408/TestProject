<?php headerGame($data); ?>

<main class="main container" id="main">
    <!--- INICIO CONTENIDO --->

    <div id="ai-generator-modal-izi" class="iziModal"></div>
    <div class="game-container">
        <div class="create-game-container">
            <div class="breadcrumbs-container">
            <ul class="breadcrumbs">
                <?php foreach ($data['breadcrumbs'] as $index => $crumb): ?>
                    <li>
                        <?php if (!empty($crumb['url'])): ?>
                            <a href="<?= base_url() . '/' . $crumb['url']; ?>"><?= $crumb['name']; ?></a>
                        <?php else: ?>
                            <a href="javascript:void(0);"><?= $crumb['name']; ?></a>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
            <!-- Header con título y contador -->
            <div class="header-section">
                <h1 class="page-title">Requisito original:</h1>
            </div>
            <div class="header-section">
                <h1 id="requisito-original">Revisiones por estudiantes</h1>
            </div>

            <!-- Tabla principal -->
            <div class="bottom-data">
                <div class="data-info container-table">
                    <div class="header-table">
                        <i class='bx ri-user-community-fill'></i>
                        <h2>Revisiones de estudiantes</h2>
                    </div>
                    <table id="existingRequirementsTable" class="nowrap">
                        <thead>
                            <tr>
                                <th></th>
                                <th data-i18n="create_classification.main_table.columns.description">Descripción</th>
                                <th data-i18n="create_classification.main_table.columns.type">Tipo</th>
                                <th data-i18n="create_classification.main_table.columns.is_ambiguous">Es Ambiguo</th>
                                <th>Estudiante Revisor</th>
                                <th>Comentario Estudiante</th>
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