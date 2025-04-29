<?php headerGame($data); ?>

<main class="main container" id="main">
    <div class="sessions-container">
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
        <div class="sessions-header">
            <h1 class="sessions-title" data-i18n="settings.sessions.title">Historial de inicios de sesión</h1>
            <p class="sessions-description" data-i18n="settings.sessions.description">Revisa y gestiona tus sesiones activas</p>
        </div>

        <!-- Contenido de sesiones -->
        <div class="sessions-content">
            <!-- Acción global -->
            <div class="global-action">
                <button id="closeAllSessionsBtn" class="btn btn-danger">
                    <i class='bx bx-log-out-circle'></i>
                    <span data-i18n="settings.sessions.close_all_button">Cerrar todas las sesiones excepto esta</span>
                </button>
            </div>

            <!-- Lista de sesiones -->
            <div class="sessions-list" id="sessionsList">
                <div class="loading-container">
                    <div class="spinner"></div>
                    <p data-i18n="settings.sessions.loading">Cargando sesiones...</p>
                </div>
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