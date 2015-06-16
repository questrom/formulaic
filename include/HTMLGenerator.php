<?php

abstract class Writer {
	abstract function append($other);
	abstract function getText();
	function write($other) {
		return $this->append(new StringWriter($other));
	}
}

class StringWriter extends Writer {
	private $text;
	function __construct($text = '') {
		$this->text = $text;
	}
	function append($other) {
		return new StringWriter( $this->text . $other->getText() );
	}
	function getText() {
		return $this->text;
	}
}

class NullWriter extends Writer {
	function __construct() {}
	function append($other) {
		return $this;
	}
	function getText() {
		return '';
	}
}

class MiddleWriter extends Writer {
	private $start;
	private $end;
	function __construct($start, $end) {
		$this->start = $start;
		$this->end = $end;
	}
	function append($other) {
		return new MiddleWriter($this->start->append($other), $this->end);
	}
	function getText() {
		return $this->start->write($this->end)->getText();
	}
}


// =============================================================================================================

abstract class HTMLGeneratorAbstract extends Writer {
	protected $text;
	protected $parent;

	abstract function hif($cond);
	abstract function t($text);
	abstract function add($arr);
	abstract function __get($name);
	
	function getText() { return $this->text->getText(); }
}


// =============================================================================================================

// Generates the opening tag of an HTML element
class HTMLGeneratorOpen extends HTMLGeneratorAbstract {
	function __construct($parent, $text) {
		$this->parent = $parent;
		$this->text = $text;
	}

	// Implement "Writer"
	function append($text) { return new HTMLGeneratorOpen($this->parent,  $this->text->append($text)); }

	// Helpers for appending attributes
	function __call($name, $args) { return $this->write(' ' . $name . '="' . htmlspecialchars($args[0]) . '"'); }
	function data($key, $val) { return $this->write(' data-' . $key . '="' . htmlspecialchars($val) . '"'); }

	// Automatic closing of opening tags
	protected function close() { return new HTMLGenerator( $this->parent, $this->text->write('>') ); }
	function hif($cond) { return $this->close()->hif($cond); } // Html IF
	function t($text) { return $this->close()->t($text); }
	function add($arr) { return $this->close()->add($arr); }
	function __get($name) { return $this->close()->__get($name); }
}

// Generates the inside of an HTML element
class HTMLGenerator extends HTMLGeneratorAbstract {
	function __construct($parent = null, $text = null) {
		$this->parent = $parent;
		$this->text = ($text === null) ? new StringWriter() : $text;
	}

	function add($arr) {
		if(is_array($arr)) {
			return array_reduce($arr, function($a, $b) {
				return $a->add($b);
			}, $this);
		} else {
			return $this->append( ($arr instanceof Component) ? $arr->get(new HTMLGenerator()) : $arr );
		}
	}

	function __get($name) {
		if($name === 'end') {
			return $this->parent->add($this->text);
		} else {
			return new HTMLGeneratorOpen( $this, new MiddleWriter( new StringWriter('<' . $name), '</' . $name . '>')  );
		}
	}

	// Implement "Writer"
	function append($text) { return new HTMLGenerator($this->parent, $this->text->append($text)); }

	// Helper for appending text	
	function t($text) { return $this->write(htmlspecialchars($text)); }

	// Helper for "if" statements
	function hif($cond) { return new HTMLGenerator($this, $cond ? new StringWriter() : new NullWriter()); }
}
