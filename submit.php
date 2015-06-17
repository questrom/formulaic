<?php

// TODO validate here also

// var_dump($_POST);


require('vendor/autoload.php');
require('parts.php');

$result = parse_yaml('forms/test.yml');
$page = new Page($result);


var_dump($page->validate($_POST));
// echo json_encode(['v' => $page->validate($_POST) ]);