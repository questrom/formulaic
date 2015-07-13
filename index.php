<?php

require 'include/all.php';

use Tracy\Debugger;

$formlist = new FormList(Parser::getFormInfo());

echo '<!DOCTYPE html>' . $formlist->makeFormList()->render()->generateString();