<?php
class ReviewerStudentsMenu extends AuthController{
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
			'reviewerStudentsMenu/reviewerStudents.js'
		);
		$data['page_css'] =  array(
			'reviewerStudentsMenu/reviewerStudents.css',
		);
		
		$this->addNavInfo($data);
		$this->views->getView($this, "reviewerStudents", $data);
	}

	public function reviewerStudentsMenu()
	{
		$data = array();
		$data['page_tag'] = "reviewerStudentsMenu - " . name_project();
		$data['page_title'] = name_project();
		$data['page_name'] = "reviewerStudentsMenu";
		$data['page_functions_js'] = array(
			'jquery-3.7.1.min.js',
			'reviewerStudentsMenu/reviewerStudentsMenu.js'
		);
		$data['page_css'] =  array(
			'reviewerStudentsMenu/reviewerStudentsMenu.css',
		);
		
		$this->addNavInfo($data);
		$this->views->getView($this, "reviewerStudentsMenu", $data);
	}

	public function feedback_suggestions_list()
	{
		$data = array();
		$data['page_tag'] = "feedback_suggestions_list - " . name_project();
		$data['page_title'] = name_project();
		$data['page_name'] = "feedback_suggestions_list";
		$data['page_functions_js'] = array(
			'jquery-3.7.1.min.js',
			'reviewerStudentsMenu/feedback_suggestions_list.js'
		);
		$data['page_css'] =  array(
			'reviewerStudentsMenu/feedback_suggestions_list.css',
		);
		
		$this->addNavInfo($data);
		$this->views->getView($this, "feedback_suggestions_list", $data);
	}

	public function feedback_suggestions()
	{
		$data = array();
		$data['page_tag'] = "New Reviewers - " . name_project();
		$data['page_title'] = name_project();
		$data['page_name'] = "New Reviewers";
		$data['breadcrumbs'] = [
            ['name' => 'Revisiones', 'url' => 'reviewerStudentsMenu'],
            ['name' => 'Partidas', 'url' => 'reviewerStudentsMenu/feedback_suggestions_list'],
            ['name' => 'Requisitos', 'url' => ''],
            ['name' => 'Revisiones de estudiantes', 'url' => '']
        ];
		$data['page_functions_js'] = array(
			'jquery-3.7.1.min.js',
			'plugins/datatables/dataTables.min.js',
			'plugins/datatables/dataTables.responsive.js',
			'plugins/datatables/responsive.dataTables.js',
			'plugins/papaparse.min.js',
			'reviewerStudentsMenu/feedback_suggestions.js'
			
		);
		$data['page_css'] =  array(
			'game/game-focal.css',
			'levels/levels-base.css',
			'levels/levels-focal.css',
			'levels/create-clasification.css',
			'levels/create-construction.css',
			'levels/create-construction-form.css',
			'reviewerStudentsMenu/feedback_suggestions.css',
			
		);
		$data['page_libraries_css'] =  array(
			'plugins/datatables/dataTables.dataTables.min.css',
			'plugins/datatables/responsive.dataTables.css'
		);
		
		$this->addNavInfo($data);
		$this->views->getView($this, "feedback_suggestions", $data);
	}

	public function original_requirements()
	{
		$data = array();
		$data['page_tag'] = "New Reviewers - " . name_project();
		$data['page_title'] = name_project();
		$data['page_name'] = "New Reviewers";
		$data['breadcrumbs'] = [
            ['name' => 'Partidas', 'url' => 'ReviewerStudentsMenu'],
            ['name' => 'Requisitos', 'url' => '']
        ];
		$data['page_functions_js'] = array(
			'jquery-3.7.1.min.js',
			'plugins/datatables/dataTables.min.js',
			'plugins/datatables/dataTables.responsive.js',
			'plugins/datatables/responsive.dataTables.js',
			'plugins/papaparse.min.js',
			'reviewerStudentsMenu/original_requirements.js'
			
		);
		$data['page_css'] =  array(
			'game/game-focal.css',
			'levels/levels-base.css',
			'levels/create-clasification.css',
			'levels/create-construction-form.css',
			'reviewerStudentsMenu/original_requirements.css',
			
		);
		$data['page_libraries_css'] =  array(
			'plugins/datatables/dataTables.dataTables.min.css',
			'plugins/datatables/responsive.dataTables.css'
		);
		
		$this->addNavInfo($data);
		$this->views->getView($this, "original_requirements", $data);
	}

	public function feedback_suggestions_details()
	{
		$data = array();
		$data['page_tag'] = "New Reviewers - " . name_project();
		$data['page_title'] = name_project();
		$data['page_name'] = "New Reviewers";
		$data['breadcrumbs'] = [
            ['name' => 'Revisores', 'url' => 'reviewerStudentsMenu'],
            ['name' => 'Partidas', 'url' => 'reviewerStudentsMenu/feedback_suggestions_list'],
            ['name' => 'Requisitos', 'url' => ''],
            ['name' => 'Revisiones de estudiantes', 'url' => '']
        ];
		$data['page_functions_js'] = array(
			'jquery-3.7.1.min.js',
			'plugins/datatables/dataTables.min.js',
			'plugins/datatables/dataTables.responsive.js',
			'plugins/datatables/responsive.dataTables.js',
			'plugins/papaparse.min.js',
			'reviewerStudentsMenu/feedback_suggestions_details.js'
			
		);
		$data['page_css'] =  array(
			'game/game-focal.css',
			'levels/levels-base.css',
			'levels/levels-focal.css',
			'levels/create-clasification.css',
			'levels/create-construction.css',
			'levels/create-construction-form.css',
			'reviewerStudentsMenu/feedback_suggestions_details.css',
			
		);
		$data['page_libraries_css'] =  array(
			'plugins/datatables/dataTables.dataTables.min.css',
			'plugins/datatables/responsive.dataTables.css'
		);
		
		$this->addNavInfo($data);
		$this->views->getView($this, "feedback_suggestions_details", $data);
	}

	public function get_partidas_estudiante_revisor()
	{
		try {
			$jsonData = file_get_contents('php://input');
			$postData = json_decode($jsonData, true);
			$idJugador = $this->getUserData('id');

			if (!isset($postData['encryptedData'])) {
				throw new Exception('Datos no recibidos');
			}

			$decryptedData = decryptData($postData['encryptedData']);
			$data = json_decode($decryptedData, true);

			if (!$data) {
				throw new Exception('Error al descifrar datos');
			}

			$response = $this->model->get_partidas_estudiante_revisor($data, $idJugador);

			$jsonResponse = json_encode($response, JSON_UNESCAPED_UNICODE);
			$encryptedResponse = encryptResponse($jsonResponse);

			echo json_encode([
				'data' => $encryptedResponse
			]);
		} catch (Exception $e) {
			$errorResponse = [
				'success' => false,
				'message' => 'Error: ' . $e->getMessage()
			];

			$jsonResponse = json_encode($errorResponse, JSON_UNESCAPED_UNICODE);
			$encryptedResponse = encryptResponse($jsonResponse);

			echo json_encode([
				'data' => $encryptedResponse
			]);
		}
		die();
	}

	public function get_original_requirement_reviewer()
	{
		$jsonData = file_get_contents('php://input');
		$postData = json_decode($jsonData, true);
		$idJugador = $this->getUserData('id');

		$data = $this->model->get_original_requirement_reviewer($postData, $idJugador);
		$jsonResponse = json_encode($data, JSON_UNESCAPED_UNICODE);
		$encryptedResponse = encryptResponse($jsonResponse);
		echo json_encode([
			'data' => $encryptedResponse // Tu función de encriptación
		]);
		exit();
	}

	public function create_suggestion_requirements()
	{
		try {
			$idJugador = $this->getUserData('id'); // Por ahora usamos 1 como ejemplo
			$jsonData = file_get_contents('php://input');
			$postData = json_decode($jsonData, true);
			$response = $this->model->create_suggestion_requirements($postData, $idJugador);
		} catch (Error $e) {
			// Captura errores fatales como "Call to undefined method"
			$response = [
				'success' => false,
				'message' => 'Error al crear el requisito: ' . $e->getMessage()
			];
		} catch (Exception $e) {
			$response = [
				'success' => false,
				'message' => 'Error al crear el requisito: ' . $e->getMessage()
			];
		}
		//echo json_encode($response);
		$jsonResponse = json_encode($response, JSON_UNESCAPED_UNICODE);
		$encryptedResponse = encryptResponse($jsonResponse);
		echo json_encode([
			'data' => $encryptedResponse // Tu función de encriptación
		]);
		die();
	}

	public function get_feedback_suggestions()
	{
		$jsonData = file_get_contents('php://input');
		$postData = json_decode($jsonData, true);
		$idJugador = $this->getUserData('id');

		$data = $this->model->get_feedback_suggestions($postData, $idJugador);
		$jsonResponse = json_encode($data, JSON_UNESCAPED_UNICODE);
		$encryptedResponse = encryptResponse($jsonResponse);
		echo json_encode([
			'data' => $encryptedResponse // Tu función de encriptación
		]);
		exit();
	}

	public function get_feedback_suggestions_details()
	{
		$jsonData = file_get_contents('php://input');
		$postData = json_decode($jsonData, true);
		$idJugador = $this->getUserData('id');

		$data = $this->model->get_feedback_suggestions_details($postData, $idJugador);
		$jsonResponse = json_encode($data, JSON_UNESCAPED_UNICODE);
		$encryptedResponse = encryptResponse($jsonResponse);
		echo json_encode([
			'data' => $encryptedResponse // Tu función de encriptación
		]);
		exit();
	}
	
}