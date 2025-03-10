<?php

namespace Services\AI\Strategies;

use Services\AI\AiServiceInterface;
use Services\AI\Config\AiServiceConfig;
use Services\Http\HttpClientInterface;
use Entity\GameConfigEntity;

class OpenAiRequirementStrategy implements AiServiceInterface
{
    private $httpClient;
    private $config;

    /**
     * Constructor con inyección de dependencias
     * 
     * @param HttpClientInterface $httpClient Cliente HTTP
     */
    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
        $this->config = AiServiceConfig::getOpenAiConfig();
    }

    /**
     * {@inheritdoc}
     */
    public function generateRequirements(GameConfigEntity $gameConfig): array
    {
        try {
            // Crear un nuevo hilo
            $threadId = $this->createThread();

            // Establecer valores predeterminados si es necesario
            if (empty($gameConfig->Language)) {
                $gameConfig->Language = 'es';
            }

            if (empty($gameConfig->Context)) {
                $gameConfig->Context = 'Genera requerimientos para un proyecto de ingeniería de software.';
            }

            // Enviar la solicitud inicial al asistente
            $userMessage = $gameConfig->convertirAJson();
            $this->createMessage($threadId, $userMessage);

            // Ejecutar el asistente para la generación
            $runId = $this->createRun($threadId, $this->config['assistant_id']);
            $this->checkCompleteStatus($threadId, $runId);

            // Obtener el contenido generado
            $finalContent = $this->getGeneratedContent($threadId);

            $decodedData = json_decode($finalContent, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception("Error al decodificar la respuesta JSON: " . json_last_error_msg());
            }

            // Verificar si 'requirements' está presente y es un array
            if (!isset($decodedData['requirements']) || !is_array($decodedData['requirements'])) {
                throw new \Exception("El JSON no contiene un array 'requirements' válido.");
            }

            return $decodedData['requirements'];
        } catch (\Exception $e) {
            throw new \Exception("Error en OpenAI: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isAvailable(): bool
    {
        return !empty($this->config['api_key']) && !empty($this->config['assistant_id']);
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'OpenAI';
    }

    /**
     * Crea un nuevo hilo de conversación
     * 
     * @return string ID del hilo creado
     * @throws \Exception Si ocurre un error en la creación
     */
    private function createThread(): string
    {
        $response = $this->httpClient->post(
            $this->config['threads_endpoint'],
            [],
            [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $this->config['api_key'],
                'OpenAI-Beta' => 'assistants=v2'
            ]
        );

        return $response['id'];
    }

    /**
     * Crea un mensaje en el hilo
     * 
     * @param string $threadId ID del hilo
     * @param string $content Contenido del mensaje
     * @return array Respuesta del servidor
     * @throws \Exception Si ocurre un error en la creación
     */
    private function createMessage(string $threadId, string $content): array
    {
        return $this->httpClient->post(
            $this->config['threads_endpoint'] . '/' . $threadId . '/messages',
            [
                'role' => 'user',
                'content' => $content
            ],
            [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $this->config['api_key'],
                'OpenAI-Beta' => 'assistants=v2'
            ]
        );
    }

    /**
     * Crea una ejecución del asistente en el hilo
     * 
     * @param string $threadId ID del hilo
     * @param string $assistantId ID del asistente
     * @return string ID de la ejecución
     * @throws \Exception Si ocurre un error en la creación
     */
    private function createRun(string $threadId, string $assistantId): string
    {
        $response = $this->httpClient->post(
            $this->config['threads_endpoint'] . '/' . $threadId . '/runs',
            ['assistant_id' => $assistantId],
            [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $this->config['api_key'],
                'OpenAI-Beta' => 'assistants=v2'
            ]
        );

        return $response['id'];
    }

    /**
     * Verifica el estado de una ejecución hasta que esté completa
     * 
     * @param string $threadId ID del hilo
     * @param string $runId ID de la ejecución
     * @param int $timeout Tiempo límite en segundos
     * @return string Estado final de la ejecución
     * @throws \Exception Si la ejecución no se completa dentro del tiempo límite
     */
    private function checkCompleteStatus(string $threadId, string $runId, int $timeout = 80): string
    {
        $startTime = time();
        $completed = false;

        while (!$completed && (time() - $startTime) < $timeout) {
            $response = $this->httpClient->get(
                $this->config['threads_endpoint'] . '/' . $threadId . '/runs/' . $runId,
                [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $this->config['api_key'],
                    'OpenAI-Beta' => 'assistants=v2'
                ]
            );

            if (isset($response['status'])) {
                if ($response['status'] === 'completed') {
                    $completed = true;
                } elseif ($response['status'] === 'failed' || $response['status'] === 'cancelled') {
                    throw new \Exception("La ejecución ha fallado con estado: " . $response['status']);
                } else {
                    // Esperar un segundo antes de verificar de nuevo
                    sleep(1);
                }
            } else {
                throw new \Exception("Respuesta inesperada al verificar el estado: " . json_encode($response));
            }
        }

        if (!$completed) {
            throw new \Exception("Timeout: La ejecución no se completó dentro del tiempo límite de $timeout segundos.");
        }

        return $response['status'];
    }

    /**
     * Obtiene el contenido generado por el asistente
     * 
     * @param string $threadId ID del hilo
     * @return string Contenido generado
     * @throws \Exception Si ocurre un error al obtener el contenido
     */
    private function getGeneratedContent(string $threadId): string
    {
        $response = $this->httpClient->get(
            $this->config['threads_endpoint'] . '/' . $threadId . '/messages',
            [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $this->config['api_key'],
                'OpenAI-Beta' => 'assistants=v2'
            ]
        );

        if (!isset($response['data']) || empty($response['data'])) {
            throw new \Exception("No se encontraron mensajes en la respuesta");
        }

        $firstMessage = $response['data'][0];

        if (!isset($firstMessage['content']) || empty($firstMessage['content'])) {
            throw new \Exception("El mensaje no contiene contenido");
        }

        if (!isset($firstMessage['content'][0]['text']['value'])) {
            throw new \Exception("Formato de contenido inesperado");
        }

        return $firstMessage['content'][0]['text']['value'];
    }
}
