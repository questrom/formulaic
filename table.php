<?php

require('include/all.php');

$page = Parser::parse_jade('forms/test.jade');

$view = $page->getView('table');
$view->query($_GET);

echo '<!DOCTYPE html>' . generateString($view->makeTableViewPart());