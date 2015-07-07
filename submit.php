<?php

require('include/all.php');

$page = Parser::parse_jade('forms/test.jade');

$config = getConfig();

$page
	->getMerger(Result::ok(new ClientData($_POST, $_FILES)))
	->ifError(function($val) {
		return Result::error(json_encode([
			'success' => false,
			'errors' =>  $val
		]));
	})
	->innerBind(function($val) use ($page, $config) {

		ob_start();
			$val = $page->outputs->run($val, $page);
			var_dump($val);
		$out = ob_get_clean();

		return Result::ok(json_encode([
			'success' => true,
			'debugOutput' => $config['debug'] ? $out : ''
		]));
	})
	->ifError(function($val) {
		return Result::ok($val);
	})
	->innerBind(function($output) {
		echo $output;
	});
