<?php
namespace Services\Http;

interface HttpClientInterface {
    /**
     * Realiza una petición GET
     * 
     * @param string $url URL de la petición
     * @param array $headers Cabeceras HTTP
     * @return array Respuesta decodificada
     * @throws \Exception Si ocurre un error en la petición
     */
    public function get(string $url, array $headers = []): array;
    
    /**
     * Realiza una petición POST
     * 
     * @param string $url URL de la petición
     * @param array $data Datos a enviar
     * @param array $headers Cabeceras HTTP
     * @return array Respuesta decodificada
     * @throws \Exception Si ocurre un error en la petición
     */
    public function post(string $url, array $data, array $headers = []): array;
}