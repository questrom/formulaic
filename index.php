<?php

require('vendor/autoload.php');
require('parts.php');

$page = Parser::parse_jade('forms/test.jade');
echo '<!DOCTYPE html>' . $page->get(new HTMLParentlessContext());