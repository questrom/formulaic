<?php

require('parts.php');

$page = Parser::parse_jade('forms/test.jade');

$view = new DetailsView($page);

$view->setPage($page);
$view->query($_GET);

echo '<!DOCTYPE html>' . $view->get(new HTMLParentlessContext());