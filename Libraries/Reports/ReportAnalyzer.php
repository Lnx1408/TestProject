<?php

class ReportAnalyzer
{
    private $attemptsData;
    private $totalRequirements;
    private $performanceRanges;
    private $templates;
    private $lang;

    public function __construct($attemptsData, $totalRequirements)
    {
        $this->attemptsData = $attemptsData;
        $this->totalRequirements = $totalRequirements;

        // Obtener el idioma actual
        $sessionManager = SessionManager::getInstance();
        $this->lang = $sessionManager->getLanguage();

        $this->initializeTemplates();
        $this->initializeRanges();
    }

    private function initializeTemplates()
    {
        // Cargar el archivo JSON según el idioma
        $jsonPath = dirname(__FILE__) . "/templates/report-narrative-templates-{$this->lang}.json";
        if (!file_exists($jsonPath)) {
            // Fallback a inglés si no existe el archivo del idioma actual
            $jsonPath = dirname(__FILE__) . "/templates/report-narrative-templates-en.json";
        }

        $this->templates = json_decode(file_get_contents($jsonPath), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Error loading language templates: " . json_last_error_msg());
        }
    }

    private function initializeRanges()
    {
        $this->performanceRanges = [
            'attempts' => [
                'few' => 2,      // ≤ 3 intentos
                'moderate' => 6   // ≤ 6 intentos
                // > 6 = many
            ],
            'efficiency' => [
                'high' => 71,    // ≥ 70% de eficiencia
                'medium' => 41   // ≥ 40% de eficiencia
                // < 40% = low
            ],
            'consistency' => [
                'high' => 71,    // ≥ 75% de consistencia (estabilidad + mejora)
                'medium' => 41   // ≥ 50% de consistencia
                // < 50% = low
            ],
            'starting' => [      // Nuevo rango para evaluación inicial
                'high' => 71,    // ≥ 70% primer intento
                'medium' => 41   // ≥ 40% primer intento
            ]
        ];
    }

    public function analyzePerformance()
    {
        $totalAttempts = count($this->attemptsData);
        $firstAttempt = $this->attemptsData[0];
        $correctosInicial = $firstAttempt['requisitos_correctos'];

        // Calcular eficiencia promedio
        $totalCorrectos = array_sum(array_column($this->attemptsData, 'requisitos_correctos'));
        $promedioCorrectos = $totalCorrectos / $totalAttempts;
        $promedioCorrectosPorc = ($promedioCorrectos / $this->totalRequirements) * 100;


        // Calcular consistencia (variación entre intentos)
        $ratios = array_map(function ($attempt) {
            return ($attempt['requisitos_correctos'] / $attempt['total_requisitos']) * 100;
        }, $this->attemptsData);
        //$variacion = $this->calculateVariation($ratios);
        $consistencyScore = $this->calculateConsistency($ratios);


        // Determinar niveles de rendimiento
        $attemptLevel = $this->getAttemptLevel($totalAttempts);
        $efficiencyLevel = $this->getEfficiencyLevel($promedioCorrectosPorc);
        //$consistencyLevel = $this->getConsistencyLevel_old($variacion);
        $consistencyLevel = $this->getConsistencyLevel($consistencyScore); 

        // Generar narrativa
        $narrative = $this->generateNarrative([
            'correctos_inicial' => $correctosInicial,
            'total_requisitos' => $this->totalRequirements,
            'total_intentos' => $totalAttempts,
            'promedio_correctos' => round($promedioCorrectos, 1)
        ]);

        // Generar recomendaciones
        $recommendations = [
            'attempts' => $this->templates['classification']['recommendationTemplates']['attempts'][$attemptLevel],
            'efficiency' => $this->templates['classification']['recommendationTemplates']['efficiency'][$efficiencyLevel],
            'consistency' => $this->templates['classification']['recommendationTemplates']['consistency'][$consistencyLevel]
        ];

        return [
            'narrative' => $narrative,
            'recommendations' => $recommendations,
            'performanceLevels' => [
                'attempts' => $attemptLevel,
                'efficiency' => $efficiencyLevel,
                'consistency' => $consistencyLevel
            ],
            'metrics' => [
                'totalAttempts' => $totalAttempts,
                'averageCorrect' => $promedioCorrectos,
                //'consistency' => $variacion
                'consistency' => $consistencyScore
            ]
        ];
    }

