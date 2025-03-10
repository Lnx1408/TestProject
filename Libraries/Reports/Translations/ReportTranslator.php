<?php

class ReportTranslator {
    private $translations;
    private $currentLang;
    
    public function __construct($lang = 'es') {
        $this->currentLang = $lang;
        $this->loadTranslations();
    }
    
    private function loadTranslations() {
        //$path = __DIR__ . "/../Assets/js/i18n/{$this->currentLang}.json";
        $projectRoot = realpath(__DIR__ . "/../../../");
        $path = "{$projectRoot}/Assets/js/i18n/{$this->currentLang}.json";
        
        if (!file_exists($path)) {
            throw new Exception("Translation file not found for language: {$this->currentLang}");
        }
        
        $content = file_get_contents($path);
        $this->translations = json_decode($content, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Error loading translations: " . json_last_error_msg());
        }
    }
    
    public function translate($key) {
        $keys = explode('.', $key);
        $value = $this->translations;
        
        foreach ($keys as $k) {
            if (!isset($value[$k])) {
                return $key; // Retorna la clave si no encuentra la traducción
            }
            $value = $value[$k];
        }
        
        return $value;
    }

    public function getCurrentLang() {
        return $this->currentLang;
    }
}