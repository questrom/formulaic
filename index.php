<?php

require('include/all.php');

$formlist = new FormList(Parser::getFormInfo());

echo '<!DOCTYPE html>' . $formlist->makeFormList()->render()->generateString();