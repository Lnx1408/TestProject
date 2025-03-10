<?php
require_once("Libraries/Reports/ReportGeneralConstructionNarrativeGenerator.php");
require_once("Libraries/Reports/ReportGeneralClassificationNarrativeGenerator.php");

class AnalyticsInfraestructure extends Mysql
{
	private $db;
	private const type_construction = "MOD-BUILD";
	private const type_classification = "MOD-CLASS";

	function __construct() {}

	private function conectar()
	{
		if (!$this->db) {
			$this->db = (new Conexion())->conect();
		}
	}

	private function cerrarConexion()
	{
		$this->db = null;
	}

	public function getMyGamesBD(int $idJugador, array $offset, int $limit)
	{
		try {
			$response = $this->executeProcedureWithParametersOut(
				'sp_get_partidas_por_usuario',
				[
					$idJugador
					//, 
					//$offset['classification'],
					//$offset['construction'], 
					//$limit
				],
				[
					'codigo',
					'mensaje'
					//, 'has_more_classification', 'has_more_construction'
				]
			);

			if (!empty($response) && $response['outParams']['codigo'] == 1) {
				// Procesar los resultados
				$results = $response['results'];

				// Separar las partidas por tipo
				$classification = array_filter($results, fn($game) => $game['tipo'] === self::type_classification);
				$construction = array_filter($results, fn($game) => $game['tipo'] === self::type_construction);

				// Calcular totales
				$totals = [
					'classification' => count($classification),
					'construction' => count($construction)
				];

				// Verificar si hay más partidas
				$hasMore = [
					//'classification' => (bool)$response['outParams']['has_more_classification'],
					//'construction' => (bool)$response['outParams']['has_more_construction']
					'classification' => true,
					'construction' => true
				];

				$arrResponse = [
					'success' => true,
					'classification' => array_values($classification),
					'construction' => array_values($construction),
					'totals' => $totals,
					'hasMore' => $hasMore,
					'message' => $response['outParams']['mensaje']
				];
			} else {
				$arrResponse = [
					'success' => false,
					'message' => $response['outParams']['mensaje'] ?? 'Error al obtener las partidas'
				];
			}
		} catch (PDOException $e) {
			error_log("Error en procedimiento almacenado: " . $e->getMessage());
			$arrResponse = [
				'success' => false,
				'message' => 'Error al obtener las partidas: ' . $e->getMessage()
			];
		} finally {
			$this->cerrarConexion();
		}

		return $arrResponse;
	}

	public function get_analiticas_jugadores_partidaBD(string $gameCode, string $idJugador)
	{
		try {
			$responseAnalyticsJugadores = $this->executeProcedureWithParametersOut(
				'sp_estadisticas_jugadores_partida_clasificacion',
				[$gameCode, $idJugador],
				['codigo', 'mensaje']  // Parámetros de salida
			);
			if (!empty($responseAnalyticsJugadores) && $responseAnalyticsJugadores['outParams']['codigo'] == 0) {
				$arrResponse = array(
					'status' => true,
					'analytics' => $responseAnalyticsJugadores['results'],
					'message' => $responseAnalyticsJugadores['outParams']['mensaje']
				);
			} else {
				$arrResponse = array(
					'status' => false,
					'analytics' => $responseAnalyticsJugadores['results'],
					'message' => $responseAnalyticsJugadores['outParams']['mensaje']
				);
			}
		} catch (PDOException $e) {
			error_log("Error en procedimiento almacenado: " . $e->getMessage());
			return [
				'status' => false,
				'message' => 'Error al obtener datos del juego: ' . $e->getMessage(),
				'analytics' => []
			];
		} finally {
			$this->cerrarConexion();
		}

		return $arrResponse;
	}


