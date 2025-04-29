<?php

class SessionManager
{
    private static $instance = null;
    private const SESSION_STARTED = true;
    private const SESSION_NOT_STARTED = false;
    private $sessionState = self::SESSION_NOT_STARTED;

    // Roles/Tipos de usuario usando las letras correspondientes
    public const ROLE_ADMIN = 'A';
    public const ROLE_TEACHER = 'D';  // D de Docente
    public const ROLE_STUDENT = 'E';  // E de Estudiante

    // Array asociativo para nombres de roles (útil para mostrar en interfaz)
    private const ROLE_NAMES = [
        self::ROLE_ADMIN => 'Administrador',
        self::ROLE_TEACHER => 'Docente',
        self::ROLE_STUDENT => 'Estudiante'
    ];

    private function __construct()
    {
        $this->startSession();
    }

    public static function getInstance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();

            // Si ya existe una sesión, restauramos el estado
            if (isset($_SESSION['user'])) {
                self::$instance->sessionState = self::SESSION_STARTED;
            }
        }
        return self::$instance;
    }


    public function startSession()
    {
        if ($this->sessionState == self::SESSION_NOT_STARTED) {
            $this->sessionState = session_start();
        }
        return $this->sessionState;
    }

    public function initSession($userData)
    {
        if (!$this->isLoggedIn()) {
            $this->startSession();
            $_SESSION['user'] = [
                'id' => $userData['id_usuario'],
                'type' => $userData['tipo_usuario'],
                'typeName' => self::ROLE_NAMES[$userData['tipo_usuario']] ?? 'Usuario',
                'firstName' => $userData['nombres'],
                'lastName' => $userData['apellidos'],
                'email' => $userData['correo'],
                'session_id' => $userData['session_id'],
                'token' => $userData['token'],
                'isLoggedIn' => true,
                'lastActivity' => time()
            ];
            $this->sessionState = self::SESSION_STARTED;
        }
    }

    public function setLanguage($lang)
    {
        $_SESSION['language'] = $lang;
    }

    public function getLanguage()
    {
        return $_SESSION['language'] ?? 'es';
    }

    public function isLoggedIn()
    {
        return isset($_SESSION['user']) &&
            isset($_SESSION['user']['isLoggedIn']) &&
            $_SESSION['user']['isLoggedIn'] === true;
    }

    public function getUserData($key = null)
    {
        $this->startSession(); // Asegurar que la sesión está iniciada
        if ($key && isset($_SESSION['user'][$key])) {
            return $_SESSION['user'][$key];
        }
        return isset($_SESSION['user']) ? $_SESSION['user'] : null;
    }

    public function getRoleName($role = null)
    {
        if ($role === null) {
            $role = $this->getUserData('type');
        }
        return self::ROLE_NAMES[$role] ?? 'Usuario';
    }

    /**
     * Obtiene el ID de sesión actual
     * 
     * @return string|null ID de sesión o null si no hay sesión
     */
    public function getSessionId()
    {
        return $this->getUserData('session_id');
    }

    /**
     * Obtiene el token JWT actual
     * 
     * @return string|null Token JWT o null si no hay sesión
     */
    public function getToken()
    {
        return $this->getUserData('token');
    }

    /**
     * Verifica si un token es válido
     * 
     * @param string $token Token JWT a validar
     * @return bool Resultado de la validación
     */
    public function validateToken($token)
    {
        $jwtHandler = new \Libraries\Security\JwtHandler();
        $decoded = $jwtHandler->validateToken($token);

        if (!$decoded || !isset($decoded['data'])) {
            return false;
        }

        // Verificar si la sesión sigue activa en la base de datos
        $db = new \Mysql();
        $sessionId = $decoded['data']['session_id'];

        $result = $db->executeProcedureWithParametersOut(
            'sp_verificar_sesion_activa',
            [$sessionId],
            ['codigo', 'mensaje', 'activa']
        );

        return !empty($result) && $result['outParams']['codigo'] == 1 && $result['outParams']['activa'] == 1;
    }

    /**
     * Actualiza los datos de la sesión actual
     * 
     * @param array $sessionData Nuevos datos de sesión
     * @return bool Resultado de la operación
     */
    public function updateSessionData($sessionData)
    {
        if (!$this->isLoggedIn()) {
            return false;
        }

        foreach ($sessionData as $key => $value) {
            if (isset($_SESSION['user'][$key])) {
                $_SESSION['user'][$key] = $value;
            }
        }

        // Actualizar timestamp de última actividad
        $_SESSION['user']['lastActivity'] = time();

        return true;
    }


    public function hasRole($roles)
    {
        if (!$this->isLoggedIn()) return false;

        $userRole = $this->getUserData('type');
        if (is_array($roles)) {
            return in_array($userRole, $roles);
        }
        return $userRole === $roles;
    }

    public function checkPermission($requiredRoles)
    {
        if (!$this->isLoggedIn()) {
            header('Location: ' . base_url() . '/login');
            exit();
        }

        if (!empty($requiredRoles) && !$this->hasRole($requiredRoles)) {
            header('Location: ' . base_url() . '/error/unauthorized');
            exit();
        }
    }

    public function refreshSession()
    {
        if ($this->isLoggedIn()) {
            $this->startSession();
            $_SESSION['user']['lastActivity'] = time();
        }
    }

    public function destroySession()
    {
        $this->startSession();
        if ($this->sessionState == self::SESSION_STARTED) {
            unset($_SESSION['user']);
            session_destroy();
            $this->sessionState = self::SESSION_NOT_STARTED;
            self::$instance = null; // Importante: resetear la instancia
            return true;
        }
        return false;
    }

    // Evitar la clonación del objeto
    private function __clone() {}

    // Evitar la deserialización
    public function __wakeup()
    {
        throw new Exception("Cannot unserialize singleton");
    }
}
