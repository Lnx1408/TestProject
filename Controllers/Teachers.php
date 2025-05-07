<?php

class Teachers extends AuthController{
    public function __construct() {
        // Especificar roles permitidos para este controlador
        parent::__construct([
            SessionManager::ROLE_ADMIN
        ]);
    }

    public function teachers()
	{
		$data = array();
		$data['page_tag'] = "Teachers - " . name_project();
		$data['page_title'] = name_project();
		$data['page_name'] = "Teachers";
		$data['page_functions_js'] = array(
			'jquery-3.7.1.min.js',
			'teachers/teachers.js'
		);
		$data['page_css'] =  array(
			'game/game-focal.css',
			'teachers/teachers.css'
		);
		$data['page_libraries_css'] =  array();
		$this->addNavInfo($data);
		$this->views->getView($this, "teachers", $data);
	}
}