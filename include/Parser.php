<?php

require('jade/autoload.php.dist');
use Everzet\Jade\Jade;

class TextElem implements YAMLPart {
	function __construct($args) {}
	static function fromYaml($elem) {
		return $elem->text;
	}
}

class ChildElem implements YAMLPart {
	function __construct($args) {}
	static function fromYaml($elem) {
		return $elem->children;
	}
}

class AllowElem implements YAMLPart {
	function __construct($args) {}
	static function fromYaml($elem) {
		return [$elem->attrs['ext'] => $elem->attrs['mime']];
	}
}

$parsers =  [
	'checkbox' => ['Checkbox', 'fromYaml'],
	'textbox' => ['Textbox', 'fromYaml'],
	'password' => ['Password', 'fromYaml'],
	'dropdown' => ['Dropdown', 'fromYaml'],
	'radios' => ['Radios', 'fromYaml'],
	'checkboxes' => ['Checkboxes', 'fromYaml'],
	'textarea' => ['TextArea', 'fromYaml'],
	'range' => ['Range', 'fromYaml'],
	'time' => ['TimeInput', 'fromYaml'],
	'group' => ['Group', 'fromYaml'],
	'date' => ['DatePicker', 'fromYaml'],
	'phonenumber' => ['PhoneNumber','fromYaml'],
	'email' => ['EmailAddr','fromYaml'],
	'url' => ['UrlInput','fromYaml'],
	'number' => ['NumberInp','fromYaml'],
	'mongo' => ['MongoOutput', 'fromYaml'],
	'notice' => ['Notice', 'fromYaml'],
	'header' => ['Header', 'fromYaml'],
	'datetime' => ['DateTimePicker', 'fromYaml'],
	's3' => ['S3Output', 'fromYaml'],
	'file' => ['FileUpload', 'fromYaml'],
	'allow' => ['AllowElem', 'fromYaml'],
	'option' => ['TextElem', 'fromYaml'],
	'fields' => ['FormElem', 'fromYaml'],
	'li' => ['TextElem', 'fromYaml'],
	'outputs' => ['SuperOutput', 'fromYaml'],
	'form' => ['Page', 'fromYaml'],
	'list' => ['ListComponent', 'fromYaml'],
	'show-if' => ['ShowIfComponent', 'fromYaml'],
	'views' => ['ChildElem', 'fromYaml'],
	'table-view' => ['TableView', 'fromYaml'],
	'col' => ['Column', 'fromYaml']
];





class NodeData {
	function __construct() {
		$this->tag = '';
		$this->attrs = [];
		$this->children = [];
		$this->byTag = [];
		$this->text = '';
	}
}


class Parser {
	static function parse_jade($file) {

		$file = "!!! xml\n" . file_get_contents($file);

		$parsed = (new Everzet\Jade\Parser(new Everzet\Jade\Lexer\Lexer()))->parse($file);
		$xml = (new Everzet\Jade\Dumper\PHPDumper())->dump($parsed);


		$reader = new Sabre\Xml\Reader();

		global $parsers;


		foreach($parsers as $name => $parser) {
			$reader->elementMap['{}' . $name] = function($reader) use ($parser) {
					global $parsers;
					$arr = new NodeData();
					// var_dump($reader);

					$arr->tag = substr($reader->getClark(), 2);

					$arr->attrs = $reader->parseAttributes();
					$tree = $reader->parseInnerTree();

					if(is_array($tree)) {
						$arr->children = array_map(function($x) use(&$arr) {
							return $arr->byTag[substr($x['name'],2)] = $x['value'];
						}, $tree);
					} else if(is_string($tree)) {
						$arr->text = $tree;
					}

					return $parser($arr);
				};
		}

		// var_dump($reader->elementMap);

		$reader->xml($xml);
		$readData = $reader->parse();

		$page = $readData['value'];


		return $page;
	}
}