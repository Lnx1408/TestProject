<?php
require_once("Infraestructure/AnalyticsStudentInfraestructure.php");

class AnalyticsStudentModel extends AnalyticsStudentInfraestructure
{

    public function __construct()
    {
        parent::__construct();
    }

    public function getMyGames($data, int $idJugador)
    {
        try {
            return $this->getMyGamesBD($idJugador);
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error al obtener las partidas: ' . $e->getMessage()
            ];
        }
    }

    //CLASSIFICATION
    public function get_intentos_jugador($postData, int $idJugador)
    {
        if (isset($postData['encryptedData'])) {
            $decryptedData = decryptData($postData['encryptedData']);
            $data = json_decode($decryptedData, true);

            return $this->get_intentos_jugadorBD($data['gamecode'], $idJugador);
        } else {
            return [
                'success' => false,
                'message' => 'Datos no recibidos',
                'attempts' => [],
                'details' => []
            ];
        }
    }

    public function get_intentos_detalles_jugador($gamecode, int $idJugador)
    {
        if (isset($gamecode) || isset($idJugador)) {
            return $this->get_intentos_detalles_jugadorBD($gamecode, $idJugador);
        } else {
            return [
                'status' => false,
                'message' => 'Datos no recibidos',
                'attempts' => [],
                'details' => []
            ];
        }
    }

    public function get_detalles_intento($postData, int $idJugador)
    {
        if (isset($postData['encryptedData'])) {
            $decryptedData = decryptData($postData['encryptedData']);
            $data = json_decode($decryptedData, true);

            return $this->get_detalles_intentoBD($data['gamecode'], $idJugador, $data['id_intento']);
        } else {
            return [
                'success' => false,
                'message' => 'Datos no recibidos',
                'attemptDetails' => [],
                'headerDetails' => []
            ];
        }
    }

    //CONSTRUCTION
    public function get_intentos_jugador_construction($postData, int $idJugador)
    {
        if (isset($postData['encryptedData'])) {
            $decryptedData = decryptData($postData['encryptedData']);
            $data = json_decode($decryptedData, true);

            return $this->get_intentos_jugador_constructionBD($data['gamecode'], $idJugador);
        } else {
            return [
                'success' => false,
                'message' => 'Datos no recibidos',
                'attempts' => [],
                'details' => []
            ];
        }
    }

    public function get_detalles_intento_construction($postData, int $idCreador)
    {
        if (isset($postData['encryptedData'])) {
            $decryptedData = decryptData($postData['encryptedData']);
            $data = json_decode($decryptedData, true);

            return $this->get_detalles_intento_constructionBD($data['gamecode'], $idCreador, $data['id_intento']);
        } else {
            return [
                'success' => false,
                'message' => 'Datos no recibidos',
                'attemptDetails' => [],
                'headerDetails' => []
            ];
        }
    }

    public function get_full_construction_report($gamecode, int $idJugador)
    {
        if (isset($gamecode) || isset($idJugador)) {
            return $this->get_full_construction_reportBD(
                $gamecode,
                $idJugador
            );
        } else {
            return [
                'status' => false,
                'message' => 'Datos no recibidos',
                'data' => null
            ];
        }
    }
}
