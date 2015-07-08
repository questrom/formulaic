<?php


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