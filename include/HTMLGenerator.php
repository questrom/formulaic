<?php

class IncrementEscape {}
class DecrementEscape {}

abstract class HTMLGeneratorAbstract {
	abstract function addH($arr);
	abstract function __get($name);
	abstract function toStringArray();

	final function t($text) {
		return $this->addH($text);
	}

	final function generateString() {
		$out = '';
		foreach($this->toFullArray() as $x) {
			$out .= $x;
		}
		return $out;
	}

	final function toFullArray() {
		// Based on: http://stackoverflow.com/questions/29991016/

		// var_dump(iterator_to_array($this->toStringArray()));

		$positions = [ new ArrayIterator([$this]) ];
		$escapeCount = 0;

		while(count($positions)) {
			$input = array_pop($positions);
			while($input->valid()) {

				$element = $input->current();
				$input->next();

				if($element instanceof IncrementEscape) {
					$escapeCount++;
					$element = null;
				} else if($element instanceof DecrementEscape) {
					$escapeCount--;
					$element = null;
				} else if($element instanceof Renderable) {
					$element = $element->render();
				}

				if ($element instanceof HTMLGeneratorAbstract) {
					$element = $element->toStringArray();
				} else if(is_scalar($element)) {
					$element = new ArrayIterator([new IncrementEscape(), new SafeString($element), new DecrementEscape()]);
				} else if(is_array($element)) {
					$element = new ArrayIterator($element);
				} else if($element instanceof DoubleEncode) {
					$element = new ArrayIterator([new IncrementEscape(), $element->value, new DecrementEscape()]);
				}

				if($element instanceof Iterator) {
					$positions[] = $input;
					$input = $element;
				} else if(!is_null($element)) {
					if($element instanceof SafeString) {
						$element = $element->getValue();
					} else {
						throw new Exception('Invalid HTML generation target!');
					}

					for($j = 0; $j < $escapeCount; $j++) {
						$element = htmlspecialchars($element, ENT_QUOTES);
					}

					yield $element;
				}

			}
		}
	}
}


// =============================================================================================================


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
		foreach($this->children as $x) {

			if($x instanceof HTMLGeneratorAbstract) {
				foreach($x->toStringArray() as $y) {
					yield $y;
				}
			} else {
				yield $x;
			}
		}
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
		yield new SafeString('<');
		yield $this->tag;
		foreach ($this->attrs as $key => $value) {
			yield new SafeString(' ');
			yield $key;
			yield new SafeString('="');
			yield $value;
			yield new SafeString('"');
		}

		yield new SafeString('>');
		foreach($this->contents->toStringArray() as $x) {
			yield $x;
		}
		yield new SafeString('</');
		yield $this->tag;
		yield new SafeString('>');
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
		foreach($this->generator->toStringArray() as $x) { yield $x; }
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
		foreach($this->generator->toStringArray() as $x) { yield $x; }
	}
}

function h() {
	return new HTMLParentlessContext();
}