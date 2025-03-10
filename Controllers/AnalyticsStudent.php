<?php
require_once("Libraries/Reports/ReportAnalyzer.php");
class AnalyticsStudent extends AuthController
{
	public function __construct()
	{
		parent::__construct([
			SessionManager::ROLE_STUDENT
		]);
	}

	//VISTA GENERAL DE TODAS LA PARTIDAS
	public function analyticsStudent()
	{
		$data = array();
		$data['page_tag'] = "My Games - " . name_project();
		$data['page_title'] = name_project();
		$data['page_name'] = "My Games";
		$data['page_functions_js'] = array(
			'jquery-3.7.1.min.js',
			'analyticsStudent/myGames.js'
		);
		$data['page_css'] = array(
			'analytics/games.css'
		);
		$data['page_libraries_css'] = array();
		$this->addNavInfo($data);
		$this->views->getView($this, "analytics", $data);
	}

	public function get_my_games()
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

			$response = $this->model->getMyGames($data, $idJugador);

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

	//VISTA DE DETALLES DE CLASIFICACION
	public function details_user_classification()
	{
		$data = array();
		$data['page_tag'] = "GAME - " . name_project();
		$data['page_title'] = name_project();
		$data['page_name'] = "GAME";
		$data['page_functions_js'] = array(
			'jquery-3.7.1.min.js',
			'plugins/datatables/dataTables.min.js',
			'plugins/datatables/dataTables.responsive.js',
			'plugins/datatables/responsive.dataTables.js',
			'analyticsStudent/details-clasification.js'
		);
		$data['page_libraries_css'] =  array(
			'plugins/datatables/dataTables.dataTables.min.css',
			'plugins/datatables/responsive.dataTables.css'
		);
		$data['page_css'] =  array(
			'analytics/base.css',
			'analytics/details-clasification.css'
		);
		$this->addNavInfo($data);
		$this->views->getView($this, "datails_user_clasification", $data);
	}

	public function get_intentos_jugador()
	{
		$jsonData = file_get_contents('php://input');
		$postData = json_decode($jsonData, true);
		$idUsuarioSesion = $this->getUserData('id');

		$analytics = $this->model->get_intentos_jugador($postData, $idUsuarioSesion);
		$jsonResponse = json_encode($analytics, JSON_UNESCAPED_UNICODE);
		$encryptedResponse = encryptResponse($jsonResponse);
		echo json_encode([
			'data' => $encryptedResponse // Tu función de encriptación
		]);
		exit();
	}

	public function get_detalles_intento()
	{
		$jsonData = file_get_contents('php://input');
		$postData = json_decode($jsonData, true);
		$idUsuarioSesion = $this->getUserData('id');

		$attemptDetails = $this->model->get_detalles_intento($postData, $idUsuarioSesion);
		$jsonResponse = json_encode($attemptDetails, JSON_UNESCAPED_UNICODE);
		$encryptedResponse = encryptResponse($jsonResponse);
		echo json_encode([
			'data' => $encryptedResponse // Tu función de encriptación
		]);
		exit();
	}

	public function downloadReportClass()
	{
		try {
			// Obtener parámetros necesarios
			$gameCode = $_GET['gamecode'] ?? '';
			$playerId = $this->getUserData('id');

			if (empty($gameCode) || empty($playerId)) {
				throw new Exception("Datos incompletos para generar el reporte");
			}

			// Obtener datos para el reporte
			$reportData = $this->getReportData($gameCode, $playerId);

			// Preparar datos para la vista
			$data = array();
			$data['report_data'] = $reportData;
			$data['download_mode'] = true; // Flag para indicar modo descarga
			$data['page_css'] = array(
				'report/variables.css',
				'report/report.css',
				'report/progress.css',
				'report/table.css',
				'report/print.css'
			);

			// Cargar la vista de descarga
			$this->views->getView($this, "template/report_download_classification", $data);
		} catch (Exception $e) {
			error_log("Error generando reporte: " . $e->getMessage());
			echo json_encode([
				'status' => false,
				'message' => 'Error al generar el reporte'
			]);
			die();
		} catch (Throwable $e) {
			// Manejo de errores y excepciones
			error_log("Se capturó un error o excepción:");
			error_log("Mensaje: " . $e->getMessage());
			error_log("Archivo: " . $e->getFile());
			error_log("Línea: " . $e->getLine());
			header('Location: ' . base_url() . '/dashboard');
			die();
		}
	}

