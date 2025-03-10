<?php headerGame($data); ?>

<?php
$gameCode = $_GET['code'] ?? '';
if (!$gameCode) {
    header('Location: home.php');
    exit;
}
?>


<main class="main container" id="main">
    <!--- INICIO CONTENIDO --->

    <div class="game-container">
        <div class="game-info">
            <h2>
                <span data-i18n="game_classification.header.match">Partida</span>: <span id="gameCode"><?php echo htmlspecialchars($gameCode); ?></span>
            </h2>
            <div class="metrics">
                <p>
                    <span data-i18n="game_classification.header.attempts">Intentos</span>: <span id="attempts">0</span>
                </p>
                <p>
                    <span data-i18n="game_classification.header.time">Tiempo</span>: <span id="timer">00:00</span>
                </p>
                <p>
                    <span data-i18n="game_classification.header.moves">Movimientos</span>: <span id="moves">0</span>
                </p>
            </div>
        </div>

        <div class="panels-container">

            <div id="ambiguous-panel" class="panel">
                <h3 data-i18n="game_classification.panels.ambiguous">Requisitos Ambiguos</h3>
                <div class="requirements-list" id="ambiguousRequirements"></div>
            </div>

            <div id="requirements-panel" class="panel">
                <h3 data-i18n="game_classification.panels.to_classify">Requisitos por Clasificar</h3>
                <div class="requirements-list" id="initialRequirements"></div>
            </div>

            <div id="non-ambiguous-panel" class="panel">
                <h3 data-i18n="game_classification.panels.non_ambiguous">Requisitos No Ambiguos</h3>
                <div class="requirements-list" id="nonAmbiguousRequirements"></div>
            </div>
        </div>

        <div class="game-controls">
            <button id="validateBtn" class="btn-primary" data-i18n="game_classification.buttons.validate">Validar Clasificación</button>
        </div>

        <div id="feedbackModal" class="modal">
            <div class="modal-content">
                <h3>Resultado de la Validación</h3>
                <div id="feedbackContent"></div>
                <button onclick="closeFeedbackModal()" class="btn-primary">Continuar</button>
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