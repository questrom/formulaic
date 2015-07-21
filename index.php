<?php

require 'include/all.php';
use Gregwar\Cache\Cache;

$klein = new \Klein\Klein();

$klein->onHttpError(function ($code, $router) {
	// based on klein docs
	$res = $router->response();
	$message = h()
		->h1->style('text-align:center;font-size:72px;')
		->t($res->status()->getCode())
		->end
		->h2->style('text-align:center')
		->t($res->status()->getMessage())
		->end;
	$router->response()->body(
		'<!DOCTYPE html>' . $message->generateString()
	);

});

$klein->respond('GET', '/', function () {
	$formlist = new FormList(Parser::getFormInfo());
	return '<!DOCTYPE html>' . fixAssets($formlist->makeFormList()->render()->generateString());
});

$klein->respond('GET', '/view.php', function () {
	$page = Parser::parseJade($_GET['form']);
	$view = $page->getView($_GET['view']);
	return fixAssets($view->makeView($view->query($_GET))->render()->generateString());
});

$klein->respond('GET', '/form.php', function () {


	$csrf = new \Riimu\Kit\CSRF\CSRFHandler();
	$token = $csrf->getToken();

	$config = Config::get();

	$cache = $config['cache-forms'] ? new Cache() : new FakeCache();

	$cache->setPrefixSize(0);
	$html = $cache->getOrCreate('jade-' . sha1_file(Parser::getForm($_GET['form'])) . '-' . sha1_file('config/config.toml'), [], function () {
		$page = Parser::parseJade($_GET['form']);
		return '<!DOCTYPE html>' . $page->makeFormPart()->render()->generateString();
	});

	// Do the replacement here so that it won't be cached...
	$html = str_replace('__{{CSRF__TOKEN}}__', htmlspecialchars($token), $html);

	return fixAssets($html);
});

$klein->respond('POST', '/submit.php', function () {
	$csrf = new \Riimu\Kit\CSRF\CSRFHandler();
	$csrf->validateRequest(true);

	$page = Parser::parseJade($_POST['__form_name']);

	$config = Config::get();

	return $page
		->form
		->getSubmissionPart(Result::ok(new ClientData($_POST, $_FILES)))
		->ifError(function ($val) {
			return Result::error([
				'success' => false,
				'errors' => $val
			]);
		})
		->innerBind(function ($val) use ($page, $config) {

			ob_start();
			$val = $page->outputs->run($val, $page);
			var_dump($val);
			$out = ob_get_clean();

			return Result::ok([
				'success' => true,
				'debugOutput' => $config['debug'] ? $out : ''
			]);
		})
		->ifError(function ($val) {
			return Result::ok($val);
		})
		->innerBind(function ($output) use ($csrf) {
			return json_encode([
				'data' => $output
			]);
		});

});

$klein->respond('GET', '/details.php', function () {
	$page = Parser::parseJade($_GET['form']);
	$view = new DetailsView();
	$view->setPage($page);
	return '<!DOCTYPE html>' . fixAssets($view->makeView($view->query($_GET))->render()->generateString());
});

$klein->dispatch();