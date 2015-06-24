
<?php

require('vendor/autoload.php');
require('parts.php');

$page = parse_jade('forms/test.jade');

echo '<!DOCTYPE html>' . $page->get(new HTMLGeneratorUnparented())->getText();