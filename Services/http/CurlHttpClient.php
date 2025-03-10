<?php
namespace Services\Http;

class CurlHttpClient implements HttpClientInterface {
    /**
     * Realiza una petición GET
     * 
     * @param string $url URL de la petición
     * @param array $headers Cabeceras HTTP
     * @return array Respuesta decodificada
     * @throws \Exception Si ocurre un error en la petición
     */
    public function get(string $url, array $headers = []): array {
        return $this->request('GET', $url, null, $headers);
    }
    
    /**
     * Realiza una petición POST
     * 
     * @param string $url URL de la petición
     * @param array $data Datos a enviar
     * @param array $headers Cabeceras HTTP
     * @return array Respuesta decodificada
     * @throws \Exception Si ocurre un error en la petición
     */
    public function post(string $url, array $data, array $headers = []): array {
        return $this->request('POST', $url, $data, $headers);
    }
    
    /**
     * Realiza una petición HTTP
     * 
     * @param string $method Método HTTP (GET, POST, etc)
     * @param string $url URL de la petición
     * @param array|null $data Datos a enviar (para POST)
     * @param array $headers Cabeceras HTTP
     * @return array Respuesta decodificada
     * @throws \Exception Si ocurre un error en la petición
     */
    private function request(string $method, string $url, ?array $data = null, array $headers = []): array {
        $ch = curl_init($url);
        
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $test = json_encode($data); 

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($data !== null) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
        } else {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        }
        
        if (!empty($headers)) {
            $formattedHeaders = [];
            foreach ($headers as $key => $value) {
                if (is_string($key)) {
                    $formattedHeaders[] = "$key: $value";
                } else {
                    $formattedHeaders[] = $value;
                }
            }
            curl_setopt($ch, CURLOPT_HTTPHEADER, $formattedHeaders);
        }
        
        $response = curl_exec($ch);
        
        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new \Exception("Error en la petición HTTP: $error");
        }
        
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        $responseData = json_decode($response, true);
        
        if ($httpCode >= 400) {
            $errorMessage = isset($responseData['error']['message']) 
                ? $responseData['error']['message'] 
                : "Error HTTP $httpCode";
            throw new \Exception("Error en la respuesta del servidor: $errorMessage");
        }
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception("Error al decodificar la respuesta JSON: " . json_last_error_msg());
        }
        
        return $responseData;
    }
}