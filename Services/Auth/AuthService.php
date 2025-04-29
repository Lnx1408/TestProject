<?php

namespace Services\Auth;

use Libraries\Security\JwtHandler;
use Services\Email\Providers\PHPMailerService;
use Services\Email\EmailQueueService;
use Exception;

class AuthService
{
    private $db;
    private $jwtHandler;
    private $sessionManager;
    private $emailService;     // Servicio directo de email
    private $emailQueue;       // Cola de emails (solo para reintentos)


    public function __construct()
    {
        $this->db = new \Mysql(); // Utiliza tu clase de conexión a DB
        $this->jwtHandler = new JwtHandler();
        $this->sessionManager = \SessionManager::getInstance();
        $this->emailService = new PHPMailerService();  // Para envío directo
        $this->emailQueue = new EmailQueueService();   // Para encolado en caso de fallo
    }

    /**
     * Maneja el proceso de login
     * 
     * @param string $email Email o nombre de usuario
     * @param string $password Contraseña sin hash
     * @return array Resultado del intento de login
     */
    public function login($email, $password)
    {
        try {
            // 1. Verificar credenciales con el SP
            $requestUser = $this->db->executeProcedureWithParametersOut(
                'sp_login_usuario',
                [$email, hash("SHA256", $password)], // Mantenemos el hash por compatibilidad
                [
                    'codigo',
                    'mensaje',
                    'id_usuario',
                    'tipo_usuario',
                    'nombres',
                    'apellidos',
                    'correo',
                    'max_sesiones',
                    'estado'
                ]
            );

            if (empty($requestUser) || $requestUser['outParams']['codigo'] != 1) {
                // Credenciales incorrectas o cuenta no activa
                return [
                    'status' => false,
                    'msg' => $requestUser['outParams']['mensaje'] ?? 'Error de autenticación'
                ];
            }

            // 2. Generar ID de sesión único
            $sessionId = $this->generateUuid();

            // 3. Generar token JWT
            $token = $this->jwtHandler->generateToken(
                [
                    'id_jugador' => $requestUser['outParams']['id_usuario'],
                    'tipo_usuario' => $requestUser['outParams']['tipo_usuario']
                ],
                $sessionId
            );

            // 4. Crear registro de sesión
            $expiration = date('Y-m-d H:i:s', strtotime('+24 hours'));
            $deviceInfo = json_encode([
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown'
            ]);

            $sessionResult = $this->db->executeProcedureWithParametersOut(
                'sp_crear_sesion',
                [
                    $sessionId,
                    $requestUser['outParams']['id_usuario'],
                    $expiration,
                    $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
                    $deviceInfo
                ],
                ['codigo', 'mensaje']
            );

            if (empty($sessionResult) || $sessionResult['outParams']['codigo'] != 1) {
                throw new Exception('Error al crear sesión: ' .
                    ($sessionResult['outParams']['mensaje'] ?? 'Error desconocido'));
            }

            // 5. Iniciar sesión con SessionManager
            $this->sessionManager->initSession([
                'id_usuario' => $requestUser['outParams']['id_usuario'],
                'tipo_usuario' => $requestUser['outParams']['tipo_usuario'],
                'nombres' => $requestUser['outParams']['nombres'],
                'apellidos' => $requestUser['outParams']['apellidos'],
                'correo' => $requestUser['outParams']['correo'],
                'session_id' => $sessionId,
                'token' => $token
            ]);

            return [
                'status' => true,
                'msg' => 'Usuario accede correctamente',
                'token' => $token,
                'session_id' => $sessionId
            ];
        } catch (Exception $e) {
            return [
                'status' => false,
                'msg' => 'Error en el sistema: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Obtiene información detallada del perfil
     * 
     * @param int $userId ID del usuario
     * @return array Información del perfil o error
     */
    public function getProfileInfo($userId)
    {
        try {
            $response = $this->db->executeProcedureWithParametersOut(
                'sp_obtener_info_perfil',
                [$userId],
                ['codigo', 'mensaje']
            );

            if (empty($response) || $response['outParams']['codigo'] != 1) {
                return [
                    'status' => false,
                    'msg' => $response['outParams']['mensaje'] ?? 'Error al obtener información del perfil'
                ];
            }

            return [
                'status' => true,
                'profileData' => $response['results'][0] ?? [],
                'msg' => $response['outParams']['mensaje']
            ];
        } catch (\Exception $e) {
            error_log("Error en getProfileInfo: " . $e->getMessage());
            return [
                'status' => false,
                'msg' => 'Error al obtener información del perfil: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Actualiza información del perfil
     * 
     * @param int $userId ID del usuario
     * @param string $firstName Nombre
     * @param string $lastName Apellido
     * @return array Resultado de la actualización
     */
    public function updateProfileInfo($userId, $firstName, $lastName)
    {
        try {
            $result = $this->db->executeProcedureWithParametersOut(
                'sp_actualizar_info_perfil',
                [$userId, $firstName, $lastName],
                ['codigo', 'mensaje']
            );

            if (empty($result) || $result['outParams']['codigo'] != 1) {
                return [
                    'status' => false,
                    'msg' => $result['outParams']['mensaje'] ?? 'Error al actualizar perfil'
                ];
            }

            return [
                'status' => true,
                'msg' => $result['outParams']['mensaje']
            ];
        } catch (\Exception $e) {
            error_log("Error en updateProfileInfo: " . $e->getMessage());
            return [
                'status' => false,
                'msg' => 'Error al actualizar perfil: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Inicia el proceso de recuperación de contraseña
     * 
     * @param string $email Correo electrónico
     * @return array Resultado del proceso
     */
    public function requestPasswordReset($email)
    {
        try {
            // Generar token único
            $resetToken = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+2 hour'));

            // Actualizar en base de datos
            $result = $this->db->executeProcedureWithParametersOut(
                'sp_actualizar_token_recuperacion',
                [$email, $resetToken, $expires],
                ['codigo', 'mensaje', 'id_jugador']
            );

            if (empty($result) || $result['outParams']['codigo'] != 1) {
                // No revelamos si el correo existe o no por seguridad
                return [
                    'status' => true,
                    'msg' => 'Si el correo está registrado, se ha enviado un enlace de recuperación.'
                ];
            }

            // Enviar correo electrónico
            $resetLink = base_url() . '/resetpassword?token=' . $resetToken;
            $this->sendPasswordResetEmail($email, $resetLink);

            return [
                'status' => true,
                'msg' => 'Se ha enviado un enlace de recuperación a tu correo electrónico.'
            ];
        } catch (Exception $e) {
            return [
                'status' => false,
                'msg' => 'Error al procesar la solicitud'
            ];
        }
    }

    /**
     * Realiza el cambio de contraseña
     * 
     * @param string $token Token de recuperación
     * @param string $newPassword Nueva contraseña
     * @return array Resultado del proceso
     */
    public function resetPassword($token, $newPassword)
    {
        try {
            // Verificar token y usuario con el SP
            $verifyResult = $this->db->executeProcedureWithParametersOut(
                'sp_verificar_token_recuperacion',
                [$token],
                ['codigo', 'mensaje', 'id_jugador']
            );

            if (empty($verifyResult) || $verifyResult['outParams']['codigo'] != 1) {
                return [
                    'status' => false,
                    'msg' => $verifyResult['outParams']['mensaje'] ?? 'Token inválido o expirado'
                ];
            }

            $userId = $verifyResult['outParams']['id_jugador'];

            // Hash de la nueva contraseña
            $passwordHash = hash("SHA256", $newPassword); // Mantenemos el hash por compatibilidad

            // Actualizar contraseña y cerrar sesiones
            $result = $this->db->executeProcedureWithParametersOut(
                'sp_cambiar_password',
                [$userId, $passwordHash, $token],
                ['codigo', 'mensaje']
            );

            if (empty($result) || $result['outParams']['codigo'] != 1) {
                return [
                    'status' => false,
                    'msg' => $result['outParams']['mensaje'] ?? 'Error al cambiar la contraseña'
                ];
            }

            return [
                'status' => true,
                'msg' => 'Contraseña actualizada correctamente'
            ];
        } catch (Exception $e) {
            return [
                'status' => false,
                'msg' => 'Error al procesar la solicitud'
            ];
        }
    }

    // Método para cambiar la contraseña del usuario (versión sin token)
    public function changeUserPassword($userId, $currentPassword, $newPassword)
    {
        try {
            $result = $this->db->executeProcedureWithParametersOut(
                'sp_cambiar_password_usuario',
                [$userId, hash("SHA256", $currentPassword), hash("SHA256", $newPassword)],
                ['codigo', 'mensaje']
            );

            if (empty($result) || $result['outParams']['codigo'] != 1) {
                return [
                    'status' => false,
                    'msg' => $result['outParams']['mensaje'] ?? 'Error al cambiar contraseña'
                ];
            }

            return [
                'status' => true,
                'msg' => $result['outParams']['mensaje']
            ];
        } catch (\Exception $e) {
            error_log("Error en changeUserPassword: " . $e->getMessage());
            return [
                'status' => false,
                'msg' => 'Error al cambiar contraseña: ' . $e->getMessage()
            ];
        }
    }

    // Método para actualizar el correo electrónico
    public function updateUserEmail($userId, $newEmail, $password)
    {
        try {
            $result = $this->db->executeProcedureWithParametersOut(
                'sp_actualizar_correo_usuario',
                [$userId, $newEmail, hash("SHA256", $password)],
                ['codigo', 'mensaje']
            );

            if (empty($result) || $result['outParams']['codigo'] != 1) {
                return [
                    'status' => false,
                    'msg' => $result['outParams']['mensaje'] ?? 'Error al actualizar correo'
                ];
            }

            return [
                'status' => true,
                'msg' => $result['outParams']['mensaje']
            ];
        } catch (\Exception $e) {
            error_log("Error en updateUserEmail: " . $e->getMessage());
            return [
                'status' => false,
                'msg' => 'Error al actualizar correo: ' . $e->getMessage()
            ];
        }
    }


    /**
     * Obtiene todas las sesiones activas de un usuario
     * 
     * @param int $userId ID del usuario
     * @return array Lista de sesiones activas
     */
    public function getActiveSessions($userId)
    {
        try {
            $response = $this->db->executeProcedureWithParametersOut(
                'sp_obtener_sesiones_activas',
                [$userId],
                ['codigo', 'mensaje']
            );

            if (empty($response) || $response['outParams']['codigo'] != 1) {
                return [];
            }

            return $response['results'] ?? [];
        } catch (Exception $e) {
            error_log("Error al obtener sesiones: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene el historial de actividad de un usuario
     * 
     * @param int $userId ID del usuario
     * @param int $period Período en días para el historial (por defecto 90)
     * @param int $offset Desplazamiento para la paginación
     * @param int $limit Límite de registros a retornar
     * @return array Datos del historial de actividad
     */
    public function getActivityHistory($userId, $period = 90, $offset = 0, $limit = 20)
    {
        try {
            $db = new \Mysql();

            // Usamos un procedimiento almacenado para obtener el historial de actividad
            $result = $db->executeProcedureWithParametersOut(
                'sp_obtener_historial_actividad',
                [$userId, $period, $offset, $limit],
                ['codigo', 'mensaje', 'total_registros']
            );

            if (empty($result) || $result['outParams']['codigo'] != 1) {
                throw new \Exception($result['outParams']['mensaje'] ?? 'Error al obtener el historial de actividad');
            }

            // Procesar los registros para identificar tipo de evento y estado
            $history = [];
            foreach ($result['results'] as $session) {
                // Determinar tipo de evento (login/logout) y estado (success/failed)
                $isActive = isset($session['activa']) ? (bool)$session['activa'] : false;

                // Para sesiones inactivas, crear dos eventos: login y logout
                if (!$isActive) {
                    // Evento de inicio de sesión (siempre existe)
                    $history[] = [
                        'id_sesion' => $session['id_sesion'],
                        'fecha' => $session['fecha_creacion'],
                        'ip_direccion' => $session['ip_direccion'],
                        'info_dispositivo' => $session['info_dispositivo'],
                        'type' => 'login',
                        'status' => 'success'
                    ];

                    // Evento de cierre de sesión (solo si ya está cerrada)
                    $history[] = [
                        'id_sesion' => $session['id_sesion'],
                        'fecha' => $session['ultima_actividad'],
                        'ip_direccion' => $session['ip_direccion'],
                        'info_dispositivo' => $session['info_dispositivo'],
                        'type' => 'logout',
                        'status' => 'success'
                    ];
                } else {
                    // Para sesiones activas, solo mostrar el evento de inicio
                    $history[] = [
                        'id_sesion' => $session['id_sesion'],
                        'fecha' => $session['fecha_creacion'],
                        'ip_direccion' => $session['ip_direccion'],
                        'info_dispositivo' => $session['info_dispositivo'],
                        'type' => 'login',
                        'status' => 'success'
                    ];
                }
            }

            // Ordenar por fecha descendente (más reciente primero)
            usort($history, function ($a, $b) {
                return strtotime($b['fecha']) - strtotime($a['fecha']);
            });

            return [
                'status' => true,
                'history' => $history,
                'hasMore' => count($history) < (int)$result['outParams']['total_registros'],
                'totalRecords' => (int)$result['outParams']['total_registros']
            ];
        } catch (\Exception $e) {
            error_log("Error obteniendo historial de actividad: " . $e->getMessage());
            return [
                'status' => false,
                'msg' => $e->getMessage()
            ];
        }
    }

    /**
     * Cierra una sesión específica
     * 
     * @param int $userId ID del usuario
     * @param string $sessionId ID de la sesión
     * @return array Resultado de la operación
     */
    public function closeSession($userId, $sessionId)
    {
        try {
            $result = $this->db->executeProcedureWithParametersOut(
                'sp_cerrar_sesion',
                [$userId, $sessionId],
                ['codigo', 'mensaje']
            );

            if (empty($result) || $result['outParams']['codigo'] != 1) {
                return [
                    'status' => false,
                    'msg' => $result['outParams']['mensaje'] ?? 'Error al cerrar la sesión'
                ];
            }

            return [
                'status' => true,
                'msg' => $result['outParams']['mensaje']
            ];
        } catch (Exception $e) {
            return [
                'status' => false,
                'msg' => 'Error al cerrar la sesión: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Cierra todas las sesiones de un usuario
     * 
     * @param int $userId ID del usuario
     * @return array Resultado de la operación
     */
    public function closeAllSessions($userId)
    {
        try {
            $result = $this->db->executeProcedureWithParametersOut(
                'sp_cerrar_todas_sesiones',
                [$userId],
                ['codigo', 'mensaje', 'sesiones_cerradas']
            );

            if (empty($result) || $result['outParams']['codigo'] != 1) {
                return [
                    'status' => false,
                    'msg' => $result['outParams']['mensaje'] ?? 'Error al cerrar las sesiones'
                ];
            }

            return [
                'status' => true,
                'msg' => $result['outParams']['mensaje'],
                'sesiones_cerradas' => $result['outParams']['sesiones_cerradas']
            ];
        } catch (Exception $e) {
            return [
                'status' => false,
                'msg' => 'Error al cerrar las sesiones: ' . $e->getMessage()
            ];
        }
    }

    // Método para cerrar todas las sesiones excepto la actual
    public function closeAllSessionsExceptCurrent($userId, $currentSessionId)
    {
        try {
            $result = $this->db->executeProcedureWithParametersOut(
                'sp_cerrar_sesiones_excepto_actual',
                [$userId, $currentSessionId],
                ['codigo', 'mensaje', 'sesiones_cerradas']
            );

            if (empty($result) || $result['outParams']['codigo'] != 1) {
                return [
                    'status' => false,
                    'msg' => $result['outParams']['mensaje'] ?? 'Error al cerrar sesiones'
                ];
            }

            return [
                'status' => true,
                'msg' => $result['outParams']['mensaje'],
                'sesiones_cerradas' => $result['outParams']['sesiones_cerradas']
            ];
        } catch (\Exception $e) {
            error_log("Error en closeAllSessionsExceptCurrent: " . $e->getMessage());
            return [
                'status' => false,
                'msg' => 'Error al cerrar sesiones: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Genera un UUID v4
     * 
     * @return string UUID generado
     */
    private function generateUuid()
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    /**
     * Envía email de recuperación de contraseña
     * 
     * @param string $email Correo del destinatario
     * @param string $resetLink Enlace de recuperación
     * @return bool Resultado del envío
     */
    private function sendPasswordResetEmail($email, $resetLink)
    {
        $data = [
            'email' => $email,
            'resetLink' => $resetLink,
            'asunto' => 'Recuperación de contraseña - ' . name_project()
        ];

        // Intentar envío directo primero
        $sent = $this->emailService->sendTemplate(
            $email,
            'Recuperación de contraseña - ' . name_project(),
            'password_reset',  // Nombre exacto de la plantilla existente
            $data
        );

        // Si falla el envío directo, encolar para reintentos
        if (!$sent) {
            error_log("Falló el envío directo de recuperación de contraseña para: $email. Encolando...");
            return $this->emailQueue->enqueueTemplate(
                $email,
                'Recuperación de contraseña - ' . name_project(),
                'password_reset',
                $data,
                [],
                2  // Prioridad alta (2)
            );
        }
        return $sent;
    }
}
