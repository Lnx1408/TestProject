<?php headerGame($data); ?>

<main class="main container" id="main">
    <!--- INICIO CONTENIDO --->

    <div class="teachers-container">
        <!-- Header Section -->
        <div class="teachers-header">
            <h1 class="teachers-title">Revisiones</h1>
            <p class="teachers-description">Realiza revisiones sobre las partidas y recibe el feedback de docentes</p>
        </div>

        <!-- Opcion Crear docente -->
        <div class="teachers-content">
            <!-- Estructura de acordeón para las opciones de configuración -->
            <div class="teachers-menu">
                <!-- Sección: Hacer revisiones -->
                <div class="teachers-section">
                    <div id="personalInfo" class="section-content">
                        <a href="<?= base_url(); ?>/ReviewerStudentsMenu/ReviewerStudents" class="menu-option">
                            <div class="header-icon">
                                <i class='bx bx-check-square'></i>
                            </div>    
                            <div class="option-info">
                                <h3>Hacer revisiones</h3>
                                <p>Realiza sugerencias de los requisitos generados por IA</p>
                            </div>
                            <div class="option-chevron">
                                <i class='bx bx-chevron-right'></i>
                            </div>
                        </a>
                    </div>
                </div>
                <!-- Sección: Ver revisiones colaboraticas -->
                <div class="teachers-section">
                    <div id="personalInfo" class="section-content">
                        <a href="<?= base_url(); ?>/ReviewerStudentsMenu/feedback_suggestions_list" class="menu-option">
                            <div class="header-icon">
                                <i class='bx bx-message'></i>
                            </div>    
                            <div class="option-info">
                                <h3>Ver Mis Sugerencias
                                </h3>
                                <p>Visualiza las sugerencias que haz realizado y el feedback de los docentes</p>
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