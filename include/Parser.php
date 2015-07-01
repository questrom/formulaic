<?php

require('jade/autoload.php.dist');
use Everzet\Jade\Jade;


trait NormalParse {
	static function xmlDeserialize($reader) {
		$arr = new NodeData();

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


		return static::fromYaml($arr);
	}
}

require('ComponentAbstract.php');


class TextElem implements YAMLPart {
	use NormalParse;
	function __construct($args) {}
	static function fromYaml($elem) {
		return $elem->text;
	}
}

class ChildElem implements YAMLPart {
	use NormalParse;
	function __construct($args) {}
	static function fromYaml($elem) {
		return $elem->children;
	}
}

class AllowElem implements YAMLPart {
	use NormalParse;
	function __construct($args) {}
	static function fromYaml($elem) {
		// var_dump($elem);
		return [$elem->attrs['ext'] => $elem->attrs['mime']];
	}
}

$parsers =  [
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
	'mongo' => 'MongoOutput',
	'notice' => 'Notice',
	'header' => 'Header',
	'datetime' => 'DateTimePicker',
	's3' => 'S3Output',
	'file' => 'FileUpload',
	'allow' => 'AllowElem',
	'option' => 'TextElem',
	'fields' => 'FormElem',
	'li' => 'TextElem',
	'outputs' => 'SuperOutput',
	'form' => 'Page',
	'list' => 'ListComponent',
	'show-if' => 'ShowIfComponent',
	'views' => 'ChildElem',
	'table-view' => 'TableView',
	'col' => 'Column'
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
				return $parser::xmlDeserialize($reader);
			};
		}

		// var_dump($reader->elementMap);

		$reader->xml($xml);
		$readData = $reader->parse();

		$page = $readData['value'];


		return $page;
	}
}