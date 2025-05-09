<?php

class Reviewers extends AuthController{
    public function __construct() {
        // Especificar roles permitidos para este controlador
        parent::__construct([
            SessionManager::ROLE_ADMIN,
			SessionManager::ROLE_TEACHER
        ]);
    }

    public function reviewers()
	{
		$data = array();
		$data['page_tag'] = "Reviewers - " . name_project();
		$data['page_title'] = name_project();
		$data['page_name'] = "Reviewers";
		$data['page_functions_js'] = array(
			'jquery-3.7.1.min.js',
			'reviewers/reviewers.js'
		);
		$data['page_css'] =  array(
			'game/game-focal.css',
			'reviewers/reviewers.css'
		);
		$data['page_libraries_css'] =  array();
		$this->addNavInfo($data);
		$this->views->getView($this, "reviewers", $data);
	}
}