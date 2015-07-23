<?php

class StringBuilder {
	function __construct($arr, $escapeCount) {
		$this->arr = $arr;
		$this->escapeCount = $escapeCount;
	}
	function process($element) {
		if ($element instanceof HTMLGeneratorAbstract) {
			return $this->buildString($element->toStringArray());
		} else if(is_scalar($element)) {
			return new StringBuilder($element, $this->escapeCount + 1);
			// return htmlspecialchars((string) $element, ENT_QUOTES);
		} else if(is_array($element) || $element instanceof Traversable) {
			return $this->buildString($element);
		} else if($element instanceof DoubleEncode) {
			return new StringBuilder($element, $this->escapeCount + 1);
		} else if($element instanceof SafeString) {
			return $element->getValue();
		} else if (!is_null($element)) {
			throw new Exception('Invalid HTML generation target!');
		}
	}
}

class StackLocation {
	function __construct($iterator, $escapeCount = 0, $out = '') {
		$this->iterator = $iterator;
		$this->escapeCount = $escapeCount;
		$this->out = $out;
	}
	function escape($text) {
		for($j = 0; $j < $this->escapeCount; $j++) {
			$text = htmlspecialchars($text, ENT_QUOTES);
		}
		return $text;
	}
	function process() {
		if(count($this->iterator) === 0) {
		 	return $this->out;
		}

		 $element = $this->iterator[0];

		while($element instanceof Renderable) {
			$element = $element->render();
		}

		if(is_scalar($element)) {
			$element = new DoubleEncode(new SafeString($element));
		}
		if(is_null($element)) {
			$element = new SafeString('');
		}
		if ($element instanceof HTMLGeneratorAbstract) {
			$element = $element->toStringArray();
		}

		if(is_array($element)) {
			$element = new ArrayIterator($element);
		}

		if($element instanceof DoubleEncode) {
			return new StackLocation([
				$element->value
			], $this->escapeCount + 1, $this->out);
		} else if($element instanceof Iterator) {
			return new StackLocation(iterator_to_array($element), $this->escapeCount, $this->out);
		} else if($element instanceof SafeString) {

			return new StackLocation(array_slice($this->iterator, 1), $this->escapeCount, $this->out . $this->escape($element->getValue()));
		} else {
			throw new Exception('Invalid HTML generation target!');
		}
	}

}

abstract class HTMLGeneratorAbstract {
	abstract function addH($arr);
	abstract function __get($name);
	abstract function toStringArray();

	final function t($text) {
		return $this->addH($text);
	}

	# May exceed xdebug call stack - if so change php conf.
 final function generateString() {

		 // $time = microtime(true);
		 $out = '';
		 $positions = new SplStack();
		 $positions->push(
			new StackLocation($this->toStringArray())
		 );


		 $count = 10;

		 while(!$positions->isEmpty()) {

				 $item = $positions->pop();

				 while($item instanceof StackLocation) {

				 	$positions->push($item);

					$item = $item->process();
				 }
				 $out .= $item;


				 if($count-- == 0) {
				 	var_dump($out);
					 die();
				}



		 }
		 return $out;
   }
	final function buildString($iterator) {

		/*
$out = '';


		foreach($iterator as $element) {
			while($element instanceof Renderable) {
				$element = $element->render();
			}

			if ($element instanceof HTMLGeneratorAbstract) {
				$out .= $this->buildString($element->toStringArray());
			} else if(is_scalar($element)) {
				$out .= htmlspecialchars((string) $element, ENT_QUOTES);
			} else if(is_array($element) || $element instanceof Traversable) {
				$out .= $this->buildString($element);
			} else if($element instanceof DoubleEncode) {
				$out .= htmlspecialchars($this->buildString($element), ENT_QUOTES);
			} else if($element instanceof SafeString) {
				$out .= $element->getValue();
			} else if (!is_null($element)) {
				throw new Exception('Invalid HTML generation target!');
			}
		}
		return $out;
		*/
	}

