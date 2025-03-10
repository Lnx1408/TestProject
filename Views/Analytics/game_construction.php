<?php headerGame($data); ?>

<main class="main container" id="main">
    <!--- INICIO CONTENIDO --->

    <div class="game-container">
        <!-- Analyses -->
        <div class="analyse">
            <div class="card-item color-success">
                <div class="status">
                    <div class="info">
                        <h3 data-i18n="game_analytics_construction.cards.average.title">Promedio</h3>
                        <h1 data-i18n="game_analytics_construction.cards.average.subtitle">Precisión</h1>
                    </div>
                    <div class="progresss">
                        <svg>
                            <circle cx="38" cy="38" r="36"></circle>
                        </svg>
                        <div class="percentage">
                            <p id="precision-promedio">+0%</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-item li">
                <i class='bx light-success ri-timer-flash-line'></i>
                <span class="info">
                    <div class="title-wrapper">
                        <p class="analyse-title" data-i18n="game_analytics_construction.cards.time.title">Tiempo Promedio</p>
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
                        <p class="analyse-title" data-i18n="game_analytics_construction.cards.players.title">Total Jugadores</p>
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
                        <p class="analyse-title" data-i18n="game_analytics_construction.cards.attempts.title">Promedio Intentos</p>
                        <i class='bx bx-info-circle info-icon' onclick='DashboardModule.showStatsInfo("attempts")'></i>
                    </div>
                    <p class="analyse-info" id="promedio-intentos">
                        0
                    </p>
                </span>
            </div>
            <div class="card-item hidden li">
                <i class='bx light-success ri-vip-crown-line'></i>
                <span class="info">
                    <div class="title-wrapper">
                        <p class="analyse-title" data-i18n="game_analytics_construction.cards.min_attempts.title">Mínimo Intentos</p>
                        <i class='bx bx-info-circle info-icon' onclick='DashboardModule.showStatsInfo("min_attempts")'></i>
                    </div>
                    <p class="analyse-info" id="minimo-intentos">
                        0
                    </p>
                </span>
            </div>
            <div class="card-item hidden li">
                <i class='bx light-danger bxs-star-half'></i>
                <span class="info">
                    <div class="title-wrapper">
                        <p class="analyse-title" data-i18n="game_analytics_construction.cards.max_attempts.title">Máximo Intentos</p>
                        <i class='bx bx-info-circle info-icon' onclick='DashboardModule.showStatsInfo("max_attempts")'></i>
                    </div>
                    <p class="analyse-info" id="maximo-intentos">
                        0
                    </p>
                </span>
            </div>
        </div>
        <!-- End of Analyses -->

        <!-- Botón Ver más -->
        <div class="show-more-container">
            <button id="showMoreBtn" class="show-more-btn">
                <i class='bx bx-plus'></i>
                <span data-i18n="game_analytics_construction.buttons.show_more">Ver más</span>
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
                    <h3 data-i18n="game_analytics_construction.table.title">Jugadores</h3>
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