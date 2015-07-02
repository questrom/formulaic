<?php

require('parts.php');

$cache_enabled = false;

// $time = microtime(true);

$hash = sha1_file('forms/test.jade');
$cache = './cache/' . $hash;

if($cache_enabled && file_exists($cache)) {
	$html = file_get_contents($cache);
} else {
	$page = Parser::parse_jade('forms/test.jade');
	$html = '<!DOCTYPE html>' . $page->get(new HTMLParentlessContext());
	file_put_contents($cache, $html);
}

// $time = microtime(true) - $time;

echo $html;