<?php 

	class Errors extends Controllers{
		public function __construct()
		{
			parent::__construct();
		}

		public function notFound()
		{
			$this->views->getView($this,"error");
		}

		public function unauthorized()
		{
			header('Location: ' . base_url() . '/dashboard');
            exit();
		}
	}

	$notFound = new Errors();
	$notFound->notFound();
 ?>