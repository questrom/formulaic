<?php

use Sabre\Xml\XmlDeserializable;

interface Condition {
	public function evaluate($cd);
	public function getName();
	public function getCondition();
}

class IsCheckedCondition implements XmlDeserializable, Condition {
	use Configurable;
	function __construct($args) {
		$this->name = $args['name'];
	}
	function evaluate($cd) {
		return isset($cd->post[$this->name]) ? $cd->post[$this->name] === "on" : false;
	}
	function getName() {
		return $this->name;
	}
	function getCondition() {
		return 'is-checked';
	}
}

class IsNotCheckedCondition implements XmlDeserializable, Condition {
	use Configurable;
	function __construct($args) {
		$this->name = $args['name'];
	}
	function evaluate($cd) {
		return !(isset($cd->post[$this->name]) ? $cd->post[$this->name] === "on" : false);
	}
	function getName() {
		return $this->name;
	}
	function getCondition() {
		return 'is-not-checked';
	}
}

class IsRadioSelectedCondition implements XmlDeserializable, Condition {
	use Configurable;
	function __construct($args) {
		$this->name = $args['name'];
		$this->value = $args['value'];
	}
	function evaluate($cd) {
		return isset($cd->post[$this->name]) ? ($cd->post[$this->name] === $this->value) : false;
	}
	function getName() {
		return $this->name;
	}
	function getCondition() {
		return 'is-radio-selected:' . $this->value;
	}
}