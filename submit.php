<?php

require('vendor/autoload.php');
require('parts.php');

$result = parse_yaml('forms/test.yml');
$page = new Page($result);

$data = $page->validate($_POST);

if($data instanceof Err) {
	throw new Exception();
}

$data = $data->get();

foreach($result['outputs'] as $output) {
	$output->run($data);
}

echo json_encode([
	'data' => $data
]);