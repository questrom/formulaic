<?php

use Sabre\Xml\XmlDeserializable;
use Gregwar\Cache\Cache;

# This trait provides an implementation of the XmlDeserializable interface,
# which sabre/xml uses to construct PHP objects from XML elements.

# Basically, the XmlDeserializable interface includes a static function xmlDeserialize()
# which is called with the contents of the XML node. As implemented in Configurable,
# the xmlDeserialize() method converts these contents into array form, and then
# creates a new instance of the class using that array.

# Later in this file, a map of XML tag names -> classes implementing XmlDeserializable
# is given and used to initialize sabre/xml.

trait Configurable {
	abstract public function __construct($args);
	static function xmlDeserialize(Sabre\Xml\Reader $reader) {
		$attrs = $reader->parseAttributes();
		$tree = $reader->parseInnerTree();

		$attrs['children'] = [];
		$attrs['innerText'] = '';
		if (is_array($tree)) {

			$attrs['children'] = array_map(
				function ($x) { return $x['value']; },
				$tree
			);
		} else {
			if (is_string($tree)) {
				$attrs['innerText'] = $tree;
			}
		}

		return new static($attrs);
	}
}

# An implementation of XmlDeserializable that does things by-tag
trait ByTagConfigurable {
	static function xmlDeserialize(Sabre\Xml\Reader $reader) {
		$attrs = $reader->parseAttributes();
		$attrs['byTag'] = Sabre\Xml\Element\KeyValue::xmlDeserialize($reader);

		return new static($attrs);
	}
}

# A very simple XmlDeserializable implementation used
# for elements whose sole job is to contain text.
class TextElem implements XmlDeserializable {
	// use Configurable;
	static function xmlDeserialize(Sabre\Xml\Reader $reader) {
		$tree = $reader->parseInnerTree();
		return is_string($tree) ? $tree : '';
	}
}

# Used for the <allow ext="..." mime="..."> element within file upload inputs.
class AllowElem implements XmlDeserializable {
	use Configurable;
	function __construct($args) {
		$this->ext = $args['ext'];
		$this->mime = $args['mime'];
	}
}

# Doesn't really do anything at the moment
function caller($class) {
	return function(Sabre\Xml\Reader $reader) use($class) {

		return call_user_func([$class, 'xmlDeserialize'], ($reader));
	};
}

# This class manages and parses configuration files.
class Parser {

	# Get the configuration file name corresponding to a form ID.
	# For example, if the input is "test", this function returns
	# "forms/test.jade".
	static function getForm($name) {
		if (is_string($name) && !preg_match('/[^A-za-z0-9_-]/', $name) && strlen($name) > 0) {
			return 'forms/' . $name . '.jade';
		} else {
			throw new Exception('Invalid form name!');
		}
	}

	# This function gets some "global" information about each of the forms.
	# This information is displayed on the main page of the app, which
	# provides a lsit of forms and views.
	static function getFormInfo() {

		$files = scandir('forms');

		$files = array_values(array_filter($files, function ($item) {
			return preg_match('/^[A-za-z0-9_-]+\.jade$/', $item);
		}));

		$files = array_map(function ($item) {
			return preg_replace('/\.jade$/', '', $item);
		}, $files);


		$files = array_map(function ($item) {

			$page = Parser::parseJade($item);
			return [
				'id' => $item,
				'name' => $page->title,
				'views' => $page->views->getAllViews(),
				'count' => SubmitCounts::get($item)
			];
		}, $files);

		return $files;
	}

	static $reader;
	static $jade;

	# Get an XML reader
	static function getReader() {
		if(!isset(self::$reader)) {
			$reader = self::$reader = new Sabre\Xml\Reader();

			# The map of XML element names to PHP classes that implement Sabre\Xml\XmlDeserializable.
			# Usually they implement this interface by means of the Configurable trait given earlier
			# in this file.

			# Note that each element name must have '{}' prepended to it.
			$reader->elementMap = [
				'{}checkbox' => caller('Checkbox'),
				'{}textbox' => caller('Textbox'),
				'{}password' => caller('Password'),
				'{}dropdown' => caller('Dropdown'),
				'{}radios' => caller('Radios'),
				'{}checkboxes' => caller('Checkboxes'),
				'{}textarea' => caller('TextArea'),
				'{}range' => caller('Range'),
				'{}time' => caller('TimeInput'),
				'{}group' => caller('Group'),
				'{}date' => caller('DatePicker'),
				'{}phonenumber' => caller('PhoneNumber'),
				'{}email' => caller('EmailAddr'),
				'{}url' => caller('UrlInput'),
				'{}number' => caller('NumberInp'),

				'{}notice' => caller('Notice'),
				'{}header' => caller('Header'),
				'{}datetime' => caller('DateTimePicker'),
				'{}file' => caller('FileUpload'),
				'{}allow' => caller('AllowElem'),

				'{}mongo' => caller('MongoOutput'),
				'{}s3' => caller('S3Output'),

				'{}option' => caller('TextElem'),
				'{}fields' => caller('FieldList'),
				'{}li' => caller('TextElem'),
				'{}outputs' => caller('SuperOutput'),
				'{}form' => caller('Page'),
				'{}list' => caller('ListComponent'),
				'{}show-if' => caller('ShowIfComponent'),
				'{}table-view' => caller('TableView'),
				'{}col' => caller('Column'),
				'{}email-to' => caller('EmailOutput'),
				'{}graph-view' => caller('GraphView'),
				'{}bar' => caller('BarGraph'),
				'{}pie' => caller('PieChart'),
				'{}captcha' => caller('Captcha'),
				'{}is-checked' => caller('IsCheckedCondition'),
				'{}is-not-checked' => caller('IsNotCheckedCondition'),
				'{}is-radio-selected' => caller('IsRadioSelectedCondition'),
				'{}views' => caller('ViewList')
			];
		} else {
			$reader = self::$reader;
		}
		return $reader;
	}

	# Get a jade parser
	static function getJade() {
		if(!isset(self::$jade)) {
			self::$jade = new Everzet\Jade\Jade(
				new Everzet\Jade\Parser(new Everzet\Jade\Lexer\Lexer()),
				new Everzet\Jade\Dumper\PHPDumper()
			);
		}
		return self::$jade;
	}

	# Given a form ID, this gets the contents of the associated configuration file.
	static function parseJade($id) {

		$file = self::getForm($id);
		$config = Config::get();

		# If the "cache-xml" setting is enabled, we cache the compiled XML.
		$cache = $config['cache-xml'] ? new Cache() : new FakeCache();
		$cache->setPrefixSize(0);

		$xml = $cache->getOrCreate('xml-' . sha1_file($file), [], function ($param) use ($file) {
			return self::getJade()->render("!!! xml\n" . file_get_contents($file));
		});


		$reader = self::getReader();
		$reader->xml($xml);
		$readData = $reader->parse();

		$page = $readData['value'];

		# Tell the page data object what its form ID is.
		$page->setId($id);

		return $page;
	}
}