<?php

abstract class HTMLGeneratorAbstract {
	protected $parent;
	abstract function hif($cond);

	abstract function add($arr);
	abstract function __get($name);
	abstract function __toString();
	function t($text) {
		return $this->add($text);
	}
}


// =============================================================================================================


abstract class HTMLRealGenerator extends HTMLGeneratorAbstract {
	function __get($name) {
		return new HTMLTagGenerator(new HTMLContentGenerator(), $name, []);
	}

	// Helper for "if" statements
	function hif($cond) { return $cond ? new HTMLContentGenerator() : new HTMLDummyGenerator(); }
}

class HTMLDummyGenerator extends HTMLGeneratorAbstract {
	function add($arr) { return $this; }
	function __get($name) { return $this; }
	function ins($gen) { return $this; }
	function __toString() { return ''; }
	function append($text) { return $this; }
	function hif($cond) { return $this; }
	function data($key, $val, $cond = true) { return $this; }
	function __call($name, $args) { return $this; }
}


class HTMLContentGenerator extends HTMLRealGenerator {

	function __construct($children = []) {
		$this->children = $children;
	}

	function add($arr) {
		if(!is_array($arr)) {
			$arr = [$arr];
		}
		$arr = array_map(function($item) {
			if($item instanceof HTMLComponent) {
				return $item->get(new HTMLParentlessContext());
			} else if(is_scalar($item)) {
				return htmlspecialchars($item);
			} else {
				return $item;
			}
		}, $arr);
		return new HTMLContentGenerator(
			array_merge(
				$this->children,
				$arr
			)
		);
	}

	function __toString() {
		// Avoid errors caused by excess recursion by using iteration here...
		// return implode($this->children);

		return implode($this->children);

	}


}

// Generates the inside of an HTML element
class HTMLTagGenerator extends HTMLRealGenerator {
	function __construct( $tagless, $tag, $attrs) {
		$this->tagless = $tagless;
		$this->tag = $tag;
		$this->attrs = $attrs;
	}

	function __call($name, $args) {
		if(isset($args[1]) && !$args[1]) {
			return $this;
		}
		return new HTMLTagGenerator($this->tagless, $this->tag, array_merge($this->attrs, [ $name => $args[0] ] )  );
	}
	function data($key, $val, $cond = true) {
		if(!$cond) {
			return $this;
		}
		return new HTMLTagGenerator($this->tagless, $this->tag, array_merge($this->attrs, [  'data-' . $key => $val ] ));
	}

	function add($arr) {
		return new HTMLTagGenerator($this->tagless->add($arr), $this->tag, $this->attrs);
	}

	function __toString() {
		$att = '<' . $this->tag;
		foreach ($this->attrs as $key => $value) {
			$att .= ' ' . $key . '="' . htmlspecialchars($value) . '"';
		}
		$att .= '>';

		return $att . $this->tagless. '</' . $this->tag . '>';
	}


	function __get($name) {
		return new HTMLTagGenerator( new HTMLContentGenerator(), $name, [] );
	}


	// Helper for "if" statements
	function hif($cond) {
		return $cond ? new HTMLContentGenerator() : new HTMLDummyGenerator();
	}
}

class HTMLParentContext extends HTMLGeneratorAbstract {
	function __construct($parent, $generator) {
		$this->parent = $parent;
		$this->generator = $generator;
	}

	function hif($cond) {
		return new HTMLParentContext($this, $this->generator->hif($cond));
	}

	function __call($name, $args) { return new HTMLParentContext( $this->parent, $this->generator->__call($name, $args)); }
	function add($arr) { return new HTMLParentContext( $this->parent, $this->generator->add($arr)); }
	function data($key, $val, $cond = true) { return new HTMLParentContext( $this->parent, $this->generator->data($key, $val, $cond)); }

	function ins($gen) {
		return new HTMLParentContext($this, $gen->generator);
	}

	function __get($name) {
		if($name === 'end') {
			return $this->parent->add($this);
		} else {
			return new HTMLParentContext($this, $this->generator->__get($name));
		}
	}

	function __toString() { return $this->generator->__toString(); }
}

class HTMLParentlessContext extends HTMLGeneratorAbstract{
	function __construct($generator = null) {
		$this->generator = $generator ? $generator : new HTMLContentGenerator();
	}
	function __get($name) {
		return new HTMLParentContext($this, $this->generator->__get($name));
	}
	function add($arr) {
		return new HTMLParentlessContext($this->generator->add($arr));
	}
	function hif($arr) {
		return new HTMLParentContext($this, $this->generator->hif($arr));
	}
	function ins($gen) {
		return new HTMLParentContext($this, $gen->generator);
	}
	function __toString() {
		return $this->generator->__toString();
	}
}