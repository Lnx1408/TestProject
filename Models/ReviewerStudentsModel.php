<?php
require_once("Infraestructure/ReviewerStudentsInfraestructure.php");

class ReviewerStudentsModel extends ReviewerStudentsInfraestructure
{

    public function __construct()
    {
        parent::__construct();
    }

    public function get_partidas_estudiante_revisor($data, int $idJugador)
    {
        try {
            $offset = [
                'classification' => $data['offset']['classification'] ?? 0,
                'construction' => $data['offset']['construction'] ?? 0
            ];
            $limit = $data['limit'] ?? 10;

            return $this->get_partidas_estudiante_revisorDB($idJugador, $offset, $limit);
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error al obtener las partidas: ' . $e->getMessage()
            ];
        }
    }

    public function get_original_requirement_reviewer($postData, int $idJugador)
    {
        if (isset($postData['encryptedData'])) {
            $decryptedData = decryptData($postData['encryptedData']);
            $data = json_decode($decryptedData, true);

            return $this->get_original_requirement_reviewerDB($data['gamecode'], $idJugador);
        } else {
            return [
                'success' => false,
                'message' => 'Datos no recibidos',
                'data' => []
            ];
        }
    }

}
