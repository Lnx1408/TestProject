<?php
namespace Services\AI;

use Entity\GameConfigEntity;

interface AiServiceInterface {
    /**
     * Genera requisitos basados en la configuración proporcionada
     * 
     * @param GameConfigEntity $gameConfig Configuración del juego
     * @return array Lista de requisitos generados
     * @throws \Exception Si ocurre un error en la generación
     */
    public function generateRequirements(GameConfigEntity $gameConfig): array;
    
    /**
     * Verifica si el servicio está disponible
     * 
     * @return bool true si el servicio está disponible, false en caso contrario
     */
    public function isAvailable(): bool;
    
    /**
     * Obtiene el nombre del servicio
     * 
     * @return string Nombre del servicio
     */
    public function getName(): string;
}