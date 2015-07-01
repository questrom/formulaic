<?php

require('jade/autoload.php.dist');
use Everzet\Jade\Jade;

$parsers =  [
	'checkbox' => ['Checkbox', 'fromYaml'],
	'textbox' => ['Textbox', 'fromYaml'],
	'password' => ['Password', 'fromYaml'],
	'dropdown' => function($v) {
		$v->attrs['options'] = $v->children;
		return new Dropdown($v->attrs);
	},
	'radios' => function($v) {
		$v->attrs['options'] = $v->children;
		return new Radios($v->attrs);
	},
	'checkboxes' => function($v) {
		$v->attrs['options'] = $v->children;
		return new Checkboxes($v->attrs);
	},
	'textarea' => ['TextArea', 'fromYaml'],
	'range' => function($v) {
		return Range::fromYaml($v);
	},
	'time' => function($v) {
		return TimeInput::fromYaml($v);
	},
	'group' => ['Group', 'fromYaml'],
	'date' => ['DatePicker', 'fromYaml'],
	'phonenumber' => ['PhoneNumber','fromYaml'],
	'email' => ['EmailAddr','fromYaml'],
	'url' => ['UrlInput','fromYaml'],
	'number' => ['NumberInp','fromYaml'],
	'mongo' => ['MongoOutput', 'fromYaml'],
	'notice' => function($v) {
		if(count($v->children)) {
			$v->attrs['list'] = $v->children;
		}
		return new Notice($v->attrs);
	},
	'header' => function($v) {
		$v->attrs['text'] = $v->children[0];
		return new Header($v->attrs);
	},
	'datetime' => ['DateTimePicker', 'fromYaml'],
	's3' => ['S3Output', 'fromYaml'],
	'file' => function($v) {
		$v->attrs['allowed-extensions'] = array_reduce($v->children, 'array_merge', []);
		return new FileUpload($v->attrs);
	},
	'allow' => function($v) {
		return [$v->attrs['ext'] => $v->attrs['mime']];
	},
	'option' => function($v) {
		return $v->children[0] . '';
	},
	'fields' => ['FormElem', 'fromYaml'],
	'li' => function($v) {
		return $v->children[0] . '';
	},
	'outputs' => ['SuperOutput', 'fromYaml'],
	'form' => function($v) {
		return new Page([
			'fields' => $v->byTag['fields'],
			'title' => $v->attrs['title'],
			'success-message' => $v->attrs['success-message'],
			'debug' => isset($v->attrs['debug']),
			'outputs' => $v->byTag['outputs'],
			'views' => $v->byTag['views']
		]);
	},
	'list' => ['ListComponent', 'fromYaml'],
	'show-if' => function($v) {
		$v->attrs['item'] = $v->children[0];
		return new ShowIfComponent($v->attrs);
	},
	'views' => function($v) {
		return $v->children;
	},
	'table-view' => ['TableView', 'fromYaml'],
	'col' => ['Column', 'fromYaml']
];




class NodeData {
	function __construct() {
		$this->tag = '';
		$this->attrs = [];
		$this->children = [];
		$this->byTag = [];
	}
}

class Parser {

	static protected function domToArray($elem){
		global $parsers;

		$arr = new NodeData();
		$arr->tag = $elem->tagName;

		foreach($elem->attributes as $k => $v) {
			$arr->attrs[$k] = $v->value;
		}

		foreach ($elem->childNodes as $child) {
			if($child instanceof DOMElement) {
				$arr->children[] = $arr->byTag[$child->tagName] = self::domToArray($child);
			} else if($child instanceof DOMText) {
				if(trim($child->data) !== '') {
					$arr->children[] = $child->data;
				}
			}
		}

		return $parsers[$arr->tag]($arr);
	}

	static function parse_jade($file) {
		$file = "!!! xml\n" . file_get_contents($file);

		$parsed = (new Everzet\Jade\Parser(new Everzet\Jade\Lexer\Lexer()))->parse($file);
		$xml = (new Everzet\Jade\Dumper\PHPDumper())->dump($parsed);

		$doc = new DOMDocument();
		$doc->loadXML($xml);
		$root = $doc->documentElement;
		$page = self::domToArray($root);


		return $page;
	}
}