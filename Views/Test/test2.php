<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte Descriptivo - Preview</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
    <!-- Importar los archivos CSS -->
    <?php
    if (!empty($data['page_libraries_css'])) {
        foreach ($data['page_libraries_css'] as $css) {
            echo '<link rel="stylesheet" href="' . media() . '/css/' . $css . '">';
        }
    }
    if (!empty($data['page_css'])) {
        foreach ($data['page_css'] as $css) {
            echo '<link rel="stylesheet" href="' . media() . '/css/' . $css . '">';
        }
    }
    ?>
</head>

<body>
    <div class="report-container">
        <!-- Header del Reporte -->
        <header class="report-header">
            <div class="header-top">
                <div class="header-left">
                    <h1 class="report-title">
                        <!-- Cada palabra en una línea -->
                        <span class="title-line" data-i18n="report.title.line1">REPORTE</span>
                        <span class="title-line title-bold" data-i18n="report.title.line2">DESCRIPTIVO</span>
                    </h1>
                </div>
                <div class="header-logo">
                    <!-- Posicionado a la derecha con un margen -->
                    <!-- <img src="path_to_logo/twitch.png" alt="Logo" class="logo"> -->
                </div>
            </div>
            <div class="player-info">
                <div>
                    <h2 class="player-name">STEFAN SALVATORE</h2>
                </div>
                <div>
                    <div class="game-code-container">
                        <span class="code-label" data-i18n="report.game_code.label">Código del juego:</span>
                        <span class="code-value">ABCDEF</span>
                    </div>
                </div>
            </div>
        </header>


        <!-- Sección de Estadísticas Principales -->
        <section class="main-stats">
        <h3 data-i18n="report.sections.game_details">Desglose de la Partida</h3>
            <div class="stat-cards">
                <div class="stat-card time">
                    <span class="stat-value">0min<br>32s</span>
                    <span class="stat-label" data-i18n="report.sections.time">Tiempo</span>
                </div>
                <div class="stat-card attempts">
                    <span class="stat-value">3</span>
                    <span class="stat-label" data-i18n="report.sections.attempts">Intentos</span>
                </div>
                <div class="stat-card precision">
                    <div class="progress-circle">
                        <svg viewBox="0 0 36 36">
                            <!-- Círculo de fondo -->
                            <path d="M18 2.0845
                                a 15.9155 15.9155 0 0 1 0 31.831
                                a 15.9155 15.9155 0 0 1 0 -31.831"
                                fill="none"
                                stroke="#eee"
                                stroke-width="3" />
                            <!-- Círculo de progreso -->
                            <path d="M18 2.0845
                                a 15.9155 15.9155 0 0 1 0 31.831
                                a 15.9155 15.9155 0 0 1 0 -31.831"
                                fill="none"
                                stroke="#4b6cc1"
                                stroke-width="3"
                                stroke-dasharray="35, 100" />
                        </svg>
                        <span class="percentage">35%</span>
                    </div>
                    <span class="stat-label" data-i18n="report.sections.precision">Precisión</span>
                </div>
            </div>
        </section>

        <!-- Tabla de Intentos -->
        <section class="attempts-section">
            <h3 data-i18n="report.sections.attempt_details">Desglose de intentos</h3>
            <table class="attempts-table">
                <thead>
                    <tr>
                        <th>Intento</th>
                        <th>Tiempo</th>
                        <th>Movimientos</th>
                        <th>Requisitos</th>
                        <th>Correctos</th>
                        <th>Incorrectos</th>
                        <th>Precisión Aciertos</th>
                        <th>Precisión Errores</th>
                        <th>Precisión Progresiva</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Filas de datos -->
                </tbody>
            </table>
        </section>

        <!-- Sección de Resumen -->
        <section class="summary-section">
            <h3 data-i18n="report.sections.summary">Resumen</h3>
            <div class="summary-content">
                <!-- Contenido dinámico del resumen -->
            </div>
        </section>

        <!-- Footer -->
        <footer class="report-footer">
            <div class="footer-container">
                <span class="footer-line text-primary font-bold" data-i18n="report.footer.generated_by">Reporte generado por Twitch</span>
                <span class="footer-line text-color">01/01/2025, 00:00</span>
            </div>
        </footer>
    </div>
</body>

</html>