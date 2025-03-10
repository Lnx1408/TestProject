<?php ReportNarrative(); ?>

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

        <!-- Estadísticas Generales -->
        <section class="main-stats">
            <h3 data-i18n="report.sections.game_details" class="text-color-primary">Desglose de la Partida</h3>
            <div class="stat-cards">
                <!-- Tiempo Total -->
                <div class="stat-card time">
                    <span class="stat-value">
                        <?= $data['report_data']['generalStats']['totalTime']['minutes'] ?>min<br>
                        <?= $data['report_data']['generalStats']['totalTime']['seconds'] ?>s
                    </span>
                    <span class="stat-label font-s-12" data-i18n="report.stats.time">Tiempo Total</span>
                </div>

                <!-- Total de Intentos -->
                <div class="stat-card attempts">
                    <span class="stat-value"><?= $data['report_data']['generalStats']['totalAttempts'] ?></span>
                    <span class="stat-label font-s-12" data-i18n="report.stats.attempts">Total Intentos</span>
                </div>

                <!-- Promedio de Intentos por Requisito -->
                <div class="stat-card average">
                    <div class="average-display">
                        <span class="stat-value">
                            <?= number_format($data['report_data']['generalStats']['averageAttemptsPerRequirement'], 1) ?>
                        </span>
                        <span class="unit">intentos/req</span>
                    </div>
                    <span class="stat-label font-s-12" data-i18n="report.stats.average_attempts">
                        Promedio de Intentos por Requisito
                    </span>
                </div>

                <!-- Total de Requisitos -->
                <div class="stat-card requirements">
                    <span class="stat-value"><?= $data['report_data']['generalStats']['totalRequirements'] ?></span>
                    <span class="stat-label font-s-12" data-i18n="report.stats.total_requirements">
                        Total de Requisitos
                    </span>
                </div>
            </div>
        </section>

        <!-- Análisis por Requisito -->
        <section class="requirements-analysis attempts-narrative">
            <h3 data-i18n="report.sections.requirements_analysis" class="text-color-primary">
                Análisis por Requisito
            </h3>

            <?php foreach ($data['report_data']['requirementsAnalysis'] as $index => $reqAnalysis): ?>
                <div class="requirement-section">
                    <h4 class="requirement-title">
                        Requisito <?= $index  ?>: <?= $reqAnalysis['requirement'] ?>
                    </h4>
                    <!-- class="attempts-narrative-content" -->
                    <div class="attempt-description">
                        <!-- Resumen del requisito -->
                        <div class="requirement-summary">
                            <!-- Información general del requisito -->
                            <?php
                            $narrator = new ReportNarrativeConstructor();
                            echo $narrator->generateRequirementSummary($reqAnalysis);
                            ?>
                        </div>

                        <!-- Gráfico de progresión -->
                        <?php if (count($reqAnalysis['attempts']) > 1): ?>
                            <div class="requirement-progression">
                                <h5 class="progression-title text-color-primary" data-i18n="report.sections.progression">Curva de Aprendizaje</h5>
                                <div class="chart-container">
                                    <canvas id="progressionChart_<?= $index ?>"></canvas>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Detalles de intentos -->
                        <div class="attempts-detail">
                            <?php foreach ($reqAnalysis['attempts'] as $attempt): ?>
                                <div class="attempt-card">
                                    <div class="attempt-header">
                                        <h5 class="text-color-primary">Intento <?= $attempt['attemptNumber'] ?></h5>
                                        <div class="attempt-narrative">
                                            <?php echo $narrator->generateAttemptDetail($attempt); ?>
                                        </div>
                                    </div>

                                    <!-- Construcción realizada -->
                                    <div class="construction-preview">
                                        <h6>Construcción Realizada</h6>
                                        <div class="fragments-container">
                                            <?php
                                            $orderedFragments = array_filter(
                                                $attempt['fragments'],
                                                fn($f) => $f['posicion_usada'] > 0
                                            );
                                            usort(
                                                $orderedFragments,
                                                fn($a, $b) => $a['posicion_usada'] - $b['posicion_usada']
                                            );

                                            foreach ($orderedFragments as $fragment):
                                            ?>
                                                <div class="fragment-card <?= $fragment['es_correcto'] ? 'correct' : 'incorrect' ?>">
                                                    <div class="fragment-content">
                                                        <div class="fragment-text">
                                                            <?= $fragment['texto'] ?>
                                                        </div>
                                                    </div>
                                                    <div class="fragment-metrics">
                                                        <div class="metric">
                                                            <i class='bx bx-move'></i>
                                                            <span><?= $fragment['cantidad_movimientos'] ?> movimientos</span>
                                                        </div>
                                                        <div class="metric">
                                                            <i class='bx bx-time'></i>
                                                            <span><?= $fragment['tiempo_colocacion'] ?>s</span>
                                                        </div>
                                                        <!--
                                                        <div class="metric">
                                                            <i class='bx bx-target-lock'></i>
                                                            <span>Posición <?= $fragment['posicion_usada'] ?></span>
                                                        </div>
                                                        -->
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>

                                    <!-- Señuelos utilizados -->
                                    <?php
                                    $decoysUsed = array_filter(
                                        $attempt['fragments'],
                                        fn($f) => $f['es_señuelo'] && $f['cantidad_movimientos'] > 0
                                    );
                                    if (!empty($decoysUsed)):
                                    ?>
                                        <div class="decoys-used">
                                            <h6>Señuelos Interactuados</h6>
                                            <div class="decoys-container">
                                                <?php foreach ($decoysUsed as $decoy): ?>
                                                    <div class="decoy">
                                                        <span class="decoy-text"><?= $decoy['texto'] ?></span>
                                                        <span class="decoy-moves">
                                                            <?= $decoy['cantidad_movimientos'] ?> movimientos
                                                        </span>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
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
            <?php foreach ($data['report_data']['requirementsAnalysis'] as $index => $reqAnalysis): ?>
                <?php if (count($reqAnalysis['attempts']) > 1): ?>
                    new Chart(document.getElementById('progressionChart_<?= $index ?>').getContext('2d'), {
                        type: 'line',
                        data: {
                            labels: <?= json_encode(array_map(
                                        fn($a) => "Intento " . $a['attemptNumber'],
                                        $reqAnalysis['attempts']
                                    )) ?>,
                            datasets: [{
                                label: 'Precisión',
                                data: <?= json_encode(array_map(
                                            fn($a) => $a['precision'],
                                            $reqAnalysis['attempts']
                                        )) ?>,
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
                <?php endif; ?>
            <?php endforeach; ?>
        });
    </script>
</body>

</html>