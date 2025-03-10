<!DOCTYPE html>
<html lang="<?= $lang ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ReporteGeneral_<?= $data['report_data']['gameInfo']['gameCode'] ?>_<?= date('Y-m-d') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
    <!--=============== BOX ICONS ===============-->
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>

    <!--=============== REMIXICONS ===============-->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.5.0/remixicon.css">

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
    <script>
        const base_url = "<?= base_url(); ?>";
    </script>
    <script>
        // Configuración para guardado automático
        window.onload = function() {
            setTimeout(() => {
                window.print();
            }, 1000);
        };
    </script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
    <script src="<?= media(); ?>/js/i18n/languageManager.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', async () => {
            await LanguageManager.init();
        });
    </script>
</head>

<body>
    <div class="page-background"></div>

    <div class="report-container">
        <!-- Header del Reporte -->
        <header class="report-header">
            <div class="header-top">
                <div class="header-left">
                    <h1 class="report-title">
                        <span class="title-line" data-i18n="report_general.title.line1">Reporte</span>
                        <span class="title-line title-bold" data-i18n="report_general.title.line2">General Partida</span>
                    </h1>
                </div>
                <div class="header-logo">
                    <img src="<?= media() ?>/images/twitch.png" alt="Logo" class="logo">
                </div>
            </div>
            <div class="player-info">
                <div class="creator-info">
                    <div class="info-group">
                        <span data-i18n="report_general.game_info.creator">Creado por:</span>
                        <span class="font-bold"><?= $data['report_data']['gameInfo']['creatorName'] ?></span>
                    </div>
                    <div class="info-group">
                        <span data-i18n="report_general.game_info.date">Fecha de Creación:</span>
                        <span class="font-bold"><?= $data['report_data']['gameInfo']['creationDate'] ?></span>
                    </div>
                </div>
                <div>
                    <div class="game-code-container">
                        <span class="code-label" data-i18n="report_general.game_info.code">Código del juego:</span>
                        <span class="code-value"><?= $data['report_data']['gameInfo']['gameCode'] ?></span>
                        <span data-i18n="report_general.game_info.requirements">Total de Requisitos:</span>
                        <span class="font-bold"><?= $data['report_data']['gameInfo']['totalRequirements'] ?></span>
                    </div>
                </div>
            </div>
        </header>

        <!-- Sección de Resumen General -->
        <section class="main-stats">
            <h2 class="text-primary font-bold" data-i18n="report_general.construction.summary.title">Resumen General</h2>

            <!-- Métricas Principales -->
            <div class="stat-cards">
                <!-- Total de Jugadores -->
                <div class="stat-card">
                    <div class="metric-info">
                        <span class="metric-value"><i class="ri-group-line"></i> <?= $data['report_data']['summary']['totalPlayers'] ?></span>
                        <span class="metric-label" data-i18n="report_general.construction.summary.metrics.players">Total Jugadores</span>
                    </div>
                </div>

                <!-- Precisión Promedio -->
                <div class="stat-card">
                    <div class="metric-info">
                        <span class="metric-value"><i class="ri-focus-2-line"></i> <?= $data['report_data']['summary']['averageAccuracy'] ?>%</span>
                        <span class="metric-label" data-i18n="report_general.construction.summary.metrics.accuracy">Precisión Promedio</span>
                        <div class="metric-distribution">
                            <div class="progress-mini">
                                <div class="progress-bar" style="width: <?= $data['report_data']['summary']['averageAccuracy'] ?>%"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tiempo Promedio -->
                <div class="stat-card">
                    <div class="metric-info">
                        <span class="metric-value"><i class="ri-time-line"></i> <?= $data['report_data']['summary']['averageTime'] ?></span>
                        <span class="metric-label" data-i18n="report_general.construction.summary.metrics.time">Tiempo Promedio</span>
                        <div class="metric-subtext">
                            <span data-i18n="report_general.construction.summary.metrics.per_requirement">por requisito</span>
                        </div>
                    </div>
                </div>

                <!-- Tasa de Finalización -->
                <div class="stat-card">
                    <div class="metric-info">
                        <span class="metric-value"><i class="ri-checkbox-circle-line"></i> <?= $data['report_data']['summary']['completionRate'] ?>%</span>
                        <span class="metric-label" data-i18n="report_general.construction.summary.metrics.completion">Tasa de Finalización</span>
                    </div>
                </div>
            </div>

            <!-- Estado de Avance -->
            <div class="progress-section general-section progress-status">
                <div>
                    <h3 class="text-primary font-bold" data-i18n="report_general.construction.summary.progress.title">Estado de Avance</h3>
                    <div class="progress-overview">
                        <!-- Gráfico Circular -->
                        <div class="progress-chart">
                            <canvas id="progressChart"></canvas>
                        </div>
                    </div>
                </div>
                <div>
                    <div>
                        <!-- Detalles del Estado -->
                        <div class="progress-details">
                            <div class="status-group">
                                <div class="status-item completed">
                                    <div class="status-indicator"></div>
                                    <div class="status-info">
                                        <span class="status-label" data-i18n="report_general.construction.summary.progress.completed">Completado</span>
                                        <span class="status-count"><?= $data['report_data']['progress']['completed'] ?></span>
                                        <span class="status-percentage"><?= $data['report_data']['progress']['completedPercentage'] ?>%</span>
                                    </div>
                                </div>

                                <div class="status-item in-progress">
                                    <div class="status-indicator"></div>
                                    <div class="status-info">
                                        <span class="status-label" data-i18n="report_general.construction.summary.progress.in_progress">En Progreso</span>
                                        <span class="status-count"><?= $data['report_data']['progress']['inProgress'] ?></span>
                                        <span class="status-percentage"><?= $data['report_data']['progress']['inProgressPercentage'] ?>%</span>
                                    </div>
                                </div>

                                <div class="status-item not-started">
                                    <div class="status-indicator"></div>
                                    <div class="status-info">
                                        <span class="status-label" data-i18n="report_general.construction.summary.progress.not_started">Sin Iniciar</span>
                                        <span class="status-count"><?= $data['report_data']['progress']['notStarted'] ?></span>
                                        <span class="status-percentage"><?= $data['report_data']['progress']['notStartedPercentage'] ?>%</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Descripción Narrativa -->
                        <div class="progress-narrative">
                            <p><?= $data['report_data']['progress']['narrative'] ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Sección de Explicación de Métricas -->
        <section class="general-section">
            <h2 class="text-primary font-bold" data-i18n="report_general.construction.metrics_explanation.title">Explicación de Métricas</h2>

            <div class="metrics-explanation">
                <!-- Precisión Promedio -->
                <div class="metric-detail">
                    <h3 class="text-primary" data-i18n="report_general.construction.metrics_explanation.average_accuracy.title">Precisión Promedio</h3>
                    <p data-i18n="report_general.construction.metrics_explanation.average_accuracy.description">
                        Representa el promedio general de las precisiones promedio alcanzadas por cada jugador. La precisión de cada jugador se calcula promediando las precisiones obtenidas en todos sus intentos por requisito.
                    </p>
                    <div class="formula-section">
                        <h4>Cálculo:</h4>
                        <div class="formula" data-i18n="report_general.construction.metrics_explanation.average_accuracy.formula.step1">
                            1. Precisión por intento = (Fragmentos correctamente colocados / Total de fragmentos) × 100
                        </div>
                        <div class="formula" data-i18n="report_general.construction.metrics_explanation.average_accuracy.formula.step2">
                            2. Precisión promedio del jugador = Suma de precisiones de todos los intentos / Total de intentos
                        </div>
                        <div class="formula" data-i18n="report_general.construction.metrics_explanation.average_accuracy.formula.step3">
                            3. Precisión promedio general = Suma de precisiones promedio de jugadores / Total de jugadores
                        </div>
                    </div>
                    <p class="interpretation" data-i18n="report_general.construction.metrics_explanation.average_accuracy.interpretation">
                        Esta métrica refleja la precisión global del juego, considerando todos los intentos de cada jugador en cada requisito. Un valor más alto indica una mejor capacidad general de los jugadores para construir los requisitos correctamente.
                    </p>
                </div>

                <!-- Tiempo Promedio de Partida -->
                <div class="metric-detail">
                    <h3 class="text-primary" data-i18n="report_general.construction.metrics_explanation.average_game_time.title">Tiempo Promedio de Partida</h3>
                    <p data-i18n="report_general.construction.metrics_explanation.average_game_time.description">
                        Representa el tiempo promedio que toma completar toda la partida, calculado a partir del tiempo total de cada jugador.
                    </p>
                    <div class="formula-section">
                        <h4>Cálculo:</h4>
                        <div class="formula" data-i18n="report_general.construction.metrics_explanation.average_game_time.formula.step1">
                            1. Tiempo total del jugador = Suma de tiempos empleados en todos los requisitos
                        </div>
                        <div class="formula" data-i18n="report_general.construction.metrics_explanation.average_game_time.formula.step2">
                            2. Tiempo promedio de partida = Suma de tiempos totales de todos los jugadores / Total de jugadores
                        </div>
                    </div>
                    <p class="interpretation" data-i18n="report_general.construction.metrics_explanation.average_game_time.interpretation">
                        Esta métrica proporciona una visión general del tiempo necesario para completar todos los requisitos del juego.
                    </p>
                </div>

                <!-- Tasa de Finalización -->
                <div class="metric-detail">
                    <h3 class="text-primary" data-i18n="report_general.construction.metrics_explanation.completion_rate.title">Tasa de Finalización</h3>
                    <p data-i18n="report_general.construction.metrics_explanation.completion_rate.description">
                        Indica el porcentaje de jugadores que han completado todos los requisitos del juego.
                    </p>
                    <div class="formula-section">
                        <h4>Cálculo:</h4>
                        <div class="formula" data-i18n="report_general.construction.metrics_explanation.completion_rate.formula">
                            Tasa de Finalización = (Jugadores que completaron / Total de jugadores) × 100
                        </div>
                    </div>
                    <p class="interpretation" data-i18n="report_general.construction.metrics_explanation.completion_rate.interpretation">
                        Una tasa del 66.67% indica que dos tercios de los jugadores han completado exitosamente todos los requisitos.
                    </p>
                </div>


                <!-- Tiempo Promedio por Requisito -->
                <div class="metric-detail">
                    <h3 class="text-primary" data-i18n="report_general.construction.metrics_explanation.average_time.title">Tiempo Promedio por Requisito</h3>
                    <p data-i18n="report_general.construction.metrics_explanation.average_time.description">
                        Representa el tiempo promedio que les toma a los jugadores completar correctamente cada requisito, considerando todos sus intentos hasta lograrlo.
                    </p>
                    <div class="formula-section">
                        <h4>Cálculo:</h4>
                        <div class="formula" data-i18n="report_general.construction.metrics_explanation.average_time.formula.step1">
                            1. Tiempo total por requisito del jugador = Suma de tiempos de todos los intentos en el requisito
                        </div>
                        <div class="formula" data-i18n="report_general.construction.metrics_explanation.average_time.formula.step2">
                            2. Tiempo promedio por requisito = Suma de tiempos totales de todos los jugadores / Total de jugadores
                        </div>
                    </div>
                    <p class="interpretation" data-i18n="report_general.construction.metrics_explanation.average_time.interpretation">
                        Esta métrica ayuda a identificar qué requisitos toman más tiempo en ser construidos correctamente, lo que puede indicar su nivel de complejidad.
                    </p>
                </div>

                <!-- Índice de Dificultad -->
                <div class="metric-detail">
                    <h3 class="text-primary" data-i18n="report_general.construction.metrics_explanation.difficulty_index.title">Índice de Dificultad</h3>
                    <p data-i18n="report_general.construction.metrics_explanation.difficulty_index.description">
                        Usa el promedio de intentos para determinar qué requisitos son más desafiantes.
                    </p>
                    <div class="formula-section">
                        <h4>Cálculo:</h4>
                        <div class="formula" data-i18n="report_general.construction.metrics_explanation.difficulty_index.formula">
                            Promedio de Intentos = Total de intentos por requisito / Número de jugadores
                        </div>
                    </div>
                    <p class="interpretation" data-i18n="report_general.construction.metrics_explanation.difficulty_index.interpretation">
                        Un promedio más alto de intentos indica mayor dificultad en la construcción del requisito.
                    </p>
                </div>
            </div>
        </section>

        <section class="general-section">
            <!-- Tabla de tiempos por requisito -->
            <div class="requirements-time">
                <h3 class="text-primary font-bold" data-i18n="report_general.construction.resume_players.table.title">Resumen Jugadores</h3>
                <div class="requirements-table">
                    <table>
                        <thead>
                            <tr>
                                <th data-i18n="report_general.construction.resume_players.table.columns.player">Jugador</th>
                                <th class="data-cell" data-i18n="report_general.construction.resume_players.table.columns.time">Tiempo total</th>
                                <th class="data-cell" data-i18n="report_general.construction.resume_players.table.columns.attempts">Intentos</th>
                                <th class="data-cell" data-i18n="report_general.construction.resume_players.table.columns.last_attempt">Último Intento</th>
                                <th class="data-cell" data-i18n="report_general.construction.resume_players.table.columns.progress">Avance</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($data['data_players'] as $req): ?>
                                <tr>
                                    <td class="requirement-cell">
                                        <div class="requirement-text"><?= $req['jugador'] ?></div>
                                    </td>
                                    <td class="data-cell"><?= $req['tiempo'] ?></td>
                                    <td class="data-cell"><?= $req['intentos'] ?></td>
                                    <td class="data-cell"><?= $req['ultimo_intento'] ?></td>
                                    <td class="data-cell">
                                        <div class="completion-bar">
                                            <div class="bar" style="width: <?= $req['avance'] ?>%"></div>
                                            <span class="completion-text"><?= $req['avance'] ?>%</span>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <!-- Sección de Análisis de Tiempo -->
        <section class="general-section time-analysis">
            <h2 class="text-primary font-bold" data-i18n="report_general.construction.time.title">Análisis de Tiempo</h2>

            <div class="time-overview">
                <!-- Indicadores principales de tiempo -->
                <div class="stat-cards">
                    <div class="stat-card">
                        <div class="metric-info">
                            <span class="metric-value"><i class="ri-time-line"></i> <?= $data['report_data']['timeAnalysis']['averageTime'] ?></span>
                            <span class="metric-label" data-i18n="report_general.construction.time.metrics.average">Tiempo Promedio</span>
                            <span class="metric-subtitle" data-i18n="report_general.construction.time.metrics.per_requirement">por requisito</span>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="metric-info">
                            <span class="metric-value"><i class="ri-timer-flash-line"></i> <?= $data['report_data']['timeAnalysis']['bestTime'] ?></span>
                            <span class="metric-label" data-i18n="report_general.construction.time.metrics.best">Mejor Tiempo</span>
                            <span class="metric-subtitle"><?= $data['report_data']['timeAnalysis']['bestTimePlayer'] ?></span>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="metric-info">
                            <span class="metric-value"><i class="ri-time-fill"></i> <?= $data['report_data']['timeAnalysis']['totalTimeInvested'] ?></span>
                            <span class="metric-label" data-i18n="report_general.construction.time.metrics.total">Tiempo Total Invertido</span>
                            <span class="metric-subtitle" data-i18n="report_general.construction.time.metrics.all_players">todos los jugadores</span>
                        </div>
                    </div>
                </div>

                <!-- Gráfico de distribución de tiempo -->
                <div class="time-distribution">
                    <h3 class="text-primary font-bold" data-i18n="report_general.construction.time.distribution.title">Distribución del Tiempo</h3>
                    <div class="distribution-chart">
                        <canvas id="timeDistributionChart"></canvas>
                    </div>
                </div>

                <!-- Tabla de tiempos por requisito -->
                <div class="requirements-time">
                    <h3 class="text-primary font-bold" data-i18n="report_general.construction.time.requirements.title">Tiempo por Requisito</h3>
                    <div class="requirements-table">
                        <table>
                            <thead>
                                <tr>
                                    <th data-i18n="report_general.construction.time.requirements.columns.requirement">Requisito</th>
                                    <th data-i18n="report_general.construction.time.requirements.columns.avg_time">Tiempo Promedio</th>
                                    <th data-i18n="report_general.construction.time.requirements.columns.completion_rate">Tasa de Finalización</th>
                                    <th data-i18n="report_general.construction.time.requirements.columns.attempts">Intentos Promedio</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($data['report_data']['timeAnalysis']['requirementsTime'] as $req): ?>
                                    <tr>
                                        <td class="requirement-cell">
                                            <div class="requirement-text"><?= $req['description'] ?></div>
                                        </td>
                                        <td class="data-cell"><?= $req['averageTime'] ?></td>
                                        <td class="data-cell">
                                            <div class="completion-bar">
                                                <div class="bar" style="width: <?= $req['completionRate'] ?>%"></div>
                                                <span class="completion-text"><?= $req['completionRate'] ?>%</span>
                                            </div>
                                        </td>
                                        <td class="data-cell"><?= $req['averageAttempts'] ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Insights y Recomendaciones -->
                <div class="time-insights">
                    <h3 class="text-primary font-bold" data-i18n="report_general.construction.time.insights.title">Conclusiones</h3>
                    <div class="insights-content">
                        <p class="insight-text"><?= $data['report_data']['timeAnalysis']['narrative'] ?></p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Sección de Análisis de Dificultad -->
        <section class="general-section difficulty-analysis">
            <h2 class="text-primary font-bold" data-i18n="report_general.construction.difficulty.title">Análisis de Dificultad</h2>

            <div class="difficulty-overview">
                <!-- Top 3 Requisitos Más Desafiantes -->
                <div class="challenging-requirements">
                    <h3 class="text-primary font-bold" data-i18n="report_general.construction.difficulty.challenging_requirements.title">Requisitos Más Desafiantes</h3>
                    <div class="top-requirements">
                        <?php foreach ($data['report_data']['difficultyAnalysis']['challengingRequirements'] as $index => $req): ?>
                            <div class="requirement-card rank-<?= $req['rank'] ?>">
                                <div class="rank-badge">#<?= $req['rank'] ?></div>
                                <div class="requirement-content">
                                    <div class="requirement-description">
                                        <?= $req['description'] ?>
                                    </div>
                                    <div class="requirement-stats">
                                        <div class="stat-item">
                                            <i class="ri-refresh-line"></i>
                                            <span class="stat-value"><?= number_format($req['averageAttempts'], 1) ?></span>
                                            <span class="stat-label" data-i18n="report_general.construction.difficulty.challenging_requirements.metrics.avg_attempts">Promedio de Intentos</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Estadísticas de Intentos -->
                <div class="attempts-statistics">
                    <h3 class="text-primary font-bold" data-i18n="report_general.construction.difficulty.attempts_stats.title">Estadísticas de Intentos</h3>
                    <div class="stat-cards">
                        <!-- Mínimo de Intentos -->
                        <div class="stat-card">
                            <div class="metric-info">
                                <span class="metric-value">
                                    <i class="ri-arrow-down-circle-line"></i>
                                    <?= $data['report_data']['difficultyAnalysis']['attemptsStats']['minAttempts'] ?>
                                </span>
                                <span class="metric-label" data-i18n="report_general.construction.difficulty.attempts_stats.metrics.min_attempts">Mínimo de Intentos</span>
                                <span class="metric-subtitle">
                                    <span data-i18n="report_general.construction.difficulty.attempts_stats.metrics.by">por</span>
                                    <?= $data['report_data']['difficultyAnalysis']['attemptsStats']['minAttemptsPlayer'] ?>
                                </span>
                            </div>
                        </div>

                        <!-- Máximo de Intentos -->
                        <div class="stat-card">
                            <div class="metric-info">
                                <span class="metric-value">
                                    <i class="ri-arrow-up-circle-line"></i>
                                    <?= $data['report_data']['difficultyAnalysis']['attemptsStats']['maxAttempts'] ?>
                                </span>
                                <span class="metric-label" data-i18n="report_general.construction.difficulty.attempts_stats.metrics.max_attempts">Máximo de Intentos</span>
                                <span class="metric-subtitle">
                                    <span data-i18n="report_general.construction.difficulty.attempts_stats.metrics.by">por</span>
                                    <?= $data['report_data']['difficultyAnalysis']['attemptsStats']['maxAttemptsPlayer'] ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Descripción Narrativa -->
                <div class="difficulty-insights">
                    <p class="insight-text">
                        <?= $data['report_data']['difficultyAnalysis']['narrative'] ?>
                    </p>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="report-footer">
            <div class="footer-container">
                <span class="footer-line text-primary font-bold" style="display: flex;">
                    <p data-i18n="report_general.footer.generated_by"> Reporte generado por</p> &nbsp;
                    <p><?= name_project(); ?></p>
                </span>
                <span class="footer-line text-color">
                    <?php
                    echo date('Y-m-d H:i:s');
                    ?>
                </span>
            </div>
        </footer>
    </div>


    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const progressCtx = document.getElementById('progressChart').getContext('2d');

            // Datos del gráfico
            const progressData = {
                labels: [
                    'Completado',
                    'En Progreso',
                    'Sin Iniciar'
                ],
                datasets: [{
                    data: [
                        <?= $data['report_data']['progress']['completedPercentage'] ?>,
                        <?= $data['report_data']['progress']['inProgressPercentage'] ?>,
                        <?= $data['report_data']['progress']['notStartedPercentage'] ?>
                    ],
                    backgroundColor: [
                        'rgba(76, 175, 80, 0.8)', // Verde para completado
                        'rgba(255, 193, 7, 0.8)', // Amarillo para en progreso
                        'rgba(189, 37, 37, 0.8)' // Gris para sin iniciar
                    ],
                    borderColor: [
                        'rgba(76, 175, 80, 1)',
                        'rgba(255, 193, 7, 1)',
                        'rgba(189, 37, 37, 1)'
                    ],
                    borderWidth: 1
                }]
            };

            // Opciones del gráfico
            const progressOptions = {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false // Ocultamos la leyenda ya que tenemos los indicadores personalizados
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return `${context.label}: ${Math.round(context.raw)}%`;
                            }
                        }
                    }
                },
                cutout: '65%', // Hace el donut más delgado
                animation: {
                    animateRotate: true,
                    animateScale: true
                }
            };

            // Creación del gráfico
            new Chart(progressCtx, {
                type: 'doughnut',
                data: progressData,
                options: progressOptions
            });

            // Agregar en el script existente, después del gráfico de progreso
            const timeDistributionCtx = document.getElementById('timeDistributionChart').getContext('2d');

            const timeData = <?= json_encode($data['report_data']['timeAnalysis']['distributionData']) ?>;

            const timeDistributionOptions = {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Número de estudiantes'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Rango de tiempo'
                        }
                    }
                }
            };

            new Chart(timeDistributionCtx, {
                type: 'bar',
                data: timeData,
                options: timeDistributionOptions
            });
        });
    </script>
</body>

</html>