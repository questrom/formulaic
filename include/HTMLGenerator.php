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



	final function buildString($iterator, &$stopped, $startAt) {
		$out = [];

		// Stop if the recursion depth gets too high..
		if($startAt == 0) {
			// return [new SafeString('')];
			$stopped = true;
			return [$iterator];
			// return $this->generateIterative($iterator);
		}

		foreach($iterator as $element) {
			while($element instanceof Renderable) {
				$element = $element->render();
			}

			if ($element instanceof HTMLGeneratorAbstract) {
				$element = $element->toStringArray();
			}

			if(is_scalar($element)) {
				$out[] = (new SafeString($element))->encode();
			} else if(is_array($element) || $element instanceof Traversable) {
				$out = array_merge($out, $this->buildString($element, $stopped, $startAt - 1));
			} else if($element instanceof DoubleEncode) {

				$data = $element->value;
				if($data instanceof HTMLGeneratorAbstract) {
					$data = $data->toStringArray();
				}
				$out = array_merge($out, array_map(function($x) {
					if($x instanceof SafeString) {
						return $x->encode();
					} else {
						return new DoubleEncode($x);
					}
				}, $this->buildString($data, $stopped, $startAt - 1)));
			} else if($element instanceof SafeString) {
				$out[] = $element;
			} else if (!is_null($element)) {
				throw new Exception('Invalid HTML generation target!');
			}
		}
		return $out;
	}

	final function generateString() {

		$stopped = true;
		$ret = $this->toStringArray();
		while($stopped) {
			$stopped = false;
			$ret = $this->buildString($ret, $stopped, 50);
		}

		$ret = array_map(function($x) {
			if(!($x instanceof SafeString)) {
				throw new Exception();
			}
			return $x->value;
		}, $ret);
		return implode($ret);

	}
	final function generateIterative($iterator) {
		// var_dump($iterator);
		// $time = microtime(true);
		$out = '';
		$positions = new SplStack();
		$arr = $iterator;
		if(is_array($arr)) {
			$arr = new ArrayIterator($arr);
		}
		$positions->push($arr);

		$escapeCount = 0;

		while(!$positions->isEmpty()) {




			$input = $positions->pop();
			while($input->valid()) {


				$element = $input->current();
				$input->next();

				while($element instanceof Renderable) {
					$element = $element->render();
				}

				if ($element instanceof HTMLGeneratorAbstract) {
					$positions->push($input);
					$element = $element->toStringArray();
					if(is_array($element)) {
						$element = new ArrayIterator($element);
					}
					$input = $element;
				} else if(is_scalar($element)) {
					$x = $element;
					for($j = $escapeCount + 1; $j > 0; $j--) {
						$x = htmlspecialchars($x, ENT_QUOTES);
					}
					$out .= $x;
				} else if(is_array($element)) {
					$positions->push($input);
					$input = new ArrayIterator($element);
				} else if($element instanceof DoubleEncode) {
					$positions->push($input);
					$escapeCount++;
					$input = new ArrayIterator([
						$element->value,
						new DecrementEscape()
					]);
					continue;
				} else if($element instanceof Iterator) {
					$positions->push($input);
					$input = $element;
				} else if($element instanceof SafeString) {
					$x = $element->value;
					for($j = $escapeCount; $j > 0; $j--) {
						$x = htmlspecialchars($x, ENT_QUOTES);
					}
					$out .= $x;
				} else if($element instanceof IncrementEscape) {
					$escapeCount++;
				} else if($element instanceof DecrementEscape) {
					$escapeCount--;
				} else if (!is_null($element)) {
					throw new Exception('Invalid HTML generation target!');
				}
			}
		}
		return $out;
	}
}

class SafeString {
	function __construct($value) {
		$this->value = $value;
	}
	function encode() {
		return new SafeString(htmlspecialchars($this->value, ENT_QUOTES));
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
/*
class IncrementEscape {}
class DecrementEscape {}

abstract class HTMLGeneratorAbstract implements IteratorAggregate {
	abstract function addH($arr);
	abstract function __get($name);
	abstract function getIterator();

	final function t($text) {
		return $this->addH($text);
	}

	final function generateString() {
		$time = microtime(true);
		$out = '';


		$rec = new RecursiveIteratorIterator(new HTMLIterator($this->getIterator(), 0));
		$rec = iterator_to_array($rec, false);
		foreach($rec as $x) {
			if(is_scalar($x)) {
				$out .= $x;
			} else {
				throw new Exception("Invalid HTML");
			}
		}
		// echo '<br><br><br>' . (microtime(true) - $time) * 1000 . 'ms';
		return $out;
	}

}

class HTMLIterator implements RecursiveIterator {
	function __construct($iterator, $escapeCount) {
		$this->iterator = $iterator;
		// var_dump($escapeCount);
		$this->escapeCount = $escapeCount;
	}
	function getChildren() {
		return $this->current();
	}
	function hasChildren() {
		return $this->current() instanceof HTMLIterator;
	}
	private function process($element) {
		while($element instanceof Renderable) {
			$element = $element->render();
		}

		if($element instanceof DoubleEncode) {
			return new HTMLIterator(new ArrayIterator([
				$element->value
			]), $this->escapeCount + 1);
		}

		if($element instanceof IteratorAggregate) {
			$element = $element->getIterator();
		}

		if(is_array($element)) {
			$element = new ArrayIterator($element);
		}

		if(is_scalar($element)) {
			return new HTMLIterator(new ArrayIterator([
				new SafeString($element)
			]), $this->escapeCount + 1);
		}

		if($element instanceof Iterator) {
			return new HTMLIterator($element, $this->escapeCount);
		} else if($element instanceof SafeString) {
			$element = $element->getValue();
			for($j = 0; $j < $this->escapeCount; $j++) {
				$element = htmlspecialchars($element, ENT_QUOTES);
			}
			return $element;
		} else if(is_null($element)) {
			return '';
		} else {
			throw new Exception("Invalid HTML");
		}

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

	function getIterator() {
		return new ArrayIterator($this->children);
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

	function getIterator() {
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

	function getIterator() {
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

	function getIterator() {
		yield $this->generator;
	}
}

function h() {
	return new HTMLParentlessContext();
}
*/