	private function getReportData($gameCode, $playerId)
	{
		// Obtener los datos necesarios para el reporte
		try {
			$attempts = $this->model->get_intentos_detalles_jugador($gameCode, $playerId);

			$analyzer = new ReportAnalyzer(
				$attempts['attempts'],
				$attempts['attempts'][0]['total_requisitos'] // Total inicial de requisitos
			);

			$performanceAnalysis = $analyzer->analyzePerformance();

			$reportData = [
				'playerInfo' => [
					'name' => $attempts['details']['nombres'] . ' ' . $attempts['details']['apellidos'],
					'email' => $attempts['details']['email'],
					'gameCode' => $gameCode
				],
				'gameOverview' => [
					'totalTime' => $this->formatTimeArray($attempts['details']['tiempo_total']),
					'attempts' => $attempts['details']['total_intentos'],
					'consistency' => $performanceAnalysis['metrics']['consistency'],
					'averageCorrect' => $performanceAnalysis['metrics']['averageCorrect']
				],
				'attemptsDetails' => array_map(function ($attempt) {
					return [
						'attempt' => $attempt['numero_intento'],
						'time' => $this->formatTime($attempt['tiempo_intento']),
						'movements' => $attempt['cantidad_movimientos'],
						'requirements' => $attempt['total_requisitos'],
						'correct' => $attempt['requisitos_correctos'],
						'incorrect' => $attempt['requisitos_incorrectos'],
						'successPrecision' => $attempt['precision_aciertos'],
						'errorPrecision' => $attempt['precision_errores'],
						'progressivePrecision' => $attempt['precision_progresiva'],
						'requeriments' => $attempt['requisitos']
					];
				}, $attempts['attempts']),
				'summary' => [
					'totalTime' => $this->formatTimeArray($attempts['details']['tiempo_total']),
					'totalAttempts' => $attempts['details']['total_intentos'],
					'gameCode' => $gameCode,
					'lastAttemptDate' => end($attempts['attempts'])['fecha_intento'],
					'progressPercentage' => $performanceAnalysis['metrics']['averageCorrect'],
					'gameStatus' => $this->getGameStatus($attempts['attempts']),
					'firstAttemptPrecision' => $attempts['attempts'][0]['precision_progresiva'],
					'lastAttemptPrecision' => end($attempts['attempts'])['precision_progresiva']
				],
				'analysis' => [
					'narrative' => $performanceAnalysis['narrative'],
					'recommendations' => $performanceAnalysis['recommendations'],
					'performanceLevels' => $performanceAnalysis['performanceLevels']
				],
				'chartData' => $this->prepareChartData($attempts['attempts'])
			];

			//$reportData = $mock; 
			return $reportData;
		} catch (Exception $e) {
			error_log("Error obteniendo datos del reporte: " . $e->getMessage());
			throw new Exception("Error al obtener los datos del reporte");
		}
	}

	private function formatTimeArray($seconds)
	{
		return [
			'minutes' => floor($seconds / 60),
			'seconds' => $seconds % 60
		];
	}

	private function formatTime($seconds)
	{
		$minutes = floor($seconds / 60);
		$remainingSeconds = $seconds % 60;
		return sprintf("%dmin %02ds", $minutes, $remainingSeconds);
	}

	private function getGameStatus($attempts)
	{
		$lastAttempt = end($attempts);
		return ($lastAttempt['requisitos_incorrectos'] === 0) ? 'completado' : 'en progreso';
	}

	private function prepareChartData($attempts)
	{
		return array_map(function ($attempt) {
			return [
				'intento' => $attempt['numero_intento'],
				'precision' => $attempt['precision_progresiva'],
				'requisitos_correctos' => $attempt['requisitos_correctos'],
				'total_requisitos' => $attempt['total_requisitos']
			];
		}, $attempts);
	}

	//VISTA DE DETALLES DE CONSTRUCCION
	public function details_user_construction()
	{
		$data = array();
		$data['page_tag'] = "GAME - " . name_project();
		$data['page_title'] = name_project();
		$data['page_name'] = "GAME";
		$data['page_functions_js'] = array(
			'jquery-3.7.1.min.js',
			'plugins/datatables/dataTables.min.js',
			'plugins/datatables/dataTables.responsive.js',
			'plugins/datatables/responsive.dataTables.js',
			'plugins/datatables/dataTables.rowGroup.js',
			'plugins/datatables/rowGroup.dataTables.js',
			'analyticsStudent/details-construction.js'
		);
		$data['page_libraries_css'] =  array(
			'plugins/datatables/dataTables.dataTables.min.css',
			'plugins/datatables/responsive.dataTables.css',
			'plugins/datatables/rowGroup.dataTables.css'
		);
		$data['page_css'] =  array(
			'analytics/base.css',
			'analytics/details-clasification.css',
			'analytics/details-construction.css'
		);
		$this->addNavInfo($data);
		$this->views->getView($this, "details_user_construction", $data);
	}

