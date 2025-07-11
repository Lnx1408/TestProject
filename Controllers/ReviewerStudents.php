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
			'reviewerStudents/reviewerStudents.js'
		);
		$data['page_css'] =  array(
			'reviewerStudents/reviewerStudents.css',
		);
		
		$this->addNavInfo($data);
		$this->views->getView($this, "reviewerStudents", $data);
	}

	public function original_requirements()
	{
		$data = array();
		$data['page_tag'] = "New Reviewers - " . name_project();
		$data['page_title'] = name_project();
		$data['page_name'] = "New Reviewers";
		$data['breadcrumbs'] = [
            ['name' => 'Partidas', 'url' => 'reviewerStudents'],
            ['name' => 'Requisitos', 'url' => '']
        ];
		$data['page_functions_js'] = array(
			'jquery-3.7.1.min.js',
			'plugins/datatables/dataTables.min.js',
			'plugins/datatables/dataTables.responsive.js',
			'plugins/datatables/responsive.dataTables.js',
			'plugins/papaparse.min.js',
			'reviewerStudents/original_requirements.js'
			
		);
		$data['page_css'] =  array(
			'game/game-focal.css',
			'levels/levels-base.css',
			'levels/create-clasification.css',
			'levels/create-construction-form.css',
			'reviewers/original_requirements.css',
			
		);
		$data['page_libraries_css'] =  array(
			'plugins/datatables/dataTables.dataTables.min.css',
			'plugins/datatables/responsive.dataTables.css'
		);
		
		$this->addNavInfo($data);
		$this->views->getView($this, "original_requirements", $data);
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
	
}