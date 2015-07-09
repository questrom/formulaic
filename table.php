<?php

require('include/all.php');


if(!isset($_GET['view'])) {
	echo 'Please specify a view.';
	die();
}


$page = Parser::parse_jade($_GET['form']);

$view = $page->getView($_GET['view']);

echo '<!DOCTYPE html>' . generateString(
	$view->makeTableViewPart($view->query($_GET))
);