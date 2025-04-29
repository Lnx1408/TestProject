<?php

class Logout extends AuthController
{
	private $authService;

	public function __construct()
	{
		$this->sessionManager = SessionManager::getInstance();
		$this->authService = new Services\Auth\AuthService();
		parent::__construct();
	}

    public function logout() {
		// Obtener el ID de usuario y de sesión antes de destruir la sesión
        $userId = $this->sessionManager->getUserData('id');
        $sessionId = $this->sessionManager->getSessionId();
        
        if ($userId && $sessionId) {
            // Marcar la sesión actual como inactiva en la base de datos
            $this->authService->closeSession($userId, $sessionId);
        }
        
        // Destruir la sesión de PHP
        $this->sessionManager->destroySession();
        header('Location: ' . base_url() . '/login');
        die();
    }
}