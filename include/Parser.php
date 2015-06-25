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
		$v->attrs['allowed-extensions'] = $v->children;
		return new FileUpload($v->attrs);
	},
	'allow' => function($v) {
		return $v->attrs['ext'];
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
	}
];


// Modified Jade from original...
$jade = new Jade(new Everzet\Jade\Parser(new Everzet\Jade\Lexer\Lexer()), new Everzet\Jade\Dumper\PHPDumper());


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

		$arr->attrs = array_map(function($v) {
			return $v->value;
		}, iterator_to_array($elem->attributes));

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

		global $jade;

		$xml = $jade->render($file);

		$doc = new DOMDocument();
		$doc->loadXML($xml);

		$root = $doc->documentElement;


		$page = self::domToArray($root);


		return $page;
	}
}