<?php headerGame($data); ?>

<main class="main container" id="main">
    <!--- INICIO CONTENIDO --->

    <div class="teachers-container">
        <!-- Header Section -->
        <div class="teachers-header">
            <h1 class="teachers-title" data-i18n="teachers.title">Ajustes</h1>
            <p class="teachers-description" data-i18n="teachers.description">Administra tu cuenta y preferencias</p>
        </div>

        <!-- Opcion Crear docente -->
        <div class="teachers-content">
            <!-- Estructura de acordeón para las opciones de configuración -->
            <div class="teachers-menu">
                <!-- Sección: Información Personal -->
                <div class="teachers-section">
                    <div id="personalInfo" class="section-content">
                        <a href="<?= base_url(); ?>/teachers/teacher" class="menu-option">
                            <div class="header-icon">
                                <i class='bx bx-user-plus'></i>
                            </div>    
                            <div class="option-info">
                                <h3 data-i18n="teachers.options.create.title">Información del Perfil</h3>
                                <p data-i18n="teachers.options.create.description">Gestiona tu información personal</p>
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