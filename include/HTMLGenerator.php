<?php

abstract class HTMLGeneratorAbstract {
	protected $parent;

	abstract function addH($arr);

	abstract function __get($name);
	function t($text) {
		return $this->addH($text);
	}
	abstract function toStringArray();

	function generateString() {
		// Based on: http://stackoverflow.com/questions/29991016/
		$placeHolder = [[$this]];
		$lastIndex = [-1];
		$out = '';
		while(count($placeHolder)) {
			$input = array_pop($placeHolder);

			for($i = array_pop($lastIndex) + 1; $i < count($input); $i++) {

				$element = $input[$i];

				if($element instanceof Renderable) {
					$element = $element->render();
				}

				if ($element instanceof HTMLGeneratorAbstract) {
					$element = $element->toStringArray();
				}

				if(is_array($element)) {
					$placeHolder[] = $input;
					$lastIndex[] = $i;
					$input = $element;
					$i = -1;
				} else if($element instanceof SafeString) {
					$out .= $element->value;
				} else if(is_scalar($element)) {
					$out .= htmlspecialchars($element, ENT_QUOTES);
				} else if(!is_null($element)) {
					throw new Exception('Invalid HTML generation target!');
				}

			}
		}
		return $out;
	}
}


// =============================================================================================================

class SafeString {
	function __construct($value) {
		$this->value = $value . '';
	}
}


abstract class HTMLRealGenerator extends HTMLGeneratorAbstract {
	function __get($name) {
		return new HTMLTagGenerator(new HTMLContentGenerator(), $name, []);
	}
}

class HTMLDummyGenerator extends HTMLGeneratorAbstract {
	function addH($arr) { return $this; }
	function __get($name) { return $this; }
	function ins($gen) { return $this; }
	function append($text) { return $this; }
	function data($key, $val, $cond = true) { return $this; }
	function __call($name, $args) { return $this; }
	function toStringArray() { return []; }
}


class HTMLContentGenerator extends HTMLRealGenerator {

	function __construct($children = []) {
		$this->children = $children;
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
class HTMLTagGenerator extends HTMLRealGenerator {
	function __construct( $tagless, $tag, $attrs = []) {
		$this->tagless = $tagless;
		$this->tag = $tag;
		$this->attrs = $attrs;
	}

	function __call($name, $args) {
		if(isset($args[1]) && !$args[1]) {
			return $this;
		}
		$this->attrs[$name] = $args[0];
		return new HTMLTagGenerator($this->tagless, $this->tag, $this->attrs);
	}

	function data($key, $val, $cond = true) {
		if(!$cond) {
			return $this;
		}
		return new HTMLTagGenerator($this->tagless, $this->tag, array_merge($this->attrs, [  'data-' . $key => $val ] ));
	}

	function addH($arr) {
		return new HTMLTagGenerator($this->tagless->addH($arr), $this->tag, $this->attrs);
	}


	function __get($name) {
		return new HTMLTagGenerator(new HTMLContentGenerator(), $name);
	}


	function toStringArray() {
		$parts = [ new SafeString('<'), $this->tag ];
		foreach ($this->attrs as $key => $value) {
			$parts[] = new SafeString(' ');
			$parts[] = $key;
			$parts[] = new SafeString('="');
			$parts[] = $value;
			$parts[] = new SafeString('"');
		}
		$parts[] = new SafeString('>');
		$parts[] = $this->tagless;
		$parts[] = new SafeString('</');
		$parts[] = $this->tag;
		$parts[] = new SafeString('>');
		return $parts;
	}
}

class HTMLParentContext extends HTMLGeneratorAbstract {
	function __construct($parent, $generator) {
		$this->parent = $parent;
		$this->generator = $generator;
	}

	function __call($name, $args) { return new HTMLParentContext($this->parent, $this->generator->__call($name, $args)); }
	function addH($arr) { return new HTMLParentContext($this->parent, $this->generator->addH($arr)); }
	function data($key, $val, $cond = true) { return new HTMLParentContext($this->parent, $this->generator->data($key, $val, $cond)); }

	function ins($gen) {
		return new HTMLParentContext($this, $gen->generator);
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
	function ins($gen) {
		return new HTMLParentContext($this, $gen->generator);
	}
	function toStringArray() {
		return $this->generator->toStringArray();
	}
}

