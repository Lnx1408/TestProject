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
                <span data-i18n="game_construction.header.match">Partida</span>:
                <span id="gameCode"><?php echo htmlspecialchars($gameCode); ?></span>
            </h2>
            <div class="metrics">
                <p>
                    <span data-i18n="game_construction.header.requirements">Requisito</span>:
                    <span id="currentRequirement">1</span>
                    <span data-i18n="game_construction.header.of">de</span>
                    <span id="totalRequirements">3</span>
                </p>
                <p>
                    <span data-i18n="game_construction.header.attempts">Intento</span>:
                    <span id="attempts">1</span>
                </p>
                <p>
                    <span data-i18n="game_construction.header.time">Tiempo</span>:
                    <span id="timer">00:00</span>
                </p>
            </div>
        </div>

        <div class="construction-area">
            <h3 data-i18n="game_construction.sections.construction_area">Área de Construcción</h3>
            <div id="constructionZone" class="construction-zone">
                <!-- Las posiciones para los fragmentos se generarán dinámicamente -->
            </div>

            <h3 data-i18n="game_construction.sections.available_fragments">Fragmentos Disponibles</h3>
            <div id="fragmentsBank" class="fragments-bank">
                <!-- Los fragmentos se cargarán dinámicamente -->
            </div>
        </div>

        <div class="game-controls">
            <button id="validateBtn" class="btn-primary">
                <i class='bx bx-check-circle'></i>
                <span data-i18n="game_construction.buttons.validate">Validar Construcción</span>
            </button>
        </div>
    </div>

    <!--- FIN CONTENIDO --->
</main>

<script>
    const base_url = "<?= base_url(); ?>";
</script>
<script src="<?= media(); ?>/js/<?= $data['page_functions_js']; ?>"></script>
<?php footerGame($data); ?>