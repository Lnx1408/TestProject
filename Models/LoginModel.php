<?php

class LoginModel extends Mysql
{
	private $intIdUsuario;
	private $strUsuario;
	private $strPassword;
	private $strToken;

	public function __construct()
	{
		parent::__construct();
	}

	public function login_user($postData)
	{
		try {
			if (isset($postData['encryptedData'])) {
				$decryptedData = decryptData($postData['encryptedData']);
				$data = json_decode($decryptedData, true);
				if (!$data) {
					throw new Exception('Error al descifrar datos');
				}

				if (empty($data['txtEmail']) || empty($data['txtPassword'])) {
					throw new Exception('Error de datos vacíos');
				}

				// Limpiar y procesar las entradas
				$strCorreo = strtolower(strClean($data['txtEmail']));
				$pPassword = hash("SHA256", $data['txtPassword']);
				$requestUser = $this->executeProcedureWithParametersOut(
					'sp_login_usuario',
					[$strCorreo, $pPassword],
					['codigo', 'mensaje', 'id_usuario', 'tipo_usuario', 'nombres', 'apellidos', 'correo']  // Parámetros de salida
				);

				if (!empty($requestUser) && $requestUser['outParams']['codigo'] == 1) {
					$sessionManager = SessionManager::getInstance();
					$sessionManager->initSession($requestUser['outParams']);
					return array(
						'status' => true,
						'msg' => 'Usuario accede correctamente'
					);
				} else if (!empty($requestUser) && $requestUser['outParams']['codigo'] == 2) {
					// Credenciales incorrectas
					$arrResponse = array('status' => false, 'msg' => 'Credenciales incorrectas');
				} else {
					// Error de sistema
					throw new Exception('Error en el sistema');
				}
			} else {
				return [
					'success' => false,
					'message' => 'Datos no recibidos'
				];
			}
		} catch (Exception $e) {
			// Manejo de excepciones con respuesta de error cifrada
			$arrResponse = array('status' => false, 'msg' => $e->getMessage());
		}
		return $arrResponse;
	}
}
