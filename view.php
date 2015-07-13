<?php

require 'include/all.php';

$page = Parser::parseJade($_GET['form']);
$view = $page->getView($_GET['view']);
echo fixAssets($view->makeView($view->query($_GET))->render()->generateString());