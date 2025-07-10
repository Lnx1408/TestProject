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

	public function get_teachers_reviewers_clasificacionDB(string $gameCode, string $idJugador)
	{
		try {
			$responseAnalyticsJugadores = $this->executeProcedureWithParametersOut(
				'sp_get_teachers_reviewers_clasificacion',
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

	public function get_requisitos_reviewDB(string $gameCode, string $idJugador)
	{
		try {
			$responseAnalyticsJugadores = $this->executeProcedureWithParametersOut(
				'sp_get_requisitos_review',
				[$gameCode, $idJugador],
				['codigo', 'mensaje']  // Parámetros de salida
			);
			if (!empty($responseAnalyticsJugadores) && $responseAnalyticsJugadores['outParams']['codigo'] == 0) {
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


	public function get_requirements_suggestionsDB(string $requisito, string $idJugador)
	{
		try {
			$responseAnalyticsJugadores = $this->executeProcedureWithParametersOut(
				'sp_get_requirements_suggestions',
				[$idJugador, $requisito],
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


	public function get_original_requirementDB(int $requisito, int $idJugador)
	{
		try {
			$responseAnalyticsJugadores = $this->executeProcedureWithParametersOut(
				'sp_get_original_requirement',
				[$requisito, $idJugador],
				outParams: ['codigo', 'mensaje']  // Parámetros de salida
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



	public function update_original_requirementBD(string $id_requisito, string $requisito, string $es_funcional, string $es_ambiguo, int $id_creador)
	{
		try {
			$response = $this->executeProcedureWithParametersOut(
				'sp_update_original_requirement',
				[$id_requisito, $requisito, $es_funcional, $es_ambiguo, $id_creador],
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

	
	public function update_teacher_reviewerBD(string $codigoPartida, int $idJugador, string $rolDocente)
	{
		try {
			$response = $this->executeProcedureWithParametersOut(
				'sp_update_teacher_reviewer',
				[$codigoPartida, $idJugador, $rolDocente],
				['codigo', 'mensaje']  // Parámetro de salida actualizado
			);

			if (!empty($response) && $response['outParams']['codigo'] == 1) {
				$arrResponse = array(
					'success' => true,
					'message' => $response['outParams']['mensaje']
				);
			} else {
				$arrResponse = array(
					'success' => false,
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
