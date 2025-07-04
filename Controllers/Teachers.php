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
        try {
            $jsonData = file_get_contents('php://input');
            $postData = json_decode($jsonData, true);

            if (!isset($postData['encryptedData'])) {
                throw new \Exception('Datos no recibidos');
            }

            // Desencriptar datos
            $decryptedData = decryptData($postData['encryptedData']);
            $data = json_decode($decryptedData, true);

            if (!$data || !isset($data['txtEmailRegister']) || !isset($data['txtPasswordRegister'])) {
                throw new \Exception('Datos incompletos');
            }

            $strTipoUsuario  = strtoupper(strClean($data['txtTypeUser']));
			$strUsuario  = strtolower(strClean($data['txtUserRegister']));
			$strCorreo  = strtolower(strClean($data['txtEmailRegister']));
			$strNombres  = strtoupper(strClean($data['txtFirstNameRegister']));
			$strApellidos  = strtoupper(strClean($data['txtLastNameRegister']));
			$strPassword = empty($data['txtPasswordRegister']) ? hash("SHA256", passGenerator()) : hash("SHA256", $data['txtPasswordRegister']);


            // Validaciones adicionales
            if (empty($strCorreo) || empty($strPassword)) {
                throw new \Exception('El correo y contraseña no puede estar vacío');
            }

            $response = $this->authService->registerTeacherdb($strTipoUsuario, $strUsuario, $strNombres, $strApellidos,$strCorreo, $strPassword);

            $jsonResponse = json_encode($response, JSON_UNESCAPED_UNICODE);
            $encryptedResponse = encryptResponse($jsonResponse);

            echo json_encode(['data' => $encryptedResponse]);
            die();
        } catch (\Exception $e) {
            $response = [
                'status' => false,
                'msg' => 'Error al registrar docente: ' . $e->getMessage()
            ];

            echo json_encode([
                'data' => encryptResponse(json_encode($response))
            ]);
            die();
        }
    }
}