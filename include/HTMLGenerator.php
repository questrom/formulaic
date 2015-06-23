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

class HTMLGeneratorTagless extends HTMLGeneratorAbstract {

	function __construct($children = []) {
		$this->children = $children;
	}

	function add($arr) {
		return new HTMLGeneratorTagless(
			array_merge(
				$this->children,
				is_array($arr) ? $arr : [$arr]
			)
		);
	}

	function getText() {
		return implode( array_map(function($child) {
			if($child instanceof Component) {
				return $child->get(new HTMLGeneratorUnparented())->getText();
			} else {
				return $child->getText();
			}
		}, $this->children) );
	}

	function __get($name) {
		return new HTMLGenerator(new HTMLGeneratorTagless(), $name, []);
	}

	// Helper for appending text	
	function t($text) { return $this->add(new HTMLGeneratorText($text)); }
	// Helper for "if" statements
	function hif($cond) { return $cond ? new HTMLGeneratorTagless() : new HTMLGeneratorNull(); }
}

// Generates the inside of an HTML element
class HTMLGenerator extends HTMLGeneratorAbstract {
	function __construct( $tagless, $tag, $attrs) {
		$this->tag = $tag;
		$this->attrs = $attrs;
		$this->tagless = $tagless;
	}

	function __call($name, $args) {
		return new HTMLGenerator($this->tagless, $this->tag, array_merge($this->attrs, [ $name => $args[0] ] )  );
	}
	function data($key, $val) {
		return new HTMLGenerator($this->tagless, $this->tag, array_merge($this->attrs, [  'data-' . $key => $val ] ));
	}

	function add($arr) {
		return new HTMLGenerator($this->tagless->add($arr), $this->tag, $this->attrs);
	}

	function getText() {
		$att = '<' . $this->tag;
		foreach ($this->attrs as $key => $value) {
			$att .= ' ' . $key . '="' . htmlspecialchars($value) . '"';
		}
		$att .= '>';

		return $att . $this->tagless->getText() . '</' . $this->tag . '>';
	}


	function __get($name) { return new HTMLGenerator( new HTMLGeneratorTagless(), $name, [] ); }

	// Helper for appending text	
	function t($text) { return new HTMLGenerator($this->tagless->t($text), $this->tag, $this->attrs); }

	// Helper for "if" statements
	function hif($cond) { return $cond ? new HTMLGeneratorTagless() : new HTMLGeneratorNull(); }
}

class HTMLGeneratorParented extends HTMLGeneratorAbstract {
	function __construct($parent, $generator) {
		$this->parent = $parent;
		$this->generator = $generator;
	}

	function hif($cond) {
		return new HTMLGeneratorParented($this, $this->generator->hif($cond));
	}

	function __call($name, $args) { return new HTMLGeneratorParented( $this->parent, $this->generator->__call($name, $args)); }
	function t($text) { return new HTMLGeneratorParented( $this->parent, $this->generator->t($text)); }
	function add($arr) { return new HTMLGeneratorParented( $this->parent, $this->generator->add($arr)); }
	function data($key, $val) { return new HTMLGeneratorParented( $this->parent, $this->generator->data($key, $val)); }

	function ins($gen) {
		return new HTMLGeneratorParented($this, $gen->generator);
	}

	function __get($name) {
		if($name === 'end') {
			return $this->parent->add($this);
		} else {
			return new HTMLGeneratorParented($this, $this->generator->__get($name));
		}
	}
	
	function getText() { return $this->generator->getText(); }
}

class HTMLGeneratorUnparented extends HTMLGeneratorAbstract{
	function __construct($generator = null) {
		$this->generator = $generator ? $generator : new HTMLGeneratorTagless();
	}
	function __get($name) {
		return new HTMLGeneratorParented($this, $this->generator->__get($name));
	}
	function add($arr) {
		return new HTMLGeneratorUnparented($this->generator->add($arr));
	}
	function t($arr) {
		return new HTMLGeneratorUnparented($this->generator->t($arr));
	}
	function hif($arr) {
		return new HTMLGeneratorParented($this, $this->generator->hif($arr));
	}
	function ins($gen) {
		return new HTMLGeneratorParented($this, $gen->generator);
	}
	function getText() {
		return $this->generator->getText();
	}
}