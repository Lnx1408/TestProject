<?php

class Login extends Controllers
{
	private $sessionManager;

	public function __construct()
	{
		$this->sessionManager = SessionManager::getInstance();
        // Si ya hay una sesión activa, redirigir al dashboard
        if ($this->sessionManager->isLoggedIn()) {
            header('Location: ' . base_url() . '/dashboard');
            exit();
        }
		parent::__construct();
	}

	public function login()
	{
		$data['page_tag'] = "Login - " . name_project();
		$data['page_title'] = name_project();
		$data['page_name'] = "login";
		//$data['page_functions_js'] = "functions_login.js";
		$data['page_functions_js'] = array(
			'CryptoModule.js',
			'login/functions_login.js',
		);
		$this->views->getView($this, "login", $data);
	}

	public function registerUser()
	{
		$inputJSON = file_get_contents("php://input");
		$input = json_decode($inputJSON, true);

		if (isset($input['encryptedData'])) {
			// Descifrar datos
			$decryptedData = decryptData($input['encryptedData']);
			$data = json_decode($decryptedData, true);

			if ($data) {
				if (empty($data['txtEmailRegister']) || empty($data['txtPasswordRegister'])) {
					$arrResponse = array('status' => false, 'msg' => 'Error de datos');
				} else {
					$strTipoUsuario  = strtoupper(strClean($data['txtTypeUser']));
					$strUsuario  = strtolower(strClean($data['txtUserRegister']));
					$strCorreo  = strtolower(strClean($data['txtEmailRegister']));
					$strNombres  = strtoupper(strClean($data['txtFirstNameRegister']));
					$strApellidos  = strtoupper(strClean($data['txtLastNameRegister']));
					$strPassword = empty($data['txtPasswordRegister']) ? hash("SHA256", passGenerator()) : hash("SHA256", $data['txtPasswordRegister']);

					$requestUser = $this->model->executeProcedureWithParametersOut(
						'sp_registrar_usuario',  // Nombre del procedimiento almacenado
						[$strTipoUsuario, $strUsuario, $strNombres, $strApellidos, $strCorreo, $strPassword], // Parámetros de entrada
						['codigo', 'mensaje']  // Parámetros de salida
					);
					if (!empty($requestUser) && $requestUser['outParams']['codigo'] == 1) {
						$arrResponse = array('status' => true, 'msg' => 'Usuario registrado correctamente');
					}else if(!empty($requestUser) && $requestUser['outParams']['codigo'] == 2){
						$arrResponse = array('status' => false, 'msg' => 'El correo ya está registrado');
					}
					else if(!empty($requestUser) && $requestUser['outParams']['codigo'] == 3){
						$arrResponse = array('status' => false, 'msg' => 'El Nombre de Usuario no se encuentra disponible, intente con otro nombre de Usuario');
					}
					else{
						$arrResponse = array('status' => false, 'msg' => 'Ocurrio un error al registrar el usuario');
					}
				}
			} else {
				$arrResponse = (['status' => false, 'msg' => 'Error al descifrar datos']);
			}
		} else {
			$arrResponse = (['status' => false, 'msg' => 'Datos no recibidos']);
		}
		$jsonResponse = json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
		$encryptedResponse = encryptResponse($jsonResponse);
		echo json_encode([
			'data' => $encryptedResponse
		]);
		die();
	}

	public function loginUser()
	{
		try {
			$inputJSON = file_get_contents("php://input");
			$postData = json_decode($inputJSON, true);
			$arrResponse = $this->model->login_user($postData);
		} catch (Exception $e) {
			// Manejo de excepciones con respuesta de error cifrada
			$arrResponse = array('status' => false, 'msg' => $e->getMessage());
		}
		$jsonResponse = json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
		$encryptedResponse = encryptResponse($jsonResponse);
		$this->sessionManager; 
		echo json_encode([
			'data' => $encryptedResponse
		]);
		//echo $encryptedResponse;
		die();
	}

