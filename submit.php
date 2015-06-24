<?php

require('vendor/autoload.php');
require('parts.php');

$page = Parser::parse_jade('forms/test.jade');


$data = $page->validate(new OkJust(
	[
		'post' => $_POST,
		'files' => $_FILES
	]
));


$data
	->bind_err(function($val) {
		echo json_encode([
			'success' => false,
			'errors' =>  $val
		]);	
		return new Err($val);
	})
	->bind(function($val) use ($page) {

		ob_start();
			$val = $page->outputs->run($val);
			var_dump($val);
		$out = ob_get_clean();

		echo json_encode([
			'success' => true,
			'debugOutput' => $page->debug ? $out : ''
		]);
	});

