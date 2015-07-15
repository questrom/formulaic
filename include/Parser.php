<?php

use Sabre\Xml\XmlDeserializable;
use Gregwar\Cache\Cache;

trait Configurable {
	abstract public function __construct($args);
	static function xmlDeserialize(Sabre\Xml\Reader $reader) {
		$attrs = [];

		$attrs = $reader->parseAttributes();
		$tree = $reader->parseInnerTree();

		$attrs['children'] = [];
		$attrs['innerText'] = '';
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

class AllowElem implements XmlDeserializable {
	use Configurable;
	function __construct($args) {
		$this->ext = $args['ext'];
		$this->mime = $args['mime'];
	}
}

class SubmitCounts {
	private static $data = null;
	static function update() {
		self::$data = json_decode(file_get_contents('data/submit-counts.json'));
	}
	static function get($formName) {
		if(self::$data === null) {
			self::update();
		}
		return isget(self::$data->$formName, 0);
	}
}

class Parser {
	static function getForm($name) {
		if(is_string($name) && !preg_match('/[^A-za-z0-9_-]/', $name) && strlen($name) > 0) {
			return 'forms/' . $name . '.jade';
		} else {
			throw new Exception('Invalid form name!');
		}
	}
	static function getFormInfo() {
		$files = scandir('forms');

		$files = array_values(array_filter($files, function($item) {
			return preg_match('/^[A-za-z0-9_-]+\.jade$/', $item);
		}));

		$files = array_map(function($item) {
			return preg_replace('/\.jade$/', '', $item);
		}, $files);


		$files = array_map(function($item) {
			$page = Parser::parseJade($item);
			$views = array_map(function($view) {
				return [
					'id' => $view->name,
					'title' => $view->title,
					'type' => $view->type
				];
			}, $page->views->getAllViews());
			return [
				'id' => $item,
				'name' => $page->title,
				'views' => $views,
				'count' => SubmitCounts::get($item)
			];
		}, $files);

		return $files;
	}
	static function parseJade($id) {

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

		// echo(htmlspecialchars($xml)) . '<br><pre>';

		$reader->xml($xml);
		$readData = $reader->parse();

		$page = $readData['value'];
		$page->setId($id);


		return $page;
	}
}