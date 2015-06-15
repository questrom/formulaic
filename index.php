
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
	<link rel="stylesheet" href="pikaday.css">
	<style>
		.pika-single { display:table;}
		.pika-label { padding-top:0; padding-bottom:0; }

		.pika-prev, .pika-next { height:auto; background:transparent; font-family:"Icons"; text-indent:0; font-size:18px; line-height:20px;}
		.pika-prev:before {
		  content: "\f0d9";
		}

		.pika-next:before {
		  content: "\f0da";
		}

		.datetime td, .datetime th { padding:0 !important; }
		.datetime button { border:none !important;}

		.datetime .header { margin:0 !important; line-height: 30px !important; text-align: center; }
		.datetime thead button { border-radius: 0 !important; }
		.datetime tbody button { padding:10px !important; transition:none !important;}
	</style>
</head>
<body>
	<?=$page->get(new HTMLGenerator())?>

	<script src="http://cdnjs.cloudflare.com/ajax/libs/jquery/2.0.3/jquery.js"></script>
	<script src="vendor/moment/moment/moment.js"></script>
	<script src="vendor/semantic/ui/dist/semantic.js"></script>
	<script src="pikaday.js"></script>
	<script src="pikaday.jquery.js"></script>
	<script src="client.js"></script>
</body>
</html>