	public function get_analiticas_generales_partidaBD(string $gameCode, string $idJugador)
	{
		try {
			$responseAnalyticsJugadores = $this->executeProcedureWithParametersOut(
				'sp_estadisticas_generales_partida',
				[$gameCode, $idJugador],
				['codigo', 'mensaje']  // Parámetros de salida
			);
			if (!empty($responseAnalyticsJugadores) && $responseAnalyticsJugadores['outParams']['codigo'] == 1) {
				$arrResponse = array(
					'status' => true,
					'analytics' => $responseAnalyticsJugadores['results'],
					'message' => $responseAnalyticsJugadores['outParams']['mensaje']
				);
			} else {
				$arrResponse = array(
					'status' => false,
					'analytics' => $responseAnalyticsJugadores['results'],
					'message' => $responseAnalyticsJugadores['outParams']['mensaje']
				);
			}
		} catch (PDOException $e) {
			error_log("Error en procedimiento almacenado: " . $e->getMessage());
			return [
				'status' => false,
				'message' => 'Error al obtener datos del juego: ' . $e->getMessage(),
				'analytics' => []
			];
		} finally {
			$this->cerrarConexion();
		}

		return $arrResponse;
	}

	public function get_intentos_jugadorBD(string $gameCode, string $idCreador, string $idJugador)
	{
		try {
			$responseAnalyticsJugadores = $this->executeProcedureWithParametersOut(
				'sp_stats_for_player',
				[$gameCode, $idCreador, $idJugador],
				['codigo', 'mensaje', 'intentos', 'tiempo', 'nombres', 'apellidos', 'correo']  // Parámetros de salida
			);
			if (!empty($responseAnalyticsJugadores) && $responseAnalyticsJugadores['outParams']['codigo'] == 1) {
				$arrResponse = array(
					'status' => true,
					'attempts' => $responseAnalyticsJugadores['results'],
					'details' => [
						'tiempo_total' => $responseAnalyticsJugadores['outParams']['tiempo'],
						'total_intentos' => $responseAnalyticsJugadores['outParams']['intentos'],
						'nombres' => $responseAnalyticsJugadores['outParams']['nombres'],
						'apellidos' => $responseAnalyticsJugadores['outParams']['apellidos'],
						'email' => $responseAnalyticsJugadores['outParams']['correo']
					],
					'message' => $responseAnalyticsJugadores['outParams']['mensaje']
				);
			} else {
				$arrResponse = array(
					'status' => false,
					'attempts' => $responseAnalyticsJugadores['results'],
					'details' => [],
					'message' => $responseAnalyticsJugadores['outParams']['mensaje']
				);
			}
		} catch (PDOException $e) {
			error_log("Error en procedimiento almacenado: " . $e->getMessage());
			return [
				'status' => false,
				'message' => 'Error al obtener datos del juego: ' . $e->getMessage(),
				'attempts' => [],
				'details' => []
			];
		} finally {
			$this->cerrarConexion();
		}

		return $arrResponse;
	}

	public function get_intentos_detalles_jugadorBD(string $gameCode, string $idCreador, string $idJugador)
	{
		try {
			$responseAnalyticsJugadores = $this->executeProcedureWithParametersOut(
				'sp_stats_details_for_player',
				[$gameCode, $idCreador, $idJugador],
				['codigo', 'mensaje', 'intentos', 'tiempo', 'nombres', 'apellidos', 'correo']
			);

			if (!empty($responseAnalyticsJugadores) && $responseAnalyticsJugadores['outParams']['codigo'] == 1) {
				$processedAttempts = array_map(function ($attempt) {
					$processedAttempt = $attempt;
					// Procesar los detalles de requisitos si existen
					if (!empty($attempt['requisitos_detalles'])) {
						$requisitos = array_map(function ($requisito) {
							$parts = explode('|', $requisito);
							return [
								'id_requisito' => (int)$parts[0],
								'descripcion' => $parts[1],
								'es_correcto' => $parts[2] === '1' || $parts[2] === 'true',
								'cantidad_movimientos' => (int)$parts[3],
								'movimientos_toAmbiguo' => (int)$parts[4],
								'movimientos_toNoAmbiguo' => (int)$parts[5]
							];
						}, explode('¬', $attempt['requisitos_detalles']));
						$processedAttempt['requisitos'] = $requisitos;
						unset($processedAttempt['requisitos_detalles']); // Removemos la cadena original
					} else {
						$processedAttempt['requisitos'] = [];
					}

					return $processedAttempt;
				}, $responseAnalyticsJugadores['results']);

				$arrResponse = array(
					'status' => true,
					'attempts' => $processedAttempts,
					'details' => [
						'tiempo_total' => $responseAnalyticsJugadores['outParams']['tiempo'],
						'total_intentos' => $responseAnalyticsJugadores['outParams']['intentos'],
						'nombres' => $responseAnalyticsJugadores['outParams']['nombres'],
						'apellidos' => $responseAnalyticsJugadores['outParams']['apellidos'],
						'email' => $responseAnalyticsJugadores['outParams']['correo']
					],
					'message' => $responseAnalyticsJugadores['outParams']['mensaje']
				);
			} else {
				$arrResponse = array(
					'status' => false,
					'attempts' => [],
					'details' => [],
					'message' => $responseAnalyticsJugadores['outParams']['mensaje']
				);
			}
		} catch (PDOException $e) {
			error_log("Error en procedimiento almacenado: " . $e->getMessage());
			return [
				'status' => false,
				'message' => 'Error al obtener datos del juego: ' . $e->getMessage(),
				'attempts' => [],
				'details' => []
			];
		} finally {
			$this->cerrarConexion();
		}
		return $arrResponse;
	}

