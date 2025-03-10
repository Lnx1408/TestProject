<?php

class LevelsInfraestructure extends Mysql
{
	private $db;
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

	public function getRequirementsClasificationBD(string $idJugador)
	{
		try {
			$response = $this->executeProcedureWithParametersOut(
				'sp_get_requirements_clasification_create_level',
				[$idJugador],
				['codigo', 'mensaje']  // Parámetros de salida
			);
			if (!empty($response) && $response['outParams']['codigo'] == 1) {
				$arrResponse = array(
					'success' => true,
					'data' => $response['results'],
					'message' => $response['outParams']['mensaje']
				);
			} else {
				$arrResponse = array(
					'success' => false,
					'data' => $response['results'],
					'message' => $response['outParams']['mensaje']
				);
			}
		} catch (PDOException $e) {
			error_log("Error en procedimiento almacenado: " . $e->getMessage());
			return [
				'success' => false,
				'message' => 'Error al obtener datos del juego: ' . $e->getMessage(),
			];
		} finally {
			$this->cerrarConexion();
		}

		return $arrResponse;
	}

	public function createRequirementClasificationBD(int $idCreador, string $description, string $es_ambiguo, string $retro, string $es_funcional)
	{
		try {
			$response = $this->executeProcedureWithParametersOut(
				'sp_create_requirements_clasification',
				[$idCreador, $description, $es_ambiguo, $retro, $es_funcional],
				['codigo', 'mensaje', 'id_requisito']  // Parámetros de salida
			);
			if (!empty($response) && $response['outParams']['codigo'] == 1) {
				$arrResponse = array(
					'success' => true,
					'requirement' => $response['results'],
					'outputs' => [
						'id_requeriment' => $response['outParams']['id_requisito'],
					],
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
				'message' => 'Error al obtener datos del juego: ' . $e->getMessage(),
			];
		} finally {
			$this->cerrarConexion();
		}
		return $arrResponse;
	}

	public function importRequirementsClasificationBD(int $idCreador, array $requirements)
	{
		try {
			$this->conectar();

			// Convertir array de requisitos a formato string para el SP
			$reqString = '';
			foreach ($requirements as $req) {
				$reqString .= implode('|', [
					$req['descripcion'],
					$req['es_ambiguo'],
					$req['es_funcional'],
					$req['retroalimentacion']
				]) . '¬';
			}
			$reqString = rtrim($reqString, '¬');

			$response = $this->executeProcedureWithParametersOut(
				'sp_import_requirements_clasification',
				[
					$idCreador,
					$reqString
				],
				['codigo', 'mensaje', 'total_importados']
			);

			if (!empty($response) && $response['outParams']['codigo'] == 1) {
				return [
					'success' => true,
					'message' => $response['outParams']['mensaje'],
					'totalImported' => $response['outParams']['total_importados']
				];
			} else {
				return [
					'success' => false,
					'message' => $response['outParams']['mensaje'] ?? 'Error al importar requisitos'
				];
			}
		} catch (PDOException $e) {
			error_log("Error en importación de requisitos: " . $e->getMessage());
			return [
				'success' => false,
				'message' => 'Error al importar requisitos: ' . $e->getMessage()
			];
		} finally {
			$this->cerrarConexion();
		}
	}

	public function createGameClasificationBD(int $idCreador, $requeriments)
	{
		try {
			$requisitosString = implode(',', $requeriments);
			$response = $this->executeProcedureWithParametersOut(
				'sp_crear_partida_clasificacion',
				[$idCreador, $requisitosString],
				['codigo', 'mensaje', 'codigo_partida']  // Parámetros de salida
			);
			if (!empty($response) && $response['outParams']['codigo'] == 1) {
				$arrResponse = array(
					'success' => true,
					'gameCode' => $response['outParams']['codigo_partida'],
					'message' => $response['outParams']['mensaje']
				);
			} else {
				$arrResponse = array(
					'success' => false,
					'message' => $response['outParams']['mensaje']
				);
			}
		} catch (PDOException $e) {
			error_log("Error en procedimiento almacenado: " . $e->getMessage());
			return [
				'success' => false,
				'message' => 'Error al obtener datos del juego: ' . $e->getMessage(),
			];
		} finally {
			$this->cerrarConexion();
		}
		return $arrResponse;
	}

	public function getRequirementsConstructionBD(string $idJugador)
	{
		try {
			$response = $this->executeProcedureWithParametersOut(
				'sp_get_requirements_construction_create_level',
				[$idJugador],
				['codigo', 'mensaje']  // Parámetros de salida
			);
			if (!empty($response) && $response['outParams']['codigo'] == 1) {
				$results = array_map(function ($requisito) {
					$fragmentosArray = [];
					if (!empty($requisito['fragmentos'])) {
						$fragmentos = explode('¬', $requisito['fragmentos']);
						foreach ($fragmentos as $fragmento) {
							list($id, $texto, $posicion, $es_señuelo) = explode('|', $fragmento);
							$fragmentosArray[] = [
								'id' => (int)$id,
								'texto' => $texto,
								'posicion_correcta' => $posicion === 'NULL' ? null : (int)$posicion,
								'es_señuelo' => $es_señuelo === 'true'
							];
						}
					}
					return [
						'id' => (int)$requisito['id'],
						'requisito_completo' => $requisito['requisito_completo'],
						'nivel_dificultad' => (int)$requisito['nivel_dificultad'],
						'fragmentos' => $fragmentosArray
					];
				}, $response['results']);

				$arrResponse = array(
					'success' => true,
					'data' => $results,
					'message' => $response['outParams']['mensaje']
				);
			} else {
				$arrResponse = array(
					'success' => false,
					'data' => $response['results'],
					'message' => $response['outParams']['mensaje']
				);
			}
		} catch (PDOException $e) {
			error_log("Error en procedimiento almacenado: " . $e->getMessage());
			return [
				'success' => false,
				'message' => 'Error al obtener datos del juego: ' . $e->getMessage(),
			];
		} finally {
			$this->cerrarConexion();
		}

		return $arrResponse;
	}

	public function createRequirementConstructionBD(int $idCreador, $data)
	{
		try {
			$fragmentosStr = [];
			foreach ($data['fragmentos'] as $fragmento) {
				$fragmentosStr[] = implode('|', [
					$fragmento['texto'],
					$fragmento['posicion_correcta'] ?? 'NULL',
					$fragmento['es_señuelo'] ? 'true' : 'false'
				]);
			}
			$fragmentosFormatted = implode('¬', $fragmentosStr);


			$response = $this->executeProcedureWithParametersOut(
				'sp_create_requirements_construction',
				[
					$idCreador,
					$data['requisito_completo'],
					$fragmentosFormatted
				],
				['codigo', 'mensaje', 'id_requisito']  // Parámetros de salida
			);
			if (!empty($response) && $response['outParams']['codigo'] == 1) {
				// Procesar los fragmentos del resultado
				$results = [];
				if (!empty($response['results'][0])) {
					$requisito = $response['results'][0];
					$fragmentosArray = [];
					if (!empty($requisito['fragmentos'])) {
						$fragmentos = explode('¬', $requisito['fragmentos']);
						foreach ($fragmentos as $fragmento) {
							list($id, $texto, $posicion, $es_señuelo) = explode('|', $fragmento);
							$fragmentosArray[] = [
								'id' => (int)$id,
								'texto' => $texto,
								'posicion_correcta' => $posicion === 'NULL' ? null : (int)$posicion,
								'es_señuelo' => $es_señuelo === 'true'
							];
						}
					}
					$results = [
						'id' => (int)$requisito['id'],
						'requisito_completo' => $requisito['requisito_completo'],
						'nivel_dificultad' => (int)$requisito['nivel_dificultad'],
						'fragmentos' => $fragmentosArray
					];
				}

				$arrResponse = [
					'success' => true,
					'requirement' => $results,
					'outputs' => [
						'id_requeriment' => $response['outParams']['id_requisito']
					],
					'message' => $response['outParams']['mensaje']
				];
			} else {
				$arrResponse = array(
					'success' => false,
					'attemptDetails' => $response['results'],
					'message' => $response['outParams']['mensaje']
				);
			}
		} catch (PDOException $e) {
			error_log("Error en procedimiento almacenado: " . $e->getMessage());
			return [
				'success' => false,
				'message' => 'Error al obtener datos del juego: ' . $e->getMessage(),
			];
		} finally {
			$this->cerrarConexion();
		}
		return $arrResponse;
	}

	public function importRequirementsConstructionBD(int $idCreador, array $requirements) {
		try {
			$this->conectar();
			
			// Convertir array de requisitos a formato string para el SP
			$reqString = implode('§', array_map(function($req) {
				return implode('|', [
					$req['requisito_completo'],
					$req['fragmentos'] 
				]);
			}, $requirements));
	
			$response = $this->executeProcedureWithParametersOut(
				'sp_import_requirements_construction',
				[
					$idCreador,
					$reqString
				],
				['codigo', 'mensaje', 'total_importados']
			);
	
			if (!empty($response) && $response['outParams']['codigo'] == 1) {
				return [
					'success' => true,
					'message' => $response['outParams']['mensaje'],
					'totalImported' => $response['outParams']['total_importados']
				];
			} else {
				return [
					'success' => false,
					'message' => $response['outParams']['mensaje'] ?? 'Error al importar requisitos'
				];
			}
	
		} catch (PDOException $e) {
			error_log("Error en importación de requisitos: " . $e->getMessage());
			return [
				'success' => false,
				'message' => 'Error al importar requisitos: ' . $e->getMessage()
			];
		} finally {
			$this->cerrarConexion();
		}
	}

	public function createGameConstructionBD(int $idCreador, $requeriments)
	{
		try {
			$requisitosString = implode(',', $requeriments);
			$response = $this->executeProcedureWithParametersOut(
				'sp_crear_partida_construction',
				[$idCreador, $requisitosString],
				['codigo', 'mensaje', 'codigo_partida']  // Parámetros de salida
			);
			if (!empty($response) && $response['outParams']['codigo'] == 1) {
				$arrResponse = array(
					'success' => true,
					'gameCode' => $response['outParams']['codigo_partida'],
					'message' => $response['outParams']['mensaje']
				);
			} else {
				$arrResponse = array(
					'success' => false,
					'message' => $response['outParams']['mensaje']
				);
			}
		} catch (PDOException $e) {
			error_log("Error en procedimiento almacenado: " . $e->getMessage());
			return [
				'success' => false,
				'message' => 'Error al obtener datos del juego: ' . $e->getMessage(),
			];
		} finally {
			$this->cerrarConexion();
		}
		return $arrResponse;
	}
}
