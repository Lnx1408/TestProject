<?php headerGame($data); ?>

<main class="main container" id="main">
    <!--- INICIO CONTENIDO --->

    <div class="teachers-container">
        <!-- Header Section -->
        <div class="teachers-header">
            <h1 class="teachers-title" data-i18n="reviewers.title">Ajustes</h1>
            <p class="teachers-description" data-i18n="reviewers.description">Administra tu cuenta y preferencias</p>
        </div>

        <!-- Opcion Crear docente -->
        <div class="teachers-content">
            <!-- Estructura de acordeón para las opciones de configuración -->
            <div class="teachers-menu">
                <!-- Sección: Agregar Revisor -->
                <div class="teachers-section">
                    <div id="personalInfo" class="section-content">
                        <a href="<?= base_url(); ?>/reviewers/list_reviewers" class="menu-option">
                            <div class="header-icon">
                                <i class='bx bx-user-plus'></i>
                            </div>    
                            <div class="option-info">
                                <h3 data-i18n="reviewers.options.create.title">Agregar usuario</h3>
                                <p data-i18n="reviewers.options.create.description">Agregar usuario con rol "docente"</p>
                            </div>
                            <div class="option-chevron">
                                <i class='bx bx-chevron-right'></i>
                            </div>
                        </a>
                    </div>
                </div>
                <!-- Sección: Ver revisiones -->
                <div class="teachers-section">
                    <div id="personalInfo" class="section-content">
                        <a href="<?= base_url(); ?>/reviewers/list_reviews" class="menu-option">
                            <div class="header-icon">
                                <i class='bx bx-check-square'></i>
                            </div>    
                            <div class="option-info">
                                <h3 data-i18n="reviewers.options.reviews.title">Ver revisiones</h3>
                                <p data-i18n="reviewers.options.reviews.description">Visualizar las sugerencias realizadas por los revisores</p>
                            </div>
                            <div class="option-chevron">
                                <i class='bx bx-chevron-right'></i>
                            </div>
                        </a>
                    </div>
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