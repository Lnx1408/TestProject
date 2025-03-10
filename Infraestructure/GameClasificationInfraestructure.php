<?php

class GameClasificationInfraestructure extends Mysql
{
	private $db;
	private $strquery;
	private $arrValues;

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

	public function validate_game_codeBD(string $gameCode, int $idJugador)
	{
		try {
            $responsePartida = $this->executeProcedureWithParametersOut(
				'sp_unirse_partida',
				[$gameCode, $idJugador],
				['codigo', 'mensaje', 'tipo']  // Parámetros de salida
			);
            if (!empty($responsePartida) && $responsePartida['outParams']['codigo'] != -1 ) {
				$arrResponse = array(
					'success' => true, 
					'gameType' => $responsePartida['outParams']['tipo'], 
					'message' => $responsePartida['outParams']['mensaje']
				);	
			}else{
				$arrResponse = array(
					'success' => false, 
					'message' => $responsePartida['outParams']['mensaje']
				);
			}
		} catch (PDOException $e) {
			error_log("Error en procedimiento almacenado: " . $e->getMessage());
			return [
				'success' => false,
				'message' => 'Error al obtener datos del juego: ' . $e->getMessage()
			];
		} finally {
			$this->cerrarConexion();
		}
        return $arrResponse;
	}


	public function get_requirements(string $gameCode, int $idJugador)
	{
		try {
			$this->conectar();
			if (!$this->db || $this->db === 'Error de conexión') {
				throw new PDOException("No se pudo establecer la conexión a la base de datos.");
			}
			// Llamar al procedimiento almacenado
			$sql = "CALL sp_get_game_requirements(:codigo, :id_jugador, @p_estado, @p_mensaje)";
			$stmt = $this->db->prepare($sql);
			$stmt->execute([
				':codigo' => $gameCode,
				':id_jugador' => $idJugador
			]);

			// Obtener los requisitos
			$requirements = $stmt->fetchAll(PDO::FETCH_ASSOC);

			$stmt->closeCursor();

			// Obtener el estado y mensaje del procedimiento
			$result = $this->db->query("SELECT @p_estado as estado, @p_mensaje as mensaje")->fetch(PDO::FETCH_ASSOC);

			if ($result['estado'] == -1) {
				return [
					'success' => false,
					'message' => $result['mensaje'],
					'requirements' => []
				];
			} else {
				$response = [
					'success' => true,
					'message' => $result['mensaje'],
					'gameState' => [
						'isNewGame' => $result['estado'] == 1,
						'inProgress' => $result['estado'] == 2
					],
					'requirements' => $requirements
				];

				// Si es un juego en progreso, agregar métricas del último intento
				if ($result['estado'] == 2 && !empty($requirements)) {
					$response['lastAttempt'] = [
						'attemptNumber' => $requirements[0]['numero_intento'],
						'progressiveAccuracy' => $requirements[0]['precision_progresiva']
					];
				}
				return $response;
			}
		} catch (PDOException $e) {
			error_log("Error en procedimiento almacenado: " . $e->getMessage());
			return [
				'success' => false,
				'message' => 'Error al obtener los requisitos: ' . $e->getMessage(),
				'requirements' => []
			];
		} finally {
			$this->cerrarConexion();
		}
	}

