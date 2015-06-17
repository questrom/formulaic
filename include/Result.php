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
}
