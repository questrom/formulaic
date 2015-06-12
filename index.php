
<?php

require('vendor/autoload.php');

class Checkbox {	
	public $label;
	public $name;
	function __construct($args) {
		$this->label = $args['label'];
		$this->name = $args['name'];
	}
	function render() {
		// TODO fix xss
		return '<div class="field"> <div class="ui  checkbox"> <input name="' . $this->name. '" type="checkbox"> <label>' . $this->label . '</label> </div> </div>';
	}
}

class Textbox {
	public $label;
	public $name;
	function __construct($args) {
		$this->label = $args['label'];
		$this->name = $args['name'];
	}
	function render() {
		// TODO fix xss
		return '<div class="field"> <input name="' . $this->name. '" type="text"> <label>' . $this->label . '</label> </div>';
	}
}

class Form {
	public $items;
	function __construct($args) {
		$this->items = $args;
	}
	function render() {
		$text = '<form action="submit.php" method="POST" class="ui form">';
		foreach($this->items as $k => $x) {
			if($x['type'] == 'checkbox') {
				$x = new Checkbox($x);
			} else {
				$x = new Textbox($x);
			}
			$text .= $x->render();
		}
		return $text . ' <input type="Submit" value="hey" class="submit button" /> </form>';
	}
}

class Page {
	public $form;
	public $json;
	function __construct($form, $json) {
		$this->form = $form;
		$this->json = $json;
	}
	function render() {
		return '<div class="ui page grid"><div class="sixteen wide column">' . $this->form->render() . '</div></div>';
	}
}


$result = yaml_parse_file('forms/test.yml', 0, $ndocs);


$json = json_encode($result);

$form = new Form($result['fields']);

$page = new Page($form, $json);

?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<title>Form</title>
	<link rel="stylesheet" href="vendor/semantic/ui/dist/semantic.css">
	<script src="http://cdnjs.cloudflare.com/ajax/libs/jquery/2.0.3/jquery.js"></script>
	<script src="vendor/semantic/ui/dist/semantic.js"></script>
</head>
<body>
	<?=$page->render()?>
	<script src="client.js"></script>
</body>
</html>
