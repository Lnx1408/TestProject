<?php
/*
	spl_autoload_register(function($class){
		if(file_exists("Libraries/".'Core/'.$class.".php")){
			require_once("Libraries/".'Core/'.$class.".php");
		}
	});
	*/

spl_autoload_register(function ($class) {
	// Convertir los separadores de namespace a separadores de directorio
	$file = str_replace('\\', DIRECTORY_SEPARATOR, $class) . '.php';

	// Lista de directorios base para buscar
	$directories = [
		'',                     // Raíz del proyecto
		'Libraries' . DIRECTORY_SEPARATOR,
		'Services' . DIRECTORY_SEPARATOR,
		'Models' . DIRECTORY_SEPARATOR,
		'Entity' . DIRECTORY_SEPARATOR
	];

	// Buscar el archivo en cada directorio
	foreach ($directories as $directory) {
		$fullPath = $directory . $file;
		if (file_exists($fullPath)) {
			require_once $fullPath;
			return true;
		}
	}

	// Mantén la compatibilidad con tu sistema actual
	if (file_exists("Libraries/Core/" . $class . ".php")) {
		require_once("Libraries/Core/" . $class . ".php");
		return true;
	}

	return false;
});
