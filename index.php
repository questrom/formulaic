
<?php

require('vendor/autoload.php');
require('parts.php');

$result = parse_yaml('forms/test.yml');
$page = new Page($result);

?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<title>Form</title>
	<link rel="stylesheet" href="vendor/semantic/ui/dist/semantic.css">
	<link rel="stylesheet" href="styles.css">
</head>
<body>
	<?=$page->get(new HTMLGenerator())->getText()?>
	<script src="http://cdnjs.cloudflare.com/ajax/libs/jquery/2.0.3/jquery.js"></script>
	<script src="vendor/moment/moment/moment.js"></script>
	<script src="vendor/semantic/ui/dist/semantic.js"></script>
	<script src="client.js"></script>
</body>
</html>
