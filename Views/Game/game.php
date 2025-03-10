<?php headerGame($data); ?>

<main class="main container" id="main">
    <!--- INICIO CONTENIDO --->

    <div class="game-container">

        <div class="welcome-container">
            <div class="game-card">
                <!-- Columna Izquierda: Logo -->
                <div class="logo-section">
                    <img src="<?= media(); ?>/images/logoinicio.png" alt="Logo ReqScape" class="game-logo">
                </div>

                <!-- Columna Derecha: Contenido -->
                <div class="content-section">
                    <h2 class="content-title" data-i18n="game_welcome.title">Mejora tus habilidades en Requisitos</h2>
                    <p class="content-description" data-i18n="game_welcome.description">
                        Cada partida es una oportunidad para mejorar tu capacidad de análisis
                        y especificación de requisitos. ¿Listo para el siguiente desafío?
                        Ingresa el código de la partida y comienza a jugar.
                    </p>
                    <div class="action-section">
                        <button id="startGameBtn" class="btn-primary">
                            <i class='bx bx-play-circle'></i>
                            <span data-i18n="game_welcome.start_button">Ingresar a Partida</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <!-- Modal para ingresar código -->
        <div id="gameCodeModal">
            <!-- El contenido del modal será manejado por SweetAlert2 -->
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