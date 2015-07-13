<?php

require 'include/all.php';

if(!isset($_GET['view'])) {
	echo 'Please specify a view.';
	die();
}

$page = Parser::parseJade($_GET['form']);

$view = $page->getView($_GET['view']);
if($view instanceof GraphView) {
	echo '<!DOCTYPE html>' .
		$view->makeGraphViewPart($view->query($_GET))->render()->generateString()
	;
} else if ($view instanceof TableView) {
	echo '<!DOCTYPE html>' .
		$view->makeTableViewPart($view->query($_GET))->render()->generateString()
	;
}