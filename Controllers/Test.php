<?php
require_once("Infraestructure/TestInfraestructure.php");

require_once 'Services/AI/AiServiceFactory.php';

require_once('Entity/GameConfigEntity.php');

class Test extends Controllers
{
	public function __construct()
	{
		parent::__construct();
	}

	public function test()
	{
		$data['page_id'] = 2;
		$data['page_tag'] = "Test - Tienda Virtual";
		$data['page_title'] = "Test - Tienda Virtual";
		$data['page_name'] = "Test";
		$data['page_functions_js'] = "test/functions_test.js";
		$data['page_libraries_css'] =  array(
			'plugins/datatables/dataTables.dataTables.min.css'
		);
		$data['page_css'] =  array(
			'test/test.css'
		);
		$this->views->getView($this, "test", $data);
	}

	public function test2()
	{
		$data['page_id'] = 2;
		$data['page_tag'] = "Test - Tienda Virtual";
		$data['page_title'] = "Test - Tienda Virtual";
		$data['page_name'] = "Test";
		$data['page_functions_js'] = array();
		$data['page_css'] =  array(
			'report/variables.css',
			'report/report.css',
			'report/progress.css',
			'report/table.css'
		);
		$this->views->getView($this, "test2", $data);
	}

	public function testservice()
	{

		$gameConfig = new GameConfigEntity('es', 'Genera requerimientos basados en el desarrollo de un sistema ERP para una universidad.', 3);
		$response = GptService::generateRequirements($gameConfig);
		$this->views->getView($this, "test2", $data);
	}

	public function testservice2()
	{

		$gameConfig = new \Entity\GameConfigEntity('es', 'Genera requerimientos basados en el desarrollo de un sistema ERP para una universidad.', 3);

		$serviceName = 'OpenAI';
		$serviceName = 'Gemini';

		$aiService = $serviceName
			? \Services\AI\AiServiceFactory::create($serviceName)
			: \Services\AI\AiServiceFactory::createDefault();

		// Verificar si el servicio está disponible
		if (!$aiService->isAvailable()) {
			throw new \Exception("El servicio de IA {$aiService->getName()} no está disponible. Por favor, verifica la configuración.");
		}

		// Generar los requisitos
		$requirements = $aiService->generateRequirements($gameConfig);

		// Devolver respuesta exitosa
		$response = [
			'success' => true,
			'message' => "Requisitos generados exitosamente usando {$aiService->getName()}",
			'requirements' => $requirements,
			'service' => $aiService->getName()
		];
		//$this->views->getView($this, "test2", $data);
		dep($response); 
	}
}
