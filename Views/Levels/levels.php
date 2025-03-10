<?php headerGame($data); ?>

<main class="main container" id="main">
    <!--- INICIO CONTENIDO --->

    <div class="game-container">

        <div class="welcome-container">
            <div class="game-card">
                <div class="logo-section">
                    <img src="<?= media(); ?>/images/logoinicio.png" alt="Logo ReqScape" class="game-logo">
                </div>

                <div class="content-section">
                    <h2 class="content-title" data-i18n="levels_welcome.title">Crea Desafíos de Aprendizaje</h2>
                    <p class="content-description" data-i18n="levels_welcome.description">
                        Como docente, tienes la oportunidad de crear experiencias de aprendizaje significativas...
                    </p>
                    <div class="action-section">
                        <button id="createGameBtn" class="btn-primary">
                            <i class='bx bx-plus-circle'></i>
                            <span data-i18n="levels_welcome.start_button">Crear Nueva Partida</span>
                        </button>
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