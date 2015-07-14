<?php


require 'include/all.php';

use Gregwar\Cache\Cache;

$csrf = new \Riimu\Kit\CSRF\CSRFHandler();
$token = $csrf->getToken();

$config = Config::get();

$cache = $config['cache-forms'] ? new Cache() : new FakeCache();

$cache->setPrefixSize(0);
$html = $cache->getOrCreate('jade-' . sha1_file(Parser::getForm($_GET['form'])) . '-' . sha1_file('config/config.toml'), [], function() {
	$page = Parser::parseJade($_GET['form']);
	return '<!DOCTYPE html>' . $page->makeFormPart()->render()->generateString();
});

// Do the replacement here so that it won't be cached...
$html = str_replace('__{{CSRF__TOKEN}}__', htmlspecialchars($token), $html);

echo fixAssets($html);