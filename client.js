/* jshint undef: true, unused: true */
/* globals $, document, FormData */

function addPrompt(name, prompt) {
	$(document.getElementsByName(name))
		.add($('[data-validation-name="' + name + '"]'))
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


// Based on code from jquery.inputmask
$.extend($.inputmask.defaults.aliases, {
    'proper-datetime': {
        placeholder: "mm/dd/yyyy hh:mm am",
        alias: "datetime12", //reuse functionality of dd/mm/yyyy alias
        regex: {
            val2pre: function (separator) {
            	var escapedSeparator = $.inputmask.escapeRegex.call(this, separator);
            	return new RegExp("((0[13-9]|1[012])" + escapedSeparator + "[0-3])|(02" + escapedSeparator + "[0-2])");
            }, //daypre
            val2: function (separator) {
            	var escapedSeparator = $.inputmask.escapeRegex.call(this, separator);
            	return new RegExp("((0[1-9]|1[012])" + escapedSeparator + "(0[1-9]|[12][0-9]))|((0[13-9]|1[012])" + escapedSeparator + "30)|((0[13578]|1[02])" + escapedSeparator + "31)");
            }, //day
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


function handleRange() {
	$(this).parent().find('span.range-value').text($(this).val());
}

function handleBox() {
	var $this = $(this);
	var name = $this.attr('name');
	if(name.slice(-2) === '[]') {
		return;
	}
	$('[data-show-if-name="' + name + '"][data-show-if-condition="is-checked"]').toggle($this.is(':checked'));
	$('[data-show-if-name="' + name + '"][data-show-if-condition="is-not-checked"]').toggle(!$this.is(':checked'));
}

function handleRadio() {
	var $this = $(this);
	var name = $this.attr('data-radio-group-name'),
		value = $this.find(':checked').attr('value');
	$('[data-show-if-name="' + name + '"]').hide();
	$('[data-show-if-name="' + name + '"][data-show-if-condition="is-radio-selected:' + value + '"]').show();
}

function fixName(groupName, currentName, num) {
	return groupName + '[' + num + ']' + currentName.replace(/^(.*?)($|(?=\[))/,'[$1]');
}

function enableFormControls(root) {

	root.find('.add-item').click(function() {
		var $this = $(this);
		var template = $this.closest('.list-items').find('> script').text();

		var listCo = $this.closest('.list-component');
		var num = listCo.data('count');
		listCo.data('count', num + 1);

		var item = $($('<div>').html(template).text()),
			groupName = listCo.data('group-name');

		item.find('input[name], textarea[name]').attr('name', function(i, attr) {
			return fixName(groupName, attr, num);
		});

        item.find('[data-group-name]').attr('data-group-name', function(i, attr) {
            return fixName(groupName, attr, num);
        });

		item.find('[data-validation-name]').attr('data-validation-name', function(i, attr) {
			return fixName(groupName, attr, num);
		});

		item.find('[data-show-if-name]').attr('data-show-if-name', function(i, attr) {
			return fixName(groupName, attr, num);
		});

		item.insertBefore($this.closest('.segment'));


		item.find('.delete-btn').click(function() {
			$(this).closest('.segment').remove();
		});


		enableFormControls(item);
	});

	root.find('input[type=range]').on('input', handleRange).each(handleRange);
	root.find('.ui.dropdown').dropdown();
	root.find('input[type=checkbox]').on('change', handleBox).each(handleBox);
	root.find('[data-radio-group-name]').on('change', handleRadio).each(handleRadio);
	root.find("[data-inputmask]").inputmask();
}


$(function() {

	enableFormControls($('form'));


	function doFail() {
			$('.validation-error-message').hide();
			var submit = $('[data-submit=true]').removeClass('loading').removeAttr('disabled');
				$(submit).find('span').text('Try Again');
			$('.failure-modal').modal('show');
	}


	$('form').on('submit', function(e) {
		e.preventDefault();

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
				$('.ui.form').append($('<p>').html(x));
				console.log(x);
				doFail();
				return;
			}
			x = x.data;

			if(x.errors) {
				console.log(x.errors);
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




});
