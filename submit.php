<?php

require('vendor/autoload.php');
require('parts.php');

$page = Parser::parse_jade('forms/test.jade');


$data = $page
	->getMerger(new OkJust(new ClientData($_POST, $_FILES)))
	->bind_err(function($val) {
		return new Err(json_encode([
			'success' => false,
			'errors' =>  $val
		]));
	})
	->innerBind(function($val) use ($page) {

		ob_start();
			$val = $page->outputs->run($val);
			var_dump($val);
		$out = ob_get_clean();

		return new OkJust(json_encode([
			'success' => true,
			'debugOutput' => $page->debug ? $out : ''
		]));
	})
	->bind_err(function($val) {
		return new OkJust($val);
	})
	->innerBind(function($output) {
		echo $output;
	});
