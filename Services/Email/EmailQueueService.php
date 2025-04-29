<?php

namespace Services\Email;

use Libraries\Cache\CacheManager;
use Services\Email\Providers\PHPMailerService;

class EmailQueueService
{
    private $db;
    private $cacheManager;
    private $emailService;

    public function __construct()
    {
        $this->db = new \Mysql();
        $this->cacheManager = CacheManager::getInstance();
        $this->emailService = new PHPMailerService(); // Usar la configuración por defecto
    }

    /**
     * Encolar un correo electrónico
     * 
     * @param string $to Destinatario
     * @param string $subject Asunto
     * @param string $body Cuerpo del mensaje
     * @param array $options Opciones adicionales
     * @param int $priority Prioridad (1-10, siendo 1 la más alta)
     * @return bool Resultado de la operación
     */
    public function enqueue(string $to, string $subject, string $body, array $options = [], int $priority = 5): bool
    {
        $optionsJson = json_encode($options);

        $data = [
            'to_email' => $to,
            'subject' => $subject,
            'body' => $body,
            'options' => $optionsJson,
            'priority' => $priority,
            'next_attempt_at' => date('Y-m-d H:i:s') // Inmediatamente
        ];

        try {
            $result = $this->db->executeProcedureWithParametersOut(
                'sp_enqueue_email',
                [
                    $to,                    // p_to_email
                    $subject,               // p_subject
                    $body,                  // p_body
                    $optionsJson,           // p_options
                    null,                   // p_template
                    null,                   // p_template_data
                    $priority               // p_priority
                ],
                ['p_result', 'p_message']
            );
            if (empty($result) || $result['outParams']['p_result'] != 1) {
                error_log("Error al encolar correo: " . $result['outParams']['p_message'] ?? 'Error desconocido');
                return false;
            }
            return true;
        } catch (\Exception $e) {
            error_log("Error al encolar correo: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Encolar un correo con plantilla
     * 
     * @param string $to Destinatario
     * @param string $subject Asunto
     * @param string $template Nombre de la plantilla
     * @param array $templateData Datos para la plantilla
     * @param array $options Opciones adicionales
     * @param int $priority Prioridad
     * @return bool Resultado de la operación
     */
    public function enqueueTemplate(string $to, string $subject, string $template, array $templateData = [], array $options = [], int $priority = 5): bool
    {
        $optionsJson = json_encode($options);
        $templateDataJson = json_encode($templateData);
        try {
            $result = $this->db->executeProcedureWithParametersOut(
                'sp_enqueue_email',
                [
                    $to,                    // p_to_email
                    $subject,               // p_subject
                    null,                   // p_body (null para plantillas)
                    $optionsJson,           // p_options
                    $template,              // p_template
                    $templateDataJson,      // p_template_data
                    $priority               // p_priority
                ],
                ['p_result', 'p_message']
            );

            if (empty($result) || $result['outParams']['p_result'] != 1) {
                error_log("Error al encolar correo con plantilla: " . $result['outParams']['p_message'] ?? 'Error desconocido');
                return false;
            }
            return true;
        } catch (\Exception $e) {
            error_log("Error al encolar correo con plantilla: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Procesar la cola de correos
     * 
     * @param int $limit Número máximo de correos a procesar
     * @return array Estadísticas de procesamiento
     */
    public function processQueue(int $limit = 10): array
    {
        $stats = [
            'processed' => 0,
            'sent' => 0,
            'failed' => 0,
            'requeued' => 0
        ];

        try {
            // Obtener correos pendientes ordenados por prioridad y fecha
            $pendingEmails = $this->db->executeProcedureWithParametersOut(
                'sp_get_pending_emails',
                [$limit],
                []
            );

            if (empty($pendingEmails) || empty($pendingEmails['results'])) {
                return $stats; // No hay correos pendientes
            }

            foreach ($pendingEmails['results'] as $emailJob) {
                $stats['processed']++;

                // Marcar como en procesamiento
                $this->updateStatus($emailJob['id'], 'processing');

                $success = false;

                // Procesar según el tipo (plantilla o directo)
                if (!empty($emailJob['template'])) {
                    // Con plantilla
                    $templateData = json_decode($emailJob['template_data'], true) ?? [];
                    $options = json_decode($emailJob['options'], true) ?? [];

                    $success = $this->emailService->sendTemplate(
                        $emailJob['to_email'],
                        $emailJob['subject'],
                        $emailJob['template'],
                        $templateData,
                        $options
                    );
                } else {
                    // Directo
                    $options = json_decode($emailJob['options'], true) ?? [];

                    $success = $this->emailService->send(
                        $emailJob['to_email'],
                        $emailJob['subject'],
                        $emailJob['body'],
                        $options
                    );
                }

                if ($success) {
                    // Marcar como enviado
                    $this->updateStatus($emailJob['id'], 'sent');
                    $stats['sent']++;
                } else {
                    // Incrementar intentos y programar nuevo intento
                    $newAttempts = $emailJob['attempts'] + 1;

                    if ($newAttempts >= $emailJob['max_attempts']) {
                        // Marcar como fallido definitivamente
                        $this->updateStatus($emailJob['id'], 'failed', "Máximo de intentos alcanzado");
                        $stats['failed']++;
                    } else {
                        // Calcular tiempo para el próximo intento (exponencial backoff)
                        $delay = pow(2, $newAttempts) * 60; // en segundos
                        $nextAttempt = date('Y-m-d H:i:s', time() + $delay);

                        // Actualizar registro
                        $this->requeueEmail($emailJob['id'], $newAttempts, $nextAttempt);
                        $stats['requeued']++;
                    }
                }
            }

            return $stats;
        } catch (\Exception $e) {
            error_log("Error al procesar la cola de correos: " . $e->getMessage());
            return $stats;
        }
    }

    /**
     * Actualizar el estado de un correo en la cola
     * 
     * @param int $id ID del correo
     * @param string $status Nuevo estado
     * @param string|null $error Mensaje de error (opcional)
     * @return bool Resultado de la operación
     */
    private function updateStatus(int $id, string $status, ?string $error = null): bool
    {
        try {
            $result = $this->db->executeProcedureWithParametersOut(
                'sp_update_email_status',
                [
                    $id,       // p_id
                    $status,   // p_status
                    $error     // p_error
                ],
                ['p_result', 'p_message']
            );

            return !empty($result) && $result['outParams']['p_result'] == 1;
        } catch (\Exception $e) {
            error_log("Error al actualizar estado de correo: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Reprogramar un correo para un nuevo intento
     * 
     * @param int $id ID del correo
     * @param int $attempts Número de intentos
     * @param string $nextAttempt Fecha/hora del próximo intento
     * @return bool Resultado de la operación
     */
    private function requeueEmail(int $id, int $attempts, string $nextAttempt): bool
    {
        try {
            $result = $this->db->executeProcedureWithParametersOut(
                'sp_requeue_email',
                [
                    $id,           // p_id
                    $attempts,     // p_attempts
                    $nextAttempt   // p_next_attempt
                ],
                ['p_result', 'p_message']
            );

            return !empty($result) && $result['outParams']['p_result'] == 1;
        } catch (\Exception $e) {
            error_log("Error al reprogramar correo: " . $e->getMessage());
            return false;
        }
    }
}
