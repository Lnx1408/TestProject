<?php
class Levels extends AuthController
{
	public function __construct()
	{
		parent::__construct([
			SessionManager::ROLE_ADMIN,
			SessionManager::ROLE_STUDENT,
			SessionManager::ROLE_TEACHER
		]);
	}

	public function levels()
	{
		$data = array();
		$data['page_tag'] = "Levels - " . name_project();
		$data['page_title'] = name_project();
		$data['page_name'] = "Levels";
		$data['page_functions_js'] = array(
			'jquery-3.7.1.min.js',
			'levels/levelsGameManager.js'
		);
		$data['page_css'] =  array(
			'game/game-focal.css',
			'levels/levels-focal.css'
		);
		$data['page_libraries_css'] =  array();
		$this->addNavInfo($data);
		$this->views->getView($this, "levels", $data);
	}

	//VISTA DEL NIVEL DE CLASIFICACION
	public function create_classification()
	{
		$data = array();
		$data['page_tag'] = "Create Game - " . name_project();
		$data['page_title'] = name_project();
		$data['page_name'] = "Create Game";
		$data['page_functions_js'] = array(
			'jquery-3.7.1.min.js',
			'plugins/datatables/dataTables.min.js',
			'plugins/datatables/dataTables.responsive.js',
			'plugins/datatables/responsive.dataTables.js',
			'plugins/papaparse.min.js',
			'plugins/custom-modal.js',
			'plugins/tabulator/tabulator.min.js',
			'levels/tabulator-config.js',
			'levels/requirements-generator.js',
			'levels/createClassificationGame.js'
		);
		$data['page_css'] =  array(
			'game/game-focal.css',
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
		$this->views->getView($this, "create_classification", $data);
	}


	public function get_generator_template()
	{
		$data = array();

		// Traducciones y textos para el template
		$data['generator_title'] = 'Generador IA';
		$data['context_placeholder'] = 'Describe brevemente el contexto o dominio del proyecto para el que necesitas generar requisitos. Ejemplo: Sistema de gestión de bibliotecas, aplicación de comercio electrónico, etc.';

		// Cargar vista del template
		ob_start();
		require_once("Views/Template/Generators/requirements_generator_modal.php");
		$templateContent = ob_get_clean();

		// Devolver el template como respuesta
		echo $templateContent;
		exit;
	}

	public function generate_requirements()
	{
		try {
			// Validar que sea una petición POST
			if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
				throw new Exception('Método no permitido');
			}

			// Obtener y validar los datos de la petición
			$jsonData = file_get_contents('php://input');
			$postData = json_decode($jsonData, true);

			if (!isset($postData['encryptedData'])) {
				throw new Exception('Datos no recibidos');
			}

			// Desencriptar los datos
			$decryptedData = decryptData($postData['encryptedData']);
			if (!$decryptedData) {
				throw new Exception('Error al desencriptar datos');
			}

			$requestData = json_decode($decryptedData, true);
			if (!$requestData) {
				throw new Exception('Formato de datos inválido');
			}

			// Validar campos requeridos
			$requiredFields = ['provider', 'language', 'context', 'num_requirements'];
			foreach ($requiredFields as $field) {
				if (!isset($requestData[$field])) {
					throw new Exception("Campo requerido: {$field}");
				}
			}

			// Validar número de requisitos
			$numRequirements = intval($requestData['num_requirements']);
			if ($numRequirements < 5 || $numRequirements > 20) {
				throw new Exception('Número de requisitos inválido. Debe estar entre 5 y 20.');
			}

			// Obtener ID del usuario/profesor
			$idUsuario = $this->getUserData('id');

			// Llamar al modelo para generar los requisitos
			$response = $this->model->generateRequirements(
				$requestData['provider'],
				$requestData['language'],
				$requestData['context'],
				$numRequirements,
				$idUsuario
			);
		} catch (Error $e) {
			// Captura errores fatales como "Call to undefined method"
			$response = [
				'success' => false,
				'message' => 'Error al generar los requisitos: ' . $e->getMessage()
			];
		} catch (Exception $e) {
			// Manejar errores
			$response = [
				'success' => false,
				'message' => $e->getMessage()
			];
		}
		// Preparar y enviar respuesta
		$jsonResponse = json_encode($response, JSON_UNESCAPED_UNICODE);
		$encryptedResponse = encryptResponse($jsonResponse);
		echo json_encode([
			'data' => $encryptedResponse
		]);
		die();
	}

