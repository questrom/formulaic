<?php

require 'include/all.php';

use Tracy\Debugger;

Debugger::dump(123);

$formlist = new FormList(Parser::getFormInfo());

echo '<!DOCTYPE html>' . $formlist->makeFormList()->render()->generateString();