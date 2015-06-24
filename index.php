
<?php

require('vendor/autoload.php');
require('parts.php');

$result = parse_xml('forms/test.xml');
$page = new Page($result);

echo '<!DOCTYPE html>' . $page->get(new HTMLGeneratorUnparented())->getText();