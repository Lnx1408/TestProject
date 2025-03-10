<?php

class DashboardViewFactory
{
    private static function getCommonConfig()
    {
        return [
            'page_tag' => "Home - " . name_project(),
            'page_title' => name_project(),
            'page_name' => "HOME",
            'page_libraries_css' => [],
            'page_css' => [
                'game/game-focal.css'
            ]
        ];
    }

    private static function getTeacherConfig()
    {
        return [
            'view_template' => 'welcome_teacher',
            'page_functions_js' => [],
            'page_libraries_css' => array_merge(
                self::getCommonConfig()['page_libraries_css'],
                [
                    //'plugins/datatables/dataTables.dataTables.min.css',
                    //'plugins/datatables/responsive.dataTables.css'
                ]
            )
        ];
    }

    private static function getStudentConfig()
    {
        return [
            'view_template' => 'welcome_student',
            'page_functions_js' => [],
            'page_libraries_css' => self::getCommonConfig()['page_libraries_css']
        ];
    }

    public static function createView($userType)
    {
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
