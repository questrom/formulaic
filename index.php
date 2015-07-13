<?php

require 'include/all.php';

$formlist = new FormList(Parser::getFormInfo());
echo '<!DOCTYPE html>' . fixAssets($formlist->makeFormList()->render()->generateString());