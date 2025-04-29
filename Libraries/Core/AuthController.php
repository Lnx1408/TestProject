<?php

abstract class AuthController extends Controllers {
    protected $sessionManager;
    protected $allowedRoles = [];

    public function __construct($roles = []) {
        parent::__construct();
        $this->sessionManager = SessionManager::getInstance();

        // Verificar si el usuario está logueado
        if (!$this->sessionManager->isLoggedIn()) {
            header('Location: ' . base_url() . '/login');
            exit();
        }
        // Verificar si el token es válido
        $token = $this->sessionManager->getToken();
        if (!$token || !$this->sessionManager->validateToken($token)) {
            // Si el token no es válido, destruir la sesión
            $this->sessionManager->destroySession();
            header('Location: ' . base_url() . '/login?error=invalid_session');
            exit();
        }
        
        $this->allowedRoles = $roles;
        $this->checkAuth();
    }

    protected function checkAuth() {
        $this->sessionManager->checkPermission($this->allowedRoles);
    }

    protected function getUserData($key = null) {
        return $this->sessionManager->getUserData($key);
    }

    protected function getUserLanguage() {
        return $this->sessionManager->getLanguage();
    }

    protected function getCurrentRoute() {
        // Obtener la URL actual y limpiarla
        $currentUrl = isset($_GET['url']) ? $_GET['url'] : '';
        return strtolower(explode('/', $currentUrl)[0]); // Obtener el primer segmento de la URL
    }

    protected function addNavInfo(&$data) {
        $data['current_section'] = $this->getCurrentRoute();
    }
}