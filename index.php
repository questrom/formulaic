<?php
require('vendor/autoload.php');
$result = yaml_parse_file('forms/test.yml', 0, $ndocs);
$json = json_encode($result['fields']);
?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<link rel="stylesheet" href="vendor/semantic/ui/dist/semantic.css">
	<link rel="stylesheet" href="vendor/semantic/ui/dist/components/checkbox.css">
	<link rel="stylesheet" href="vendor/semantic/ui/dist/components/form.css">

  <script src="http://cdnjs.cloudflare.com/ajax/libs/jquery/2.0.3/jquery.js"></script>
  <script src="http://cdnjs.cloudflare.com/ajax/libs/jquery.address/1.6/jquery.address.js"></script>
	<script src="vendor/semantic/ui/dist/semantic.min.js"></script>
	
</head>
<body>
<!-- <div class="pusher"> -->
	<div class="ui page grid">
	    <div class="sixteen wide column">
	      	<div id="formholder">
	      	</div>
	    </div>
	</div>
	<!-- </div> -->
<script src="vendor/semantic/ui/dist/components/checkbox.js"></script>
<script src="vendor/semantic/ui/dist/components/form.js"></script>
	<script src="jquery.serializejson.js"></script>
	<!-- <script src="hyperscript-mod.js"></script> -->
	<script src="mercury.js"></script>

<script>
var js = <?=$json?>;
</script>

<!-- -->
<script>

var h = mercury.h;

// document.getElementById('formholder').appendChild(form(js));

function checkbox(o) {
	return h('.field',
		h('.ui.checkbox', [
			h('input', {name: o.name, type: 'checkbox'}),
			h('label', o.label)
		)
	);
}

function textbox(o) {

	return h('.field',
		h('.ui.input', [
			h('input.input', { name: o.name, type: 'text' }),
			h('label', o.label)
		])
	);
}

function form(o) {
	o = o.form;
	return h('form.ui.form', {action: 'submit.php', method: 'POST'}, [
		o.map(function(inner) {
			console.log('$I', inner);
			if(inner.type === "checkbox") {
				return checkbox(inner);
			} else {
				return textbox(inner);
			}
		}),
		h('input.submit.button', {type:'Submit', value:'hey'})
	]);
}


var semantic = {};

  semantic.validateForm = {};

 semantic.validateForm.ready = function() {
	$('.ui.checkbox').checkbox();
		$('.ui.form').form({
			tb01: {
				identifier: 'tb01',
			rules: [
				{
				      type   : 'empty',
		          prompt : 'Please enter a password'
		      }
			]
			}
		}, {
			inline: true, on: 'blur'
		});
};


$(document)
  .ready(function() {

	mercury.app(document.getElementById('formholder'), mercury.state({ form: mercury.value( js ) }), form);

  	semantic.validateForm.ready();
  })
;
</script>
</body>
</html>