	public function get_classification_general_report_data(string $gameCode, string $idCreador)
	{
		try {
			// 1. Información general del juego
			$gameInfoResponse = $this->executeProcedureWithParametersOut(
				'sp_get_general_info_classification_report',
				[$gameCode, $idCreador],
				['codigo', 'mensaje']
			);

			// 2. Estadísticas de resumen
			$summaryResponse = $this->executeProcedureWithParametersOut(
				'sp_get_summary_stats_classification_report',
				[$gameCode, $idCreador],
				[
					'p_one_attempt',
					'p_one_attempt_percentage',
					'p_two_three_attempts',
					'p_two_three_percentage',
					'p_more_attempts',
					'p_more_percentage',
					'codigo',
					'mensaje'
				]
			);

			// 3. Análisis de tiempo
			$timeResponse = $this->executeProcedureWithParametersOut(
				'sp_get_time_analysis_classification_report',
				[$gameCode, $idCreador],
				[
					'average_time',
					'best_time',
					'best_time_player_name',
					'best_time_player_lastn',
					'worst_time',
					'worst_time_player_name',
					'worst_time_player_lastn',
					'codigo',
					'mensaje'
				]
			);

			// 4. Análisis de requisitos
			$requirementsResponse = $this->executeProcedureWithParametersOut(
				'sp_get_requirements_analysis_classification_report',
				[$gameCode, $idCreador],
				['ambiguous_count', 'non_ambiguous_count', 'codigo', 'mensaje']
			);

			// 5. Requisitos desafiantes
			$challengingResponse = $this->executeProcedureWithParametersOut(
				'sp_get_challenging_requirements_classification_report',
				[$gameCode, $idCreador],
				['codigo', 'mensaje']
			);

			if (
				$gameInfoResponse['outParams']['codigo'] == 1 &&
				$summaryResponse['outParams']['codigo'] == 1 &&
				$timeResponse['outParams']['codigo'] == 1 &&
				$requirementsResponse['outParams']['codigo'] == 1 &&
				$challengingResponse['outParams']['codigo'] == 1
			) {							  
				$narrativeGenerator = new ReportGeneralClassificationNarrativeGenerator();
				$reportData = [
					'gameInfo' => [
						'creatorName' => $gameInfoResponse['results'][0]['creator_name'],
						'creationDate' => $gameInfoResponse['results'][0]['creation_date'],
						'gameCode' => $gameInfoResponse['results'][0]['game_code'],
						'totalRequirements' => $gameInfoResponse['results'][0]['total_requirements']
					],
					'summary' => [
						'totalPlayers' => $summaryResponse['results'][0]['total_players'],
						'attempts' => [
							'oneAttempt' => $summaryResponse['outParams']['p_one_attempt'],
							'oneAttemptPercentage' => $summaryResponse['outParams']['p_one_attempt_percentage'],
							'twoThreeAttempts' => $summaryResponse['outParams']['p_two_three_attempts'],
							'twoThreeAttemptsPercentage' => $summaryResponse['outParams']['p_two_three_percentage'],
							'moreThanThree' => $summaryResponse['outParams']['p_more_attempts'],
							'moreThanThreePercentage' => $summaryResponse['outParams']['p_more_percentage']
						],
						'firstAttemptAccuracy' => $summaryResponse['results'][0]['first_attempt_accuracy'],
						'averageCompletionTime' => $summaryResponse['results'][0]['average_time']
					],
					'progress' => [
						'completed' => $summaryResponse['results'][0]['completed_count'],
						'completedPercentage' => $summaryResponse['results'][0]['completed_percentage'],
						'inProgress' => $summaryResponse['results'][0]['in_progress_count'],
						'inProgressPercentage' => $summaryResponse['results'][0]['in_progress_percentage'],
						'notStarted' => $summaryResponse['results'][0]['not_started_count'],
						'notStartedPercentage' => $summaryResponse['results'][0]['not_started_percentage'],
						'narrative' => $narrativeGenerator->generateSummaryNarrative(
							$summaryResponse['results'][0]['completed_percentage'],
							$summaryResponse['results'][0]['in_progress_count']
						)
					],
					'requirementsAnalysis' => [
						'distribution' => [
							'ambiguous' => $requirementsResponse['outParams']['ambiguous_count'],
							'nonAmbiguous' => $requirementsResponse['outParams']['non_ambiguous_count'],
						],
						'requirements' => array_map(function ($req) {
							return [
								'description' => $req['descripcion'],
								'isAmbiguous' => $req['es_ambiguo'],
								'avgTime' => $req['avg_time'],
								'avgMoves' => $req['avg_moves'],
								'correctRate' => $req['success_rate']
							];
						}, $requirementsResponse['results']),
						'mostChallenging' => array_map(function ($req) {
							return [
								'description' => $req['description'],
								'isAmbiguous' => $req['is_ambiguous'],
								'errorRate' => $req['error_rate'],
								'playersWithErrors' => $req['players_with_errors'],
								'totalPlayers' => $req['total_players']
							];
						}, $challengingResponse['results'])
					],
					'timeAnalysis' => [
						'averageTime' => $timeResponse['outParams']['average_time'],
						'bestTime' => [
							'time' => $timeResponse['outParams']['best_time'],
							'playerName' => $this->formatName(
								$timeResponse['outParams']['best_time_player_name'],
								$timeResponse['outParams']['best_time_player_lastn']
							)
						],
						'worstTime' => [
							'time' => $timeResponse['outParams']['worst_time'],
							'playerName' => $this->formatName(
								$timeResponse['outParams']['worst_time_player_name'],
								$timeResponse['outParams']['worst_time_player_lastn']
							)
						],
						'distribution' => array_map(function ($dist) {
							return [
								'range' => $dist['rango_tiempo'],
								'count' => $dist['cantidad_jugadores']
							];
						}, $timeResponse['results']),
						'narrative' => $narrativeGenerator->generateTimeAnalysisNarrative(
							$timeResponse['outParams']['average_time']
						)
					]
				];

				return [
					'status' => true,
					'data' => $reportData,
					'message' => 'Reporte generado exitosamente'
				];
			}

			return [
				'status' => false,
				'message' => 'Error al generar el reporte',
				'data' => null
			];
		} catch (Exception $e) {
			return [
				'status' => false,
				'message' => 'Error: ' . $e->getMessage(),
				'data' => null
			];
		}
	}

