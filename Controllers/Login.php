<?php

class Login extends Controllers
{
	private $sessionManager;
	private $authService;


	public function __construct()
	{
		$this->sessionManager = SessionManager::getInstance();
        // Si ya hay una sesión activa, redirigir al dashboard
        if ($this->sessionManager->isLoggedIn()) {
            header('Location: ' . base_url() . '/dashboard');
            exit();
        }
		$this->authService = new Services\Auth\AuthService();
		parent::__construct();
	}

	public function login()
	{
		$data['page_tag'] = "Login - " . name_project();
		$data['page_title'] = name_project();
		$data['page_name'] = "login";
		$data['page_css'] =  array(
			'main.css',
			'login.css',
			'style.css'
		);
		$data['page_libraries_css'] =  array(
			'plugins/sweetalert2.min.css'
		);
		$data['page_functions_js'] = array(
			'CryptoModule.js',
			'plugins/sweetalert2.all.min.js',
			'login/functions_login.js'
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
            
            if (isset($postData['encryptedData'])) {
                $decryptedData = decryptData($postData['encryptedData']);
                $data = json_decode($decryptedData, true);
                
                if (!$data || empty($data['txtEmail']) || empty($data['txtPassword'])) {
                    throw new Exception('Datos incompletos');
                }
                
                $email = strClean($data['txtEmail']);
                $password = $data['txtPassword'];
                
                // Utilizar el servicio de autenticación
                $arrResponse = $this->authService->login($email, $password);
                
                // Incluir token en la respuesta si la autenticación fue exitosa
                if ($arrResponse['status']) {
                    $arrResponse['token'] = $arrResponse['token'];
                    $arrResponse['session_id'] = $arrResponse['session_id'];
                }
            } else {
                $arrResponse = array('status' => false, 'msg' => 'Datos no recibidos');
            }
        } catch (Exception $e) {
            $arrResponse = array('status' => false, 'msg' => $e->getMessage());
        }
        
        $jsonResponse = json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        $encryptedResponse = encryptResponse($jsonResponse);
        echo json_encode([
            'data' => $encryptedResponse
        ]);
        die();
    }

	public function resetPassword() {
        // Maneja la vista para recuperación de contraseña
        $data['page_tag'] = "Recuperar Contraseña - " . name_project();
        $data['page_title'] = name_project();
        $data['page_name'] = "reset_password";
        $data['page_functions_js'] = array(
            'CryptoModule.js',
            'login/functions_reset.js',
        );
        $this->views->getView($this, "reset_password", $data);
    }
    
    public function requestReset() {
        try {
            $inputJSON = file_get_contents("php://input");
            $postData = json_decode($inputJSON, true);
            
            if (isset($postData['encryptedData'])) {
                $decryptedData = decryptData($postData['encryptedData']);
                $data = json_decode($decryptedData, true);
                
                if (!$data || empty($data['email'])) {
                    throw new Exception('Email no proporcionado');
                }
                
                $email = strClean($data['email']);
                $arrResponse = $this->authService->requestPasswordReset($email);
            } else {
                $arrResponse = array('status' => false, 'msg' => 'Datos no recibidos');
            }
        } catch (Exception $e) {
            $arrResponse = array('status' => false, 'msg' => $e->getMessage());
        }
        
        $jsonResponse = json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        $encryptedResponse = encryptResponse($jsonResponse);
        echo json_encode([
            'data' => $encryptedResponse
        ]);
        die();
    }
    
    public function changePassword() {
        try {
            $inputJSON = file_get_contents("php://input");
            $postData = json_decode($inputJSON, true);
            
            if (isset($postData['encryptedData'])) {
                $decryptedData = decryptData($postData['encryptedData']);
                $data = json_decode($decryptedData, true);
                
                if (!$data || empty($data['token']) || empty($data['password'])) {
                    throw new Exception('Datos incompletos');
                }
                
                $token = strClean($data['token']);
                $password = $data['password'];
                
                $arrResponse = $this->authService->resetPassword($token, $password);
            } else {
                $arrResponse = array('status' => false, 'msg' => 'Datos no recibidos');
            }
        } catch (Exception $e) {
            $arrResponse = array('status' => false, 'msg' => $e->getMessage());
        }
        
        $jsonResponse = json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        $encryptedResponse = encryptResponse($jsonResponse);
        echo json_encode([
            'data' => $encryptedResponse
        ]);
        die();
    }
}
