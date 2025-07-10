<?php
class ReviewerStudents extends AuthController{
    public function __construct() {
        // Especificar roles permitidos para este controlador
        parent::__construct([
            SessionManager::ROLE_STUDENT
        ]);
    }

    public function reviewerStudents()
	{
		$data = array();
		$data['page_tag'] = "reviewerStudents - " . name_project();
		$data['page_title'] = name_project();
		$data['page_name'] = "reviewerStudents";
		$data['page_functions_js'] = array(
			'jquery-3.7.1.min.js',
			'reviewers/list_reviews.js',
			'reviewerStudents/reviewerStudents.js'
		);
		$data['page_css'] =  array(
			'reviewerStudents/reviewerStudents.css',
			'reviewers/add_reviews.css',
			'analytics/games.css'
		);
		
		$this->addNavInfo($data);
		$this->views->getView($this, "reviewerStudents", $data);
	}
	
}