<?php

abstract class HTMLGeneratorAbstract {
	abstract function addH($arr);
	abstract function __get($name);
	abstract function toStringArray();

	final function t($text) {
		return $this->addH($text);
	}

	final function generateString() {

		$t = microtime(true);
		$stack = [];
		$out = '';

		$x = [];
		$element = $this;
		$escapeCount = 0;

		while($x !== null) {



			while($element instanceof Renderable) {
				$element = $element->render();
			}

			if($element instanceof HTMLGeneratorAbstract) {
				$element = $element->toStringArray();
			} elseif ($element instanceof DoubleEncode) {
				$element = [$element->value];
				$escapeCount++;
			}



			if (is_array($element) && count($element) > 0) {
				$stack[] = [
					'element' => array_pop($element),
					'escapeCount' => $escapeCount
				];
			} else {
				if(!is_array($element) && $element !== null && $element !== '') {
					$element = (!($element instanceof SafeString) ? htmlspecialchars((string) $element, ENT_QUOTES) : (string) $element->value);

					for($j = $escapeCount; $j--;) {
						$element = htmlspecialchars($element, ENT_QUOTES);
					}
					$out .= $element;
				}
				$x = array_pop($stack);
				$element = $x['element'];
				$escapeCount = $x['escapeCount'];
			}
		}
		// echo '<br><br><Br>' . (microtime(true) - $t)*1000;
		return $out;
	}
}

class SafeString {
	function __construct($value) {
		$this->value = $value;
	}
}

class DoubleEncode {
	function __construct($value) {
		$this->value = $value;
	}
}

class AssetUrl extends SafeString{
	function __construct($value) {
		$this->value = '____{{asset ' . $value . '}}____';;
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
