<?php
namespace Services\Email;

interface EmailServiceInterface {
    /**
     * Enviar un correo electrónico
     * 
     * @param string $to Destinatario
     * @param string $subject Asunto
     * @param string $body Cuerpo del mensaje
     * @param array $options Opciones adicionales (cc, bcc, attachments, etc.)
     * @return bool Éxito del envío
     */
    public function send(string $to, string $subject, string $body, array $options = []): bool;
    
    /**
     * Enviar un correo electrónico usando una plantilla
     * 
     * @param string $to Destinatario
     * @param string $subject Asunto
     * @param string $template Nombre de la plantilla
     * @param array $data Datos para la plantilla
     * @param array $options Opciones adicionales
     * @return bool Éxito del envío
     */
    public function sendTemplate(string $to, string $subject, string $template, array $data = [], array $options = []): bool;
    
    /**
     * Verificar si el servicio está configurado correctamente
     * 
     * @return bool Resultado de la verificación
     */
    public function isConfigured(): bool;
}