<?php

require('include/all.php');

$submitCounts = json_decode(file_get_contents('data/submit-counts.json'));

$files = scandir('forms');

$files = array_values(array_filter($files, function($item) {
	return preg_match('/^[A-za-z0-9_]+\.jade$/', $item);
}));

$files = array_map(function($item) {
	return preg_replace('/\.jade$/', '', $item);
}, $files);


$files = array_map(function($item) use($submitCounts) {
	$page = Parser::parse_jade($item);
	$views = array_map(function($view) {
		return [
			'id' => $view->name,
			'title' => $view->title,
			'type' => $view->type
		];
	}, $page->views->getAllViews());
	return [
		'id' => $item,
		'name' => $page->title,
		'views' => $views,
		'count' => isget($submitCounts->$item, 0)
	];
}, $files);

// var_dump($files);

$formlist = new FormList($files);

echo '<!DOCTYPE html>' . generateString($formlist->makeFormList()->render());