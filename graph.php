<?php

require('include/all.php');

$page = Parser::parse_jade('forms/test.jade');

$view = $page->views->getByName('graph');

$view->setPage($page);
$view->query($_GET);

echo '<!DOCTYPE html>' . generateString($view->get(new HTMLParentlessContext()));