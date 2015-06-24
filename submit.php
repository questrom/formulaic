<?php

require('vendor/autoload.php');
require('parts.php');

$result = parse_xml('forms/test.xml');
$page = new Page($result);

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
			'v' =>  $val
		]);	
		return new Err($val);
	})
	->bind(function($val) use ($result) {
		ob_start();
		
		foreach($result['outputs'] as $output) {
			$val = $output->run($val);
		}
		var_dump($val);

		$out = ob_get_clean();

		if(!isset($result['debug']) || $result['debug'] === false) {
			$out = '';
		}

		echo json_encode([
			'success' => true,
			'data' => $out
		]);
	});

