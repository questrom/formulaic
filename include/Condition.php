<?php


# A condition that can be used as the first child of a 'show-if' element.
interface Condition {
	public function evaluate($cd);
	public function getName();
	public function getCondition();
}

# A condition that is satisfied when a checkbox is checked.
class IsCheckedCondition implements Configurable, Condition {

	function __construct($args, $context) {
		$this->name = $args['name'];
	}
	function evaluate($cd) {
		return isset($cd->post[ $this->name ]) ? $cd->post[ $this->name ] === 'on' : false;
	}
	function getName() {
		return $this->name;
	}
	function getCondition() {
		return 'is-checked';
	}
}

# A condition that is satisfied when a checkbox is NOT checked.
class IsNotCheckedCondition implements Configurable, Condition {

	function __construct($args, $context) {
		$this->name = $args['name'];
	}
	function evaluate($cd) {
		return !(isset($cd->post[ $this->name ]) ? $cd->post[ $this->name ] === 'on' : false);
	}
	function getName() {
		return $this->name;
	}
	function getCondition() {
		return 'is-not-checked';
	}
}

# A condition that is satisfied when a radio button within a group is selected.s
class IsRadioSelectedCondition implements Configurable, Condition {

	function __construct($args, $context) {
		$this->name = $args['name'];
		$this->value = $args['value'];
	}
	function evaluate($cd) {
		return isset($cd->post[ $this->name ]) ? ($cd->post[ $this->name ] === $this->value) : false;
	}
	function getName() {
		return $this->name;
	}
	function getCondition() {
		return 'is-radio-selected:' . $this->value;
	}
}