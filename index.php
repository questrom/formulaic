<?php

require('vendor/autoload.php');
require('parts.php');

$result = parse_jade('forms/test.jade');
$page = new Page($result);

echo '<!DOCTYPE html>' . $page->get(new HTMLGeneratorUnparented())->getText();