	public function resetPass()
	{
		if ($_POST) {
			error_reporting(0);

			if (empty($_POST['txtEmailReset'])) {
				$arrResponse = array('status' => false, 'msg' => 'Error de datos');
			} else {
				$token = token();
				$strEmail  =  strtolower(strClean($_POST['txtEmailReset']));
				$arrData = $this->model->getUserEmail($strEmail);

				if (empty($arrData)) {
					$arrResponse = array('status' => false, 'msg' => 'Usuario no existente.');
				} else {
					$idpersona = $arrData['idpersona'];
					$nombreUsuario = $arrData['nombres'] . ' ' . $arrData['apellidos'];

					$url_recovery = base_url() . '/login/confirmUser/' . $strEmail . '/' . $token;
					$requestUpdate = $this->model->setTokenUser($idpersona, $token);

					$dataUsuario = array(
						'nombreUsuario' => $nombreUsuario,
						'email' => $strEmail,
						'asunto' => 'Recuperar cuenta - ' . NOMBRE_REMITENTE,
						'url_recovery' => $url_recovery
					);
					if ($requestUpdate) {
						$sendEmail = sendEmail($dataUsuario, 'email_cambioPassword');

						if ($sendEmail) {
							$arrResponse = array(
								'status' => true,
								'msg' => 'Se ha enviado un email a tu cuenta de correo para cambiar tu contraseña.'
							);
						} else {
							$arrResponse = array(
								'status' => false,
								'msg' => 'No es posible realizar el proceso, intenta más tarde.'
							);
						}
					} else {
						$arrResponse = array(
							'status' => false,
							'msg' => 'No es posible realizar el proceso, intenta más tarde.'
						);
					}
				}
			}
			echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
		}
		die();
	}

	public function confirmUser(string $params)
	{
		if (empty($params)) {
			header('Location: ' . base_url());
		} else {
			$arrParams = explode(',', $params);
			$strEmail = strClean($arrParams[0]);
			$strToken = strClean($arrParams[1]);
			$arrResponse = $this->model->getUsuario($strEmail, $strToken);
			if (empty($arrResponse)) {
				header("Location: " . base_url());
			} else {
				$data['page_tag'] = "Cambiar contraseña";
				$data['page_name'] = "cambiar_contrasenia";
				$data['page_title'] = "Cambiar Contraseña";
				$data['email'] = $strEmail;
				$data['token'] = $strToken;
				$data['idpersona'] = $arrResponse['idpersona'];
				$data['page_functions_js'] = "functions_login.js";
				$this->views->getView($this, "cambiar_password", $data);
			}
		}
		die();
	}

	public function setPassword()
	{

		if (empty($_POST['idUsuario']) || empty($_POST['txtEmail']) || empty($_POST['txtToken']) || empty($_POST['txtPassword']) || empty($_POST['txtPasswordConfirm'])) {

			$arrResponse = array(
				'status' => false,
				'msg' => 'Error de datos'
			);
		} else {
			$intIdpersona = intval($_POST['idUsuario']);
			$strPassword = $_POST['txtPassword'];
			$strPasswordConfirm = $_POST['txtPasswordConfirm'];
			$strEmail = strClean($_POST['txtEmail']);
			$strToken = strClean($_POST['txtToken']);

			if ($strPassword != $strPasswordConfirm) {
				$arrResponse = array(
					'status' => false,
					'msg' => 'Las contraseñas no son iguales.'
				);
			} else {
				$arrResponseUser = $this->model->getUsuario($strEmail, $strToken);
				if (empty($arrResponseUser)) {
					$arrResponse = array(
						'status' => false,
						'msg' => 'Erro de datos.'
					);
				} else {
					$strPassword = hash("SHA256", $strPassword);
					$requestPass = $this->model->insertPassword($intIdpersona, $strPassword);

					if ($requestPass) {
						$arrResponse = array(
							'status' => true,
							'msg' => 'Contraseña actualizada con éxito.'
						);
					} else {
						$arrResponse = array(
							'status' => false,
							'msg' => 'No es posible realizar el proceso, intente más tarde.'
						);
					}
				}
			}
		}
		echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
		die();
	}
}
