
<?php

require('vendor/autoload.php');
require('parts.php');

$result = yaml_parse_file('forms/test.yml', 0, $ndocs, array(
	'!checkbox' => function($v) { return new Checkbox($v); },
	'!textbox' => function($v) { return new Textbox($v); }
));

$page = new Page($result);

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
	<?=$page->get()?>
	<script src="client.js"></script>
</body>
</html>
