<?php

class Settings extends AuthController
{
    private $authService;

    public function __construct()
    {
        // Permitir acceso a todos los tipos de usuarios autenticados
        parent::__construct([
            SessionManager::ROLE_ADMIN,
            SessionManager::ROLE_TEACHER,
            SessionManager::ROLE_STUDENT
        ]);

        $this->authService = new Services\Auth\AuthService();
    }

    /**
     * Vista principal de ajustes (acordeón)
     */
    public function settings()
    {
        $data = array();
        $data['page_tag'] = "Ajustes - " . name_project();
        $data['page_title'] = name_project();
        $data['page_name'] = "Ajustes";
        $data['page_functions_js'] = array(
            'jquery-3.7.1.min.js',
            'plugins/sweetalert2.all.min.js',
            'settings/settings.js'
        );
        $data['page_css'] = array(
            'settings/settings.css'
        );


        $this->addNavInfo($data);
        $this->views->getView($this, "settings", $data);
    }

    /**
     * Vista de información de perfil
     */
    public function profile()
    {
        $data = array();
        $data['page_tag'] = "Perfil - " . name_project();
        $data['page_title'] = name_project();
        $data['page_name'] = "Perfil";
        $data['breadcrumbs'] = [
            ['name' => 'Ajustes', 'url' => 'settings'],
            ['name' => 'Información Personal', 'url' => 'settings?section=personal'],
            ['name' => 'Perfil', 'url' => '']
        ];
        $data['page_functions_js'] = array(
            'jquery-3.7.1.min.js',
            'plugins/sweetalert2.all.min.js',
            'settings/profile.js'
        );
        $data['page_css'] = array(
            'settings/settings.css',
            'settings/profile.css'
        );

        $this->addNavInfo($data);
        $this->views->getView($this, "profile", $data);
    }

    /**
     * Vista de historial de sesiones
     */
    public function sessions()
    {
        $data = array();
        $data['page_tag'] = "Sesiones - " . name_project();
        $data['page_title'] = name_project();
        $data['page_name'] = "Sesiones";
        $data['breadcrumbs'] = [
            ['name' => 'Ajustes', 'url' => 'settings'],
            ['name' => 'Actividad y Seguridad', 'url' => 'settings?section=security'],
            ['name' => 'Sesiones', 'url' => '']
        ];
        $data['page_functions_js'] = array(
            'jquery-3.7.1.min.js',
            'plugins/sweetalert2.all.min.js',
            'settings/sessions.js'
        );
        $data['page_css'] = array(
            'settings/settings.css',
            'settings/sessions.css'
        );

        $this->addNavInfo($data);
        $this->views->getView($this, "sessions", $data);
    }

    /**
     * Vista de historial de actividad
     * Muestra un registro detallado de inicios y cierres de sesión
     */
    public function activity()
    {
        $data = array();
        $data['page_tag'] = "Historial de Actividad - " . name_project();
        $data['page_title'] = name_project();
        $data['page_name'] = "Historial de Actividad";
        $data['breadcrumbs'] = [
            ['name' => 'Ajustes', 'url' => 'settings'],
            ['name' => 'Actividad y Seguridad', 'url' => 'settings?section=security'],
            ['name' => 'Historial de Actividad', 'url' => '']
        ];
        $data['page_functions_js'] = array(
            'jquery-3.7.1.min.js',
            'plugins/sweetalert2.all.min.js',
            'settings/activity.js'
        );
        $data['page_css'] = array(
            'settings/settings.css',
            'settings/activity.css'
        );

        $this->addNavInfo($data);
        $this->views->getView($this, "activity", $data);
    }

    /**
     * Vista de cambio de contraseña
     */
    public function password()
    {
        $data = array();
        $data['page_tag'] = "Cambiar Contraseña - " . name_project();
        $data['page_title'] = name_project();
        $data['page_name'] = "Cambiar Contraseña";
        $data['breadcrumbs'] = [
            ['name' => 'Ajustes', 'url' => 'settings'],
            ['name' => 'Configuración de Cuenta', 'url' => 'settings?section=account'],
            ['name' => 'Cambiar Contraseña', 'url' => '']
        ];
        $data['page_functions_js'] = array(
            'jquery-3.7.1.min.js',
            'plugins/sweetalert2.all.min.js',
            'settings/password.js'
        );
        $data['page_css'] = array(
            'settings/settings.css',
            'settings/password.css'
        );

        $this->addNavInfo($data);
        $this->views->getView($this, "password", $data);
    }

    /**
     * Vista de cambio de correo
     */
    public function email()
    {
        $data = array();
        $data['page_tag'] = "Cambiar Correo - " . name_project();
        $data['page_title'] = name_project();
        $data['page_name'] = "Cambiar Correo";
        $data['breadcrumbs'] = [
            ['name' => 'Ajustes', 'url' => 'settings'],
            ['name' => 'Configuración de Cuenta', 'url' => 'settings?section=account'],
            ['name' => 'Cambiar Correo', 'url' => '']
        ];
        $data['page_functions_js'] = array(
            'jquery-3.7.1.min.js',
            'plugins/sweetalert2.all.min.js',
            'settings/email.js'
        );
        $data['page_css'] = array(
            'settings/settings.css',
            'settings/email.css'
        );

        $this->addNavInfo($data);
        $this->views->getView($this, "email", $data);
    }

