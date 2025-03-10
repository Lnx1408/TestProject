<?php
require_once("Infraestructure/AnalyticsInfraestructure.php");

class AnalyticsModel extends AnalyticsInfraestructure
{

    public function __construct()
    {
        parent::__construct();
    }

    public function getMyGames($data, int $idJugador)
    {
        try {
            $offset = [
                'classification' => $data['offset']['classification'] ?? 0,
                'construction' => $data['offset']['construction'] ?? 0
            ];
            $limit = $data['limit'] ?? 10;

            return $this->getMyGamesBD($idJugador, $offset, $limit);
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error al obtener las partidas: ' . $e->getMessage()
            ];
        }
    }

    public function get_analiticas_jugadores_partida($postData, int $idJugador)
    {
        if (isset($postData['encryptedData'])) {
            $decryptedData = decryptData($postData['encryptedData']);
            $data = json_decode($decryptedData, true);

            return $this->get_analiticas_jugadores_partidaBD($data['gamecode'], $idJugador);
        } else {
            return [
                'success' => false,
                'message' => 'Datos no recibidos',
                'analytics' => []
            ];
        }
    }

    public function get_analiticas_generales_partida($postData, int $idJugador)
    {
        //return $this->get_analiticas_generales_partidaBD($strGameCode, $idJugador);

        if (isset($postData['encryptedData'])) {
            $decryptedData = decryptData($postData['encryptedData']);
            $data = json_decode($decryptedData, true);

            return $this->get_analiticas_generales_partidaBD($data['gamecode'], $idJugador);
        } else {
            return [
                'success' => false,
                'message' => 'Datos no recibidos',
                'analytics' => []
            ];
        }
    }

    public function get_intentos_jugador($postData, int $idCreador)
    {
        if (isset($postData['encryptedData'])) {
            $decryptedData = decryptData($postData['encryptedData']);
            $data = json_decode($decryptedData, true);

            return $this->get_intentos_jugadorBD($data['gamecode'], $idCreador, $data['id_jugador']);
        } else {
            return [
                'success' => false,
                'message' => 'Datos no recibidos',
                'attempts' => [],
                'details' => []
            ];
        }
    }

    public function get_clasificacion_general_report($gameCode, int $idJugador) {
        if (isset($gameCode)) {
            
            return $this->get_classification_general_report_data($gameCode, $idJugador);
        } else {
            return [
                'success' => false,
                'message' => 'Datos no recibidos',
                'data' => null
            ];
        }
    }

    public function get_intentos_detalles_jugador($postData, int $idCreador)
    {
        if (isset($postData['encryptedData'])) {
            $decryptedData = decryptData($postData['encryptedData']);
            $data = json_decode($decryptedData, true);

            return $this->get_intentos_detalles_jugadorBD($data['gamecode'], $idCreador, $data['id_jugador']);
        } else {
            return [
                'status' => false,
                'message' => 'Datos no recibidos',
                'attempts' => [],
                'details' => []
            ];
        }
    }

    public function get_detalles_intento($postData, int $idCreador)
    {
        if (isset($postData['encryptedData'])) {
            $decryptedData = decryptData($postData['encryptedData']);
            $data = json_decode($decryptedData, true);

            return $this->get_detalles_intentoBD($data['gamecode'], $idCreador, $data['id_jugador'], $data['id_intento']);
        } else {
            return [
                'success' => false,
                'message' => 'Datos no recibidos',
                'attemptDetails' => [],
                'headerDetails' => []
            ];
        }
    }

    public function get_analiticas_generales_partida_construccion($postData, int $idJugador)
    {
        if (isset($postData['encryptedData'])) {
            $decryptedData = decryptData($postData['encryptedData']);
            $data = json_decode($decryptedData, true);

            return $this->get_analiticas_generales_partida_construccionBD($data['gamecode'], $idJugador);
        } else {
            return [
                'success' => false,
                'message' => 'Datos no recibidos',
                'analytics' => []
            ];
        }
    }

    public function get_construccion_general_report($gameCode, int $idJugador) {
        if (isset($gameCode)) {
            
            return $this->get_construccion_general_report_data($gameCode, $idJugador);
        } else {
            return [
                'success' => false,
                'message' => 'Datos no recibidos',
                'data' => null
            ];
        }
    }

    public function get_analiticas_jugadores_partida_construccion($postData, int $idJugador)
    {
        if (isset($postData['encryptedData'])) {
            $decryptedData = decryptData($postData['encryptedData']);
            $data = json_decode($decryptedData, true);

            return $this->get_analiticas_jugadores_partida_construccionBD($data['gamecode'], $idJugador);
        } else {
            return [
                'success' => false,
                'message' => 'Datos no recibidos',
                'analytics' => []
            ];
        }
    }

    public function get_intentos_jugador_construction($postData, int $idCreador)
    {
        if (isset($postData['encryptedData'])) {
            $decryptedData = decryptData($postData['encryptedData']);
            $data = json_decode($decryptedData, true);

            return $this->get_intentos_jugador_constructionBD($data['gamecode'], $idCreador, $data['id_jugador']);
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

            return $this->get_detalles_intento_constructionBD($data['gamecode'], $idCreador, $data['id_jugador'], $data['id_intento']);
        } else {
            return [
                'success' => false,
                'message' => 'Datos no recibidos',
                'attemptDetails' => [],
                'headerDetails' => []
            ];
        }
    }

    public function get_full_construction_report($postData, int $idCreador)
    {
        if (isset($postData['encryptedData'])) {
            $decryptedData = decryptData($postData['encryptedData']);
            $data = json_decode($decryptedData, true);

            return $this->get_full_construction_reportBD(
                $data['gamecode'],
                $idCreador,
                $data['id_jugador']
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
