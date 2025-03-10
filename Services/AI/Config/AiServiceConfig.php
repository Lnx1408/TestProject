<?php
namespace Services\AI\Config;

class AiServiceConfig {
    /**
     * Obtiene la configuración para OpenAI
     * 
     * @return array Configuración de OpenAI
     */
    public static function getOpenAiConfig(): array {
        return [
            'api_key' => getenv('OPENAI_API_KEY') ?: '',
            'assistant_id' => getenv('SOF_REQ_ASSISTANT_ID') ?: '',
            'threads_endpoint' => 'https://api.openai.com/v1/threads',
            'messages_endpoint' => 'https://api.openai.com/v1/messages',
            'assistants_endpoint' => 'https://api.openai.com/v1/assistants'
        ];
    }
    
    /**
     * Obtiene la configuración para Gemini
     * 
     * @return array Configuración de Gemini
     */
    public static function getGeminiConfig(): array {

        $configFilePath = __DIR__ . '/../../../Config/AI/Gemini/gemini_config.json';
        $promptsFilePath = __DIR__ . '/../../../Config/AI/Gemini/Prompts/gemini_prompts.json';
        $schemaFilePath = __DIR__ . '/../../../Config/AI/Gemini/Schemas/gemini_schema.json';

        // Configuración predeterminada
        $defaultConfig = [
            'api_key' => getenv('GEMINI_API_KEY') ?: '',
            'base_endpoint' => 'https://generativelanguage.googleapis.com/v1beta/models',
            'default_model' => 'gemini-1.5-flash-8b',
            'available_models' => [
                'gemini-1.5-flash-8b' => 'gemini-1.5-flash-8b',
                'gemini-2.0-flash-lite' => 'gemini-2.0-flash-lite'
            ],
            'generation_config' => [
                'temperature' => 0.7,
                'maxOutputTokens' => 2048,
                'response_mime_type' => 'application/json'
            ]
        ];

        // Cargar configuración desde archivo si existe
        $fileConfig = [];
        if (file_exists($configFilePath)) {
            $fileConfigJson = file_get_contents($configFilePath);
            $fileConfig = json_decode($fileConfigJson, true) ?: [];
        }
        
        // Cargar plantillas de prompts si existe el archivo
        $prompts = [];
        if (file_exists($promptsFilePath)) {
            $promptsJson = file_get_contents($promptsFilePath);
            $prompts = json_decode($promptsJson, true) ?: [];
        }
        
        // Cargar esquema de respuesta si existe el archivo
        $responseSchema = null;
        if (file_exists($schemaFilePath)) {
            $schemaJson = file_get_contents($schemaFilePath);
            $responseSchema = json_decode($schemaJson, true) ?: null;
        }
        
        // Combinar configuraciones (prioridad: archivo > predeterminada)
        $config = array_merge($defaultConfig, $fileConfig);
        
        // Agregar prompts y esquema a la configuración
        $config['prompts'] = $prompts;
        $config['response_schema'] = $responseSchema;
        
        return $config;
    }
    
    /**
     * Obtiene el servicio predeterminado
     * 
     * @return string Nombre del servicio predeterminado
     */
    public static function getDefaultService(): string {
        return getenv('DEFAULT_AI_SERVICE') ?: 'OpenAI';
    }
}