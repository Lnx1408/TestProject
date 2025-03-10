<?php

class Mysql extends Conexion
{
	private $conexion;
	private $strquery;
	private $arrValues;

	function __construct() {}

	private function conectar()
	{
		if (!$this->conexion) {
			$this->conexion = (new Conexion())->conect();
		}
	}

	private function cerrarConexion()
	{
		$this->conexion = null;
	}

	public function executeProcedure(string $procedureName, array $params = [])
	{
		$data = null;
		try {
			$this->conectar();

			// Validamos si la conexión se realizó correctamente
			if (!$this->conexion || $this->conexion === 'Error de conexión') {
				throw new PDOException("No se pudo establecer la conexión a la base de datos.");
			}

			// Construimos la consulta para el procedimiento almacenado con los placeholders para los parámetros
			$placeholders = implode(',', array_fill(0, count($params), '?'));
			$this->strquery = "CALL $procedureName($placeholders)";
			$stmt = $this->conexion->prepare($this->strquery);

			// Ejecutamos el procedimiento pasando los parámetros
			$stmt->execute($params);

			// Obtener los resultados, si existen
			$data = $stmt->fetchAll(PDO::FETCH_ASSOC);
		} catch (PDOException $e) {
			error_log("Error en procedimiento almacenado: " . $e->getMessage());
			$data = null;
		} finally {
			$this->cerrarConexion();
		}

		return $data;
	}

	public function executeProcedureWithParametersOut(string $procedureName, array $inParams = [], array $outParams = [])
	{
		$data = ['results' => null, 'outParams' => []];
		try {
			$this->conectar();

			if (!$this->conexion || $this->conexion === 'Error de conexión') {
				throw new PDOException("No se pudo establecer la conexión a la base de datos.");
			}

			// Inicializar las variables de salida
			foreach ($outParams as $paramName) {
				$this->conexion->query("SET @$paramName = NULL");
			}

			// Construir la consulta
			$inPlaceholders = str_repeat('?,', count($inParams));
			$outPlaceholders = '';

			foreach ($outParams as $paramName) {
				$outPlaceholders .= "@$paramName,";
			}

			$allPlaceholders = rtrim($inPlaceholders . $outPlaceholders, ',');
			$this->strquery = "CALL $procedureName($allPlaceholders)";

			// Preparar y ejecutar
			$stmt = $this->conexion->prepare($this->strquery);
			$stmt->execute($inParams);

			// Obtener los resultados del SELECT si existen
			$data['results'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
			// Cerrar el cursor del statement anterior
			$stmt->closeCursor();

			// Si hay parámetros de salida, obtenerlos
			if (!empty($outParams)) {
				$selectParts = [];
				foreach ($outParams as $paramName) {
					$selectParts[] = "@$paramName AS `$paramName`";
				}
				$outQuery = "SELECT " . implode(', ', $selectParts);
				$outResult = $this->conexion->query($outQuery);
				if ($outResult) {
					$data['outParams'] = $outResult->fetch(PDO::FETCH_ASSOC);
				}
			}
		} catch (PDOException $e) {
			error_log("Error en procedimiento almacenado: " . $e->getMessage());
			$data = ['results' => null, 'outParams' => [], 'error' => $e->getMessage()];
		} finally {
			$this->cerrarConexion();
		}

		return $data;
	}


	public function insert(string $query, array $arrValues)
	{
		try {
			$this->conectar();
			$this->strquery = $query;
			$this->arrValues = $arrValues;
			$insert = $this->conexion->prepare($this->strquery);
			$resInsert = $insert->execute($this->arrValues);
			$lastInsert = $resInsert ? $this->conexion->lastInsertId() : 0;
		} catch (PDOException $e) {
			// Manejo del error: registrar el mensaje o lanzar una excepción
			error_log("Error en insert: " . $e->getMessage());
			$lastInsert = 0;
		} finally {
			$this->cerrarConexion();
		}
		return $lastInsert;
	}

	//Busca un registro
	public function select(string $query)
	{
		$this->strquery = $query;
		$result = $this->conexion->prepare($this->strquery);
		$result->execute();
		$data = $result->fetch(PDO::FETCH_ASSOC);
		return $data;
	}
	//Devuelve todos los registros
	public function select_all(string $query)
	{
		$this->strquery = $query;
		$result = $this->conexion->prepare($this->strquery);
		$result->execute();
		$data = $result->fetchall(PDO::FETCH_ASSOC);
		return $data;
	}
	//Actualiza registros
	public function update(string $query, array $arrValues)
	{
		$this->strquery = $query;
		$this->arrValues = $arrValues;
		$update = $this->conexion->prepare($this->strquery);
		$resExecute = $update->execute($this->arrValues);
		return $resExecute;
	}
	//Eliminar un registros
	public function delete(string $query)
	{
		$this->strquery = $query;
		$result = $this->conexion->prepare($this->strquery);
		$del = $result->execute();
		return $del;
	}
}
