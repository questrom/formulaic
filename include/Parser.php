<?php

use Sabre\Xml\XmlDeserializable;
use Gregwar\Cache\Cache;

trait Configurable {
	abstract public function __construct($args);
	static function xmlDeserialize(Sabre\Xml\Reader $reader) {
		$attrs = [];

		$attrs = $reader->parseAttributes();
		$tree = $reader->parseInnerTree();

		if(is_array($tree)) {
			$attrs['children'] = array_map(
				function($x) { return $x['value']; },
				$tree
			);
		} else if(is_string($tree)) {
			$attrs['innerText'] = $tree;
		}

		return new static($attrs);
	}
}


class TextElem implements XmlDeserializable {
	static function xmlDeserialize(Sabre\Xml\Reader $reader) {
		$tree = $reader->parseInnerTree();

		if(is_string($tree)) {
			return $tree;
		} else {
			return '';
		}
	}
}

class ChildElem implements XmlDeserializable  {
	static function xmlDeserialize(Sabre\Xml\Reader $reader) {

		$tree = $reader->parseInnerTree();

		if(is_array($tree)) {
			return array_map(function($x) {
				return $x['value'];
			}, $tree);
		} else {
			return [];
		}
	}
}

class AllowElem implements XmlDeserializable {
	use Configurable;
	function __construct($args) {
		$this->ext = $args['ext'];
		$this->mime = $args['mime'];
	}
}



class Parser {
	static function getForm($name) {
		if(is_string($name) && !preg_match('/[^A-za-z0-9_]/', $name) && strlen($name) > 0) {
			return 'forms/' . $name . '.jade';
		} else {
			throw new Exception('Invalid form name!');
		}
	}
	static function parse_jade($id) {

		$file = self::getForm($id);

		$config = Config::get();

		$cache = $config['cache-xml'] ? new Cache() : new FakeCache();
		$cache->setPrefixSize(0);
		$xml = $cache->getOrCreate('xml-' . sha1_file($file), [], function($param) use ($file) {

			$file = "!!! xml\n" . file_get_contents($file);
			$jade = new Everzet\Jade\Jade(
				new Everzet\Jade\Parser(new Everzet\Jade\Lexer\Lexer()),
				new Everzet\Jade\Dumper\PHPDumper()
			);
			return $jade->render($file);
		});


		$reader = new Sabre\Xml\Reader();

		$reader->elementMap = [
			'{}checkbox' => 'Checkbox',
			'{}textbox' => 'Textbox',
			'{}password' => 'Password',
			'{}dropdown' => 'Dropdown',
			'{}radios' => 'Radios',
			'{}checkboxes' => 'Checkboxes',
			'{}textarea' => 'TextArea',
			'{}range' => 'Range',
			'{}time' => 'TimeInput',
			'{}group' => 'Group',
			'{}date' => 'DatePicker',
			'{}phonenumber' => 'PhoneNumber',
			'{}email' => 'EmailAddr',
			'{}url' => 'UrlInput',
			'{}number' => 'NumberInp',
			'{}mongo' => 'MongoOutput',
			'{}notice' => 'Notice',
			'{}header' => 'Header',
			'{}datetime' => 'DateTimePicker',
			'{}s3' => 'S3Output',
			'{}file' => 'FileUpload',
			'{}allow' => 'AllowElem',
			'{}option' => 'TextElem',
			'{}fields' => 'FieldList',
			'{}li' => 'TextElem',
			'{}outputs' => 'SuperOutput',
			'{}form' => 'Page',
			'{}list' => 'ListComponent',
			'{}show-if' => 'ShowIfComponent',
			'{}table-view' => 'TableView',
			'{}col' => 'Column',
			'{}email-to' => 'EmailOutput',
			'{}graph-view' => 'GraphView',
			'{}bar' => 'BarGraph',
			'{}pie' => 'PieChart',
			'{}captcha' => 'Captcha',
			'{}is-checked' => 'IsCheckedCondition',
			'{}is-not-checked' => 'IsNotCheckedCondition',
			'{}is-radio-selected' => 'IsRadioSelectedCondition',
			'{}views' => 'ViewList'
		];

		$reader->xml($xml);
		$readData = $reader->parse();

		$page = $readData['value'];
		$page->setId($id);


		return $page;
	}
}