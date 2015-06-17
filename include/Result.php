<?php


abstract class Result { }

class Err extends Result {
	private $value;
	function __construct($value) {
		$this->value = $value;
	}
	function get() {
		return $this->value;
	}
	function bind(callable $x) {
		return $this;
	}
	function bind_err(callable $x) {
		return $x($this->value);
	}
}

class Ok extends Result {
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
	function bind_err(callable $x) {
		return $this;
	}
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