	public function get_detalles_intentoBD(string $gameCode, string $idCreador, string $idJugador, string $idIntento)
	{
		try {
			$responseAnalyticsJugadores = $this->executeProcedureWithParametersOut(
				'sp_details_for_attempt',
				[$gameCode, $idCreador, $idJugador, $idIntento],
				['codigo', 'mensaje', 'tiempo', 'paciertos', 'perrores']  // Parámetros de salida
			);
			if (!empty($responseAnalyticsJugadores) && $responseAnalyticsJugadores['outParams']['codigo'] == 1) {
				$arrResponse = array(
					'status' => true,
					'attemptDetails' => $responseAnalyticsJugadores['results'],
					'headerDetails' => [
						'tiempo' => $responseAnalyticsJugadores['outParams']['tiempo'],
						'margen_aciertos' => $responseAnalyticsJugadores['outParams']['paciertos'],
						'margen_errores' => $responseAnalyticsJugadores['outParams']['perrores']
					],
					'message' => $responseAnalyticsJugadores['outParams']['mensaje']
				);
			} else {
				$arrResponse = array(
					'status' => false,
					'attemptDetails' => $responseAnalyticsJugadores['results'],
					'headerDetails' => [],
					'message' => $responseAnalyticsJugadores['outParams']['mensaje']
				);
			}
		} catch (PDOException $e) {
			error_log("Error en procedimiento almacenado: " . $e->getMessage());
			return [
				'status' => false,
				'message' => 'Error al obtener datos del juego: ' . $e->getMessage(),
				'attemptDetails' => [],
				'headerDetails' => []
			];
		} finally {
			$this->cerrarConexion();
		}

		return $arrResponse;
	}

