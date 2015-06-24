/* jshint undef: true, unused: true */
/* globals $, document, FormData */


function addPrompt(name, prompt) {
	$(document.getElementsByName(name))
		.add($(document.getElementsByName(name + '[]')))
		.closest('.field:not(.not-validation-root), .validation-root')
		.addClass('error')
		.append(
			$('<div>')
				.addClass('ui red pointing prompt label')
				.text(prompt)
		);
}

function removePrompts() {
	$('.red.prompt').remove();
	$('.field.error, .validation-root.error').removeClass('error');
}

$(function() {

	// Based on code from jquery.inputmask
    $.extend($.inputmask.defaults.aliases, {
        'proper-datetime': {
            placeholder: "mm/dd/yyyy hh:mm am",
            alias: "datetime12", //reuse functionality of dd/mm/yyyy alias
            regex: {
                val2pre: function (separator) { var escapedSeparator = $.inputmask.escapeRegex.call(this, separator); return new RegExp("((0[13-9]|1[012])" + escapedSeparator + "[0-3])|(02" + escapedSeparator + "[0-2])"); }, //daypre
                val2: function (separator) { var escapedSeparator = $.inputmask.escapeRegex.call(this, separator); return new RegExp("((0[1-9]|1[012])" + escapedSeparator + "(0[1-9]|[12][0-9]))|((0[13-9]|1[012])" + escapedSeparator + "30)|((0[13578]|1[02])" + escapedSeparator + "31)"); }, //day
                val1pre: new RegExp("[01]"), //monthpre
                val1: new RegExp("0[1-9]|1[012]") //month
            },
            leapday: "02/29/",
            onKeyDown: function (e) {
                var $input = $(this);
                if (e.ctrlKey && e.keyCode == $.inputmask.keyCode.RIGHT) {
                    var today = new Date();
                    $input.val((today.getMonth() + 1).toString() + today.getDate().toString() + today.getFullYear().toString());
                    $input.triggerHandler('setvalue.inputmask');
                }
            }
        }
    });

	$.fn.modal.settings.transition='scale';


	$('.ui.dropdown').dropdown({
		metadata: {
			defaultText: 'abc',
			defaultValue: 'null'
		}
	});

	function doFail() {
			$('.validation-error-message').hide();
			var submit = $('[data-submit=true]').removeClass('loading').removeAttr('disabled');
				$(submit).find('span').text('Try Again');
			$('.failure-modal').modal('show');
	}

	$('.checkbox').checkbox();

	function handleBox() {
		var $this = $(this);
		var name = $this.attr('name');
		if(name.slice(-2) === '[]') {
			return;
		}
		$('[data-show-if=' + name + ']').toggle($this.is(':checked'));
	}

	$('input[type=checkbox]').on('change', handleBox).each(handleBox);

	$('[data-submit=true]').on('click', function() {

		removePrompts();
		$('[data-submit=true]').addClass('loading').attr('disabled', true);

		var formData = new FormData($('form')[0]);

		$.ajax('submit.php', {
			data: formData,
			method: 'POST',
			// http://stackoverflow.com/questions/10899384/uploading-both-data-and-files-in-one-form-using-ajax
			contentType: false,
			cache: false,
			processData: false
		}).done(function(x) {
			try {
				x = JSON.parse(x);
			} catch(e) {
				console.log(x);
				doFail();
				return;
			}
			if(x.errors) {
				$('.validation-error-message').show();
				var submit = $('[data-submit=true]').removeClass('loading').removeAttr('disabled');
				$(submit).find('span').text('Try Again');

				var results = x.errors;

				for(var k in results) {
					addPrompt(k, results[k]);	
				}
			} else {
				$('.validation-error-message').hide();
				$('[data-submit=true]').removeClass('loading').removeAttr('disabled').find('span').text('Submit Again');


				var output = x.debugOutput;
				if(output) {
					$('.ui.form').append($('<p>').html(output));
				}
				$('.success-modal').modal('show');
			}
		}).fail(function() {
			doFail();
		});
	});





	$("[data-inputmask]").inputmask();
	
});
