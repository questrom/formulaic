
<?php

require('vendor/autoload.php');
require('parts.php');

$result = parse_yaml('forms/test.yml');
$page = new Page($result);

echo '<!DOCTYPE html>' . $page->get(new HTMLGeneratorUnparented())->getText();