	public function get_requirements_clasification()
	{
		try {
			$idJugador = $this->getUserData('id'); // Por ahora usamos 1 como ejemplo
			$response = $this->model->getRequirementsClasification($idJugador);
		} catch (Error $e) {
			// Captura errores fatales como "Call to undefined method"
			$response = [
				'success' => false,
				'message' => 'Error al obtener los requisitos: ' . $e->getMessage()
			];
		} catch (Exception $e) {
			$response = [
				'success' => false,
				'message' => 'Error al obtener los requisitos: ' . $e->getMessage()
			];
		}
		$jsonResponse = json_encode($response, JSON_UNESCAPED_UNICODE);
		$encryptedResponse = encryptResponse($jsonResponse);
		echo json_encode([
			'data' => $encryptedResponse // Tu función de encriptación
		]);
		die();
	}

	public function create_requirement_clasification()
	{
		try {
			$idJugador = $this->getUserData('id'); // Por ahora usamos 1 como ejemplo
			$jsonData = file_get_contents('php://input');
			$postData = json_decode($jsonData, true);
			$response = $this->model->createRequirementClasification($postData, $idJugador);
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

	/**
	 * Guarda los requisitos generados por IA
	 */
	public function save_generated_requirements()
	{
		try {
			// Validar que sea una petición POST
			if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
				throw new Exception('Método no permitido');
			}

			// Obtener y validar los datos de la petición
			$jsonData = file_get_contents('php://input');
			$postData = json_decode($jsonData, true);

			if (!isset($postData['encryptedData'])) {
				throw new Exception('Datos no recibidos');
			}

			// Desencriptar los datos
			$decryptedData = decryptData($postData['encryptedData']);
			if (!$decryptedData) {
				throw new Exception('Error al desencriptar datos');
			}

			$requestData = json_decode($decryptedData, true);
			if (!$requestData || !isset($requestData['requirements']) || !is_array($requestData['requirements'])) {
				throw new Exception('Formato de datos inválido');
			}

			// Validar que hay requisitos para guardar
			if (empty($requestData['requirements'])) {
				throw new Exception('No hay requisitos para guardar');
			}

			// Obtener ID del usuario/profesor
			$idUsuario = $this->getUserData('id');

			// Transformar los datos para el formato esperado por el modelo
			$requirements = [];
			foreach ($requestData['requirements'] as $req) {
				$requirements[] = [
					'descripcion' => $req['description'],
					'es_ambiguo' => $req['is_ambiguous'],
					'es_funcional' => $req['is_functional'],
					'retroalimentacion' => $req['feedback']
				];
			}

			// Llamar al modelo para guardar los requisitos
			$data = [
				'requirements' => $requirements
			];

			$response = $this->model->importRequirementsClasification($data, $idUsuario, true);

			// Preparar y enviar respuesta
			$jsonResponse = json_encode($response, JSON_UNESCAPED_UNICODE);
			$encryptedResponse = encryptResponse($jsonResponse);

			echo json_encode([
				'data' => $encryptedResponse
			]);
		} catch (Exception $e) {
			// Manejar errores
			$errorResponse = [
				'success' => false,
				'message' => $e->getMessage()
			];

			$jsonResponse = json_encode($errorResponse, JSON_UNESCAPED_UNICODE);
			$encryptedResponse = encryptResponse($jsonResponse);

			echo json_encode([
				'data' => $encryptedResponse
			]);
		}

		die();
	}

	public function import_requirements()
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

			if (!$data || !isset($data['requirements'])) {
				throw new Exception('Formato de datos inválido');
			}

			$response = $this->model->importRequirementsClasification($data, $idJugador);

			$jsonResponse = json_encode($response, JSON_UNESCAPED_UNICODE);
			$encryptedResponse = encryptResponse($jsonResponse);

			echo json_encode([
				'data' => $encryptedResponse
			]);
		} catch (Exception $e) {
			$response = [
				'success' => false,
				'message' => 'Error: ' . $e->getMessage()
			];

			$jsonResponse = json_encode($response, JSON_UNESCAPED_UNICODE);
			$encryptedResponse = encryptResponse($jsonResponse);

			echo json_encode([
				'data' => $encryptedResponse
			]);
		}
		die();
	}

	public function create_game_clasification()
	{
		try {
			$idJugador = $this->getUserData('id'); // Por ahora usamos 1 como ejemplo
			$jsonData = file_get_contents('php://input');
			$postData = json_decode($jsonData, true);
			$response = $this->model->createGameClasification($postData, $idJugador);
		} catch (Error $e) {
			// Captura errores fatales como "Call to undefined method"
			$response = [
				'success' => false,
				'message' => 'Error al obtener los requisitos: ' . $e->getMessage()
			];
		} catch (Exception $e) {
			$response = [
				'success' => false,
				'message' => 'Error al obtener los requisitos: ' . $e->getMessage()
			];
		}
		$jsonResponse = json_encode($response, JSON_UNESCAPED_UNICODE);
		$encryptedResponse = encryptResponse($jsonResponse);
		echo json_encode([
			'data' => $encryptedResponse // Tu función de encriptación
		]);
		die();
	}

	//VISTA DEL NIVEL DE CONSTRUCCION
	public function create_construction()
	{
		$data = array();
		$data['page_tag'] = "Create Game - " . name_project();
		$data['page_title'] = name_project();
		$data['page_name'] = "Create Game";
		$data['page_functions_js'] = array(
			'jquery-3.7.1.min.js',
			'plugins/datatables/dataTables.min.js',
			'plugins/datatables/dataTables.responsive.js',
			'plugins/datatables/responsive.dataTables.js',
			'plugins/papaparse.min.js',
			'levels/createConstructionGame.js'
		);
		$data['page_css'] =  array(
			'game/game-focal.css',
			'levels/levels-base.css',
			'levels/levels-focal.css',
			'levels/create-clasification.css',
			'levels/create-construction.css',
			'levels/create-construction-form.css'
		);
		$data['page_libraries_css'] =  array(
			'plugins/datatables/dataTables.dataTables.min.css',
			'plugins/datatables/responsive.dataTables.css'
		);
		$this->addNavInfo($data);
		$this->views->getView($this, "create_construction", $data);
	}

	public function get_requirements_construction()
	{
		try {
			$idJugador = $this->getUserData('id'); // Por ahora usamos 1 como ejemplo
			$response = $this->model->getRequirementsConstruction($idJugador);
		} catch (Error $e) {
			// Captura errores fatales como "Call to undefined method"
			$response = [
				'success' => false,
				'message' => 'Error al obtener los requisitos: ' . $e->getMessage()
			];
		} catch (Exception $e) {
			$response = [
				'success' => false,
				'message' => 'Error al obtener los requisitos: ' . $e->getMessage()
			];
		}
		$jsonResponse = json_encode($response, JSON_UNESCAPED_UNICODE);
		$encryptedResponse = encryptResponse($jsonResponse);
		echo json_encode([
			'data' => $encryptedResponse // Tu función de encriptación
		]);
		die();
	}

	public function create_requirement_construction()
	{
		try {
			$idJugador = $this->getUserData('id'); // Por ahora usamos 1 como ejemplo
			$jsonData = file_get_contents('php://input');
			$postData = json_decode($jsonData, true);
			$response = $this->model->createRequirementConstruction($postData, $idJugador);
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

	public function update_requirement_clasification()
	{
		try {
			$idJugador = $this->getUserData('id');
			$jsonData = file_get_contents('php://input');
			$postData = json_decode($jsonData, true);

			if (!isset($postData['encryptedData'])) {
				throw new Exception('Datos no recibidos');
			}

			$response = $this->model->updateRequirementClasification($postData, $idJugador);
		} catch (Error $e) {
			$response = [
				'success' => false,
				'message' => 'Error al actualizar el requisito: ' . $e->getMessage()
			];
		} catch (Exception $e) {
			$response = [
				'success' => false,
				'message' => 'Error al actualizar el requisito: ' . $e->getMessage()
			];
		}

		$jsonResponse = json_encode($response, JSON_UNESCAPED_UNICODE);
		$encryptedResponse = encryptResponse($jsonResponse);
		echo json_encode([
			'data' => $encryptedResponse
		]);
		die();
	}

	public function import_requirements_construction()
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

			if (!$data || !isset($data['requirements'])) {
				throw new Exception('Formato de datos inválido');
			}

			$response = $this->model->importRequirementsConstruction($data, $idJugador);

			$jsonResponse = json_encode($response, JSON_UNESCAPED_UNICODE);
			$encryptedResponse = encryptResponse($jsonResponse);

			echo json_encode([
				'data' => $encryptedResponse
			]);
		} catch (Exception $e) {
			$response = [
				'success' => false,
				'message' => 'Error: ' . $e->getMessage()
			];

			$jsonResponse = json_encode($response, JSON_UNESCAPED_UNICODE);
			$encryptedResponse = encryptResponse($jsonResponse);

			echo json_encode([
				'data' => $encryptedResponse
			]);
		}
		die();
	}

	public function create_game_construction()
	{
		try {
			$idJugador = $this->getUserData('id'); // Por ahora usamos 1 como ejemplo
			$jsonData = file_get_contents('php://input');
			$postData = json_decode($jsonData, true);
			$response = $this->model->createGameConstruction($postData, $idJugador);
		} catch (Error $e) {
			// Captura errores fatales como "Call to undefined method"
			$response = [
				'success' => false,
				'message' => 'Error al obtener los requisitos: ' . $e->getMessage()
			];
		} catch (Exception $e) {
			$response = [
				'success' => false,
				'message' => 'Error al obtener los requisitos: ' . $e->getMessage()
			];
		}
		$jsonResponse = json_encode($response, JSON_UNESCAPED_UNICODE);
		$encryptedResponse = encryptResponse($jsonResponse);
		echo json_encode([
			'data' => $encryptedResponse // Tu función de encriptación
		]);
		die();
	}
}
