<?php

//require_once 'entities/GameConfigEntity.php';
use Entity\GameConfigEntity;

class GptService
{
    private static $threadsEndpoint = 'https://api.openai.com/v1/threads';

    private static function prepareAPI($endpoint, $method, $additionalHeaders = [], $data = [])
    {
        $ch = curl_init($endpoint);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        } else {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        }

        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . getenv('OPENAI_API_KEY'),
        ];

        if (!empty($additionalHeaders)) {
            $headers = array_merge($headers, $additionalHeaders);
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        return $ch;
    }

    private static function createThread()
    {
        $ch = self::prepareAPI(self::$threadsEndpoint, 'POST', [
            'OpenAI-Beta: assistants=v2',
        ]);
        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            throw new Exception('Error al crear el hilo: ' . curl_error($ch));
        }

        $response = json_decode($response, true);
        return $response['id'];
    }

    private static function createRun($threadId, $assistantId)
    {
        $endpoint = self::$threadsEndpoint . '/' . $threadId . '/runs';

        $data = [
            'assistant_id' => $assistantId,
        ];

        $ch = self::prepareAPI($endpoint, 'POST', [
            'OpenAI-Beta: assistants=v2',
        ], $data);
        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            throw new Exception('Error al crear la ejecución: ' . curl_error($ch));
        }

        $response = json_decode($response, true);
        return $response['id'];
    }

    private static function checkCompleteStatus($threadId, $runId, $timeout = 80)
    {
        $startTime = time();
        $completed = false;

        while (!$completed && (time() - $startTime) < $timeout) {
            $endpoint = self::$threadsEndpoint . '/' . $threadId . '/runs/' . $runId;

            $ch = self::prepareAPI($endpoint, 'GET', [
                'OpenAI-Beta: assistants=v2',
            ]);
            $response = curl_exec($ch);

            if (curl_errno($ch)) {
                throw new Exception('Error al verificar el estado de la ejecución: ' . curl_error($ch));
            }

            $response = json_decode($response, true);

            if ($response === null) {
                // Si la respuesta es nula, salir del bucle
                break;
            }
        
            if (isset($response['status']) && $response['status'] === 'completed') {
                $completed = true;
            } else {
                // Sleep for 1 second before checking again
                sleep(1);
            }
        }

        if (!$completed) {
            throw new Exception('Timeout: La ejecución no se completó dentro del tiempo límite de ' . $timeout . ' segundos.');
        }
        return $response['status'];
    }

    private static function createMessage($threadId, $content)
    {
        $endpoint = self::$threadsEndpoint . '/' . $threadId . '/messages';

        $data = [
            'role' => 'user',
            'content' => $content
        ];

        $ch = self::prepareAPI($endpoint, 'POST', [
            'OpenAI-Beta: assistants=v2',
        ], $data);
        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            throw new Exception('Error al crear el mensaje: ' . curl_error($ch));
        }

        return $response;
    }

    private static function getGeneratedConent($threadId)
    {
        $endpoint = self::$threadsEndpoint . '/' . $threadId . '/messages';

        $ch = self::prepareAPI($endpoint, 'GET', [
            'OpenAI-Beta: assistants=v2',
        ]);
        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            throw new Exception('Error al obtener la lista de mensajes: ' . curl_error($ch));
        }

        $response = json_decode($response, true);
        $firstMessage = $response['data'][0];
        $content = $firstMessage['content'][0]['text']['value'];

        return $content;
    }

    public static function generateRequirements(GameConfigEntity $gameConfig)
    {
        $threadId = self::createThread();

        $languageMap = [
            'es' => 'Español',
            'en' => 'English',
        ];

        if (empty($gameConfig->language)) {
            $gameConfig->Language = 'es';
        }

        if (empty($gameConfig->additional_context)) {
            $gameConfig->Context = 'Genera requerimientos para un proyecto de ingeniería de software.';
        }

        // Enviar la solicitud inicial al asistente
        $userMessage = $gameConfig->convertirAJson();
        self::createMessage($threadId, $userMessage);

        // Ejecutar el asistente para la generación inicial
        $runId = self::createRun($threadId, getenv('SOF_REQ_ASSISTANT_ID'));
        self::checkCompleteStatus($threadId, $runId);

        // Obtener el contenido final validado
        $finalContent = self::getGeneratedConent($threadId);

        return json_decode($finalContent, true);
    }
}
