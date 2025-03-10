<?php
require_once("Libraries/Factories/LevelsViewFactory.php");
class Game extends AuthController
{

	public function __construct()
	{
		parent::__construct([
			SessionManager::ROLE_STUDENT,
			SessionManager::ROLE_TEACHER
		]);
	}
	public function game() {
		$data = array();
		$data['page_tag'] = "GAME - " . name_project();
		$data['page_title'] = name_project();
		$data['page_name'] = "GAME";
		$data['page_functions_js'] = array(
			'jquery-3.7.1.min.js',
			'game/gameEntryManager.js'
		);
		$data['page_css'] =  array(
			'game/game-focal.css'
		);
		$data['page_libraries_css'] =  array();
		$this->addNavInfo($data);
		$this->views->getView($this, "game", $data);
	}


	public function validate_game_code()
	{
		$idJugador = $this->getUserData('id');
		$data = json_decode(file_get_contents('php://input'), true);
		try {
			$response = $this->model->validate_game_code($data, $idJugador);
		} catch (Exception $e) {
			$response = [
				'success' => false,
				'message' => 'Error al ejecutar el proceso: ' . $e->getMessage()
			];
		}
		$jsonResponse = json_encode($response, JSON_UNESCAPED_UNICODE);
		$encryptedResponse = encryptResponse($jsonResponse);
		echo json_encode([
			'data' => $encryptedResponse // Tu función de encriptación
		]);
		die();
	}

	public function game_clasification()
	{
		$data = array();
		$data['page_tag'] = "GAME - " . name_project();
		$data['page_title'] = name_project();
		$data['page_name'] = "GAME";
		$data['page_functions_js'] = array(
			'jquery-3.7.1.min.js',
			'game/clasification-game.js'
		);
		$data['page_css'] =  array(
			'game/game-clasification.css'
		);
		$data['page_libraries_css'] =  array();
		$this->addNavInfo($data);
		$this->views->getView($this, "game_clasification", $data);
	}

	public function get_requirements()
	{
		$idJugador = $this->getUserData('id'); // Por ahora usamos 1 como ejemplo
		$gameCode = $_GET['game'] ?? '';

		$requirements = $this->model->get_requirements_game($gameCode, $idJugador);
		echo json_encode($requirements);
	}

	public function validate_moves()
	{
		$data = json_decode(file_get_contents('php://input'), true);
		$idJugador = $this->getUserData('id');
		$response = $this->model->validate_moves_game($data, $idJugador);
		echo json_encode($response);
	}

	public function game_construction()
	{
		$data = array();
		$data['page_tag'] = "GAME - " . name_project();
		$data['page_title'] = name_project();
		$data['page_name'] = "GAME";
		$data['page_functions_js'] = "game/construction-game.js";
		$data['page_css'] =  array(
			'game/game-clasification.css',
			'game/game-construction.css'
		);
		$this->addNavInfo($data);
		$this->views->getView($this, "game_construction", $data);
	}

	public function get_requirements_construcion()
	{
		$idJugador = $this->getUserData('id');
		$gameCode = $_GET['game'] ?? '';

		$requirements = $this->model->get_requirements_construcion_game($gameCode, $idJugador);
		echo json_encode($requirements);
	}

	public function validate_construction()
	{
		$data = json_decode(file_get_contents('php://input'), true);
		$idJugador = $this->getUserData('id');
		$response = $this->model->validate_construction_game($data, $idJugador);
		echo json_encode($response);
	}
}