    private function calculateVariation($ratios)
    {
        if (count($ratios) < 2) return 0;
        $differences = [];
        for ($i = 1; $i < count($ratios); $i++) {
            $differences[] = abs($ratios[$i] - $ratios[$i - 1]);
        }
        return array_sum($differences) / count($differences);
    }

    private function calculateConsistency($ratios) {
        if (count($ratios) < 2) return 100; // Perfecto si solo hay un intento
        
        // 1. Calcula la desviación estándar normalizada (0-100)
        $stdDev = $this->calculateStandardDeviation($ratios);
        $maxPossibleStdDev = 100; // Máxima desviación posible
        $stabilityScore = 100 * (1 - ($stdDev / $maxPossibleStdDev));
        
        // 2. Calcula la tendencia de mejora
        $improvements = [];
        for ($i = 1; $i < count($ratios); $i++) {
            $improvements[] = max(0, $ratios[$i] - $ratios[$i-1]);
        }
        $improvementScore = !empty($improvements) ? 
            (array_sum($improvements) / count($improvements)) : 0;
        
        // 3. Combina los scores dando más peso a la estabilidad
        $consistencyScore = ($stabilityScore * 0.7) + ($improvementScore * 0.3);
        
        return max(0, min(100, $consistencyScore));
    }
    
    private function calculateStandardDeviation($values) {
        $mean = array_sum($values) / count($values);
        $variance = array_reduce($values, function($carry, $val) use ($mean) {
            return $carry + pow($val - $mean, 2);
        }, 0) / count($values);
        
        return sqrt($variance);
    }

    private function getAttemptLevel($attempts)
    {
        if ($attempts <= $this->performanceRanges['attempts']['few']) return 'few';
        if ($attempts <= $this->performanceRanges['attempts']['moderate']) return 'moderate';
        return 'many';
    }

    private function getEfficiencyLevel($efficiency)
    {
        if ($efficiency >= $this->performanceRanges['efficiency']['high']) return 'high';
        if ($efficiency >= $this->performanceRanges['efficiency']['medium']) return 'medium';
        return 'low';
    }

    private function getConsistencyLevel_old($variation)
    {
        if ($variation <= $this->performanceRanges['consistency']['high']) return 'high';
        if ($variation <= $this->performanceRanges['consistency']['medium']) return 'medium';
        return 'low';
    }

    private function getConsistencyLevel($consistencyScore) {
        if ($consistencyScore >= $this->performanceRanges['consistency']['high']) return 'high';
        if ($consistencyScore >= $this->performanceRanges['consistency']['medium']) return 'medium';
        return 'low';
    }

    private function getStartingLevel($ratio)
    {
        if ($ratio >= $this->performanceRanges['efficiency']['high']) return 'good';
        if ($ratio >= $this->performanceRanges['efficiency']['medium']) return 'average';
        return 'poor';
    }

    private function getProgressionLevel($attempts)
    {
        if ($attempts <= $this->performanceRanges['attempts']['few']) return 'efficient';
        if ($attempts <= $this->performanceRanges['attempts']['moderate']) return 'steady';
        return 'gradual';
    }

    private function generateNarrative($data)
    {
        $initialRatio = ($data['correctos_inicial'] / $data['total_requisitos']) * 100;
        $promedioCorrectosPorc = ($data['promedio_correctos'] / $this->totalRequirements) * 100;

        // Seleccionar frase inicial
        $startingLevel = $this->getStartingLevel($initialRatio);
        $progressionLevel = $this->getProgressionLevel($data['total_intentos']);
        $efficiencyLevel = $this->getEfficiencyLevel($promedioCorrectosPorc);


        $startingPhrase = $this->templates['classification']['narrativeTemplates']['startingPhrase'][$startingLevel];
        $progressionPhrase = $this->templates['classification']['narrativeTemplates']['progressionPhrase'][$progressionLevel];
        $efficiencyPhrase = $this->templates['classification']['narrativeTemplates']['efficiencyPhrase'][$efficiencyLevel];

        // Reemplazar placeholders
        $phrases = [$startingPhrase, $progressionPhrase, $efficiencyPhrase];
        foreach ($phrases as &$phrase) {
            foreach ($data as $key => $value) {
                $phrase = str_replace("{{$key}}", "<strong>{$value}</strong>", $phrase);
            }
        }

        return implode(" ", $phrases);
    }
}
