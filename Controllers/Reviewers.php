<?php
require_once("Libraries/Reports/ReportAnalyzer.php");
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
			'reviewers/reviewers.css'
		);
		
		$this->addNavInfo($data);
		$this->views->getView($this, "reviewers", $data);
	}

	public function add_reviewers()
	{
		$data = array();
		$data['page_tag'] = "New Reviewers - " . name_project();
		$data['page_title'] = name_project();
		$data['page_name'] = "New Reviewers";
		$data['breadcrumbs'] = [
            ['name' => 'Revisores', 'url' => 'reviewers'],
            ['name' => 'Partidas', 'url' => 'reviewers/list_reviewers'],
            ['name' => 'Agregar revisor', 'url' => '']
        ];
		$data['page_functions_js'] = array(
			'jquery-3.7.1.min.js',
			'plugins/datatables/dataTables.min.js',
			'plugins/datatables/dataTables.responsive.js',
			'plugins/datatables/responsive.dataTables.js',
			'reviewers/add_reviewers.js'
		);
		$data['page_css'] =  array(
			'reviewers/add_reviewers.css',
			'game/game-focal.css',
			'analytics/base.css',
			'levels/requirements-generator.css',
			'modal-custom.css',
			'plugins/tabulator/tabulator.min.css',
			'levels/levels-base.css',
			'levels/levels-focal.css',
			'levels/create-clasification.css'
		);
		$data['page_libraries_css'] =  array(
			'plugins/datatables/dataTables.dataTables.min.css',
			'plugins/datatables/responsive.dataTables.css'
		);
		
		$this->addNavInfo($data);
		$this->views->getView($this, "add_reviewers", $data);
	}

	public function add_teacher_reviewer()
	{
		$data = array();
		$data['page_tag'] = "New Reviewers - " . name_project();
		$data['page_title'] = name_project();
		$data['page_name'] = "New Reviewers";
		$data['breadcrumbs'] = [
            ['name' => 'Revisores', 'url' => 'reviewers'],
            ['name' => 'Partidas', 'url' => 'reviewers/list_teachers_reviews'],
            ['name' => 'Agregar Docente Revisor', 'url' => '']
        ];
		$data['page_functions_js'] = array(
			'jquery-3.7.1.min.js',
			'plugins/datatables/dataTables.min.js',
			'plugins/datatables/dataTables.responsive.js',
			'plugins/datatables/responsive.dataTables.js',
			'reviewers/add_teacher_reviewer.js'
		);
		$data['page_css'] =  array(
			'reviewers/add_teacher_reviewer.css',
			'game/game-focal.css',
			'analytics/base.css',
			'levels/requirements-generator.css',
			'modal-custom.css',
			'plugins/tabulator/tabulator.min.css',
			'levels/levels-base.css',
			'levels/levels-focal.css',
			'levels/create-clasification.css'
		);
		$data['page_libraries_css'] =  array(
			'plugins/datatables/dataTables.dataTables.min.css',
			'plugins/datatables/responsive.dataTables.css'
		);
		
		$this->addNavInfo($data);
		$this->views->getView($this, "add_teacher_reviewer", $data);
	}

	public function list_reviews()
	{
		$data = array();
		$data['page_tag'] = "Reviews - " . name_project();
		$data['page_title'] = name_project();
		$data['page_name'] = "Reviews";
		$data['page_functions_js'] = array(
			'jquery-3.7.1.min.js',
			'reviewers/list_reviews.js',
		);
		$data['page_css'] =  array(
			'reviewers/add_reviews.css',
			'analytics/games.css'
		);
		$data['page_libraries_css'] =  array(
			'plugins/datatables/dataTables.dataTables.min.css',
			'plugins/datatables/responsive.dataTables.css'
		);
		
		$this->addNavInfo($data);
		$this->views->getView($this, "list_reviews", $data);
	}
	public function list_reviewers()
	{
		$data = array();
		$data['page_tag'] = "New Reviewers - " . name_project();
		$data['page_title'] = name_project();
		$data['page_name'] = "New Reviewers";
		$data['page_functions_js'] = array(
			'jquery-3.7.1.min.js',
			'reviewers/list_reviewers.js',
			
		);
		$data['page_css'] =  array(
			'reviewers/list_reviewers.css',
			'analytics/games.css'
		);
		$data['page_libraries_css'] =  array(
			'plugins/datatables/dataTables.dataTables.min.css',
			'plugins/datatables/responsive.dataTables.css'
		);
		
		$this->addNavInfo($data);
		$this->views->getView($this, "list_reviewers", $data);
	}

	public function list_teachers_reviews()
	{
		$data = array();
		$data['page_tag'] = "New Reviewers - " . name_project();
		$data['page_title'] = name_project();
		$data['page_name'] = "New Reviewers";
		$data['page_functions_js'] = array(
			'jquery-3.7.1.min.js',
			'reviewers/list_teachers_reviews.js',
			
		);
		$data['page_css'] =  array(
			'reviewers/list_teachers_reviews.css',
			'analytics/games.css'
		);
		$data['page_libraries_css'] =  array(
			'plugins/datatables/dataTables.dataTables.min.css',
			'plugins/datatables/responsive.dataTables.css'
		);
		
		$this->addNavInfo($data);
		$this->views->getView($this, "list_teachers_reviews", $data);
	}

	public function review_classification()
	{
		$data = array();
		$data['page_tag'] = "New Reviewers - " . name_project();
		$data['page_title'] = name_project();
		$data['page_name'] = "New Reviewers";
		$data['breadcrumbs'] = [
            ['name' => 'Revisores', 'url' => 'reviewers'],
            ['name' => 'Partidas', 'url' => 'reviewers/list_reviews'],
            ['name' => 'Requisitos', 'url' => '']
        ];
		$data['page_functions_js'] = array(
			'jquery-3.7.1.min.js',
			'plugins/datatables/dataTables.min.js',
			'plugins/datatables/dataTables.responsive.js',
			'plugins/datatables/responsive.dataTables.js',
			'plugins/papaparse.min.js',
			'reviewers/review_classification.js'
			
		);
		$data['page_css'] =  array(
			'game/game-focal.css',
			'levels/levels-base.css',
			'levels/levels-focal.css',
			'levels/create-clasification.css',
			'levels/create-construction.css',
			'levels/create-construction-form.css',
			'reviewers/review_classification.css',
			
		);
		$data['page_libraries_css'] =  array(
			'plugins/datatables/dataTables.dataTables.min.css',
			'plugins/datatables/responsive.dataTables.css'
		);
		
		$this->addNavInfo($data);
		$this->views->getView($this, "review_classification", $data);
	}

	public function requirements_suggestions()
	{
		$data = array();
		$data['page_tag'] = "New Reviewers - " . name_project();
		$data['page_title'] = name_project();
		$data['page_name'] = "New Reviewers";
		$data['breadcrumbs'] = [
            ['name' => 'Revisores', 'url' => 'reviewers'],
            ['name' => 'Partidas', 'url' => 'reviewers/list_reviews'],
            ['name' => 'Requisitos', 'url' => ''],
            ['name' => 'Revisiones de estudiantes', 'url' => '']
        ];
		$data['page_functions_js'] = array(
			'jquery-3.7.1.min.js',
			'plugins/datatables/dataTables.min.js',
			'plugins/datatables/dataTables.responsive.js',
			'plugins/datatables/responsive.dataTables.js',
			'plugins/papaparse.min.js',
			'reviewers/requirements_suggestions.js'
			
		);
		$data['page_css'] =  array(
			'game/game-focal.css',
			'levels/levels-base.css',
			'levels/levels-focal.css',
			'levels/create-clasification.css',
			'levels/create-construction.css',
			'levels/create-construction-form.css',
			'reviewers/requirements_suggestions.css',
			
		);
		$data['page_libraries_css'] =  array(
			'plugins/datatables/dataTables.dataTables.min.css',
			'plugins/datatables/responsive.dataTables.css'
		);
		
		$this->addNavInfo($data);
		$this->views->getView($this, "requirements_suggestions", $data);
	}

	public function get_reviewers_partida_clasificacion()
	{
		$jsonData = file_get_contents('php://input');
		$postData = json_decode($jsonData, true);
		$idJugador = $this->getUserData('id');

		$analytics = $this->model->get_reviewers_partida_clasificacion($postData, $idJugador);
		$jsonResponse = json_encode($analytics, JSON_UNESCAPED_UNICODE);
		$encryptedResponse = encryptResponse($jsonResponse);
		echo json_encode([
			'data' => $encryptedResponse // Tu función de encriptación
		]);
		exit();
	}

	public function get_teachers_reviewers_clasificacion()
	{
		$jsonData = file_get_contents('php://input');
		$postData = json_decode($jsonData, true);
		$idJugador = $this->getUserData('id');

		$analytics = $this->model->get_teachers_reviewers_clasificacion($postData, $idJugador);
		$jsonResponse = json_encode($analytics, JSON_UNESCAPED_UNICODE);
		$encryptedResponse = encryptResponse($jsonResponse);
		echo json_encode([
			'data' => $encryptedResponse // Tu función de encriptación
		]);
		exit();
	}

	public function update_reviewer()
	{
		try {
			$jsonData = file_get_contents('php://input');
			$postData = json_decode($jsonData, true);
			
			if (!isset($postData['encryptedData'])) {
				throw new Exception('Datos no recibidos');
			}

			$response = $this->model->update_reviewer($postData);
		} catch (Error $e) {
			$response = [
				'success' => false,
				'message' => 'Error al promover estudiante a revisor: ' . $e->getMessage()
			];
		} catch (Exception $e) {
			$response = [
				'success' => false,
				'message' => 'Error al promover estudiante a revisor: ' . $e->getMessage()
			];
		}

		$jsonResponse = json_encode($response, JSON_UNESCAPED_UNICODE);
		$encryptedResponse = encryptResponse($jsonResponse);
		echo json_encode([
			'data' => $encryptedResponse
		]);
		die();
	}

	public function get_requisitos_review()
	{
		$jsonData = file_get_contents('php://input');
		$postData = json_decode($jsonData, true);
		$idJugador = $this->getUserData('id');

		$data = $this->model->get_requisitos_review($postData, $idJugador);
		$jsonResponse = json_encode($data, JSON_UNESCAPED_UNICODE);
		$encryptedResponse = encryptResponse($jsonResponse);
		echo json_encode([
			'data' => $encryptedResponse // Tu función de encriptación
		]);
		exit();
	}


	public function get_requirements_suggestions()
	{
		$jsonData = file_get_contents('php://input');
		$postData = json_decode($jsonData, true);
		$idJugador = $this->getUserData('id');

		$data = $this->model->get_requirements_suggestions($postData, $idJugador);
		$jsonResponse = json_encode($data, JSON_UNESCAPED_UNICODE);
		$encryptedResponse = encryptResponse($jsonResponse);
		echo json_encode([
			'data' => $encryptedResponse // Tu función de encriptación
		]);
		exit();
	}

	public function get_original_requirement()
	{
		$jsonData = file_get_contents('php://input');
		$postData = json_decode($jsonData, true);
		$idJugador = $this->getUserData('id');

		$data = $this->model->get_original_requirement($postData, $idJugador);
		$jsonResponse = json_encode($data, JSON_UNESCAPED_UNICODE);
		$encryptedResponse = encryptResponse($jsonResponse);
		echo json_encode([
			'data' => $jsonResponse // Tu función de encriptación
		]);
		exit();
	}

	public function update_original_requirement()
	{
		try {
			$jsonData = file_get_contents('php://input');
			$postData = json_decode($jsonData, true);
			$idJugador = $this->getUserData('id');
			
			if (!isset($postData['encryptedData'])) {
				throw new Exception('Datos no recibidos');
			}

			$response = $this->model->update_original_requirement($postData, $idJugador);
		} catch (Error $e) {
			$response = [
				'success' => false,
				'message' => 'Error al promover estudiante a revisor: ' . $e->getMessage()
			];
		} catch (Exception $e) {
			$response = [
				'success' => false,
				'message' => 'Error al promover estudiante a revisor: ' . $e->getMessage()
			];
		}

		$jsonResponse = json_encode($response, JSON_UNESCAPED_UNICODE);
		$encryptedResponse = encryptResponse($jsonResponse);
		echo json_encode([
			'data' => $encryptedResponse
		]);
		die();
	}

	public function update_teacher_reviewer()
	{
		try {
			$jsonData = file_get_contents('php://input');
			$postData = json_decode($jsonData, true);
			
			if (!isset($postData['encryptedData'])) {
				throw new Exception('Datos no recibidos');
			}

			$response = $this->model->update_teacher_reviewer($postData);
		} catch (Error $e) {
			$response = [
				'success' => false,
				'message' => 'Error al promover docente a revisor: ' . $e->getMessage()
			];
		} catch (Exception $e) {
			$response = [
				'success' => false,
				'message' => 'Error al promover docente a revisor: ' . $e->getMessage()
			];
		}

		$jsonResponse = json_encode($response, JSON_UNESCAPED_UNICODE);
		$encryptedResponse = encryptResponse($jsonResponse);
		echo json_encode([
			'data' => $encryptedResponse
		]);
		die();
	}
	
}