<?php

require('jade/autoload.php.dist');
use Everzet\Jade\Jade;

$parsers =  [
	'checkbox' => function($v) {
		return new Checkbox($v->attrs);
	},
	'textbox' => function($v) {
		return new Textbox($v->attrs);
	},
	'password' => function($v) {
		return new Password($v->attrs);
	},
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
	'textarea' => function($v) {
		return new Textarea($v->attrs);
	},
	'range' => function($v) {
		return new Range($v->attrs);
	},
	'time' => function($v) {
		return new TimeInput($v->attrs);
	},
	'group' => function($v) {
		$v->attrs['fields'] = $v->children;
		return new Group($v->attrs);
	},
	'date' => function($v) {
		return new DatePicker($v->attrs);
	},
	'phonenumber' => function($v) {
		return new PhoneNumber($v->attrs);
	},
	'email' => function($v) {
		return new EmailAddr($v->attrs);
	},
	'url' => function($v) {
		return new UrlInput($v->attrs);
	},
	'number' => function($v) {
		return new NumberInp($v->attrs);
	},
	'mongo' => function($v) {
		return new MongoOutput($v->attrs);
	},
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
	'datetime' => function($v) {
		return new DateTimePicker($v->attrs);
	},
	's3' => function($v) {
		return new S3Output($v->attrs);
	},
	'file' => function($v) {
		$aext = array_reduce($v->children, 'array_merge', []);
		$v->attrs['allowed-extensions'] = $aext;
		return new FileUpload($v->attrs);
	},
	'allow' => function($v) {
		return [$v->attrs['ext'] => $v->attrs['mime']];
	},
	'option' => function($v) {
		return $v->children[0] . '';
	},
	'fields' => function($v) {
		return new FormElem($v->children);
	},
	'li' => function($v) {
		return $v->children[0] . '';
	},
	'outputs' => function($v) {
		return new SuperOutput($v->children);
	},
	'form' => function($v) {
		return new Page([
			'fields' => $v->byTag['fields'],
			'title' => $v->attrs['title'],
			'success-message' => $v->attrs['success-message'],
			'debug' => isset($v->attrs['debug']),
			'outputs' => $v->byTag['outputs']
		]);
	},
	'list' => function($v) {
		$v->attrs['items'] = $v->children;
		return new ListComponent($v->attrs);
	},
	'show-if' => function($v) {
		$v->attrs['item'] = $v->children[0];
		return new ShowIfComponent($v->attrs);
	}
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

	static function domToArray($elem){
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