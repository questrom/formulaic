<?php

require('vendor/autoload.php');
require('parts.php');


$page = parse_xml('config/test.xml');

echo '<!DOCTYPE html>' . $page->get(new HTMLGeneratorUnparented())->getText();