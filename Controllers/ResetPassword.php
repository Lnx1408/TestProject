<?php

class ResetPassword extends Controllers
{
	public function __construct()
	{
		parent::__construct();
	}

	public function resetpassword()
	{
		$data['page_tag'] = "Reset - " . name_project();
		$data['page_title'] = name_project();
		$data['page_name'] = "Reset";
		$data['page_css'] =  array(
			'main.css',
			'login.css',
			'style.css'
		);
		$data['page_libraries_css'] =  array(
			'plugins/sweetalert2.min.css'
		);
		$data['page_functions_js'] = array(
			'CryptoModule.js',
			'plugins/sweetalert2.all.min.js',
			'login/functions_reset.js'
		);
		$this->views->getView($this, "reset_password", $data);
	}
}