	// final function generateString() {
	// 	return $this->buildString($this->toStringArray());
	// }

}

class SafeString {
	function __construct($value) {
		$this->value = $value;
	}
	function getValue() {
		return $this->value;
	}
}

class DoubleEncode {
	function __construct($value) {
		$this->value = $value;
	}
}

class AssetUrl extends SafeString {
	function __construct($value) {
		$this->value = $value;
	}
	function getValue() {
		return '____{{asset ' . $this->value . '}}____';
	}
}


class HTMLContentGenerator extends HTMLGeneratorAbstract {
	function __construct($children = []) {
		$this->children = $children;
	}

	function __get($name) {
		return new HTMLTagGenerator(new HTMLContentGenerator(), $name);
	}

	function addH($arr) {
		$children = $this->children;
		$children[] = $arr;
		return new HTMLContentGenerator($children);
	}

	function toStringArray() {
		return $this->children;
	}
}

// Generates the inside of an HTML element
class HTMLTagGenerator extends HTMLGeneratorAbstract {
	private $contents, $tag, $attrs;

	function __construct( $contents, $tag, $attrs = []) {
		$this->contents = $contents;
		$this->tag = $tag;
		$this->attrs = $attrs;
	}

	function __call($name, $args) {
		if(isset($args[1]) && !$args[1]) {
			return $this;
		}
		$this->attrs[$name] = $args[0];
		return new HTMLTagGenerator($this->contents, $this->tag, $this->attrs);
	}

	function data($key, $val, $cond = true) {
		if(!$cond) {
			return $this;
		}
		return new HTMLTagGenerator($this->contents, $this->tag, array_merge($this->attrs, [  'data-' . $key => $val ] ));
	}

	function addH($arr) {
		return new HTMLTagGenerator($this->contents->addH($arr), $this->tag, $this->attrs);
	}

	function __get($name) {
		return new HTMLTagGenerator(new HTMLContentGenerator(), $name);
	}

	function toStringArray() {

		$arr = [];
		$arr[] = new SafeString('<');
		$arr[] = $this->tag;
		foreach ($this->attrs as $key => $value) {
			$arr[] = new SafeString(' ');
			$arr[] = $key;
			$arr[] = new SafeString('="');
			$arr[] = $value;
			$arr[] = new SafeString('"');
		}

		$arr[] = new SafeString('>');
		$arr[] = $this->contents;

		$arr[] = new SafeString('</');
		$arr[] = $this->tag;
		$arr[] = new SafeString('>');
		return $arr;
	}
}

class HTMLParentContext extends HTMLGeneratorAbstract {
	function __construct($parent, $generator) {
		$this->parent = $parent;
		$this->generator = $generator;
	}

	function __call($name, $args) {
		return new HTMLParentContext($this->parent, $this->generator->__call($name, $args));
	}

	function addH($arr) {
		return new HTMLParentContext($this->parent, $this->generator->addH($arr));
	}

	function data($key, $val, $cond = true) {
		return new HTMLParentContext($this->parent, $this->generator->data($key, $val, $cond));
	}

	function __get($name) {
		if($name === 'end') {
			return $this->parent->addH($this);
		} else {
			return new HTMLParentContext($this, $this->generator->__get($name));
		}
	}

	function toStringArray() {
		return $this->generator->toStringArray();
	}
}

class HTMLParentlessContext extends HTMLGeneratorAbstract{
	function __construct($generator = null) {
		$this->generator = $generator ? $generator : new HTMLContentGenerator();
	}

	function __get($name) {
		return new HTMLParentContext($this, $this->generator->__get($name));
	}

	function addH($arr) {
		return new HTMLParentlessContext($this->generator->addH($arr));
	}

	function toStringArray() {
		return $this->generator->toStringArray();
	}
}

function h() {
	return new HTMLParentlessContext();
}
