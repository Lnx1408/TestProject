<?php

abstract class AuthController extends Controllers {
    protected $sessionManager;
    protected $allowedRoles = [];

    public function __construct($roles = []) {
        parent::__construct();
        $this->sessionManager = SessionManager::getInstance();
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