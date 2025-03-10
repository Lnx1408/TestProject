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

    public function initSession($userData) {
        if (!$this->isLoggedIn()) {
            $this->startSession();
            $_SESSION['user'] = [
                'id' => $userData['id_usuario'],
                'type' => $userData['tipo_usuario'],
                'typeName' => self::ROLE_NAMES[$userData['tipo_usuario']] ?? 'Usuario',
                'firstName' => $userData['nombres'],
                'lastName' => $userData['apellidos'],
                'email' => $userData['correo'],
                'isLoggedIn' => true,
                'lastActivity' => time()
            ];
            $this->sessionState = self::SESSION_STARTED;
        }
    }

    public function setLanguage($lang) {
        $_SESSION['language'] = $lang;
    }

    public function getLanguage() {
        return $_SESSION['language'] ?? 'es';
    }

    public function isLoggedIn() {
        return isset($_SESSION['user']) && 
               isset($_SESSION['user']['isLoggedIn']) && 
               $_SESSION['user']['isLoggedIn'] === true;
    }

    public function getUserData($key = null) {
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

    public function refreshSession() {
        if ($this->isLoggedIn()) {
            $this->startSession();
            $_SESSION['user']['lastActivity'] = time();
        }
    }

    public function destroySession() {
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
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}
