<?php
require_once("Libraries/Reports/ReportGeneralConstructionNarrativeGenerator.php");
require_once("Libraries/Reports/ReportGeneralClassificationNarrativeGenerator.php");

class ReviewerStudentsInfraestructure extends Mysql
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

    public function get_partidas_estudiante_revisorDB(int $idJugador, array $offset, int $limit)
	{
		try {
			$response = $this->executeProcedureWithParametersOut(
				'sp_get_partidas_estudiante_revisor',
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

    public function get_original_requirement_reviewerDB(string $gameCode, string $idJugador)
	{
		try {
			$responseAnalyticsJugadores = $this->executeProcedureWithParametersOut(
				'sp_get_original_requirement_reviewer',
				[$gameCode, $idJugador],
				['codigo', 'mensaje']  // Parámetros de salida
			);
			if (!empty($responseAnalyticsJugadores) && $responseAnalyticsJugadores['outParams']['codigo'] == 1) {
				$arrResponse = array(
					'status' => true,
					'data' => $responseAnalyticsJugadores['results'],
					'message' => $responseAnalyticsJugadores['outParams']['mensaje']
				);
			} else {
				$arrResponse = array(
					'status' => false,
					'data' => $responseAnalyticsJugadores['results'],
					'message' => $responseAnalyticsJugadores['outParams']['mensaje']
				);
			}
		} catch (PDOException $e) {
			error_log("Error en procedimiento almacenado: " . $e->getMessage());
			return [
				'status' => false,
				'message' => 'Error al obtener datos del juego: ' . $e->getMessage(),
				'data' => []
			];
		} finally {
			$this->cerrarConexion();
		}

		return $arrResponse;
	}

}