	public function get_analiticas_generales_partida_construccionBD(string $gameCode, string $idJugador)
	{
		try {
			$responseAnalyticsJugadores = $this->executeProcedureWithParametersOut(
				'sp_estadisticas_generales_partida_construccion',
				[$gameCode, $idJugador],
				['codigo', 'mensaje']  // Parámetros de salida
			);
			if (!empty($responseAnalyticsJugadores) && $responseAnalyticsJugadores['outParams']['codigo'] == 1) {
				$arrResponse = array(
					'status' => true,
					'analytics' => $responseAnalyticsJugadores['results'],
					'message' => $responseAnalyticsJugadores['outParams']['mensaje']
				);
			} else {
				$arrResponse = array(
					'status' => false,
					'analytics' => $responseAnalyticsJugadores['results'],
					'message' => $responseAnalyticsJugadores['outParams']['mensaje']
				);
			}
		} catch (PDOException $e) {
			error_log("Error en procedimiento almacenado: " . $e->getMessage());
			return [
				'status' => false,
				'message' => 'Error al obtener datos del juego: ' . $e->getMessage(),
				'analytics' => []
			];
		} finally {
			$this->cerrarConexion();
		}

		return $arrResponse;
	}

	public function get_construccion_general_report_data(string $gameCode, string $idCreador)
	{
		try {
			// Game Info
			$gameInfoResponse = $this->executeProcedureWithParametersOut(
				'sp_get_general_info_construction_report',
				[$gameCode, $idCreador],
				['codigo', 'mensaje']
			);

			// Summary Stats
			$summaryResponse = $this->executeProcedureWithParametersOut(
				'sp_get_summary_stats_construction_report',
				[$gameCode, $idCreador],
				['codigo', 'mensaje']
			);

			// Time Analysis
			$timeResponse = $this->executeProcedureWithParametersOut(
				'sp_get_time_analysis_construction_report',
				[$gameCode, $idCreador],
				[
					'average_time',
					'tiempo_total',
					'mejor_tiempo',
					'mejor_tiempo_nombre',
					'mejor_tiempo_apellido',
					'codigo',
					'mensaje',
				]
			);

			$timeResponseRequirement = $this->executeProcedureWithParametersOut(
				'sp_get_time_analysis_requirement_construction_report',
				[$gameCode, $idCreador],
				['codigo', 'mensaje']
			);

			// Difficulty Analysis
			$difficultyResponse = $this->executeProcedureWithParametersOut(
				'sp_get_difficulty_analysis_construction_report',
				[$gameCode, $idCreador],
				[
					'min_intentos',
					'max_intentos',
					'min_nombre',
					'min_apellido',
					'max_nombre',
					'max_apellido',
					'codigo',
					'mensaje'
				]
			);

			if (
				$gameInfoResponse['outParams']['codigo'] == 1 &&
				$summaryResponse['outParams']['codigo'] == 1 &&
				$timeResponse['outParams']['codigo'] == 1 &&
				$timeResponseRequirement['outParams']['codigo'] == 1 &&
				$difficultyResponse['outParams']['codigo'] == 1
			) {

				$narrativeGenerator = new ReportGeneralConstructionNarrativeGenerator();
				// Estructurar los resultados según el formato requerido
				$reportData = [
					'gameInfo' => [
						'creatorName' => $gameInfoResponse['results'][0]['creator_name'],
						'creationDate' => $gameInfoResponse['results'][0]['creation_date'],
						'gameCode' => $gameInfoResponse['results'][0]['game_code'],
						'totalRequirements' => $gameInfoResponse['results'][0]['total_requirements']
					],
					'summary' => [
						'totalPlayers' => $summaryResponse['results'][0]['total_players'],
						'averageAccuracy' => $summaryResponse['results'][0]['average_accuracy'],
						'averageTime' => $summaryResponse['results'][0]['average_time'],
						'completionRate' => $summaryResponse['results'][0]['completion_rate']
					],
					'progress' => [
						'completed' => $summaryResponse['results'][0]['completed_count'],
						'completedPercentage' => $summaryResponse['results'][0]['completed_percentage'],
						'inProgress' => $summaryResponse['results'][0]['in_progress_count'],
						'inProgressPercentage' => $summaryResponse['results'][0]['in_progress_percentage'],
						'notStarted' => $summaryResponse['results'][0]['not_started_count'],
						'notStartedPercentage' => $summaryResponse['results'][0]['not_started_percentage'],
						'narrative' => $narrativeGenerator->generateSummaryNarrative(
							$summaryResponse['results'][0]['completed_percentage'],
							$summaryResponse['results'][0]['in_progress_count']
						)
					],
					'timeAnalysis' => [
						'averageTime' => $timeResponse['outParams']['average_time'],
						'bestTime' => $timeResponse['outParams']['mejor_tiempo'],
						'bestTimePlayer' => $this->FormatName($timeResponse['outParams']['mejor_tiempo_nombre'], $timeResponse['outParams']['mejor_tiempo_apellido']),
						'totalTimeInvested' => $timeResponse['outParams']['tiempo_total'],
						'distributionData' => $this->formatTimeDistribution($timeResponse['results']),
						'requirementsTime' => $this->formatRequirementsTimeAnalysis($timeResponseRequirement['results']),
						'narrative' => $narrativeGenerator->generateTimeAnalysisNarrative(
							$timeResponse['outParams']['average_time']
						)
					],
					'difficultyAnalysis' => [
						'challengingRequirements' => array_map(function ($req) {
							return [
								'rank' => $req['rankg'],
								'description' => $req['description'],
								'averageAttempts' => $req['average_attempts']
							];
						}, $difficultyResponse['results']),
						'attemptsStats' => [
							'minAttempts' => $difficultyResponse['outParams']['min_intentos'],
							'maxAttempts' => $difficultyResponse['outParams']['max_intentos'],
							'minAttemptsPlayer' => $this->FormatName($difficultyResponse['outParams']['min_nombre'], $difficultyResponse['outParams']['min_apellido']),
							'maxAttemptsPlayer' => $this->FormatName($difficultyResponse['outParams']['max_nombre'], $difficultyResponse['outParams']['max_apellido'])
						],
						'narrative' => $narrativeGenerator->generateDifficultyNarrative(
							$difficultyResponse['results'][0]['description'],
							$difficultyResponse['results'][0]['average_attempts'],
							$difficultyResponse['outParams']['min_intentos'],
							$difficultyResponse['outParams']['max_intentos']
						)
					]
				];

				$arrResponse = [
					'status' => true,
					'data' => $reportData,
					'message' => 'Reporte generado exitosamente'
				];
			} else {
				$arrResponse = [
					'status' => false,
					'message' => 'Error al generar el reporte',
					'data' => null
				];
			}
		} catch (Exception $e) {
			$arrResponse = [
				'status' => false,
				'message' => 'Error: ' . $e->getMessage(),
				'data' => null
			];
		} finally {
			$this->cerrarConexion();
		}

		return $arrResponse;
	}

