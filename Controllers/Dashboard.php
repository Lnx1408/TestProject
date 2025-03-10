<?php
require_once("Libraries/Factories/DashboardViewFactory.php");
class Dashboard extends AuthController
{
    public function __construct()
    {
        // Especificar roles permitidos para este controlador
        parent::__construct([
            SessionManager::ROLE_ADMIN,
            SessionManager::ROLE_STUDENT,
            SessionManager::ROLE_TEACHER
        ]);
    }

    public function dashboard_old()
    {
        $data['page_id'] = 2;
        $data['page_tag'] = "Dashboard - " . name_project();
        $data['page_title'] = "Dashboard - " . name_project();
        $data['page_name'] = "dashboard";
        $data['page_functions_js'] = "functions_dashboard.js";

        // Obtener datos del usuario incluyendo el nombre del rol
        $data['user'] = $this->getUserData();
        $data['roleName'] = $this->sessionManager->getRoleName();
        $this->addNavInfo($data);

        $this->views->getView($this, "dashboard", $data);
    }

    public function dashboard()
    {
        try {
            $data['user'] = $this->getUserData();
            $type_user = $this->getUserData('type');
            $viewConfig = DashboardViewFactory::createView($type_user);
            $this->addNavInfo($viewConfig);
            $this->views->getView($this, $viewConfig['view_template'], $viewConfig);
        } catch (Exception $e) {
            error_log("Error en Levels/levels: " . $e->getMessage());
            //header('Location: ' . base_url() . '/dashboard');
            die();
        }
    }
}
