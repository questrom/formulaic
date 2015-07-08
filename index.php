<?php

session_start();
require('include/all.php');

use Gregwar\Cache\Cache;

$csrf = new \Riimu\Kit\CSRF\CSRFHandler();
$token = $csrf->getToken();

$config = Config::get();

$cache = $config['cache-forms'] ? new Cache() : new FakeCache();

// $time = microtime(true);

$cache->setPrefixSize(0);
$html = $cache->getOrCreate('jade-' . sha1_file(Parser::getForm($_GET['form'])), [], function() {
	$page = Parser::parse_jade(Parser::getForm($_GET['form']));
	return '<!DOCTYPE html>' . generateString($page->makeFormPart());
});

// echo microtime(true) - $time;

// Do the replacement here so that it won't be cached...
$html = str_replace('__{{CSRF__TOKEN}}__', htmlspecialchars($token), $html);

echo $html;