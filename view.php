<?php

require('vendor/autoload.php');
require('parts.php');

$page = Parser::parse_jade('forms/test.jade');

$view = $page->views[0];

$view->query();

echo '<!DOCTYPE html>' . $view->get(new HTMLParentlessContext());