	private function FormatName($nombres, $apellidos)
	{
		$partesNombres = explode(' ', trim($nombres));
		$partesApellidos = explode(' ', trim($apellidos));

		$primerNombre = $partesNombres[0];
		$primerApellido = $partesApellidos[0];

		return "$primerNombre $primerApellido";
	}

	private function formatTimeDistribution($distributionData)
	{
		return [
			'labels' => array_column($distributionData, 'rango_tiempo'),
			'datasets' => [[
				'label' => 'Número de estudiantes',
				'data' => array_column($distributionData, 'cantidad_jugadores'),
				'backgroundColor' => 'rgba(75, 192, 192, 0.6)',
				'borderColor' => 'rgba(75, 192, 192, 1)',
				'borderWidth' => 1
			]]
		];
	}

	private function formatRequirementsTimeAnalysis($requirementsData)
	{
		return array_map(function ($req) {
			return [
				'description' => $req['description'],
				'averageTime' => $req['average_time'],
				'completionRate' => $req['completion_rate'],
				'averageAttempts' => $req['average_attempts']
			];
		}, $requirementsData);
	}

	public function get_analiticas_jugadores_partida_construccionBD(string $gameCode, string $idJugador)
	{
		try {
			$responseAnalyticsJugadores = $this->executeProcedureWithParametersOut(
				'sp_estadisticas_jugadores_partida_construccion',
				[$gameCode, $idJugador],
				['codigo', 'mensaje']  // Parámetros de salida
			);
			if (!empty($responseAnalyticsJugadores) && $responseAnalyticsJugadores['outParams']['codigo'] == 1) {
				$arrResponse = array(
					'status' => true,
					'analytics' => $responseAnalyticsJugadores['results'],
					'message' => $responseAnalyticsJugadores['outParams']['mensaje']
				);
			} else {
				$arrResponse = array(
					'status' => false,
					'analytics' => $responseAnalyticsJugadores['results'],
					'message' => $responseAnalyticsJugadores['outParams']['mensaje']
				);
			}
		} catch (PDOException $e) {
			error_log("Error en procedimiento almacenado: " . $e->getMessage());
			return [
				'status' => false,
				'message' => 'Error al obtener datos del juego: ' . $e->getMessage(),
				'analytics' => []
			];
		} finally {
			$this->cerrarConexion();
		}

		return $arrResponse;
	}

