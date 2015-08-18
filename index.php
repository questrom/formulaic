<?php

require 'include/all.php';
use Gregwar\Cache\Cache;
use voku\helper\UTF8;

# Klein is used as the router.
$klein = new \Klein\Klein();

# Create a config file parser
$parser = new Parser();

// header('X-Frame-Options: DENY');

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

	$res->body(
		'<!DOCTYPE html>' . Stringifier::stringify($message)
	);
});

# The main list of forms
$klein->respond('GET', '/', function ($req, $res) use($parser) {
	$formlist = new FormList($parser->getFormInfo());

	$out = Stringifier::makeArray($formlist->makeFormList());

	$res->append('<!DOCTYPE html>');
	$parts = Stringifier::generateString($out, null);
	foreach($parts as $x) {
		$res->append($x);
	}
});

# A view
$klein->respond('GET', '/view', function ($req, $res) use($parser) {



	$page = $parser->parseJade($_GET['form']);
	$view = $page->getView($_GET['view']);

	return '<!DOCTYPE html>' .
		Stringifier::stringify(
			$view
			->makeView(
				$view->query( $req->paramsGet()->get('page', 1) )
			)
		);
});

# A form itself
$klein->respond('GET', '/forms/[:formID]', function($req, $res) use($parser) {



	# Create a XSRF token
	$csrf = new \Riimu\Kit\CSRF\CSRFHandler();
	$token = $csrf->getToken();

	$config = Config::get();

	# This code caches the HTML associated with a form if "cache-forms" is enabled
	$cache = $config['cache-forms'] ? new Cache() : new FakeCache();
	$cache->setPrefixSize(0);
	$html = $cache->getOrCreate(
		'jade-' . sha1_file($parser->getForm($req->formID)) . '-' . sha1_file('config/config.toml'),
		[],
		function () use($req, $parser) {
			$page = $parser->parseJade($req->formID);
			$str = json_encode(Stringifier::makeArray($page->makeFormPart()->render()));
			return $str;
		}
	);

	# We add asset URLs and the CSRF token
	# outside of the getOrCreate function
	# so that these aren't getting cached.
	$html = Stringifier::makeString(json_decode($html, true), $token);

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
	header("Content-Disposition: attachment; filename=formulaic-" . time() . ".csv");

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
	return '<!DOCTYPE html>' . Stringifier::stringify($view->makeView($view->query($_GET)));
});

# See https://github.com/chriso/klein.php/wiki/Sub-Directory-Installation
$config = Config::get();
$request = \Klein\Request::createFromGlobals();
$uri = $request->server()->get('REQUEST_URI');
$request->server()->set('REQUEST_URI', UTF8::substr($uri, UTF8::strlen($config['app-path'])));

# Route!
$klein->dispatch($request);