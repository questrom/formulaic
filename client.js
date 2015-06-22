/* jshint undef: true, unused: true */
/* globals $, moment, document */


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

	$('[data-submit=true]').on('click', function() {

		removePrompts();
		$('[data-submit=true]').addClass('loading').attr('disabled', true);

		$.ajax('validate.php', {
			data: $('form').serialize(),
			method: 'POST'	
		}).done(function(x) {
			try {
				x = JSON.parse(x);
			} catch(e) {
				console.log(x);
				doFail();
				return;
			}
			if(x.v) {
				$('.validation-error-message').show();
				var submit = $('[data-submit=true]').removeClass('loading').removeAttr('disabled');
				$(submit).find('span').text('Try Again');

				var results = x.v;

				for(var k in results) {
					addPrompt(k, results[k]);	
				}
			} else {
				$('.validation-error-message').hide();
				$('[data-submit=true]').removeClass('loading').removeAttr('disabled').find('span').text('Submit Again');


				var output = x.data;
				if(output) {
					$('.ui.form').append($('<p>').html(output));
				}
				$('.success-modal').modal('show');
			}
		}).fail(function(x) {
			doFail();
		});
	});


	// Custom date-time widget

	$('.datepicker').each(function() {
		var $this = $(this),
			latestMonth,
			hidden = $this.find('input[type=hidden]');

		function drawCal(month, date) {
			latestMonth = month;
			hidden.val(date && date.format('YYYY-MM-DD'));

			$this.closest('.dropdown').find('> .text').text(date ? date.format('YYYY-MM-DD') : 'Select a date...' );

			// Set the header

			$this.find('.header').text(month.format('MMMM YYYY'));

			// Label individual days

			var monthStart = month.clone().startOf('month').startOf('week');
			$this.find('td button').each(function() {
				$(this).text(monthStart.format('D'))
						.data('day', monthStart.format('YYYY-MM-DD'))
						.toggleClass('primary', monthStart.isSame(date))
						.toggleClass('basic',  !monthStart.isSame(date))
						.toggleClass('other-month', !monthStart.isSame(month, 'month'));

				monthStart.add(1, 'day');
			});
		}

		drawCal(moment(), null);

		$this.find('td > button').click(function() {
			var $this = $(this);
			var setTo = moment($this.data('day'));
			if(setTo.isSame(hidden.val(), 'day')) {
				drawCal(setTo, null);
			} else {
				drawCal(setTo, setTo);
			}			
		});

		$this.find('.left.button').click(function() {
			drawCal(latestMonth.subtract(1, 'month'), null);
		});

		$this.find('.right.button').click(function() {
			drawCal(latestMonth.add(1, 'month'), null);
		});

	});

});