	public function validate_moves($data, int $idJugador)
	{
		try {
			$this->conectar();
			if (!$this->db || $this->db === 'Error de conexión') {
				throw new PDOException("No se pudo establecer la conexión a la base de datos.");
			}

			$gameCode = $data['gameCode'];
			$classification = $data['classification'];
			$attemptMetrics = $classification['attemptMetrics'];
			$requisitosIncorrectosData = $classification['requirementMovements'];

			// Vamos a validar solo los requisitos que el usuario está clasificando en este intento
			$reqIds = array_merge($classification['ambiguous'], $classification['nonAmbiguous']);

			// Modificar la consulta para obtener solo los requisitos del intento actual
			$placeholders = str_repeat('?,', count($reqIds) - 1) . '?';
			$query = "SELECT r.id_requisito, r.descripcion, r.es_ambiguo, r.retroalimentacion, p.id_partida 
				FROM requisitos_clasificacion_partida rcp 
				INNER JOIN partidas p ON rcp.id_partida = p.id_partida
				INNER JOIN requisitos r ON rcp.id_requisito = r.id_requisito 
				WHERE p.codigo_partida = ? AND r.id_requisito IN ($placeholders)";

			$params = array_merge([$gameCode], $reqIds);
			$stmt = $this->db->prepare($query);
			$stmt->execute($params);
			$requirements = $stmt->fetchAll(PDO::FETCH_ASSOC);

			$correctCount = 0;
			$incorrectRequirements = [];
			$validationResults = [];

			// NUEVO: Array para almacenar los resultados de validación
			$requisitosCorrectos = [];

			//SE QUE NO ES LO MEJOR PERO NO HAY TIEMPO DE ACTUALIZAR
			$id_partida_code = (!empty($requirements) && isset($requirements[0]['id_partida']))
				? (string)$requirements[0]['id_partida']
				: null; 

			foreach ($requirements as $req) {
				$reqId = (string)$req['id_requisito'];
				$isCorrect = false;

				$userClassification = in_array($reqId, $classification['ambiguous']) ? 'ambiguous' : (in_array($reqId, $classification['nonAmbiguous']) ? 'nonAmbiguous' : 'no_clasificado');

				// Validación
				if ($req['es_ambiguo'] == 1) {
					$isCorrect = ($userClassification === 'ambiguous');
					$expectedClassification = 'ambiguo';
				} else {
					$isCorrect = ($userClassification === 'nonAmbiguous');
					$expectedClassification = 'no ambiguo';
				}

				// Debug info
				$validationResults[] = [
					'reqId' => $reqId,
					'descripcion' => $req['descripcion'],
					'es_ambiguo_en_bd' => $req['es_ambiguo'],
					'clasificacion_usuario' => $userClassification,
					'clasificacion_esperada' => $expectedClassification,
					'es_correcto' => $isCorrect
				];

				// NUEVO: Guardamos el resultado de la validación
				$requisitosCorrectos[$reqId] = $isCorrect;

				if ($isCorrect) {
					$correctCount++;
				} else {
					$incorrectRequirements[] = [
						'id' => $reqId,
						'description' => $req['descripcion'],
						'feedback' => $req['retroalimentacion'],
						'clasificacion_esperada' => $expectedClassification,
						'clasificacion_usuario' => $userClassification
					];
				}
			}

			// Total de requisitos en este intento
			$totalRequirements = count($requirements);

			$accuracy = $totalRequirements > 0 ? round(($correctCount / $totalRequirements) * 100, 2) : 0;
			$successPressure = $accuracy;
			$errorPressure = 100 - $accuracy;

			// MODIFICADO: Formateo de requisitos incluyendo la nueva columna
			$requisitosFormateados = [];
			foreach ($requisitosIncorrectosData as $req) {
				// Obtenemos el valor de isCorrect para este requisito, por defecto false
				$isCorrect = isset($requisitosCorrectos[$req['id']]) ? $requisitosCorrectos[$req['id']] : false;

				$requisitosFormateados[] = implode(',', [
					$req['id'],
					$req['movimientosAmbiguo'],
					$req['movimientosNoAmbiguo'],
					$isCorrect ? 'true' : 'false'  // Nueva columna
				]);
			}
			$requisitosIncorrectosString = implode('|', $requisitosFormateados);


			// Llamada al procedimiento almacenado para registrar el intento
			$partidaid = (int)$id_partida_code; // Código del juego (id_partida)
			$playerId = (int)$idJugador;
			$attemptNumber = isset($attemptMetrics['attemptNumber']) ? (int)$attemptMetrics['attemptNumber'] : 0; // Número del intento
			$timeSpent = isset($attemptMetrics['timeInSeconds']) ? (int)$attemptMetrics['timeInSeconds'] : 0;  // Tiempo en el intento (en segundos)
			$moveCount = isset($attemptMetrics['moves']) ? (int)$attemptMetrics['moves'] : 0;  // Cantidad de movimientos realizados

			// Definir parámetros OUT para recibir las métricas calculadas
			$stmt = $this->db->prepare("CALL registrar_intento(?, ?, ?, ?, ?, ?, ?, ?, ?, @total_requisitos, @general_precision, @aciertos_precision, @errores_precision, @progressive_accuracy)");


			// Ejecutar el procedimiento almacenado
			$stmt->bindValue(1, $partidaid, PDO::PARAM_INT);
			$stmt->bindValue(2, $playerId, PDO::PARAM_INT);
			$stmt->bindValue(3, $attemptNumber, PDO::PARAM_INT);
			$stmt->bindValue(4, $timeSpent, PDO::PARAM_INT);
			$stmt->bindValue(5, $moveCount, PDO::PARAM_INT);
			$stmt->bindValue(6, $totalRequirements, PDO::PARAM_INT);
			$stmt->bindValue(7, $correctCount, PDO::PARAM_INT);
			$stmt->bindValue(8, count($incorrectRequirements), PDO::PARAM_INT);
			$stmt->bindValue(9, $requisitosIncorrectosString, PDO::PARAM_STR);


			$stmt->execute();
			// Obtener los valores de salida
			$stmt = $this->db->query("SELECT @total_requerimientos as total_requerimientos,
                               @general_precision as general_precision, 
                               @aciertos_precision as aciertos_precision,
                               @errores_precision as errores_precision,
                               @progressive_accuracy as progressive_accuracy");
			$metrics = $stmt->fetch(PDO::FETCH_ASSOC);

			// Preparar la respuesta
			$response = [
				'correctCount' => $correctCount,
				'incorrectCount' => count($incorrectRequirements),
				'accuracy' => $totalRequirements > 0 ? round(($correctCount / $totalRequirements) * 100, 2) : 0,
				'incorrectRequirements' => $incorrectRequirements,
				'attemptMetrics' => [
					'totalRequirementsInAttempt' => $metrics['total_requerimientos'],
					'generalPrecision' => $metrics['general_precision'],
					'aciertosPrecision' => $metrics['aciertos_precision'],
					'erroresPrecision' => $metrics['errores_precision'],
					'progressiveAccuracy' => $metrics['progressive_accuracy']
				],
				'debug' => [
					'validation_details' => $validationResults,
					'received_classification' => $classification
				]
			];

			return $response;
		} catch (PDOException $e) {
			error_log("Error en procedimiento almacenado: " . $e->getMessage());
			return [
				'success' => false,
				'message' => 'Error al obtener los requisitos: ' . $e->getMessage(),
				'requirements' => []
			];
		} finally {
			$this->cerrarConexion();
		}
	}

	public function get_requirements_construcion(string $gameCode, int $idJugador)
	{
		try {
			$this->conectar();
			if (!$this->db || $this->db === 'Error de conexión') {
				throw new PDOException("No se pudo establecer la conexión a la base de datos.");
			}

			// Llamar al procedimiento almacenado
			$sql = "CALL sp_get_construction_game(:codigo, :id_jugador, @p_estado, @p_mensaje, @p_requisitos_completados, @p_total_requisitos, @p_total_intentos)";
			$stmt = $this->db->prepare($sql);
			$stmt->execute([
				':codigo' => $gameCode,
				':id_jugador' => $idJugador
			]);

			// Si hay resultados, obtenerlos primero
			$requirementData = null;
			$fragments = null;

			if ($stmt->columnCount() > 0) {
				$requirementData = $stmt->fetch(PDO::FETCH_ASSOC);
				$stmt->nextRowset();
				if ($stmt->columnCount() > 0) {
					$fragments = $stmt->fetchAll(PDO::FETCH_ASSOC);
				}
			}

			$stmt->closeCursor();

			// Obtener el estado y mensaje del procedimiento
			$result = $this->db->query("SELECT @p_estado as estado, @p_mensaje as mensaje, @p_requisitos_completados as reqactuales, @p_total_requisitos as reqtotales, @p_total_intentos as intentos")->fetch(PDO::FETCH_ASSOC);

			// Manejar diferentes estados
			switch ($result['estado']) {
				case 0: // Juego completado
					return ([
						'success' => true,
						'gameCompleted' => true,
						'message' => $result['mensaje'],
						'reqActual' => $result['reqactuales'],
						'reqTotales' => $result['reqtotales'],
						'intentos' => $result['intentos']
					]);
					break;

				case -1: // Error
					return ([
						'success' => false,
						'message' => $result['mensaje']
					]);
					break;

				default: // Estados 1 y 2 (nuevo requisito o en progreso)
					if ($requirementData && $fragments) {
						return ([
							'success' => true,
							'message' => $result['mensaje'],
							'data' => array_merge($requirementData, ['fragmentos' => $fragments]),
							'reqActual' => $result['reqactuales'],
							'reqTotales' => $result['reqtotales'],
							'intentos' => $result['intentos']
						]);
					} else {
						return ([
							'success' => false,
							'message' => 'Error al obtener datos del requisito'
						]);
					}
					break;
			}
		} catch (PDOException $e) {
			error_log("Error en procedimiento almacenado: " . $e->getMessage());
			return [
				'success' => false,
				'message' => 'Error al obtener datos del juego: ' . $e->getMessage(),
				'requirements' => []
			];
		} finally {
			$this->cerrarConexion();
		}
	}

	public function validate_construction($data, int $idJugador)
	{
		try {
			$this->conectar();
			if (!$this->db || $this->db === 'Error de conexión') {
				throw new PDOException("No se pudo establecer la conexión a la base de datos.");
			}

			$movimientosStr = '';
			foreach ($data['movements'] as $fragmentId => $movement) {
				$movimientosStr .= $fragmentId . ',' .
					$movement['position'] . ',' .
					$movement['moves'] . ',' .
					$movement['placementTime'] . ';';
			}
			$movimientosStr = rtrim($movimientosStr, ';');


			// Una sola llamada al SP con todos los movimientos
			$sql = "CALL sp_validate_construction(
				:requirementId, 
				:idJugador, 
				:timeSpent,
				:movimientos,
				@p_resultado, 
				@p_mensaje
			)";

			$stmt = $this->db->prepare($sql);
			$stmt->execute([
				':requirementId' => $data['requirementId'],
				':idJugador' => $idJugador,
				':timeSpent' => $data['timeSpent'],
				':movimientos' => $movimientosStr
			]);

			$validationData = $stmt->fetchAll(PDO::FETCH_ASSOC);
			$stmt->closeCursor();

			// Verificar resultado
			$result = $this->db->query("SELECT @p_resultado as resultado, @p_mensaje as mensaje")
				->fetch(PDO::FETCH_ASSOC);

			if ($result['resultado'] == -1) {
				throw new Exception($result['mensaje']);
			}

			$lastResult = $validationData[0];

			return ([
				'success' => true,
				'isCorrect' => $lastResult['fragmentos_correctos'] == $lastResult['total_fragmentos'],
				'accuracy' => floatval($lastResult['precision']), // Asegurar que sea número
				'correctFragments' => intval($lastResult['fragmentos_correctos']),
				'totalFragments' => intval($lastResult['total_fragmentos']),
				'attemptNumber' => intval($data['attemptNumber'])
			]);
		} catch (PDOException $e) {
			error_log("Error en procedimiento almacenado: " . $e->getMessage());
			return [
				'success' => false,
				'message' => 'Error al validar los requisitos: ' . $e->getMessage()
			];
		} finally {
			$this->cerrarConexion();
		}
	}
	
}
