<?php

use Gregwar\Cache\Cache;

# This interface indicates that a class can be instantiated from data
# obtained from a configuration file. $args is an array of attributes,
# with special "children" and "innerText" keys used to store
# the children or text inside of the element, respectively.
interface Configurable {
	public function __construct($args);
}

# Used for the <allow ext="..." mime="..."> element within file upload inputs.
class AllowElem implements Configurable {
	function __construct($args) {
		$this->ext = $args['ext'];
		$this->mime = $args['mime'];
	}
}

# A version of XMLReader that converts XML elements to things that extend Configurable.
# This code is based on Sabre/XML - see https://github.com/fruux/sabre-xml/blob/master/lib/Reader.php
class BetterReader extends XMLReader {
	function parseCurrentElement() {

		$name = $this->localName;

		$attrs = [];
		while ($this->moveToNextAttribute()) {
			$attrs[$this->localName] = $this->value;
		}
		$this->moveToElement();

		$text = '';
		$elements = [];
		$attributes = [];
		if ($this->nodeType === self::ELEMENT && $this->isEmptyElement) {
			$this->next();
		} else {
			$this->read();
			while (true) {
				switch ($this->nodeType) {
					case self::ELEMENT:
						$elements[] = $this->parseCurrentElement();
						break;
					case self::TEXT:
					case self::CDATA:
						$text .= $this->value;
						$this->read();
						break;
					case self::END_ELEMENT:
						// Ensuring we are moving the cursor after the end element.
						$this->read();
						break 2;
					default:
						// Advance to the next element
						$this->read();
						break;
				}
			}
		}

		if($this->elementMap[$name] !== '_text') {
			if(!is_subclass_of($this->elementMap[$name], 'Configurable')) {
				throw new Exception('Only put configurables in ElementMap');
			}
			# Create an object

			$attrs['children'] = [];
			$attrs['innerText'] = $text;

			foreach($elements as $item) {
				$attrs['byTag'][$item['name']] = $item['value'];
				$attrs['children'][] = $item['value'];
			}

			return [
				'name' => $name,
				'value' => new $this->elementMap[$name]($attrs)
			];
		} else {
			# Just turn the element into a string
			return [
				'name' => $name,
				'value' => $text
			];
		}
	}
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
			$reader = self::$reader = new BetterReader();

			# The map of XML element names to PHP classes that implement Configurable.
			$reader->elementMap = [
				'checkbox' => 'Checkbox',
				'textbox' => 'Textbox',
				'password' => 'Password',
				'dropdown' => 'Dropdown',
				'radios' => 'Radios',
				'checkboxes' => 'Checkboxes',
				'textarea' => 'TextArea',
				'range' => 'Range',
				'time' => 'TimeInput',
				'group' => 'Group',
				'date' => 'DatePicker',
				'phonenumber' => 'PhoneNumber',
				'email' => 'EmailAddr',
				'url' => 'UrlInput',
				'number' => 'NumberInp',

				'notice' => 'Notice',
				'header' => 'Header',
				'datetime' => 'DateTimePicker',
				'file' => 'FileUpload',
				'allow' => 'AllowElem',

				'mongo' => 'MongoOutput',
				's3' => 'S3Output',

				'option' => '_text',
				'fields' => 'FieldList',
				'li' => '_text',
				'outputs' => 'SuperOutput',
				'form' => 'Page',
				'list' => 'ListComponent',
				'show-if' => 'ShowIfComponent',
				'table-view' => 'TableView',
				'col' => 'Column',
				'email-to' => 'EmailOutput',
				'graph-view' => 'GraphView',
				'bar' => 'BarGraph',
				'pie' => 'PieChart',
				'captcha' => 'Captcha',
				'is-checked' => 'IsCheckedCondition',
				'is-not-checked' => 'IsNotCheckedCondition',
				'is-radio-selected' => 'IsRadioSelectedCondition',
				'views' => 'ViewList'
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

		// $t = microtime(true);
		$reader = self::getReader();
		$reader->xml($xml);

		while ($reader->nodeType !== XMLReader::ELEMENT) {
			$reader->read();
		}
		$readData = $reader->parseCurrentElement();
		// echo '<br><br><br>' . (microtime(true) - $t)*1000;

		$page = $readData['value'];

		# Tell the page data object what its form ID is.
		$page->setId($id);

		return $page;
	}
}