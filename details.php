<?php

require('include/all.php');

$page = Parser::parse_jade(Parser::getForm($_GET['form']));

$view = new DetailsView($page);

$view->query($_GET);

echo '<!DOCTYPE html>' . generateString($view->get(new HTMLParentlessContext()));