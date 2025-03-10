<?php

class Logout extends Controllers
{
	private $sessionManager;

	public function __construct()
	{
		$this->sessionManager = SessionManager::getInstance();
		parent::__construct();
	}

    public function logout() {
        $this->sessionManager->destroySession();
        header('Location: ' . base_url() . '/login');
        die();
    }
}