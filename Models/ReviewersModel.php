<?php
require_once("Infraestructure/ReviewersInfraestructure.php");

class ReviewersModel extends ReviewersInfraestructure
{

    public function __construct()
    {
        parent::__construct();
    }

    public function get_reviewers_partida_clasificacion($postData, int $idJugador)
    {
        if (isset($postData['encryptedData'])) {
            $decryptedData = decryptData($postData['encryptedData']);
            $data = json_decode($decryptedData, true);

            return $this->get_reviewers_partida_clasificacionDB($data['gamecode'], $idJugador);
        } else {
            return [
                'success' => false,
                'message' => 'Datos no recibidos',
                'analytics' => []
            ];
        }
    }

    public function get_teachers_reviewers_clasificacion($postData, int $idJugador)
    {
        if (isset($postData['encryptedData'])) {
            $decryptedData = decryptData($postData['encryptedData']);
            $data = json_decode($decryptedData, true);

            return $this->get_teachers_reviewers_clasificacionDB($data['gamecode'], $idJugador);
        } else {
            return [
                'success' => false,
                'message' => 'Datos no recibidos',
                'analytics' => []
            ];
        }
    }

    public function update_reviewer($postData)
    {
        if (isset($postData['encryptedData'])) {
            $decryptedData = decryptData($postData['encryptedData']);
            $data = json_decode($decryptedData, true);

            // Validar y procesar los datos
            $codigoPartida = $data['codigoPartida'];
            $idJugador = $data['id_jugador'];
            $rolEstudiante = $data['rol'];
            // Llamar a la función de base de datos con los datos procesados
            return $this->update_reviewerBD(
                codigoPartida: $codigoPartida,
                idEstudiante: $idJugador,
                rolEstudiante: $rolEstudiante,
            );
        } else {
            return [
                'success' => false,
                'message' => 'Datos no recibidos',
            ];
        }
    }

}
