/* jshint undef: true, unused: true */
/* globals $ */


$(function() {


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
		inline: true
	});
});
