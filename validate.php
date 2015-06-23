<?php

require('vendor/autoload.php');
require('parts.php');

$result = parse_yaml('forms/test.yml');
$page = new Page($result);

$data = $page->validate(new OkJust($_POST));


$data
	->bind_err(function($val) {
		echo json_encode([
			'success' => false,
			'v' =>  $val
		]);	
		return new Err($val);
	})
	->bind(function($val) use ($result) {
		ob_start();
		foreach($result['outputs'] as $output) {
			$output->run($val);
		}
		$out = ob_get_clean();

		if(!isset($result['debug']) || $result['debug'] === false) {
			$out = '';
		}

		echo json_encode([
			'success' => true,
			'data' => $out
		]);
	});

