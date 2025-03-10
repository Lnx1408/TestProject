<?php
require_once("Infraestructure/GameClasificationInfraestructure.php");

class GameModel extends GameClasificationInfraestructure
{

    public function __construct()
    {
        parent::__construct();
    }

    public function validate_game_code($postData, int $idJugador)
    {
        if (isset($postData['encryptedData'])) {
            $decryptedData = decryptData($postData['encryptedData']);
            $data = json_decode($decryptedData, true);

            return $this->validate_game_codeBD($data['gamecode'], $idJugador);
        } else {
            return [
                'success' => false,
                'message' => 'Datos no recibidos'
            ];
        }
    }


    public function get_requirements_game(string $strGameCode, int $idJugador)
    {
        if (empty($strGameCode)) {
            echo json_encode([
                'success' => false,
                'message' => 'Código de partida no proporcionado',
                'requirements' => []
            ]);
            exit;
        }
        return $this->get_requirements($strGameCode, $idJugador);
    }

    public function validate_moves_game($data, int $idJugador)
    {
        return $this->validate_moves($data, $idJugador);
    }

    public function get_requirements_construcion_game(string $strGameCode, int $idJugador)
    {
        if (empty($strGameCode)) {
            return ([
                'success' => false,
                'message' => 'Código de partida no proporcionado',
                'data' => null
            ]);
            exit;
        }
        return $this->get_requirements_construcion($strGameCode, $idJugador);
    }

    public function validate_construction_game($data, int $idJugador)
    {
        return $this->validate_construction($data, $idJugador);
    }
}
