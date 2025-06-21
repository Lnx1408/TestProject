<?php
require_once("Libraries/Reports/ReportGeneralConstructionNarrativeGenerator.php");
require_once("Libraries/Reports/ReportGeneralClassificationNarrativeGenerator.php");

class ReviewersInfraestructure extends Mysql
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

	public function get_reviewers_partida_clasificacionDB(string $gameCode, string $idJugador)
	{
		try {
			$responseAnalyticsJugadores = $this->executeProcedureWithParametersOut(
				'sp_get_reviewers_partida_clasificacion',
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

	public function update_reviewerBD(string $codigoPartida, int $idEstudiante, int $rolEstudiante)
	{
		try {
			$response = $this->executeProcedureWithParametersOut(
				'sp_update_reviewer',
				[$codigoPartida, $idEstudiante, $rolEstudiante],
				['codigo', 'mensaje']  // Parámetro de salida actualizado
			);

			if (!empty($response) && $response['outParams']['codigo'] == 1) {
				$arrResponse = array(
					'success' => true,
					'requirement' => $response['results'],
					'message' => $response['outParams']['mensaje']
				);
			} else {
				$arrResponse = array(
					'success' => false,
					'attemptDetails' => $response['results'],
					'headerDetails' => [],
					'message' => $response['outParams']['mensaje']
				);
			}
		} catch (PDOException $e) {
			error_log("Error en procedimiento almacenado: " . $e->getMessage());
			return [
				'success' => false,
				'message' => 'Error al actualizar el requisito: ' . $e->getMessage(),
			];
		} finally {
			$this->cerrarConexion();
		}

		return $arrResponse;
	}
	
}
