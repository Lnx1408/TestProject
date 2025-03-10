<?php
require_once("Libraries/Reports/ReportGeneralConstructionNarrativeGenerator.php");

class AnalyticsStudentInfraestructure extends Mysql
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

	public function getMyGamesBD(int $idJugador)
	{
		try {
			$response = $this->executeProcedureWithParametersOut(
				'sp_get_partidas_por_estudiante',
				[
					$idJugador
				],
				[
					'codigo',
					'mensaje'
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

    /** CLASSIFICATION */
	public function get_intentos_jugadorBD(string $gameCode, string $idJugador)
	{
		try {
			$responseAnalyticsJugadores = $this->executeProcedureWithParametersOut(
				'sp_stats_for_player_student',
				[$gameCode, $idJugador],
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

	public function get_detalles_intentoBD(string $gameCode, string $idJugador, string $idIntento)
	{
		try {
			$responseAnalyticsJugadores = $this->executeProcedureWithParametersOut(
				'sp_details_for_attempt_student',
				[$gameCode, $idJugador, $idIntento],
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

	/** REPORTE DE CLASIFICACION */
	public function get_intentos_detalles_jugadorBD(string $gameCode, string $idJugador)
	{
		try {
			$responseAnalyticsJugadores = $this->executeProcedureWithParametersOut(
				'sp_stats_details_for_player_student',
				[$gameCode, $idJugador],
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

    /** CONSTRUCTION */
	public function get_intentos_jugador_constructionBD(string $gameCode, string $idJugador)
	{
		try {
			$responseAnalyticsJugadores = $this->executeProcedureWithParametersOut(
				'sp_stats_for_player_construction_student',
				[$gameCode, $idJugador],
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

	public function get_detalles_intento_constructionBD(string $gameCode, string $idJugador, string $idIntento)
	{
		try {
			$responseAnalyticsJugadores = $this->executeProcedureWithParametersOut(
				'sp_details_for_attempt_construction_student',
				[$gameCode, $idJugador, $idIntento],
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

	public function get_full_construction_reportBD(string $gameCode, string $idJugador)
	{
		try {
			$response = $this->executeProcedureWithParametersOut(
				'sp_get_full_construction_report_student',
				[
					$gameCode,
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
