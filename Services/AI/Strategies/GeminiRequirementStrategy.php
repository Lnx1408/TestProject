<?php
namespace Services\AI\Strategies;

use Services\AI\AiServiceInterface;
use Services\AI\Config\AiServiceConfig;
use Services\Http\HttpClientInterface;
use Entity\GameConfigEntity;

class GeminiRequirementStrategy implements AiServiceInterface {
    private $httpClient;
    private $config;
    
    /**
     * Constructor con inyección de dependencias
     * 
     * @param HttpClientInterface $httpClient Cliente HTTP
     */
    public function __construct(HttpClientInterface $httpClient) {
        $this->httpClient = $httpClient;
        $this->config = AiServiceConfig::getGeminiConfig();
    }
    
    /**
     * {@inheritdoc}
     */
    public function generateRequirements(GameConfigEntity $gameConfig): array {
        try {
            // Establecer valores predeterminados si es necesario
            if (empty($gameConfig->Language)) {
                $gameConfig->Language = 'es';
            }
            
            if (empty($gameConfig->Context)) {
                $gameConfig->Context = 'Genera requerimientos para un proyecto de ingeniería de software.';
            }

            if (empty($gameConfig->Number_items)) {
                $gameConfig->Number_items = 10;
            }
            
            // Seleccionar el modelo
            $modelName = $gameConfig->model ?? $this->config['default_model'];
            if (!isset($this->config['available_models'][$modelName])) {
                $modelName = $this->config['default_model'];
            }

            // Construir el prompt usando la plantilla configurada
            $prompt = $this->buildPrompt($gameConfig);
            
            // Preparar el endpoint completo
            $endpoint = $this->config['base_endpoint'] . '/' . $modelName . ':generateContent';
            // Preparar la configuración de generación
            $generationConfig = $this->config['generation_config'];

            // Construir el prompt para Gemini
            $languageStr = $gameConfig->Language === 'es' ? 'español' : 'inglés';
            
            $payload = [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt]
                        ]
                    ]
                ],
                'generationConfig' => $generationConfig
            ];

            // Agregar el esquema de respuesta si está definido
            if (!empty($this->config['response_schema'])) {
                $payload['generationConfig']['response_schema'] = $this->config['response_schema'];
            }
            
            // Realizar la petición a Gemini
            $response = $this->httpClient->post(
                $endpoint . '?key=' . $this->config['api_key'],
                $payload,
                ['Content-Type' => 'application/json']
            );
            // Extraer y decodificar la respuesta JSON
            return $this->parseResponse($response);
            
        } catch (\Exception $e) {
            throw new \Exception("Error en Gemini: " . $e->getMessage(), 0, $e);
        }
    }
    
    /**
     * Construye el prompt usando la plantilla configurada
     * 
     * @param GameConfigEntity $gameConfig Configuración del juego
     * @return string Prompt formateado
     */
    private function buildPrompt(GameConfigEntity $gameConfig): string {
        $language = $gameConfig->Language;
        
        // Verificar si existe una plantilla para el idioma
        if (!isset($this->config['prompts']['requirements_generation'][$language])) {
            // Usar idioma predeterminado (español) si no existe plantilla para el idioma solicitado
            $language = 'es';
        }
        
        // Obtener la plantilla
        $template = $this->config['prompts']['requirements_generation'][$language];
        
        // Reemplazar placeholders en la plantilla
        $replacements = [
            '{num_requirements}' => $gameConfig->Number_items,
            '{context}' => $gameConfig->Context
        ];
        
        return str_replace(array_keys($replacements), array_values($replacements), $template);
    }
    
    /**
     * Parsea la respuesta de Gemini
     * 
     * @param array $response Respuesta de la API
     * @return array Requisitos decodificados
     * @throws \Exception Si hay un error al parsear la respuesta
     */
    private function parseResponse($response): array {
        // Verificar si la respuesta contiene candidatos
        if (!isset($response['candidates']) || empty($response['candidates'])) {
            throw new \Exception("No se recibieron candidatos en la respuesta de Gemini");
        }
        
        // Obtener el primer candidato
        $candidate = $response['candidates'][0];
        
        // Verificar si hay error en el candidato
        if (isset($candidate['finishReason']) && $candidate['finishReason'] !== 'STOP') {
            throw new \Exception("Error en la generación: " . ($candidate['finishReason'] ?? 'Razón desconocida'));
        }
        
        // Verificar el contenido del candidato
        if (!isset($candidate['content']) || empty($candidate['content']['parts'])) {
            throw new \Exception("Formato de respuesta inesperado");
        }
        
        // Extraer el texto del contenido
        $content = $candidate['content']['parts'][0]['text'] ?? '';
        
        // Si la respuesta ya es un JSON válido, decodificarla directamente
        $requirements = json_decode($content, true);
        
        // Si la decodificación falló, intentar extraer el JSON de la respuesta
        if (json_last_error() !== JSON_ERROR_NONE) {
            // Intentar extraer JSON usando regex
            preg_match('/\[\s*\{.*\}\s*\]/s', $content, $matches);
            
            if (!empty($matches)) {
                $requirements = json_decode($matches[0], true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new \Exception("Error al decodificar la respuesta JSON extraída: " . json_last_error_msg());
                }
            } else {
                throw new \Exception("No se pudo encontrar una estructura JSON válida en la respuesta");
            }
        }
        
        return $requirements;
    }

     /**
     * {@inheritdoc}
     */
    public function isAvailable(): bool {
        if (empty($this->config['api_key'])) {
            return false;
        }
        return true;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getName(): string {
        return 'Gemini';
    }

    /**
     * Obtiene los modelos disponibles
     * 
     * @return array Lista de modelos disponibles
     */
    public function getAvailableModels(): array {
        return $this->config['available_models'] ?? [];
    }
    
    /**
     * Obtiene el modelo predeterminado
     * 
     * @return string Nombre del modelo predeterminado
     */
    public function getDefaultModel(): string {
        return $this->config['default_model'] ?? 'gemini-1.5-flash-8b';
    }
}