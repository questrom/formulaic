/* jshint undef: true, unused: true */
/* globals $ */


$(function() {


	// $('.ui.checkbox').checkbox();
	var form = $('.ui.form').form({}, {
		inline: true
	});


	$('[value=hey]').on('click', function() {


		$('.red.prompt').remove();
		$('.field.error').removeClass('error');

		$.ajax('validate.php', {
			data: $('form').serialize(),
			method: 'POST'	
		}).done(function(x) {
			var results = JSON.parse(x).v, valid = true;
			for(var k in results) {
				form.form('add prompt', k, [ results[k] ]);
				valid = false;
			}
			if(valid) {
				form.submit();
			}
		});

	});
});
