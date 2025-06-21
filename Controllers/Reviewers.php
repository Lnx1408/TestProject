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

	public function reviews()
	{
		$data = array();
		$data['page_tag'] = "Reviews - " . name_project();
		$data['page_title'] = name_project();
		$data['page_name'] = "Reviews";
		$data['page_functions_js'] = array(
			'jquery-3.7.1.min.js',
			'reviewers/reviews.js',
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
		$this->views->getView($this, "reviews", $data);
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
}