<?php
namespace Services\AI;

use Services\AI\Strategies\OpenAiRequirementStrategy;
use Services\AI\Strategies\GeminiRequirementStrategy;
use Services\Http\CurlHttpClient;
use Services\Http\HttpClientInterface;

class AiServiceFactory {
    /**
     * Crea una instancia de un servicio de IA
     * 
     * @param string $serviceName Nombre del servicio ('OpenAI' o 'Gemini')
     * @param HttpClientInterface|null $httpClient Cliente HTTP opcional
     * @return AiServiceInterface Instancia del servicio de IA
     * @throws \InvalidArgumentException Si el servicio no es soportado
     */
    public static function create(string $serviceName, ?HttpClientInterface $httpClient = null): AiServiceInterface {
        // Si no se proporciona un cliente HTTP, crear uno por defecto
        if ($httpClient === null) {
            $httpClient = new CurlHttpClient();
        }
        
        switch (strtolower($serviceName)){
            case 'openai':
                return new OpenAiRequirementStrategy($httpClient);
            case 'gemini':
                return new GeminiRequirementStrategy($httpClient);
            default:
                throw new \InvalidArgumentException("Servicio de IA no soportado: $serviceName");
        }
    }
    
    /**
     * Crea una instancia del servicio de IA predeterminado
     * 
     * @param HttpClientInterface|null $httpClient Cliente HTTP opcional
     * @return AiServiceInterface Instancia del servicio de IA
     */
    public static function createDefault(?HttpClientInterface $httpClient = null): AiServiceInterface {
        $defaultService = Config\AiServiceConfig::getDefaultService();
        return self::create($defaultService, $httpClient);
    }
}