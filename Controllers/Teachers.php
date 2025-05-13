<?php

class Teachers extends AuthController{

	private $authService;

    public function __construct() {
        // Especificar roles permitidos para este controlador
        parent::__construct([
            SessionManager::ROLE_ADMIN
        ]);

		$this->authService = new Services\Auth\AuthService();
    }

	/**
     * Vista de información de docentes
     */
    public function teachers()
	{
		$data = array();
		$data['page_tag'] = "Teachers - " . name_project();
		$data['page_title'] = name_project();
		$data['page_name'] = "Teachers";
		

		$data['page_functions_js'] = array(
			'jquery-3.7.1.min.js',
			'teachers/teachers.js'
		);
		$data['page_css'] =  array(
			'teachers/teachers.css',
		);
		
		$this->addNavInfo($data);
		$this->views->getView($this, "teachers", $data);
	}

	/**
     * Vista de Creación de docentes
     */
    public function teacher()
    {
        $data = array();
        $data['page_tag'] = "Teacher - " . name_project();
        $data['page_title'] = name_project();
        $data['page_name'] = "Teacher";
        $data['breadcrumbs'] = [
            ['name' => 'Docentes', 'url' => 'teachers'],
            ['name' => 'Agregar docente', 'url' => '']
        ];
		$data['page_libraries_css'] =  array(
			'plugins/sweetalert2.min.css',
		);

        $data['page_functions_js'] = array(
            'jquery-3.7.1.min.js',
            'plugins/sweetalert2.all.min.js',
            'teachers/teacher.js'
        );
		
        $data['page_css'] = array(
            'teachers/teachers.css',
            'teachers/teacher.css',
			
        );

        $this->addNavInfo($data);
        $this->views->getView($this, "teacher", $data);
    }


	// API - Registrar docente

	public function registerTeacher()
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
}