    // Métodos para el API
    public function changePassword()
    {
        try {
            $jsonData = file_get_contents('php://input');
            $postData = json_decode($jsonData, true);

            if (!isset($postData['encryptedData'])) {
                throw new \Exception('Datos no recibidos');
            }

            // Desencriptar datos
            $decryptedData = decryptData($postData['encryptedData']);
            $data = json_decode($decryptedData, true);

            if (!$data || !isset($data['currentPassword']) || !isset($data['newPassword'])) {
                throw new \Exception('Datos incompletos');
            }

            $userId = $this->getUserData('id');
            $currentPassword = $data['currentPassword'];
            $newPassword = $data['newPassword'];

            // Validaciones adicionales
            if (empty($currentPassword) || empty($newPassword)) {
                throw new \Exception('Las contraseñas no pueden estar vacías');
            }

            if (strlen($newPassword) < 8) {
                throw new \Exception('La nueva contraseña debe tener al menos 8 caracteres');
            }

            $response = $this->authService->changeUserPassword($userId, $currentPassword, $newPassword);

            $jsonResponse = json_encode($response, JSON_UNESCAPED_UNICODE);
            $encryptedResponse = encryptResponse($jsonResponse);

            echo json_encode(['data' => $encryptedResponse]);
            die();
        } catch (\Exception $e) {
            $response = [
                'status' => false,
                'msg' => 'Error al cambiar contraseña: ' . $e->getMessage()
            ];

            echo json_encode([
                'data' => encryptResponse(json_encode($response))
            ]);
            die();
        }
    }

    public function changeEmail()
    {
        try {
            $jsonData = file_get_contents('php://input');
            $postData = json_decode($jsonData, true);

            if (!isset($postData['encryptedData'])) {
                throw new \Exception('Datos no recibidos');
            }

            // Desencriptar datos
            $decryptedData = decryptData($postData['encryptedData']);
            $data = json_decode($decryptedData, true);

            if (!$data || !isset($data['newEmail']) || !isset($data['password'])) {
                throw new \Exception('Datos incompletos');
            }

            $userId = $this->getUserData('id');
            $newEmail = strClean($data['newEmail']);
            $password = $data['password'];

            // Validaciones adicionales
            if (empty($newEmail) || empty($password)) {
                throw new \Exception('El correo y la contraseña no pueden estar vacíos');
            }

            // Validar formato de correo
            if (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
                throw new \Exception('El formato del correo electrónico no es válido');
            }

            $response = $this->authService->updateUserEmail($userId, $newEmail, $password);

            // Si la actualización fue exitosa, actualizar la sesión
            if ($response['status']) {
                $this->sessionManager->updateSessionData([
                    'email' => $newEmail
                ]);
            }

            $jsonResponse = json_encode($response, JSON_UNESCAPED_UNICODE);
            $encryptedResponse = encryptResponse($jsonResponse);

            echo json_encode(['data' => $encryptedResponse]);
            die();
        } catch (\Exception $e) {
            $response = [
                'status' => false,
                'msg' => 'Error al cambiar correo: ' . $e->getMessage()
            ];

            echo json_encode([
                'data' => encryptResponse(json_encode($response))
            ]);
            die();
        }
    }


    /**
     * API: Obtener información del perfil
     */
    public function getProfileInfo()
    {
        try {
            $userId = $this->getUserData('id');
            $response = $this->authService->getProfileInfo($userId);

            $jsonResponse = json_encode($response, JSON_UNESCAPED_UNICODE);
            $encryptedResponse = encryptResponse($jsonResponse);

            echo json_encode(['data' => $encryptedResponse]);
            die();
        } catch (\Exception $e) {
            $response = [
                'status' => false,
                'msg' => 'Error al obtener información: ' . $e->getMessage()
            ];

            echo json_encode([
                'data' => encryptResponse(json_encode($response))
            ]);
            die();
        }
    }