	public function get_intentos_jugador_construction()
	{
		$jsonData = file_get_contents('php://input');
		$postData = json_decode($jsonData, true);
		$idUsuarioSesion = $this->getUserData('id');

		$analytics = $this->model->get_intentos_jugador_construction($postData, $idUsuarioSesion);
		$jsonResponse = json_encode($analytics, JSON_UNESCAPED_UNICODE);
		$encryptedResponse = encryptResponse($jsonResponse);

		echo json_encode([
			'data' => $encryptedResponse // Tu función de encriptación
		]);
		die();
	}

	public function get_detalles_intento_construction()
	{
		$jsonData = file_get_contents('php://input');
		$postData = json_decode($jsonData, true);
		$idJugador = $this->getUserData('id');

		$attemptDetails = $this->model->get_detalles_intento_construction($postData, $idJugador);
		$jsonResponse = json_encode($attemptDetails, JSON_UNESCAPED_UNICODE);
		$encryptedResponse = encryptResponse($jsonResponse);

		echo json_encode([
			'data' => $encryptedResponse // Tu función de encriptación
		]);
		exit();
	}


	public function downloadReportConstruction()
	{
		try {
			$gameCode = $_GET['gamecode'] ?? '';
			$playerId = $this->getUserData('id');

			if (empty($gameCode) || empty($playerId)) {
				throw new Exception("Datos incompletos para generar el reporte");
			}

			// Obtener datos para el reporte
			$reportData = $this->getReportDataConstruction($gameCode, $playerId);

			// Preparar datos para la vista
			$data = array();
			$data['report_data'] = $reportData;
			$data['download_mode'] = true;
			$data['page_css'] = array(
				'report/variables.css',
				'report/report.css',
				'report/progress.css',
				'report/table.css',
				'report/report-build.css',
				'report/print.css'
			);

			// Cargar la vista de descarga
			$this->views->getView($this, "template/report_download_construction", $data);
		} catch (Exception $e) {
			error_log("Error generando reporte: " . $e->getMessage());
			echo json_encode([
				'status' => false,
				'message' => 'Error al generar el reporte'
			]);
			die();
		} catch (Throwable $e) {
			// Manejo de errores y excepciones
			error_log("Se capturó un error o excepción:");
			error_log("Mensaje: " . $e->getMessage());
			error_log("Archivo: " . $e->getFile());
			error_log("Línea: " . $e->getLine());
			header('Location: ' . base_url() . '/dashboard');
			die();
		}
	}

	private function getReportDataConstruction($gameCode, $playerId)
	{
		try {
			// Obtener los datos del reporte
			$reportData = $this->model->get_full_construction_report($gameCode,$playerId);

			if (!$reportData['status']) {
				throw new Exception($reportData['message']);
			}

			// Agrupar intentos por requisito
			$attemptsByRequirement = [];
			foreach ($reportData['data']['attempts'] as $attempt) {
				$reqId = $attempt['id_requisito'];
				if (!isset($attemptsByRequirement[$reqId])) {
					$attemptsByRequirement[$reqId] = [
						'requisito_completo' => $attempt['requisito_completo'],
						'intentos' => []
					];
				}
				$attemptsByRequirement[$reqId]['intentos'][] = $attempt;
			}

			// Estructura final del reporte
			$processedData = [
				'playerInfo' => [
					'name' => $reportData['data']['summary']['nombres'] . ' ' .
						$reportData['data']['summary']['apellidos'],
					'email' => $reportData['data']['summary']['correo'],
					'gameCode' => $gameCode
				],
				'generalStats' => [
					'totalTime' => $this->formatTimeArray($reportData['data']['summary']['tiempo_total']),
					'totalAttempts' => $reportData['data']['summary']['total_intentos'],
					'totalRequirements' => count($attemptsByRequirement),
					'averageAttemptsPerRequirement' => round(
						$reportData['data']['summary']['total_intentos'] / count($attemptsByRequirement),
						2
					)
				],
				'requirementsAnalysis' => array_map(function ($reqData) {
					return [
						'requirement' => $reqData['requisito_completo'],
						'attempts' => array_map(function ($attempt) {
							return [
								'attemptNumber' => $attempt['numero_intento'],
								'time' => $this->formatTimeArray($attempt['tiempo_intento']),
								'movements' => $attempt['cantidad_movimientos'],
								'correctFragments' => $attempt['fragmentos_correctos'],
								'incorrectFragments' => $attempt['fragmentos_incorrectos'],
								'decoysUsed' => $attempt['señuelos_usados'],
								'precision' => $attempt['precision_construccion'],
								'fragments' => $attempt['detalles']
							];
						}, $reqData['intentos'])
					];
				}, $attemptsByRequirement)
			];

			return $processedData;
		} catch (Exception $e) {
			error_log("Error obteniendo datos del reporte: " . $e->getMessage());
			throw new Exception("Error al obtener los datos del reporte");
		}
	}
}
