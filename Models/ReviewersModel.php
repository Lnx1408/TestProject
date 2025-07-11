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

    public function get_requisitos_review($postData, int $idJugador)
    {
        if (isset($postData['encryptedData'])) {
            $decryptedData = decryptData($postData['encryptedData']);
            $data = json_decode($decryptedData, true);

            return $this->get_requisitos_reviewDB($data['gamecode'], $idJugador);
        } else {
            return [
                'success' => false,
                'message' => 'Datos no recibidos',
                'data' => []
            ];
        }
    }

    public function get_requirements_suggestions($postData, int $idJugador)
    {
        if (isset($postData['encryptedData'])) {
            $decryptedData = decryptData($postData['encryptedData']);
            $data = json_decode($decryptedData, true);

            return $this->get_requirements_suggestionsDB($data['requisito'], $idJugador);
        } else {
            return [
                'success' => false,
                'message' => 'Datos no recibidos',
                'data' => []
            ];
        }
    }

    public function get_original_requirement($postData, int $idJugador)
    {
        if (isset($postData['encryptedData'])) {
            $decryptedData = decryptData($postData['encryptedData']);
            $data = json_decode($decryptedData, true);

            $requisito = $data['requisito'];
            return $this->get_original_requirementDB($requisito, $idJugador
            );
        } else {
            return [
                'success' => false,
                'message' => 'Datos no recibidos',
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


    public function update_original_requirement($postData, int $idJugador)
    {
        if (isset($postData['encryptedData'])) {
            $decryptedData = decryptData($postData['encryptedData']);
            $data = json_decode($decryptedData, true);

            // Validar y procesar los datos
            
            $id_requisito = $data['id_requisito'];
            $requisito = $data['requisito'];
            $es_funcional = $data['es_funcional'];
            $es_ambiguo = $data['es_ambiguo'];
            // Llamar a la función de base de datos con los datos procesados
            return $this->update_original_requirementBD(
                id_requisito: $id_requisito,
                requisito: $requisito,
                es_funcional: $es_funcional,
                es_ambiguo: $es_ambiguo,
                id_creador: $idJugador,
            );
        } else {
            return [
                'success' => false,
                'message' => 'Datos no recibidos',
            ];
        }
    }

    public function update_teacher_reviewer($postData)
    {
        if (isset($postData['encryptedData'])) {
            $decryptedData = decryptData($postData['encryptedData']);
            $data = json_decode($decryptedData, true);

            // Validar y procesar los datos
            $codigoPartida = $data['codigoPartida'];
            $idJugador = $data['id_jugador'];
            $rolDocente = $data['rol'];
            // Llamar a la función de base de datos con los datos procesados
            return $this->update_teacher_reviewerBD(
                codigoPartida: $codigoPartida,
                idJugador: $idJugador,
                rolDocente: $rolDocente,
            );
        } else {
            return [
                'success' => false,
                'message' => 'Datos no recibidos',
            ];
        }
    }

}