	public function get_intentos_jugador_constructionBD(string $gameCode, string $idCreador, string $idJugador)
	{
		try {
			$responseAnalyticsJugadores = $this->executeProcedureWithParametersOut(
				'sp_stats_for_player_construction',
				[$gameCode, $idCreador, $idJugador],
				['codigo', 'mensaje', 'intentos', 'tiempo', 'nombres', 'apellidos', 'correo']  // Parámetros de salida
			);
			if (!empty($responseAnalyticsJugadores) && $responseAnalyticsJugadores['outParams']['codigo'] == 1) {
				$arrResponse = array(
					'status' => true,
					'attempts' => $responseAnalyticsJugadores['results'],
					'details' => [
						'tiempo_total' => $responseAnalyticsJugadores['outParams']['tiempo'],
						'total_intentos' => $responseAnalyticsJugadores['outParams']['intentos'],
						'nombres' => $responseAnalyticsJugadores['outParams']['nombres'],
						'apellidos' => $responseAnalyticsJugadores['outParams']['apellidos'],
						'email' => $responseAnalyticsJugadores['outParams']['correo']
					],
					'message' => $responseAnalyticsJugadores['outParams']['mensaje']
				);
			} else {
				$arrResponse = array(
					'status' => false,
					'attempts' => $responseAnalyticsJugadores['results'],
					'details' => [],
					'message' => $responseAnalyticsJugadores['outParams']['mensaje']
				);
			}
		} catch (PDOException $e) {
			error_log("Error en procedimiento almacenado: " . $e->getMessage());
			return [
				'status' => false,
				'message' => 'Error al obtener datos del juego: ' . $e->getMessage(),
				'attempts' => [],
				'details' => []
			];
		} finally {
			$this->cerrarConexion();
		}

		return $arrResponse;
	}

