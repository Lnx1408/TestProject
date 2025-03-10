<!DOCTYPE html>
<html lang="<?= $lang ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte_<?= $data['report_data']['playerInfo']['gameCode'] ?>_<?= date('Y-m-d') ?></title>
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
                        <span class="title-line" data-i18n="report.title.line1">Reporte</span>
                        <span class="title-line title-bold" data-i18n="report.title.line2">Descriptivo</span>
                    </h1>
                </div>
                <div class="header-logo">
                    <img src="<?= media() ?>/images/twitch.png" alt="Logo" class="logo">
                </div>
            </div>
            <div class="player-info">
                <div>
                    <h2 class="player-name"><?= $data['report_data']['playerInfo']['name'] ?></h2>
                </div>
                <div>
                    <div class="game-code-container">
                        <span class="code-label" data-i18n="report.game_code.label">Código del juego:</span>
                        <span class="code-value"><?= $data['report_data']['playerInfo']['gameCode'] ?></span>
                    </div>
                </div>
            </div>
        </header>

        <!-- Sección de Estadísticas Principales -->
        <section class="main-stats">
            <h3 data-i18n="report.sections.game_details" class="text-color-primary">Desglose de la Partida</h3>
            <div class="stat-cards">
                <div class="stat-card time">
                    <span class="stat-value">
                        <?= $data['report_data']['gameOverview']['totalTime']['minutes'] ?>min<br>
                        <?= $data['report_data']['gameOverview']['totalTime']['seconds'] ?>s
                    </span>
                    <span class="stat-label font-s-12" data-i18n="report.stats.time">Tiempo</span>
                </div>
                <div class="stat-card attempts">
                    <span class="stat-value"><?= $data['report_data']['gameOverview']['attempts'] ?></span>
                    <span class="stat-label font-s-12" data-i18n="report.stats.attempts">Intentos</span>
                </div>

                <!-- Promedio de Requisitos Correctos -->
                <div class="stat-card average">
                    <div class="average-display">
                        <span class="stat-value"><?= number_format($data['report_data']['gameOverview']['averageCorrect'], 1) ?></span>
                        <span class="unit">req/intento</span>
                    </div>
                    <span class="stat-label font-s-12" data-i18n="report.stats.average_correct">Promedio Requisitos Correctos</span>
                </div>
                <!-- Consistencia -->
                <div class="stat-card consistency">
                    <div class="progress-circle">
                        <svg viewBox="0 0 36 36">
                            <path d="M18 2.0845
                                a 15.9155 15.9155 0 0 1 0 31.831
                                a 15.9155 15.9155 0 0 1 0 -31.831"
                                fill="none"
                                stroke="#eee"
                                stroke-width="3" />
                            <path d="M18 2.0845
                                a 15.9155 15.9155 0 0 1 0 31.831
                                a 15.9155 15.9155 0 0 1 0 -31.831"
                                fill="none"
                                stroke="#4b6cc1"
                                stroke-width="3"
                                stroke-dasharray="<?= $data['report_data']['gameOverview']['consistency'] ?>, 100" />
                        </svg>
                        <span class="percentage"><?= number_format($data['report_data']['gameOverview']['consistency'], 1) ?>%</span>
                    </div>
                    <span class="stat-label font-s-12" data-i18n="report.stats.consistency">Consistencia</span>
                </div>
            </div>

            <div class="metrics-explanation">
                <div class="metric-detail">
                    <h4 data-i18n="report.metrics_explanation.average_title">Promedio de Requisitos Correctos</h4>
                    <p data-i18n="report.metrics_explanation.average_description">Representa la media de requisitos clasificados correctamente por intento. Se calcula:</p>
                    <div class="formula" data-i18n="report.metrics_explanation.average_formula">
                        Promedio = (Total de requisitos correctos) / (Número total de intentos)
                    </div>
                    <p class="interpretation" data-i18n="report.metrics_explanation.average_interpretation">
                        Un valor más alto indica mejor precisión en la clasificación de requisitos.
                    </p>
                </div>

                <div class="metric-detail">
                    <!--
                    <h4 data-i18n="report.metrics_explanation.consistency_title">Índice de Consistencia</h4>
                    <p data-i18n="report.metrics_explanation.consistency_description">Mide la estabilidad del desempeño entre intentos consecutivos. Se calcula mediante:</p>
                    <div class="formula" data-i18n="report.metrics_explanation.consistency_formula">
                        Consistencia = 100% - (Variación promedio entre intentos consecutivos)
                    </div>
                    <p class="interpretation" data-i18n="report.metrics_explanation.consistency_interpretation">
                        Un valor más alto indica un desempeño más estable y predecible.
                    </p>
                    -->
                    <h4 data-i18n="report.metrics_explanation.consistency_title">Índice de Consistencia</h4>
                    <p data-i18n="report.metrics_explanation.consistency_description"></p>
                    <div class="formula-section">
                        <div class="formula">
                            <span data-i18n="report.metrics_explanation.stability_formula"></span>
                        </div>
                        <div class="formula">
                            <span data-i18n="report.metrics_explanation.improvement_formula"></span>
                        </div>
                        <div class="formula">
                            <span data-i18n="report.metrics_explanation.consistency_formula"></span>
                        </div>
                    </div>
                    <p class="interpretation" data-i18n="report.metrics_explanation.consistency_interpretation"></p>
                    <ul class="consistency-levels">
                        <li data-i18n="report.metrics_explanation.consistency_levels.0"></li>
                        <li data-i18n="report.metrics_explanation.consistency_levels.1"></li>
                        <li data-i18n="report.metrics_explanation.consistency_levels.2"></li>
                    </ul>
                </div>
            </div>
        </section>

        <!-- Sección de Análisis Narrativo -->
        <section class="narrative-section">
            <h3 data-i18n="report.sections.analysis" class="text-color-primary">Análisis de Desempeño</h3>
            <div class="analysis-content">
                <p class="narrative-text">
                    <?= $data['report_data']['analysis']['narrative'] ?>
                </p>
            </div>
        </section>
        <!-- Tabla de Intentos -->
        <section class="attempts-section">
            <h3 data-i18n="report.sections.attempt_details">Desglose de intentos</h3>
            <table class="attempts-table">
                <thead>
                    <tr>
                        <th data-i18n="report.table.columns.attempt">Intento</th>
                        <th data-i18n="report.table.columns.time">Tiempo</th>
                        <th data-i18n="report.table.columns.movements">Movimientos</th>
                        <th data-i18n="report.table.columns.requirements">Requerimientos</th>
                        <th data-i18n="report.table.columns.correct">Correctos</th>
                        <th data-i18n="report.table.columns.incorrect">Incorrectos</th>
                        <th data-i18n="report.table.columns.precision_success">Precision Aciertos</th>
                        <th data-i18n="report.table.columns.precision_errors">Precision Errores</th>
                        <th data-i18n="report.table.columns.precision_progressive">Progresion Progresiva</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data['report_data']['attemptsDetails'] as $attempt): ?>
                        <tr>
                            <td><?= $attempt['attempt'] ?></td>
                            <td><?= $attempt['time'] ?></td>
                            <td><?= $attempt['movements'] ?></td>
                            <td><?= $attempt['requirements'] ?></td>
                            <td><?= $attempt['correct'] ?></td>
                            <td><?= $attempt['incorrect'] ?></td>
                            <td><?= $attempt['successPrecision'] ?>%</td>
                            <td><?= $attempt['errorPrecision'] ?>%</td>
                            <td><?= $attempt['progressivePrecision'] ?>%</td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="table-explanation">
                <h4 data-i18n="report.table.explanation.title" class="text-color-primary">Descripción de las columnas</h4>
                <div class="columns-grid">
                    <div class="column-info">
                        <h5 data-i18n="report.table.columns.attempt" class="text-color-primary">Intento</h5>
                        <p data-i18n="report.table.explanation.attempt">Número secuencial que indica el orden de cada intento realizado.</p>
                    </div>

                    <div class="column-info">
                        <h5 data-i18n="report.table.columns.time" class="text-color-primary">Tiempo</h5>
                        <p data-i18n="report.table.explanation.time">Duración total empleada en completar el intento.</p>
                    </div>

                    <div class="column-info">
                        <h5 data-i18n="report.table.columns.movements" class="text-color-primary">Movimientos</h5>
                        <p data-i18n="report.table.explanation.movements">Número total de acciones de clasificación realizadas durante el intento.</p>
                    </div>

                    <div class="column-info">
                        <h5 data-i18n="report.table.columns.requirements" class="text-color-primary">Requerimientos</h5>
                        <p data-i18n="report.table.explanation.requirements">Total de requisitos disponibles para clasificar en el intento.</p>
                    </div>

                    <div class="column-info">
                        <h5 data-i18n="report.table.columns.correct" class="text-color-primary">Correctos</h5>
                        <p data-i18n="report.table.explanation.correct">Cantidad de requisitos clasificados correctamente.</p>
                    </div>

                    <div class="column-info">
                        <h5 data-i18n="report.table.columns.incorrect" class="text-color-primary">Incorrectos</h5>
                        <p data-i18n="report.table.explanation.incorrect">Cantidad de requisitos clasificados de manera incorrecta.</p>
                    </div>

                    <div class="column-info">
                        <h5 data-i18n="report.table.columns.precision_success" class="text-color-primary">Precisión Aciertos</h5>
                        <p data-i18n="report.table.explanation.precision_success">Porcentaje de precisión en las clasificaciones correctas: (Correctos/Total) × 100</p>
                    </div>

                    <div class="column-info">
                        <h5 data-i18n="report.table.columns.precision_errors" class="text-color-primary">Precisión Errores</h5>
                        <p data-i18n="report.table.explanation.precision_errors">Porcentaje de clasificaciones incorrectas: (Incorrectos/Total) × 100</p>
                    </div>

                    <div class="column-info">
                        <h5 data-i18n="report.table.columns.precision_progressive" class="text-color-primary">Precisión Progresiva</h5>
                        <p data-i18n="report.table.explanation.precision_progressive">Medida acumulativa del progreso en la precisión a lo largo de los intentos.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Nueva sección de descripción de intentos -->
        <section class="attempts-narrative">
            <h3 data-i18n="report.sections.attempts_description" class="text-color-primary">Descripción Detallada de Intentos</h3>
            <p>
                <span data-i18n="report.summary.player_prefix">El jugador</span>
                <strong><?= $data['report_data']['playerInfo']['name'] ?></strong>
                <span data-i18n="report.summary.progress_record">ha registrado su progreso en el juego, acumulando un tiempo total jugado de</span>
                <strong>
                    <?= $data['report_data']['summary']['totalTime']['minutes'] ?>
                    <span data-i18n="report.summary.minutes">minutos</span>
                    <?= $data['report_data']['summary']['totalTime']['seconds'] ?>
                    <span data-i18n="report.summary.seconds">segundos</span>
                </strong>
                <span data-i18n="report.summary.distributed_in">distribuidos en</span>
                <strong><?= $data['report_data']['summary']['totalAttempts'] ?>
                    <span data-i18n="report.summary.attempts">intentos</span>.
                </strong><span data-i18n="report.summary.game_identified">La partida está identificada bajo el código único</span>
                <strong><?= $data['report_data']['summary']['gameCode'] ?></strong>
                <span data-i18n="report.summary.last_attempt_prefix">y su último intento fue realizado el</span>
                <strong><?= $data['report_data']['summary']['lastAttemptDate'] ?></strong>.
            </p>
            <div class="attempts-narrative-content">
                <?php foreach ($data['report_data']['attemptsDetails'] as $index => $attempt): ?>
                    <?php
                    // Calcular porcentaje de mejora respecto al intento anterior
                    $previousPrecision = $index > 0 ? $data['report_data']['attemptsDetails'][$index - 1]['successPrecision'] : 0;
                    $currentPrecision = floatval($attempt['successPrecision']);
                    $improvementPercentage = $index > 0 ? $currentPrecision - $previousPrecision : 0;

                    // Extraer minutos y segundos del tiempo
                    $timeArray = explode(' ', $attempt['time']);
                    $minutes = intval(str_replace('min', '', $timeArray[0]));
                    $seconds = intval(str_replace('s', '', $timeArray[1]));
                    ?>

                    <div class="attempt-description">
                        <p>
                            <span data-i18n="report.attempts_narrative.during_attempt">Durante el intento</span>
                            <?= $attempt['attempt'] ?>
                            <span data-i18n="report.attempts_narrative.spent">empleó</span>
                            <?= $minutes ?>
                            <span data-i18n="report.attempts_narrative.minutes">minutos</span>
                            <?= str_pad($seconds, 2, '0', STR_PAD_LEFT) ?>
                            <span data-i18n="report.attempts_narrative.seconds">segundos</span>
                            <span data-i18n="report.attempts_narrative.and_made">y realizó</span>
                            <?= $attempt['movements'] ?>
                            <span data-i18n="report.attempts_narrative.movements">movimientos</span>
                            <span data-i18n="report.attempts_narrative.to_classify">para clasificar los</span>
                            <?= $attempt['requirements'] ?>
                            <span data-i18n="report.attempts_narrative.available_requirements">requisitos que tenía disponibles</span>.
                            <span data-i18n="report.attempts_narrative.managed_to_classify">Logró clasificar correctamente</span>
                            <?= $attempt['correct'] ?>
                            <span data-i18n="report.attempts_narrative.requirements">requisitos</span>,
                            <span data-i18n="report.attempts_narrative.leaving">dejando</span>
                            <?= $attempt['incorrect'] ?>
                            <span data-i18n="report.attempts_narrative.incorrect">incorrectos</span>,
                            <span data-i18n="report.attempts_narrative.resulting_in">lo que resultó en una precisión del</span>
                            <?= number_format($attempt['successPrecision'], 2) ?>%
                            <?php if ($index > 0): ?>
                                <span data-i18n="report.attempts_narrative.and_a">y un</span>
                                <?= $improvementPercentage >= 0 ?
                                    '<span data-i18n="report.attempts_narrative.increase">aumento</span>' :
                                    '<span data-i18n="report.attempts_narrative.decrease">descenso</span>'
                                ?>
                                <span data-i18n="report.attempts_narrative.of">del</span>
                                <?= abs(number_format($improvementPercentage, 2)) ?>%
                                <span data-i18n="report.attempts_narrative.in_precision">en la precisión</span>
                                <span data-i18n="report.attempts_narrative.compared_to_previous">con respecto al intento anterior</span>
                                <?php endif; ?>.
                        </p>

                        <!-- Nueva sección de requisitos -->
                        <div class="requirements-detail">
                            <h4 data-i18n="report.attempts_narrative.classified_requirements" class="text-color-primary">Requisitos Clasificados</h4>
                            <div class="requirements-list">
                                <?php foreach ($attempt['requeriments'] as $requisito): ?>
                                    <div class="requirement-item <?= $requisito['es_correcto'] ? 'correct' : 'incorrect' ?>">
                                        <div class="requirement-content">
                                            <div class="requirement-header">
                                                <span class="status-icon">
                                                    <i class='bx <?= $requisito['es_correcto'] ? 'bx-check' : 'bx-x' ?>'></i>
                                                </span>
                                                <p class="requirement-text"><?= htmlspecialchars($requisito['descripcion']) ?></p>
                                            </div>
                                            <div class="requirement-metrics">
                                                <span class="metric">
                                                    <i class='bx bx-move'></i>
                                                    <span data-i18n="report.attempts_narrative.total_moves">Total Movimientos</span>:
                                                    <?= $requisito['cantidad_movimientos'] ?>
                                                </span>
                                                <span class="metric">
                                                    <i class='bx bx-transfer'></i>
                                                    <span data-i18n="report.attempts_narrative.to_ambiguous">A Ambiguo</span>:
                                                    <?= $requisito['movimientos_toAmbiguo'] ?>
                                                </span>
                                                <span class="metric">
                                                    <i class='bx bx-transfer-alt'></i>
                                                    <span data-i18n="report.attempts_narrative.to_non_ambiguous">A No Ambiguo</span>:
                                                    <?= $requisito['movimientos_toNoAmbiguo'] ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- Sección de Gráfico de Progresión -->
        <section class="progression-section">
            <h3 data-i18n="report.sections.progression" class="text-color-primary">Curva de Aprendizaje</h3>
            <div class="chart-container">
                <canvas id="progressionChart"></canvas>
            </div>
        </section>

        <!-- Sección de Recomendaciones -->
        <section class="recommendations-section">
            <h3 data-i18n="report.sections.recommendations" class="text-color-primary">Recomendaciones</h3>
            <div class="recommendations-grid">
                <!-- Recomendación basada en intentos -->
                <div class="recommendation-card attempts-card">
                    <div class="recommendation-icon">
                        <i class='bx bx-target-lock'></i>
                    </div>
                    <div class="recommendation-content">
                        <h4 data-i18n="report.recommendations.attempts">Intentos</h4>
                        <p><?= $data['report_data']['analysis']['recommendations']['attempts'] ?></p>
                    </div>
                </div>

                <!-- Recomendación basada en eficiencia -->
                <div class="recommendation-card efficiency-card">
                    <div class="recommendation-icon">
                        <i class='bx bx-trending-up'></i>
                    </div>
                    <div class="recommendation-content">
                        <h4 data-i18n="report.recommendations.efficiency">Eficiencia</h4>
                        <p><?= $data['report_data']['analysis']['recommendations']['efficiency'] ?></p>
                    </div>
                </div>

                <!-- Recomendación basada en consistencia -->
                <div class="recommendation-card consistency-card">
                    <div class="recommendation-icon">
                        <i class='bx bx-line-chart'></i>
                    </div>
                    <div class="recommendation-content">
                        <h4 data-i18n="report.recommendations.consistency">Consistencia</h4>
                        <p><?= $data['report_data']['analysis']['recommendations']['consistency'] ?></p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="report-footer">
            <div class="footer-container">
                <span class="footer-line text-primary font-bold" style="display: flex;">
                    <p data-i18n="report.footer.generated_by"> Reporte generado por</p> &nbsp;
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
            const chartData = <?= json_encode($data['report_data']['chartData']) ?>;
            const ctx = document.getElementById('progressionChart').getContext('2d');

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: chartData.map(d => `Intento ${d.intento}`),
                    datasets: [{
                        label: 'Precisión Progresiva',
                        data: chartData.map(d => d.precision),
                        borderColor: '#4B6CC1',
                        tension: 0.4,
                        fill: false
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100,
                            title: {
                                display: true,
                                text: 'Precisión (%)'
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });
        });
    </script>
</body>

</html>