<?php


abstract class HTMLGeneratorAbstract {

	protected abstract function write($text);
	abstract function t($text);
	abstract function add($arr);
	abstract function __get($name);
}

class StringWriter {
	private $text;
	function __construct($text) {
		$this->text = $text;
	}
	function write($other) {
		return new StringWriter( $this->text . $other );
	}
	function append($other) {
		return new StringWriter( $this->text . $other->getText() );
	}
	function getText() {
		return $this->text;
	}
}

class NullWriter {
	function __construct() {}
	function write($other) {
		return $this;
	}
	function append($other) {
		return $this;
	}
	function getText() {
		return '';
	}
}

class MiddleWriter {
	private $start;
	private $end;
	function __construct($start, $end) {
		$this->start = $start;
		$this->end = $end;
	}
	function write($other) {
		return new MiddleWriter($this->start->write($other), $this->end);
	}
	function append($other) {
		return new MiddleWriter($this->start->append($other), $this->end);
	}
	function getText() {
		return $this->start->write($this->end)->getText();
	}
}

class HTMLGeneratorOpen extends HTMLGeneratorAbstract {
	function __construct($name, $parent, $text) {
		if(!is_string($name)) {
			throw new Exception();
		}
		$this->parent = $parent;
		$this->name = $name;
		$this->text = $text;
	}

	protected function close() {
		return new HTMLGeneratorTagless( $this->name, $this->parent, (new MiddleWriter( $this->text, '</' . $this->name . '>'))->write('>') );
	}

	protected function write($text) {
		return new HTMLGeneratorOpen( $this->name, $this->parent,  $this->text->write($text) );
	}

	function __call($name, $args) {
		return $this->write(' ' . $name . '="' . htmlspecialchars($args[0]) . '"');
	}

	function data($key, $val) {
		return $this->write(' data-' . $key . '="' . htmlspecialchars($val) . '"');
	}

	function hif($cond) { return $this->close()->hif($cond); } // Html IF
	function t($text) { return $this->close()->t($text); }
	function add($arr) { return $this->close()->add($arr); }
	function __get($name) { return $this->close()->__get($name); }
}

class HTMLGeneratorTagless extends HTMLGeneratorAbstract {
	function __construct($name, $parent, $text) {

		$this->parent = $parent;
		$this->text = $text;
	}


	function t($text) {
		return $this->write(htmlspecialchars($text));
	}

	protected function write($text) {
		return new HTMLGeneratorTagless( $this->name, $this->parent,  $this->text->write($text));
	}
	protected function append($text) {
		return new HTMLGeneratorTagless( $this->name, $this->parent,  $this->text->append($text));
	}

	function hif($cond) {
		if(!$cond) {
			return new HTMLGeneratorOpen('', $this, new NullWriter() );
		} else {
			return new HTMLGeneratorTagless('', $this, new StringWriter('') );
		}
	}

	function add($arr) {
		if(is_array($arr)) {
			$x = $this;
			foreach ($arr as $item) {
				$x = $x->add($item);
			}	
			return $x;
		} else {
			return $this->append( ($arr instanceof Component) ? $arr->get(new HTMLGenerator()) : $arr );
		}
	}

	function __get($name) {
		if($name === 'end') {
			return $this->parent->add( $this->text );
		} else {
			return new HTMLGeneratorOpen($name, $this, new StringWriter('<' . $name) );
		}
	}

}

class HTMLGenerator {

	function __get($name) {
		return new HTMLGeneratorOpen($name, $this, new StringWriter('<' . $name) );
	}
	function add($arr) {
		return ($arr instanceof Component) ? $arr->get($this) : $arr;	
	}
}
