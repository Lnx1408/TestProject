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
            <h2 class="text-primary font-bold" data-i18n="report_general.classification.summary.title">Resumen General</h2>

            <!-- Métricas Principales -->
            <div class="stat-cards">
                <!-- Total de Jugadores -->
                <div class="stat-card">
                    <div class="metric-info">
                        <span class="metric-value"><i class="ri-group-line"></i> <?= $data['report_data']['summary']['totalPlayers'] ?></span>
                        <span class="metric-label" data-i18n="report_general.classification.summary.metrics.players">Total Jugadores</span>
                    </div>
                </div>

                <!-- Precisión Promedio Primer Intento -->
                <div class="stat-card">
                    <div class="metric-info">
                        <span class="metric-value"><i class="ri-focus-2-line"></i> <?= $data['report_data']['summary']['firstAttemptAccuracy'] ?>%</span>
                        <span class="metric-label" data-i18n="report_general.classification.summary.metrics.first_attempt">Precisión Primer Intento</span>
                        <div class="metric-distribution">
                            <div class="progress-mini">
                                <div class="progress-bar" style="width: <?= $data['report_data']['summary']['firstAttemptAccuracy'] ?>%"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tiempo Promedio -->
                <div class="stat-card">
                    <div class="metric-info">
                        <span class="metric-value"><i class="ri-time-line"></i> <?= $data['report_data']['summary']['averageCompletionTime'] ?></span>
                        <span class="metric-label" data-i18n="report_general.classification.summary.metrics.avg_time">Tiempo Promedio</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- Estado de Avance -->
        <section class="general-section">
            <div class="progress-section">
                <div>
                    <h3 class="text-primary font-bold" data-i18n="report_general.classification.summary.progress.title">Estado de Avance</h3>
                    <div class="progress-overview">
                        <!-- Gráfico Circular -->
                        <div class="progress-chart">
                            <canvas id="progressChart"></canvas>
                        </div>

                        <!-- Detalles del Estado -->
                        <div class="progress-details">
                            <div class="status-group">
                                <div class="status-item completed">
                                    <div class="status-indicator"></div>
                                    <div class="status-info">
                                        <span class="status-label" data-i18n="report_general.classification.summary.progress.completed">Completado</span>
                                        <span class="status-count"><?= $data['report_data']['progress']['completed'] ?></span>
                                        <span class="status-percentage"><?= $data['report_data']['progress']['completedPercentage'] ?>%</span>
                                    </div>
                                </div>

                                <div class="status-item in-progress">
                                    <div class="status-indicator"></div>
                                    <div class="status-info">
                                        <span class="status-label" data-i18n="report_general.classification.summary.progress.in_progress">En Progreso</span>
                                        <span class="status-count"><?= $data['report_data']['progress']['inProgress'] ?></span>
                                        <span class="status-percentage"><?= $data['report_data']['progress']['inProgressPercentage'] ?>%</span>
                                    </div>
                                </div>

                                <div class="status-item not-started">
                                    <div class="status-indicator"></div>
                                    <div class="status-info">
                                        <span class="status-label" data-i18n="report_general.classification.summary.progress.not_started">Sin Iniciar</span>
                                        <span class="status-count"><?= $data['report_data']['progress']['notStarted'] ?></span>
                                        <span class="status-percentage"><?= $data['report_data']['progress']['notStartedPercentage'] ?>%</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Descripción Narrativa -->
                    <div class="progress-narrative">
                        <p><?= $data['report_data']['progress']['narrative'] ?></p>
                    </div>
                </div>
                <div>
                    <div class="attempts-distribution">
                        <h3 class="text-primary font-bold" data-i18n="report_general.classification.summary.distribution.title">Distribución de Intentos</h3>
                        <div class="distribution-cards">
                            <div class="distribution-card one-attempt">
                                <div class="percentage"><?= $data['report_data']['summary']['attempts']['oneAttemptPercentage'] ?>%</div>
                                <div class="count"><?= $data['report_data']['summary']['attempts']['oneAttempt'] ?> <span data-i18n="report_general.classification.summary.distribution.playerlabel">jugadores</span></div>
                                <div class="label" data-i18n="report_general.classification.summary.distribution.one_attempt">1 Intento</div>
                            </div>

                            <div class="distribution-card two-three-attempts">
                                <div class="percentage"><?= $data['report_data']['summary']['attempts']['twoThreeAttemptsPercentage'] ?>%</div>
                                <div class="count"><?= $data['report_data']['summary']['attempts']['twoThreeAttempts'] ?> <span data-i18n="report_general.classification.summary.distribution.playerlabel">jugadores</span></div>
                                <div class="label" data-i18n="report_general.classification.summary.distribution.two_three_attempts">2-3 Intentos</div>
                            </div>

                            <div class="distribution-card more-attempts">
                                <div class="percentage"><?= $data['report_data']['summary']['attempts']['moreThanThreePercentage'] ?>%</div>
                                <div class="count"><?= $data['report_data']['summary']['attempts']['moreThanThree'] ?> <span data-i18n="report_general.classification.summary.distribution.playerlabel">jugadores</span></div>
                                <div class="label" data-i18n="report_general.classification.summary.distribution.more_attempts">Más de 3 Intentos</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- EXPLICACION METRICAS-->
        <section class="general-section">
            <h2 class="text-primary font-bold" style="margin: 0;" data-i18n="report_general.classification.metrics_explanation.title">Explicación de Métricas</h2>
            <div class="metrics-general-explanation">
                <!-- Precisión del Primer Intento -->
                <div class="metric-detail">
                    <h3 class="text-primary" data-i18n="report_general.classification.metrics_explanation.first_attempt_accuracy.title">
                        Precisión del Primer Intento
                    </h3>
                    <p data-i18n="report_general.classification.metrics_explanation.first_attempt_accuracy.description">
                        Representa el porcentaje de requisitos que fueron clasificados correctamente en el primer intento por los jugadores.
                    </p>
                    <div class="formula">
                        <span data-i18n="report_general.classification.metrics_explanation.first_attempt_accuracy.formula">
                            Precisión = (Requisitos correctamente clasificados en primer intento / Total de requisitos) × 100
                        </span>
                    </div>
                    <p class="interpretation" data-i18n="report_general.classification.metrics_explanation.first_attempt_accuracy.interpretation">
                        Un valor más alto indica una mejor comprensión inicial de la diferencia entre requisitos ambiguos y no ambiguos.
                    </p>
                </div>
                <!-- Tasa de Error -->
                <div class="metric-detail">
                    <h3 class="text-primary" data-i18n="report_general.classification.metrics_explanation.error_rate.title">
                        Tasa de Error por Requisito
                    </h3>
                    <p data-i18n="report_general.classification.metrics_explanation.error_rate.description">
                        Indica el porcentaje de jugadores que clasificaron incorrectamente un requisito específico.
                    </p>
                    <div class="formula">
                        <span data-i18n="report_general.classification.metrics_explanation.error_rate.formula">
                            Tasa de Error = (Jugadores que clasificaron incorrectamente / Total de jugadores) × 100
                        </span>
                    </div>
                    <p class="interpretation" data-i18n="report_general.classification.metrics_explanation.error_rate.interpretation">
                        Una tasa alta puede indicar que el requisito es particularmente desafiante o puede necesitar clarificación.
                    </p>
                </div>
                <!-- Movimientos Promedio -->
                <div class="metric-detail">
                    <h3 class="text-primary" data-i18n="report_general.classification.metrics_explanation.average_movements.title">
                        Movimientos Promedio
                    </h3>
                    <p data-i18n="report_general.classification.metrics_explanation.average_movements.description">
                        Representa la cantidad media de veces que los jugadores movieron cada requisito durante el proceso de clasificación.
                    </p>
                    <div class="formula">
                        <span data-i18n="report_general.classification.metrics_explanation.average_movements.formula">
                            Movimientos Promedio = Total de movimientos de clasificación / Número de intentos
                        </span>
                    </div>
                    <p class="interpretation" data-i18n="report_general.classification.metrics_explanation.average_movements.interpretation">
                        Un número mayor de movimientos puede indicar indecisión o dificultad en la clasificación.
                    </p>
                </div>

                <!-- Porcentaje de Avance -->
                <div class="metric-detail">
                    <h3 class="text-primary" data-i18n="report_general.classification.metrics_explanation.progress_percentage.title">
                        Porcentaje de Avance
                    </h3>
                    <p data-i18n="report_general.classification.metrics_explanation.progress_percentage.description">
                        Mide el progreso general de los jugadores en la clasificación de todos los requisitos del juego.
                    </p>
                    <div class="formula">
                        <span data-i18n="report_general.classification.metrics_explanation.progress_percentage.formula">
                            Porcentaje de Avance = (Requisitos correctamente clasificados / Total de requisitos) × 100
                        </span>
                    </div>
                    <p class="interpretation" data-i18n="report_general.classification.metrics_explanation.progress_percentage.interpretation">
                        Representa el nivel de completitud del juego para cada jugador.
                    </p>
                </div>
            </div>
        </section>

        <!-- RESUMEN JUGADORES-->
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

        <!-- Analisis de requisitos-->
        <section class="general-section requirements-analysis">
            <h2 class="text-primary font-bold" data-i18n="report_general.classification.requirements.title">Análisis de Requisitos</h2>

            <!-- Distribución de Requisitos -->
            <div class="requirements-distribution">
                <div class="distribution-summary">
                    <div class="requirement-type ambiguous">
                        <span class="type-count"><?= $data['report_data']['requirementsAnalysis']['distribution']['ambiguous'] ?></span>
                        <span class="type-label">Ambiguos</span>
                    </div>
                    <div class="requirement-type non-ambiguous">
                        <span class="type-count"><?= $data['report_data']['requirementsAnalysis']['distribution']['nonAmbiguous'] ?></span>
                        <span class="type-label">No Ambiguos</span>
                    </div>
                </div>
            </div>

            <!-- Tabla de Requisitos -->
            <div class="requirements-table">
                <table>
                    <thead>
                        <tr>
                            <th class="data-cell" data-i18n="report_general.classification.requirements.table.requirement">Requisito</th>
                            <th class="data-cell" data-i18n="report_general.classification.requirements.table.avg_time">Tiempo Promedio</th>
                            <th class="data-cell" data-i18n="report_general.classification.requirements.table.avg_moves">Movimientos Promedio</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($data['report_data']['requirementsAnalysis']['requirements'] as $req): ?>
                            <tr>
                                <td class="requirement-cell">
                                    <div class="requirement-text"><?= $req['description'] ?></div>
                                    <span class="requirement-type <?= $req['isAmbiguous'] ? 'ambiguous' : 'non-ambiguous' ?>">
                                        <?= $req['isAmbiguous'] ? 'Ambiguo' : 'No Ambiguo' ?>
                                    </span>
                                </td>
                                <td class="data-cell"><?= $req['avgTime'] ?></td>
                                <td class="data-cell"><?= $req['avgMoves'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="challenging-requirements">
                <h3 class="text-primary font-bold">Requisitos Más Desafiantes</h3>
                <div class="challenging-req-container">
                    <?php foreach ($data['report_data']['requirementsAnalysis']['mostChallenging'] as $req): ?>
                        <div class="challenging-req-card">
                            <div class="req-description"><?= $req['description'] ?></div>
                            <div class="req-stats">
                                <div class="error-rate">
                                    <i class='bx bx-error-circle'></i>
                                    <span>Tasa de error: <?= $req['errorRate'] ?>%</span>
                                </div>
                                <div class="players-affected">
                                    <i class='bx bx-user-x'></i>
                                    <span><?= $req['playersWithErrors'] ?> de <?= $req['totalPlayers'] ?> jugadores</span>
                                </div>
                                <div class="ambiguity-status">
                                    <i class='bx bx-question-mark'></i>
                                    <span><?= $req['isAmbiguous'] ? 'Ambiguo' : 'No Ambiguo' ?></span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>


        <!-- Sección de Análisis de Tiempo -->
        <section class="general-section time-analysis">
            <h2 class="text-primary font-bold" data-i18n="report_general.classification.time.title">Análisis de Tiempo</h2>

            <div class="time-overview">
                <!-- Indicadores principales de tiempo -->
                <div class="stat-cards">
                    <div class="stat-card">
                        <div class="metric-info">
                            <span class="metric-value"><i class="ri-time-line"></i> <?= $data['report_data']['timeAnalysis']['averageTime'] ?></span>
                            <span class="metric-label" data-i18n="report_general.classification.time.metrics.average">Tiempo Promedio</span>
                            <span class="metric-subtitle" data-i18n="report_general.classification.time.metrics.subaverage">por requisito</span>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="metric-info">
                            <span class="metric-value"><i class="ri-timer-flash-line"></i> <?= $data['report_data']['timeAnalysis']['bestTime']['time'] ?></span>
                            <span class="metric-label" data-i18n="report_general.classification.time.metrics.best">Mejor Tiempo</span>
                            <span class="metric-subtitle"><?= $data['report_data']['timeAnalysis']['bestTime']['playerName'] ?></span>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="metric-info">
                            <span class="metric-value"><i class="ri-timer-line"></i> <?= $data['report_data']['timeAnalysis']['worstTime']['time'] ?></span>
                            <span class="metric-label" data-i18n="report_general.classification.time.metrics.worst">Peor Tiempo</span>
                            <span class="metric-subtitle"><?= $data['report_data']['timeAnalysis']['worstTime']['playerName'] ?></span>
                        </div>
                    </div>
                </div>

                <!-- Gráfico de distribución de tiempo -->
                <div class="time-distribution">
                    <h3 class="text-primary font-bold" data-i18n="report_general.classification.time.distribution.title">Distribución del Tiempo</h3>
                    <div class="distribution-chart">
                        <canvas id="timeDistributionChart"></canvas>
                    </div>
                </div>


                <!-- Insights y Recomendaciones -->
                <div class="time-insights">
                    <div class="insights-content">
                        <p class="insight-text"><?= $data['report_data']['timeAnalysis']['narrative'] ?></p>
                    </div>
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


            const timeDistributionCtx = document.getElementById('timeDistributionChart').getContext('2d');
            new Chart(timeDistributionCtx, {
                type: 'bar',
                data: {
                    labels: <?= json_encode(array_column($data['report_data']['timeAnalysis']['distribution'], 'range')) ?>,
                    datasets: [{
                        label: 'Número de estudiantes',
                        data: <?= json_encode(array_column($data['report_data']['timeAnalysis']['distribution'], 'count')) ?>,
                        backgroundColor: 'rgba(75, 192, 192, 0.6)',
                        borderColor: 'rgba(75, 192, 192, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
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
                }
            });
        });
    </script>
</body>

</html>