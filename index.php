<?php

require('include/all.php');

use Gregwar\Cache\Cache;

$config = getConfig();

if($config['cache-forms']) {
	$cache = new Cache();
	$cache->setPrefixSize(0);
	$html = $cache->getOrCreate(sha1_file('forms/test.jade'), [], function() {
		$page = Parser::parse_jade('forms/test.jade');
		return '<!DOCTYPE html>' . generateString($page->get(new HTMLParentlessContext()));
	});
} else {
	$page = Parser::parse_jade('forms/test.jade');
	$html = '<!DOCTYPE html>' . generateString($page->get(new HTMLParentlessContext()));
}

echo $html;