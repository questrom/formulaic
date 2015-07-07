<?php

require('include/all.php');

use Gregwar\Cache\Cache;

$csrf = new \Riimu\Kit\CSRF\CSRFHandler();
$token = $csrf->getToken();

$config = Config::get();

$cache = $config['cache-forms'] ? new Cache() : new FakeCache();

$cache->setPrefixSize(0);
$html = $cache->getOrCreate('jade-' . sha1_file('forms/test.jade'), [], function() {
	$page = Parser::parse_jade('forms/test.jade');
	return '<!DOCTYPE html>' . generateString($page->get(new HTMLParentlessContext()));
});

// Do the replacement here so that it won't be cached...
$html = str_replace('__{{CSRF__TOKEN}}__', htmlspecialchars($token), $html);

echo $html;