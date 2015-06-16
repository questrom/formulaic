
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
	<style>
		.datetime td, .datetime th { padding:0 !important; }
		.datetime button { border:none !important;}
		.datetime .header { margin:0 !important; line-height: 30px !important;  }
		.datetime thead button { border-radius: 0 !important; }
		.datetime tbody button { padding:10px 20px !important; transition:none !important;}
		.other-month.ui.basic.button { background:#ddd !important;}
		.datetime table.ui.table { margin:0 !important;}
		.ui.page.grid { max-width: 600px !important; padding:0 !important; margin:0 auto !important;}
	</style>
</head>
<body>
	<?=$page->get(new HTMLGenerator())->getText()?>

	<script src="http://cdnjs.cloudflare.com/ajax/libs/jquery/2.0.3/jquery.js"></script>
	<script src="vendor/moment/moment/moment.js"></script>
	<script src="vendor/semantic/ui/dist/semantic.js"></script>
	<script src="client.js"></script>
</body>
</html>
