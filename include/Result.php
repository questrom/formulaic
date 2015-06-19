<?php


abstract class Result {
	abstract function get();
	// abstract function __construct($value);
	// abstract function bind(callable $x);
	abstract function bind_err(callable $x);
}


abstract class Maybe { }

class Just extends Maybe {
	private $value;
	function __construct($value) {
		$this->value = $value;
	}
	function get() {
		return $this->value;
	}
	function bind(callable $x) {
		return $x($this->value);
	}
}

class Nothing extends Maybe {
	function get() {
		return null;
	}
	function bind(callable $x) {
		return $this;
	}
}