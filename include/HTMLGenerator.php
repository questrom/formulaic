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
		$time = microtime(true);
		$out = '';
		$escapeCount = 0;
		// $rec = new RecursiveIteratorIterator(new RecursiveArrayIterator(iterator_to_array($this->toFullArray())));

		$rec = new RecursiveIteratorIterator(new HTMLIterator($this->toStringArray()));
		foreach($rec as $x) {
			if($x instanceof IncrementEscape) {
				$escapeCount++;
			} else if ($x instanceof DecrementEscape) {
				$escapeCount--;
			} else if($x instanceof SafeString) {
				$x = $x->getValue();

				for($j = 0; $j < $escapeCount; $j++) {
					$x = htmlspecialchars($x, ENT_QUOTES);
				}
				$out .= $x;
			} else if(is_null($x)) {
				continue;
			} else {
				throw new Exception("Invalid HTML");
			}

		}
		// echo '<br><br><br>' . (microtime(true) - $time);
		return $out;
	}

}

class HTMLIterator implements RecursiveIterator {
	function __construct($iterator) {
		$this->iterator = $iterator;
	}
	function getChildren() {
		return new HTMLIterator($this->current());
	}
	function hasChildren() {
		$cur = $this->current();
		return $cur instanceof Iterator;
	}
	private function process($element) {
		while($element instanceof Renderable) {
			$element = $element->render();
		}

		if($element instanceof HTMLGeneratorAbstract || $element instanceof DoubleEncode) {
			$element = $element->toStringArray();
		}
		if(is_array($element)) {
			return new ArrayIterator($element);
		}
		if(is_scalar($element)) {
			return new ArrayIterator([
				new IncrementEscape(),
				new SafeString($element),
				new DecrementEscape()
			]);
		}
		return $element;
	}
	function current() {
		return $this->process($this->iterator->current());
	}
	function key() {
		return $this->iterator->key();
	}
	function next() {
		return $this->process($this->iterator->next());
	}
	function rewind() {
		return $this->iterator->rewind();
	}
	function valid() {
		return $this->iterator->valid();
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
	function toStringArray() {
		return [
			new IncrementEscape(),
			$this->value,
			new DecrementEscape()
		];
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
		yield $this->contents;
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
		yield $this->generator;
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
		yield $this->generator;
	}
}

function h() {
	return new HTMLParentlessContext();
}