<?php

require('include/all.php');

$page = Parser::parse_jade($_GET['form']);

$view = new DetailsView();

$view->setPage($page);

$view->query($_GET);

echo '<!DOCTYPE html>' . $view->makeDetailsView()->render()->generateString();