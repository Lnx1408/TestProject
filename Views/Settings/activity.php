<?php headerGame($data); ?>

<main class="main container" id="main">
    <div class="activity-container">
        <!-- Navegación de migas de pan -->
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

        <!-- Cabecera -->
        <div class="activity-header">
            <h1 class="activity-title" data-i18n="settings.activity.title">Historial de Actividad</h1>
            <p class="activity-description" data-i18n="settings.activity.description">Consulta tu historial detallado de inicios y cierres de sesión</p>
            
            <!-- Controles de filtro -->
            <div class="activity-controls">
                <div class="filter-selector">
                    <i class='bx bx-calendar'></i>
                    <select id="periodFilter" class="period-selector">
                        <option value="7" data-i18n="settings.activity.filters.last_7_days">Últimos 7 días</option>
                        <option value="30" data-i18n="settings.activity.filters.last_30_days">Últimos 30 días</option>
                        <option value="90" selected data-i18n="settings.activity.filters.last_90_days">Últimos 90 días</option>
                        <option value="365" data-i18n="settings.activity.filters.last_year">Último año</option>
                    </select>
                </div>
                <button id="exportBtn" class="btn btn-export">
                    <i class='bx bx-export'></i>
                    <span data-i18n="settings.activity.buttons.export">Exportar</span>
                </button>
            </div>
        </div>

        <!-- Contenido: Mensaje informativo -->
        <div class="activity-info-message">
            <i class='bx bx-info-circle'></i>
            <span data-i18n="settings.activity.messages.info">Este historial muestra todos tus inicios y cierres de sesión del período seleccionado.</span>
        </div>

        <!-- Contenido: Historial de actividad -->
        <div class="activity-content">
            <div id="activityList" class="activity-list">
                <!-- Aquí se cargarán dinámicamente los datos -->
                <div class="loading-container">
                    <div class="spinner"></div>
                    <p data-i18n="settings.activity.loading">Cargando historial...</p>
                </div>
            </div>
            
            <!-- Botón para cargar más -->
            <div class="load-more-container">
                <button id="loadMoreBtn" class="btn btn-load-more" style="display: none;">
                    <i class='bx bx-history'></i>
                    <span data-i18n="settings.activity.buttons.load_more">Ver más antiguas</span>
                </button>
            </div>
        </div>
    </div>
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