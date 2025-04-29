<?php
// Services/Autoload.php
/*
spl_autoload_register(function($class) {
    // Verificar si la clase está en el namespace Services
    if (strpos($class, 'Services\\') === 0) {
        // Calcular la ruta relativa desde la raíz del proyecto
        $basePath = __DIR__ . '/../';
        $path = $basePath . str_replace('\\', '/', $class) . '.php';
        
        if (file_exists($path)) {
            require_once $path;
            return true;
        }
    }
    return false;
});
*/