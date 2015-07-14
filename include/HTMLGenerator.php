<?php

class ArrayPointer {
	function __construct($array, $index, $escapeCount) {
		$this->array = $array;
		$this->index = $index;
		$this->escapeCount = $escapeCount;
	}
}

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

		$positions = [new ArrayPointer([$this], -1, 0)];

		$out = [];
		while(count($positions)) {
			$position = array_pop($positions);
			$input = $position->array;
			$i = $position->index;
			$escapeCount = $position->escapeCount;

			// $input = array_pop($placeHolder);
			// $i = array_pop($lastIndex);

			for($i++; $i < count($input); $i++) {

				$element = $input[$i];

				if($element instanceof Renderable) {
					$element = $element->render();
				}

				if ($element instanceof HTMLGeneratorAbstract) {
					$element = $element->toStringArray();
				}

				if(is_scalar($element)) {
					$element = new DoubleEncode(new SafeString($element));
				} else if(is_null($element)) {
					$element = new SafeString('');
				}

				if(is_array($element)) {
					$positions[] = new ArrayPointer($input, $i, $escapeCount);
					$input = $element;
					$i = -1;
				} else if($element instanceof DoubleEncode) {
					$positions[] = new ArrayPointer($input, $i, $escapeCount);
					$input = [new StartEncoding(), $element->value, new EndEncoding()];
					$i = -1;
					// var_dump($escapeCount);
					$escapeCount++;
				} else {
					$out[] = $element;
				}
			}
		}


		for($i = 0; $i < count($out); $i++) {
			if(!isset($out[$i])) {
				continue;
			}
			if($out[$i] instanceof SafeString) {
				for($j = $i + 1; $j < count($out); $j++) {
					if($out[$j] instanceof SafeString) {
						$out[$i] = new SafeString($out[$i]->getValue() . $out[$j]->getValue());
						unset($out[$j]);
					} else {
						break;
					}
				}
			} else if($out[$i] instanceof StartEncoding) {
				if(isset($out[$i+1]) && $out[$i+1] instanceof EndEncoding) {
					unset($out[$i]);
					unset($out[$i+1]);
				}
			}
		}


		foreach($out as &$value) {
			if($value instanceof SafeString) {
				$value = $value->getValue();
			}
		}

		echo '<br><br><br>';
		var_dump(serialize($out));


		$outstr = '';
		$levels = 0;
		foreach($out as $value) {

			if($value instanceof StartEncoding) {
				$levels++;
			} elseif ($value instanceof EndEncoding) {
				$levels--;
			} else {
				// $value = $value->getValue();
				for($j = 0; $j < $levels; $j++) {
					$value = htmlspecialchars($value, ENT_QUOTES);
				}
				$outstr .=$value;
			}
		}
		return $outstr;
	}
}


// =============================================================================================================

class StartEncoding {}
class EndEncoding {}

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
		$parts = [ new SafeString('<'), $this->tag ];
		foreach ($this->attrs as $key => $value) {
			$parts[] = [ new SafeString(' '), $key, new SafeString('="'), $value, new SafeString('"') ];
		}
		$parts[] = [ new SafeString('>'), $this->contents, new SafeString('</'), $this->tag, new SafeString('>')];
		return $parts;
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
