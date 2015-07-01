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
		$v->attrs['text'] = $v->text;
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
		return $v->text;
	},
	'fields' => ['FormElem', 'fromYaml'],
	'li' => function($v) {
		return $v->text;
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
		// var_dump($v);
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
		$this->text = '';
	}
}


class Parser {

	static protected function domToArray($elem){
		global $parsers;

		$arr = new NodeData();
		$arr->tag = substr($elem['name'], 2);

		foreach($elem['attributes'] as $k => $v) {
			$arr->attrs[$k] = $v;
		}

		if(is_string($elem['value']) || is_null($elem['value'])) {
			// Note: Sabre\Xml\Reader will DISCARD text if it's accompanied by other elements...
			$arr->text = $elem['value'];
		} else {
			return $elem['value'];
		}


		return $parsers[$arr->tag]($arr);
	}

	static function parse_jade($file) {
		global $reader;

		$file = "!!! xml\n" . file_get_contents($file);

		$parsed = (new Everzet\Jade\Parser(new Everzet\Jade\Lexer\Lexer()))->parse($file);
		$xml = (new Everzet\Jade\Dumper\PHPDumper())->dump($parsed);


		$reader = new Sabre\Xml\Reader();

		global $parsers;

		$reader->elementMap = [];

		foreach($parsers as $name => $parser) {
			$reader->elementMap['{}' . $name] = function($reader) use ($parser, $name) {
					global $parsers;
					$arr = new NodeData();
					// var_dump($reader);

					$arr->tag = $name;
					$arr->attrs = $reader->parseAttributes();

					$tree = $reader->parseInnerTree();

					// var_dump($arr);


					if(is_array($tree)) {
						$arr->children = array_map(function($x) use(&$arr) {

							$val = $arr->byTag[substr($x['name'],2)] = self::domToArray($x);
							return $val;
						}, $tree);
					} else {
						$arr->text = $tree;
					}

					// var_dump($arr->byTag);

					return $parsers[$name]($arr);
				};

			// $reader->elementMap['{}' . $name] = $reader->elementMap['{}' . $name]($parser, $name);
		}

		// var_dump($reader->elementMap);

		$reader->xml($xml);
		$readData = $reader->parse();

		$page = self::domToArray($readData);


		return $page;
	}
}