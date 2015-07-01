<?php

require('jade/autoload.php.dist');
use Everzet\Jade\Jade;


abstract class ConfigElement implements Sabre\Xml\XmlDeserializable {
	abstract public function __construct($args);
	static function xmlDeserialize(Sabre\Xml\Reader $reader) {
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

		$arr->attrs['children'] = $arr->children;
		$arr->attrs['innerText'] = $arr->text;
		$arr->attrs['byTag'] = $arr->byTag;
		return new static($arr->attrs);
	}
}

require('ComponentAbstract.php');


class TextElem implements Sabre\Xml\XmlDeserializable {
	function __construct($args) {

	}
	static function xmlDeserialize(Sabre\Xml\Reader $reader) {
		$tree = $reader->parseInnerTree();

		if(is_string($tree)) {
			return $tree;
		} else {
			return '';
		}
	}
}

class ChildElem implements Sabre\Xml\XmlDeserializable  {
	static function xmlDeserialize(Sabre\Xml\Reader $reader) {
		$arr = new NodeData();

		$arr->tag = substr($reader->getClark(), 2);

		$arr->attrs = $reader->parseAttributes();
		$tree = $reader->parseInnerTree();

		if(is_array($tree)) {
			return array_map(function($x) use(&$arr) {
				return $x['value'];
			}, $tree);
		} else {
			return [];
		}
	}
}

class AllowElem extends ConfigElement {
	function __construct($args) {
		$this->ext = $args['ext'];
		$this->mime = $args['mime'];
	}
}




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

		$jade = new Everzet\Jade\Jade(
			new Everzet\Jade\Parser(new Everzet\Jade\Lexer\Lexer()),
			new Everzet\Jade\Dumper\PHPDumper()
		);

		$xml = $jade->render($file);


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
			'{}fields' => 'FormElem',
			'{}li' => 'TextElem',
			'{}outputs' => 'SuperOutput',
			'{}form' => 'Page',
			'{}list' => 'ListComponent',
			'{}show-if' => 'ShowIfComponent',
			'{}views' => 'ChildElem',
			'{}table-view' => 'TableView',
			'{}col' => 'Column'
		];

		$reader->xml($xml);
		$readData = $reader->parse();

		$page = $readData['value'];


		return $page;
	}
}