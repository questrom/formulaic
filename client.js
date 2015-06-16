/* jshint undef: true, unused: true */
/* globals $, moment */


$(function() {


	// $('.ui.checkbox').checkbox();
	var form = $('.ui.form').form({}, {
		inline: true

	});

	$('.ui.dropdown').dropdown({
		metadata: {
			defaultText: 'abc',
			defaultValue: 'null'
		}
	});

	$('.checkbox').checkbox();

	$('[data-submit=true]').on('click', function() {


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

	// Custom date-time widget

	$('.datetime').each(function() {
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