    /**
     * API: Actualizar información del perfil
     */
    public function updateProfile()
    {
        try {
            $jsonData = file_get_contents('php://input');
            $postData = json_decode($jsonData, true);

            if (!isset($postData['encryptedData'])) {
                throw new \Exception('Datos no recibidos');
            }

            // Desencriptar datos
            $decryptedData = decryptData($postData['encryptedData']);
            $data = json_decode($decryptedData, true);

            if (!$data || !isset($data['nombres']) || !isset($data['apellidos'])) {
                throw new \Exception('Datos incompletos');
            }

            $userId = $this->getUserData('id');
            $firstName = strClean($data['nombres']);
            $lastName = strClean($data['apellidos']);

            // Validaciones adicionales
            if (empty($firstName) || empty($lastName)) {
                throw new \Exception('Los nombres y apellidos no pueden estar vacíos');
            }

            $response = $this->authService->updateProfileInfo($userId, $firstName, $lastName);

            // Si la actualización fue exitosa, actualizar la sesión
            if ($response['status']) {
                $this->sessionManager->updateSessionData([
                    'firstName' => $firstName,
                    'lastName' => $lastName
                ]);
            }

            $jsonResponse = json_encode($response, JSON_UNESCAPED_UNICODE);
            $encryptedResponse = encryptResponse($jsonResponse);

            echo json_encode(['data' => $encryptedResponse]);
            die();
        } catch (\Exception $e) {
            $response = [
                'status' => false,
                'msg' => 'Error al actualizar perfil: ' . $e->getMessage()
            ];

            echo json_encode([
                'data' => encryptResponse(json_encode($response))
            ]);
            die();
        }
    }

    /**
     * API: Obtener sesiones activas
     */
    public function getActiveSessions()
    {
        try {
            $userId = $this->getUserData('id');
            $activeSessions = $this->authService->getActiveSessions($userId);

            $response = [
                'status' => true,
                'sessions' => $activeSessions,
                'currentSessionId' => $this->sessionManager->getSessionId()
            ];

            $jsonResponse = json_encode($response, JSON_UNESCAPED_UNICODE);
            $encryptedResponse = encryptResponse($jsonResponse);

            echo json_encode(['data' => $encryptedResponse]);
            die();
        } catch (\Exception $e) {
            $response = [
                'status' => false,
                'msg' => 'Error al obtener sesiones: ' . $e->getMessage()
            ];

            echo json_encode([
                'data' => encryptResponse(json_encode($response))
            ]);
            die();
        }
    }

    /**
     * API: Obtener historial de actividad
     * Devuelve el historial de inicios y cierres de sesión agrupados por fecha
     */
    public function getActivityHistory()
    {
        try {
            $userId = $this->getUserData('id');
            $period = isset($_GET['period']) ? intval($_GET['period']) : 90; // Período por defecto: 90 días

            $response = $this->authService->getActivityHistory($userId, $period);

            $jsonResponse = json_encode($response, JSON_UNESCAPED_UNICODE);
            $encryptedResponse = encryptResponse($jsonResponse);

            echo json_encode(['data' => $encryptedResponse]);
            die();
        } catch (\Exception $e) {
            $response = [
                'status' => false,
                'msg' => 'Error al obtener historial de actividad: ' . $e->getMessage()
            ];

            echo json_encode([
                'data' => encryptResponse(json_encode($response))
            ]);
            die();
        }
    }

    /**
     * API: Cerrar una sesión específica
     */
    public function closeSession()
    {
        try {
            $jsonData = file_get_contents('php://input');
            $postData = json_decode($jsonData, true);

            if (!isset($postData['encryptedData'])) {
                throw new \Exception('Datos no recibidos');
            }

            // Desencriptar datos
            $decryptedData = decryptData($postData['encryptedData']);
            $data = json_decode($decryptedData, true);

            if (!$data || !isset($data['sessionId'])) {
                throw new \Exception('Datos incompletos');
            }

            $userId = $this->getUserData('id');
            $sessionId = $data['sessionId'];

            // No permitir cerrar la sesión actual
            if ($sessionId === $this->sessionManager->getSessionId()) {
                throw new \Exception('No puedes cerrar tu sesión actual desde aquí');
            }

            $response = $this->authService->closeSession($userId, $sessionId);

            $jsonResponse = json_encode($response, JSON_UNESCAPED_UNICODE);
            $encryptedResponse = encryptResponse($jsonResponse);

            echo json_encode(['data' => $encryptedResponse]);
            die();
        } catch (\Exception $e) {
            $response = [
                'status' => false,
                'msg' => 'Error al cerrar sesión: ' . $e->getMessage()
            ];

            echo json_encode([
                'data' => encryptResponse(json_encode($response))
            ]);
            die();
        }
    }

    /**
     * API: Cerrar todas las sesiones excepto la actual
     */
    public function closeAllSessions()
    {
        try {
            $userId = $this->getUserData('id');
            $currentSessionId = $this->sessionManager->getSessionId();

            $response = $this->authService->closeAllSessionsExceptCurrent($userId, $currentSessionId);

            $jsonResponse = json_encode($response, JSON_UNESCAPED_UNICODE);
            $encryptedResponse = encryptResponse($jsonResponse);

            echo json_encode(['data' => $encryptedResponse]);
            die();
        } catch (\Exception $e) {
            $response = [
                'status' => false,
                'msg' => 'Error al cerrar sesiones: ' . $e->getMessage()
            ];

            echo json_encode([
                'data' => encryptResponse(json_encode($response))
            ]);
            die();
        }
    }
}
