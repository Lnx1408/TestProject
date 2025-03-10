<?php
class Language extends AuthController {

    public function __construct() {
        parent::__construct();
    }

    public function setLanguage() {
        try {
            $inputJSON = file_get_contents("php://input");
            $input = json_decode($inputJSON, true);
            $idJugador = $this->getUserData('id');
            if (isset($input['encryptedData'])) {
                $decryptedData = decryptData($input['encryptedData']);
                $data = json_decode($decryptedData, true);

                if ($data && isset($data['language'])) {
                    // Guardar en sesión
                    $_SESSION['language'] = $data['language'];
                    
                    $lang = $this->getUserLanguage() ?? 'none';
                    $response = [
                        'success' => true,
                        'message' => 'Language updated successfully'
                    ];
                } else {
                    $response = [
                        'success' => false,
                        'message' => 'Invalid language data'
                    ];
                }
            } else {
                $response = [
                    'success' => false,
                    'message' => 'No data received'
                ];
            }
        } catch (Exception $e) {
            $response = [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }

        $jsonResponse = json_encode($response);
        $encryptedResponse = encryptResponse($jsonResponse);
        echo json_encode(['data' => $encryptedResponse]);
        die();
    }
}