	public function get_detalles_intento_constructionBD(string $gameCode, string $idCreador, string $idJugador, string $idIntento)
	{
		try {
			$responseAnalyticsJugadores = $this->executeProcedureWithParametersOut(
				'sp_details_for_attempt_construction',
				[$gameCode, $idCreador, $idJugador, $idIntento],
				['codigo', 'mensaje', 'tiempo', 'presion', 'movimientos', 'fcorrectos', 'senuelos']  // Parámetros de salida
			);
			if (!empty($responseAnalyticsJugadores) && $responseAnalyticsJugadores['outParams']['codigo'] == 1) {
				$arrResponse = array(
					'status' => true,
					'attemptDetails' => $responseAnalyticsJugadores['results'],
					'headerDetails' => [
						'tiempo' => $responseAnalyticsJugadores['outParams']['tiempo'],
						'margen_presicion' => $responseAnalyticsJugadores['outParams']['presion'],
						'movimientos' => $responseAnalyticsJugadores['outParams']['movimientos'],
						'fragmentos_correctos' => $responseAnalyticsJugadores['outParams']['fcorrectos'],
						'senuelos' => $responseAnalyticsJugadores['outParams']['senuelos']
					],
					'message' => $responseAnalyticsJugadores['outParams']['mensaje']
				);
			} else {
				$arrResponse = array(
					'status' => false,
					'attemptDetails' => $responseAnalyticsJugadores['results'],
					'headerDetails' => [],
					'message' => $responseAnalyticsJugadores['outParams']['mensaje']
				);
			}
		} catch (PDOException $e) {
			error_log("Error en procedimiento almacenado: " . $e->getMessage());
			return [
				'status' => false,
				'message' => 'Error al obtener datos del juego: ' . $e->getMessage(),
				'attemptDetails' => [],
				'headerDetails' => []
			];
		} finally {
			$this->cerrarConexion();
		}

		return $arrResponse;
	}

	public function get_full_construction_reportBD(string $gameCode, string $idCreador, string $idJugador)
	{
		try {
			$response = $this->executeProcedureWithParametersOut(
				'sp_get_full_construction_report',
				[
					$gameCode,
					$idCreador,
					$idJugador
				],
				[
					'codigo',
					'mensaje',
					'tiempo_total',
					'total_intentos',
					'nombres',
					'apellidos',
					'correo'
				]
			);

			if (!empty($response) && $response['outParams']['codigo'] == 1) {
				// Procesamos y estructuramos los datos
				$attemptsData = array_map(function ($attempt) {
					$processedAttempt = $attempt;

					// Procesar los detalles del intento si existen
					if (!empty($attempt['detalles_intento'])) {
						$detalles = array_map(function ($detalle) {
							$parts = explode('|', $detalle);
							return [
								'id_fragmento' => (int)$parts[0],
								'posicion_usada' => (int)$parts[1],
								'tiempo_colocacion' => (int)$parts[2],
								'cantidad_movimientos' => (int)$parts[3],
								'es_correcto' => $parts[4] === '1' || $parts[4] === 'true',
								'texto' => $parts[5],
								'es_señuelo' => $parts[6] === '1' || $parts[6] === 'true'
							];
						}, explode('¬', $attempt['detalles_intento']));

						$processedAttempt['detalles'] = $detalles;
						unset($processedAttempt['detalles_intento']);
					} else {
						$processedAttempt['detalles'] = [];
					}

					return $processedAttempt;
				}, $response['results']);

				$arrResponse = [
					'status' => true,
					'data' => [
						'attempts' => $attemptsData,
						'summary' => [
							'tiempo_total' => $response['outParams']['tiempo_total'],
							'total_intentos' => $response['outParams']['total_intentos'],
							'nombres' => $response['outParams']['nombres'],
							'apellidos' => $response['outParams']['apellidos'],
							'correo' => $response['outParams']['correo']
						]
					],
					'message' => $response['outParams']['mensaje']
				];
			} else {
				$arrResponse = [
					'status' => false,
					'data' => null,
					'message' => $response['outParams']['mensaje']
				];
			}
		} catch (PDOException $e) {
			error_log("Error en procedimiento almacenado: " . $e->getMessage());
			$arrResponse = [
				'status' => false,
				'data' => null,
				'message' => 'Error al obtener datos del reporte: ' . $e->getMessage()
			];
		} finally {
			$this->cerrarConexion();
		}

		return $arrResponse;
	}
}
