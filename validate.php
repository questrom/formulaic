<?php

require('vendor/autoload.php');
require('parts.php');

$result = yaml_parse_file('forms/test.yml', 0, $ndocs, array(
	'!checkbox' => function($v) { return new Checkbox($v); },
	'!textbox' => function($v) { return new Textbox($v); }
));

$page = new Page($result);

echo json_encode(['v' => $page->validate($_POST) ]);