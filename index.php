<?php

require 'include/all.php';
use Gregwar\Cache\Cache;

# Klein is used as the router.
$klein = new \Klein\Klein();

# Display simple error messages.
# Based on code from the Klein documentation.
$klein->onHttpError(function ($code, $router) {
	$res = $router->response();
	$message = h()
		->h1->style('text-align:center;font-size:72px;')
			->c($res->status()->getCode())
		->end
		->h2->style('text-align:center')
			->c($res->status()->getMessage())
		->end;
	$router->response()->body(
		'<!DOCTYPE html>' . $message->generateString()
	);
});

# The main list of forms
$klein->respond('GET', '/', function () {
	$formlist = new FormList(Parser::getFormInfo());
	$ret = '<!DOCTYPE html>' . fixAssets($formlist->makeFormList()->render()->generateString());
	return $ret;
});

# A view
$klein->respond('GET', '/view', function () {
	$page = Parser::parseJade($_GET['form']);
	$view = $page->getView($_GET['view']);
	return fixAssets($view->makeView($view->query($_GET))->render()->generateString());
});

# A form itself
$klein->respond('GET', '/forms/[:formID]', function($request) {

	# Create a XSRF token
	$csrf = new \Riimu\Kit\CSRF\CSRFHandler();
	$token = $csrf->getToken();

	$config = Config::get();

	# This code caches the HTML associated with a form if "cache-forms" is enabled
	$cache = $config['cache-forms'] ? new Cache() : new FakeCache();
	$cache->setPrefixSize(0);
	$html = $cache->getOrCreate('jade-' . sha1_file(Parser::getForm($request->formID)) . '-' . sha1_file('config/config.toml'), [], function () use($request) {
		$page = Parser::parseJade($request->formID);
		return '<!DOCTYPE html>' . $page->makeFormPart()->render()->generateString();
	});

	# Add a CSRF token. We do this outside of the getOrCreate function
	# so that it won't be cached.
	$html = str_replace('__{{CSRF__TOKEN}}__', htmlspecialchars($token), $html);

	return fixAssets($html);
});

$klein->respond('POST', '/submit', function () {

	# Check for XSRF
	$csrf = new \Riimu\Kit\CSRF\CSRFHandler();
	$csrf->validateRequest(true);



	# The name of the form is provided in the $_POST data,
	# not the URL!
	$page = Parser::parseJade($_POST['__form_name']);
	$config = Config::get();

	# Do the form submission and create data that is
	# compatible with the frontend.
	return $page
		->form
		->getSubmissionPart(Result::ok(new ClientData($_POST, $_FILES)))
		->ifError(function ($val) {
			return Result::error([
				'success' => false,
				'errors' => $val
			]);
		})
		->ifOk(function ($val) use ($page, $config) {

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
		->ifOk(function ($output) use ($csrf) {
			return json_encode([
				'data' => $output
			]);
		});

});

# Get the details of a particular table entry.
$klein->respond('GET', '/details', function () {
	$page = Parser::parseJade($_GET['form']);
	$view = new DetailsView();
	$view->setPage($page);
	return '<!DOCTYPE html>' . fixAssets($view->makeView($view->query($_GET))->render()->generateString());
});

# See https://github.com/chriso/klein.php/wiki/Sub-Directory-Installation
$config = Config::get();
$request = \Klein\Request::createFromGlobals();
$uri = $request->server()->get('REQUEST_URI');
$request->server()->set('REQUEST_URI', substr($uri, strlen($config['app-path'])));

# Route!
$klein->dispatch($request);