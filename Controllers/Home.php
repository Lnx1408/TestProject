<?php 
	class Home extends AuthController
	{
		public function __construct() {
			// Especificar roles permitidos para este controlador
			parent::__construct([
				SessionManager::ROLE_ADMIN,
				SessionManager::ROLE_STUDENT,
				SessionManager::ROLE_TEACHER
			]);
		}

		public function home()
		{
			$data['page_id'] = 1;
			$data['page_tag'] = "Home - " . name_project();
			$data['page_title'] = "Home - " . name_project();
			$data['page_name'] = "Home";

			$this->addNavInfo($data);
			$this->views->getView($this,"home",$data);
		}

	}
 ?>