<?php
class ReportGeneralClassificationNarrativeGenerator
{

    private $lang;
    private $templates;

    public function __construct()
    {
        $sessionManager = SessionManager::getInstance();
        $this->lang = $sessionManager->getLanguage();
        $this->loadTemplates();
    }

    private function loadTemplates()
    {
        $jsonPath = dirname(__FILE__) . "/templates/report-narrative-templates-{$this->lang}.json";
        if (!file_exists($jsonPath)) {
            $jsonPath = dirname(__FILE__) . "/templates/report-narrative-templates-en.json";
        }

        $this->templates = json_decode(file_get_contents($jsonPath), true);
    }

    private function replacePlaceholders($template, $data)
    {
        foreach ($data as $key => $value) {
            $template = str_replace("{{$key}}", "<strong>{$value}</strong>", $template);
        }
        return $template;
    }


    // Umbrales para las diferentes métricas
    private const THRESHOLDS = [
        'completion' => [
            'high' => 70,    // >= 70% es alta finalización
            'medium' => 40    // >= 40% es media, < 40% es baja
        ],
        'time' => [
            'fast' => 360,    // <= 3 minutos es rápido
            'moderate' => 540  // <= 5 minutos es moderado, > 5 es lento
        ]
    ];


    public function generateSummaryNarrative($completedPercentage, $inProgressCount)
    {
        $narrative = '';
        $summaryData = [
            'completed_percentage' => $completedPercentage,
            'in_progress_count' => $inProgressCount
        ];

        if ($completedPercentage >= self::THRESHOLDS['completion']['high']) {
            $level = "high";
        } elseif ($completedPercentage >= self::THRESHOLDS['completion']['medium']) {
            $level = "medium";
        } else {
            $level = "low";
        }
        $narrative = $this->replacePlaceholders(
            $this->templates['general']['classification']['summary_narrative_templates']["{$level}_completion"],
            $summaryData
        );
        return $narrative;
    }


    public function generateTimeAnalysisNarrative($avgTime)
    {
        $avgTimeInSeconds = $this->convertTimeStringToSeconds($avgTime);
        $summaryData = [
            'avg_time' => $avgTime
        ];
        
        if ($avgTimeInSeconds <= self::THRESHOLDS['time']['fast']) {
            $level = "fast";
        } elseif ($avgTimeInSeconds <= self::THRESHOLDS['time']['moderate']) {
            $level = "moderate";
        } else {
            $level = "slow";
        }
        $narrative = $this->replacePlaceholders(
            $this->templates['general']['classification']['time_narrative_templates']["{$level}_completion"],
            $summaryData
        );
        return $narrative;
    }

    private function convertTimeStringToSeconds($timeString)
    {
        // Asumiendo formato "Xmin Ys"
        preg_match('/(\d+)min\s+(\d+)s/', $timeString, $matches);
        if (count($matches) == 3) {
            return ($matches[1] * 60) + $matches[2];
        }
        return 0;
    }
}
