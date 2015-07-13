<?php

require 'include/all.php';

$csrf = new \Riimu\Kit\CSRF\CSRFHandler();
$csrf->validateRequest(true);

$page = Parser::parseJade($_POST['__form_name']);

$config = Config::get();

$page
	->form
	->getMerger(Result::ok(new ClientData($_POST, $_FILES)))
	->ifError(function($val) {
		return Result::error([
			'success' => false,
			'errors' =>  $val
		]);
	})
	->innerBind(function($val) use ($page, $config) {

		ob_start();
			$val = $page->outputs->run($val, $page);
			var_dump($val);
		$out = ob_get_clean();

		return Result::ok([
			'success' => true,
			'debugOutput' => $config['debug'] ? $out : ''
		]);
	})
	->ifError(function($val) {
		return Result::ok($val);
	})
	->innerBind(function($output) use ($csrf) {
		echo json_encode([
		 'data' => $output
		]);
	});
