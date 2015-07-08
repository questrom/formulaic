<?php

require('include/all.php');

$page = Parser::parse_jade('forms/test.jade');

$view = $page->getView('table');

echo '<!DOCTYPE html>' . generateString($view->makeTableViewPart($view->query($_GET)));