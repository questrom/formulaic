<?php

require('vendor/autoload.php');
require('parts.php');

$result = parse_yaml('forms/test.yml');
$page = new Page($result);

$validation = $page->validate($_POST);
if($validation instanceof Err) {
	echo json_encode(['v' =>  $validation->get() ]);
} else {
	echo json_encode(['v' => [] ]);
}

