<?php

require 'include/all.php';

$page = Parser::parseJade($_GET['form']);
$view = new DetailsView();
$view->setPage($page);
echo '<!DOCTYPE html>' . $view->makeView($view->query($_GET))->render()->generateString();