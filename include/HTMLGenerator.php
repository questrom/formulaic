<?php

abstract class HTMLGeneratorAbstract {

	protected $parent;

	abstract function hif($cond);
	abstract function t($text);
	abstract function add($arr);
	abstract function __get($name);

	abstract function getText();

}


// =============================================================================================================

class HTMLGeneratorNull extends HTMLGeneratorAbstract {
	function add($arr) { return $this; }
	function __get($name) { return $this; }
	function ins($gen) { return $this; }
	function getText() { return ''; }
	function append($text) { return $this; }
	function t($text) { return $this; }
	function hif($cond) { return $this; }
	function data($key, $val) { return $this; }
	function __call($name, $args) { return $this; }
}

class HTMLGeneratorText {
	function __construct($text) { $this->text = $text; }
	function getText() { return htmlspecialchars($this->text); }
}

class HTMLContentGenerator extends HTMLGeneratorAbstract {

	function __construct($children = []) {
		$this->children = $children;
	}

	function add($arr) {
		return new HTMLContentGenerator(
			array_merge(
				$this->children,
				is_array($arr) ? $arr : [$arr]
			)
		);
	}

	function getText() {
		return implode( array_map(function($child) {
			if($child instanceof Component) {
				return $child->get(new HTMLParentlessContext())->getText();
			} else {
				return $child->getText();
			}
		}, $this->children) );
	}

	function __get($name) {
		return new HTMLTagGenerator(new HTMLContentGenerator(), $name, []);
	}

	// Helper for appending text
	function t($text) { return $this->add(new HTMLGeneratorText($text)); }
	// Helper for "if" statements
	function hif($cond) { return $cond ? new HTMLContentGenerator() : new HTMLGeneratorNull(); }
}

// Generates the inside of an HTML element
class HTMLTagGenerator extends HTMLGeneratorAbstract {
	function __construct( $tagless, $tag, $attrs) {
		$this->tag = $tag;
		$this->attrs = $attrs;
		$this->tagless = $tagless;
	}

	function __call($name, $args) {
		return new HTMLTagGenerator($this->tagless, $this->tag, array_merge($this->attrs, [ $name => $args[0] ] )  );
	}
	function data($key, $val) {
		return new HTMLTagGenerator($this->tagless, $this->tag, array_merge($this->attrs, [  'data-' . $key => $val ] ));
	}

	function add($arr) {
		return new HTMLTagGenerator($this->tagless->add($arr), $this->tag, $this->attrs);
	}

	function getText() {
		$att = '<' . $this->tag;
		foreach ($this->attrs as $key => $value) {
			$att .= ' ' . $key . '="' . htmlspecialchars($value) . '"';
		}
		$att .= '>';

		return $att . $this->tagless->getText() . '</' . $this->tag . '>';
	}


	function __get($name) { return new HTMLTagGenerator( new HTMLContentGenerator(), $name, [] ); }

	// Helper for appending text
	function t($text) { return new HTMLTagGenerator($this->tagless->t($text), $this->tag, $this->attrs); }

	// Helper for "if" statements
	function hif($cond) { return $cond ? new HTMLContentGenerator() : new HTMLGeneratorNull(); }
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
	function t($text) { return new HTMLParentContext( $this->parent, $this->generator->t($text)); }
	function add($arr) { return new HTMLParentContext( $this->parent, $this->generator->add($arr)); }
	function data($key, $val) { return new HTMLParentContext( $this->parent, $this->generator->data($key, $val)); }

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

	function getText() { return $this->generator->getText(); }
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
	function t($arr) {
		return new HTMLParentlessContext($this->generator->t($arr));
	}
	function hif($arr) {
		return new HTMLParentContext($this, $this->generator->hif($arr));
	}
	function ins($gen) {
		return new HTMLParentContext($this, $gen->generator);
	}
	function getText() {
		return $this->generator->getText();
	}
}