<?php

require('vendor/autoload.php');
require('parts.php');

$result = parse_yaml('forms/test.yml');
$page = new Page($result);

$data = $page->validate($_POST);

if($data instanceof Err) {
	echo json_encode([
		'success' => false,
		'v' =>  $data->get()
	]);
} else {

	ob_start();
	$data = $data->get();
	foreach($result['outputs'] as $output) {
		$output->run($data);
	}
	$out = ob_get_clean();

	if(!isset($result['debug']) || $result['debug'] === false) {
		$out = '';
	}

	echo json_encode([
		'success' => true,
		'data' => $out
	]);
}

