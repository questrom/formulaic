
<?php

require('vendor/autoload.php');


// Twig_Autoloader::register();

$loader = new Twig_Loader_Array(array(
"page" => <<<EOT
<div class="ui page grid">
	<div class="sixteen wide column">
		{% include 'form' %}
	</div>
</div>
EOT
,
"checkbox" => <<<EOT
<div class="field">
	<div class="ui checkbox">
		<input name="{{name}}" type="checkbox">
		<label>{{label}}</label>
	</div>
</div>
EOT
,
"textbox" => <<<EOT
<div class="field">	
	<input name="{{name}}" type="text">
	<label>{{label}}</label>
</div>
EOT
,
"form" => <<<EOT
<form action="submit.php" method="POST" class="ui form">
	{% for item in fields %}
		{% if item.type == "checkbox" %}
			{%include 'checkbox' with item %}
		{% else %}
			{%include 'textbox' with item %}
		{% endif %}
	{% endfor %}
	<input type="Submit" value="hey" class="submit button" />
</form>
EOT
));
$twig = new Twig_Environment($loader);




$result = yaml_parse_file('forms/test.yml', 0, $ndocs, array(
	'!checkbox' => function($v) { $v['type'] = 'checkbox'; return $v; },
	'!textbox' => function($v) { $v['type'] = 'textbox'; return $v; }
));



$page = $twig->render('page', $result);

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
	<?=$page?>
	<script src="client.js"></script>
</body>
</html>
