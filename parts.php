<?php


class ValidationError {

}

class Component {

	function get() {
		ob_start();
		$this->render();
		return ob_get_clean();
	}
}

class Checkbox extends Component {	
	public $label;
	public $name;
	function __construct($args) {
		$this->label = new Text($args['label']);
		$this->name = new Text($args['name']);
	}
	function render() {
?>
		<div class="field">
			<div class="ui checkbox">
				<input name="<?=$this->name->get()?>" type="checkbox">
				<label><?=$this->label->get()?></label>
			</div>
		</div>
<?php
	}
	function validate() {
		return null;
	}
}

class Text extends Component {
	function __construct($str) {
		$this->str = $str;
	}
	function render() {
		echo htmlspecialchars($this->str); //set options?
	}
}

class Textbox extends Component {
	public $label;
	public $name;
	public $required;
	function __construct($args) {
		$this->label = new Text($args['label']);
		$this->name = new Text($args['name']);
		$this->required = $args['required'];
	}
	function render() {
		// TODO FIX XSS HERE AND ELSEWHERE
?>
	<div class="field">
		<div class="ui input">
			<input name="<?=$this->name->get()?>" type="text" data-required="<?=$this->required ? 'true' : 'false'?>">
			<label><?=$this->label->get()?></label>
		</div>
	</div>
<?php
	}
	function validate($against) {
		if($this->required && $against == "") {
			return "Required field cannot be empty";
		} else {
			return null;
		}
	}
}

class Form extends Component {
	public $items;
	function __construct($args) {
		$this->items = $args;
	}
	function render() {
?>
	<form action="submit.php" method="POST" class="ui form">
		<?php foreach($this->items as $x) { ?>
			<?=$x->get()?>
		<?php } ?>
		<input type="button" value="hey" class=" button" />
	</form>
<?php
	}
	function validate($against) {
		$total = [];
		foreach($this->items as $x) {
			$result = $x->validate( isset($against[$x->name->str]) ? $against[$x->name->str] : null  );
			if($result != null) {
				$total[$x->name->str] = $result;
			}
		}
		return $total;
	}
}

class Page extends Component {
	public $form;
	function __construct($yaml) {
		$this->form = new Form($yaml['fields']);
	}
	function render() {
?>
<div class="ui page grid">
	<div class="sixteen wide column">
		<?=$this->form->get()?>
	</div>
</div>
<?php
	}
	function validate($against) {
		return $this->form->validate($against);
	}
}
