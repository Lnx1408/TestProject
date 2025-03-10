<?php headerGame($data); ?>

<main class="main container" id="main">
    <!--- INICIO CONTENIDO --->

    <div class="game-container">
        <!-- Analyses -->
        <div class="analyse">
            <div class="card-item color-success">
                <div class="status">
                    <div class="info">
                        <div class="title-wrapper">
                            <h3 data-i18n="game_analytics_classification.cards.first_attempt.title">Promedio Primer Intento</h3>
                            <i class='bx bx-info-circle info-icon' onclick='DashboardModule.showStatsInfo("first_attempt")'></i>
                        </div>
                        <h1 data-i18n="game_analytics_classification.cards.first_attempt.subtitle">Aciertos</h1>
                    </div>
                    <div class="progresss">
                        <svg>
                            <circle cx="38" cy="38" r="36"></circle>
                        </svg>
                        <div class="percentage">
                            <p id="precision-primer-intento">+0%</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-item li">
                <i class='bx light-success ri-timer-flash-line'></i>
                <span class="info">
                    <div class="title-wrapper">
                        <p class="analyse-title" data-i18n="game_analytics_classification.cards.time.title">Tiempo Promedio</p>
                        <i class='bx bx-info-circle info-icon' onclick='DashboardModule.showStatsInfo("time")'></i>
                    </div>
                    <p class="analyse-info" id="tiempo-promedio">
                        0 min
                    </p>
                </span>
            </div>
            <div class="card-item li">
                <i class='bx light-warning ri-user-star-fill'></i>
                <span class="info">
                    <div class="title-wrapper">
                        <p class="analyse-title" data-i18n="game_analytics_classification.cards.players.title">Total Jugadores</p>
                        <i class='bx bx-info-circle info-icon' onclick='DashboardModule.showStatsInfo("players")'></i>
                    </div>
                    <p class="analyse-info" id="total-jugadores">
                        0
                    </p>
                </span>
            </div>
            <div class="card-item li">
                <i class='bx light-primary ri-reset-left-line'></i>
                <span class="info">
                    <div class="title-wrapper">
                        <p class="analyse-title" data-i18n="game_analytics_classification.cards.attempts.title">Promedio Intentos</p>
                        <i class='bx bx-info-circle info-icon' onclick='DashboardModule.showStatsInfo("attempts")'></i>
                    </div>
                    <p class="analyse-info" id="promedio-intentos">
                        0
                    </p>
                </span>
            </div>
            <div class="card-item hidden color-primary">
                <div class="status">
                    <div class="info">
                        <div class="title-wrapper">
                            <h3 data-i18n="game_analytics_classification.cards.one_attempt.title">Usaron 1 Intento</h3>
                            <i class='bx bx-info-circle info-icon' onclick='DashboardModule.showStatsInfo("one_attempt")'></i>
                        </div>
                        <h1 class="al-center" id="primer-intento">0</h1>
                    </div>
                    <div class="progresss">
                        <svg>
                            <circle cx="38" cy="38" r="36"></circle>
                        </svg>
                        <div class="percentage">
                            <p id="procentaje-primer-intento">+0%</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-item hidden color-warning">
                <div class="status">
                    <div class="info">
                        <div class="title-wrapper">
                            <h3 data-i18n="game_analytics_classification.cards.two_three_attempts.title">Usaron 2 a 3 Intentos</h3>
                            <i class='bx bx-info-circle info-icon' onclick='DashboardModule.showStatsInfo("two_three_attempts")'></i>
                        </div>
                        <h1 class="al-center" id="dos-tres-intentos">0</h1>
                    </div>
                    <div class="progresss">
                        <svg>
                            <circle cx="38" cy="38" r="36"></circle>
                        </svg>
                        <div class="percentage">
                            <p id="porcentaje-dos-tres-intentos">-0%</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-item hidden color-danger">
                <div class="status">
                    <div class="info">
                        <div class="title-wrapper">
                            <h3 data-i18n="game_analytics_classification.cards.more_attempts.title">Usaron +3 Intentos</h3>
                            <i class='bx bx-info-circle info-icon' onclick='DashboardModule.showStatsInfo("more_attempts")'></i>
                        </div>
                        <h1 class="al-center" id="mas-tres-intentos">0</h1>
                    </div>
                    <div class="progresss">
                        <svg>
                            <circle cx="38" cy="38" r="36"></circle>
                        </svg>
                        <div class="percentage">
                            <p id="porcentaje-mas-tres-intentos">-0%</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- End of Analyses -->

        <!-- Botón Ver más -->
        <div class="show-more-container">
            <button id="showMoreBtn" class="show-more-btn">
                <i class='bx bx-plus'></i>
                <span data-i18n="game_analytics_classification.buttons.show_more">Ver más</span>
            </button>
        </div>

        <!-- Nueva sección para el botón del reporte -->
        <div class="report-actions">
            <button id="generateReportBtn" class="btn btn-primary">
                <i class='bx bx-file'></i>
                <span data-i18n="details_classification.buttons.generate_report">Generar Reporte</span>
            </button>
        </div>

        <div class="bottom-data">
            <div class="orders container-table">
                <div class="header-table">
                    <i class='bx ri-user-community-fill'></i>
                    <h3 data-i18n="game_analytics_classification.table.title">Jugadores</h3>
                </div>
                <table id="tableJugadores" class="table-players nowrap">
                    <thead>
                        <tr>
                            <th></th>
                            <th>Jugador</th>
                            <th>Tiempo Empleado</th>
                            <th>Num. Intentos</th>
                            <th>Último Intento</th>
                            <th>Avance</th>
                            <th>Estado</th>
                            <th>Ver</th>
                        </tr>
                    </thead>
                    <tbody>

                    </tbody>
                </table>
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