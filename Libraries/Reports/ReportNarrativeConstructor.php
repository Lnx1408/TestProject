<?php

class ReportNarrativeConstructor {
    private $lang;
    private $templates;

    public function __construct() {
        $sessionManager = SessionManager::getInstance();
        $this->lang = $sessionManager->getLanguage();
        $this->loadTemplates();
    }

    private function loadTemplates() {
        $jsonPath = dirname(__FILE__) . "/templates/report-narrative-templates-{$this->lang}.json";
        if (!file_exists($jsonPath)) {
            $jsonPath = dirname(__FILE__) . "/templates/report-narrative-templates-en.json";
        }

        $this->templates = json_decode(file_get_contents($jsonPath), true);
    }

    private function replacePlaceholders($template, $data) {
        foreach ($data as $key => $value) {
            $template = str_replace("{{$key}}", "<strong>{$value}</strong>", $template);
        }
        return $template;
    }

    public function generateRequirementSummary($requirementData) {
        $attempts = $requirementData['attempts'];
        $firstAttempt = $attempts[0];
        $lastAttempt = end($attempts);
        $totalTime = array_sum(array_map(fn($a) => 
            $a['time']['minutes'] * 60 + $a['time']['seconds'], $attempts));

        $summaryData = [
            'requisito_texto' => $requirementData['requirement'],
            'total_intentos' => count($attempts),
            'tiempo_minutos' => floor($totalTime / 60),
            'tiempo_segundos' => $totalTime % 60,
            'precision_inicial' => $firstAttempt['precision'],
            'precision_final' => $lastAttempt['precision']
        ];

        $narrative = $this->replacePlaceholders(
            $this->templates['construction']['requirement_summary']['base'], 
            $summaryData
        );

        // Agregar información sobre mejora si hubo más de un intento
        if (count($attempts) > 1) {
            $precisionImprovement = $lastAttempt['precision'] - $firstAttempt['precision'];
            if ($precisionImprovement > 0) {
                $improvementData = ['porcentaje_mejora' => number_format($precisionImprovement, 1)];
                $narrative .= " " . $this->replacePlaceholders(
                    $this->templates['construction']['requirement_summary']['improvement'],
                    $improvementData
                );
            }
        }

        // Agregar información sobre señuelos si es relevante
        $totalDecoys = array_sum(array_map(fn($a) => $a['decoysUsed'], $attempts));
        if ($totalDecoys > 0) {
            $decoysData = ['total_señuelos' => $totalDecoys];
            $narrative .= " " . $this->replacePlaceholders(
                $this->templates['construction']['requirement_summary']['decoys'],
                $decoysData
            );
        }

        return $narrative;
    }

    public function generateAttemptDetail($attempt) {
        $attemptData = [
            'numero_intento' => $attempt['attemptNumber'],
            'tiempo_minutos' => $attempt['time']['minutes'],
            'tiempo_segundos' => str_pad($attempt['time']['seconds'], 2, '0', STR_PAD_LEFT),
            'total_movimientos' => $attempt['movements'],
            'fragmentos_correctos' => $attempt['correctFragments'],
            'fragmentos_incorrectos' => $attempt['incorrectFragments'],
            'señuelos_usados' => $attempt['decoysUsed'],
            'precision' => $attempt['precision']
        ];

        return $this->replacePlaceholders(
            $this->templates['construction']['attempt_detail'],
            $attemptData
        );
    }
}