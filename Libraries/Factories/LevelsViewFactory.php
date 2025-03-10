<?php

class LevelsViewFactory {
    private static function getCommonConfig() {
        return [
            'page_tag' => "Levels - " . name_project(),
            'page_title' => name_project(),
            'page_name' => "GAME",
            'page_libraries_css' => [
                'plugins/sweetalert2.min.css'
            ],
            'page_css' => [
                'game/game-focal.css'
            ]
        ];
    }

    private static function getTeacherConfig() {
        return [
            'view_template' => 'levels_d',
            'page_functions_js' => [
                'jquery-3.7.1.min.js',
                'plugins/sweetalert2.all.min.js',
                'CryptoModule.js',
                'game/gameEntryManager.js'
            ],
            'page_libraries_css' => array_merge(
                self::getCommonConfig()['page_libraries_css'],
                [
                    'plugins/datatables/dataTables.dataTables.min.css',
                    'plugins/datatables/responsive.dataTables.css'
                ]
            ),
            'additional_data' => [
                'can_create_games' => true,
                'can_view_analytics' => true
            ]
        ];
    }

    private static function getStudentConfig() {
        return [
            'view_template' => 'levels_e',
            'page_functions_js' => [
                'jquery-3.7.1.min.js',
                'plugins/sweetalert2.all.min.js',
                'CryptoModule.js'
            ],
            'page_libraries_css' => self::getCommonConfig()['page_libraries_css'],
            'additional_data' => [
                'can_join_games' => true,
                'show_progress' => true
            ]
        ];
    }

    public static function createView($userType) {
        $commonConfig = self::getCommonConfig();
        try {
            switch ($userType) {
                case SessionManager::ROLE_TEACHER:
                    $specificConfig = self::getTeacherConfig();
                    break;

                case SessionManager::ROLE_STUDENT:
                    $specificConfig = self::getStudentConfig();
                    break;

                default:
                    throw new Exception("Tipo de usuario no válido: {$userType}");
            }

            return array_merge_recursive($commonConfig, $specificConfig);

        } catch (Exception $e) {
            error_log("Error en LevelsViewFactory: " . $e->getMessage());
            throw $e;
        }
    }
}