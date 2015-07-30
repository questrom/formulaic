<?php

require 'include/all.php';
use Gregwar\Cache\Cache;
use voku\helper\UTF8;

# Klein is used as the router.
$klein = new \Klein\Klein();

# Create a config file parser
$parser = new Parser();

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
		'<!DOCTYPE html>' . stringize($message->generateString())
	);
});

# The main list of forms
$klein->respond('GET', '/', function () use($parser) {
	$formlist = new FormList($parser->getFormInfo());
	$ret = '<!DOCTYPE html>' . stringize($formlist->makeFormList()->render()->generateString());
	return $ret;
});

# A view
$klein->respond('GET', '/view', function ($req) use($parser) {

	$page = $parser->parseJade($_GET['form']);
	$view = $page->getView($_GET['view']);

	return '<!DOCTYPE html>' .
		stringize(
			$view
			->makeView(
				$view->query( $req->paramsGet()->get('page', 1) )
			)
			->render()
			->generateString()
		);
});

# A form itself
$klein->respond('GET', '/forms/[:formID]', function($request) use($parser) {

	# Create a XSRF token
	$csrf = new \Riimu\Kit\CSRF\CSRFHandler();
	$token = $csrf->getToken();

	$config = Config::get();

	# This code caches the HTML associated with a form if "cache-forms" is enabled
	$cache = $config['cache-forms'] ? new Cache() : new FakeCache();
	$cache->setPrefixSize(0);
	$html = $cache->getOrCreate(
		'jade-' . sha1_file($parser->getForm($request->formID)) . '-' . sha1_file('config/config.toml'),
		[],
		function () use($request, $parser) {
			$page = $parser->parseJade($request->formID);
			return json_encode($page->makeFormPart()->render()->generateString());
		}
	);

	# We add asset URLs and the CSRF token
	# outside of the getOrCreate function
	# so that these aren't getting cached.
	$html = stringize(json_decode($html, true), $token);

	return '<!DOCTYPE html>' . $html;
});

$klein->respond('POST', '/submit', function ($req, $res) use($parser) {

	# Check for XSRF
	$csrf = new \Riimu\Kit\CSRF\CSRFHandler();
	$csrf->validateRequest(true);



	# The name of the form is provided in the $_POST data,
	# not the URL!
	$page = $parser->parseJade($_POST['__form_name']);
	$config = Config::get();

	$res->header('Content-Type', 'application/json; charset=utf-8');

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

# Generate a CSV file for a TableView
# See http://stackoverflow.com/questions/217424/create-a-csv-file-for-a-user-in-php
$klein->respond('GET', '/csv', function($req) use($parser) {
	$page = $parser->parseJade($_GET['form']);
	$view = new CSVView($page->getView($_GET['view']));

	header("Content-type: text/csv; charset=utf-8");
	header("Content-Disposition: attachment; filename=" . $page->id . "-" . time() . ".csv");

	ob_start();
	$view
		->makeView($view->query(1))
		->render()
		->save('php://output');
	$result = ob_get_clean();

	return $result;
});

# Get the details of a particular table entry.
$klein->respond('GET', '/details', function () use($parser) {
	$page = $parser->parseJade($_GET['form']);
	$view = new DetailsView();
	$view->setPage($page);
	return '<!DOCTYPE html>' . stringize($view->makeView($view->query($_GET))->render()->generateString());
});

# See https://github.com/chriso/klein.php/wiki/Sub-Directory-Installation
$config = Config::get();
$request = \Klein\Request::createFromGlobals();
$uri = $request->server()->get('REQUEST_URI');
$request->server()->set('REQUEST_URI', UTF8::substr($uri, UTF8::strlen($config['app-path'])));

# Route!
$klein->dispatch($request);