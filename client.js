/* jshint undef: true, unused: true */
/* globals $, Pikaday */


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

	// $('.datetime').each(function() {
	// 	var elem = this;
	// 	new Pikaday({
	// 		field: $(elem).find('input')[0],
	// 		bound: false,
	// 		container: $(elem).find('.container')[0],
	// 		i18n: {
	// 		    previousMonth : '',
	// 		    nextMonth     : '',
	// 		    months        : ['January','February','March','April','May','June','July','August','September','October','November','December'],
	// 		    weekdays      : ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'],
	// 		    weekdaysShort : ['Sun','Mon','Tue','Wed','Thu','Fri','Sat']
	// 		}
	// 	});
	// });



	$('.datetime').each(function() {
		var $this = $(this),
			latest;

		function drawCal(date, showSel) {
			latest = date;
			date = date.clone();
			$this.find('.header').text(date.format('MMMM YYYY'));
			var monthStart = date.clone().startOf('month').startOf('week');
			$this.find('td button').each(function() {
				var $this = $(this);
				$this.text(monthStart.format('D'));
				$this.data('day', monthStart.toISOString());
				
				if(monthStart.isSame(date) && showSel) {
					$this.addClass('primary').removeClass('basic');
				} else if(monthStart.isSame(date, 'month')) {
					$this.removeClass('primary').addClass('basic');
				} else {
					$this.removeClass('primary').removeClass('basic');
				}

				monthStart.add('days', 1);
			});
		}

		drawCal( moment(), false );

		$this.find('td > button').click(function() {

			var setTo = moment( $(this).data('day') );
			if(setTo.isSame(latest, 'day')) {
				drawCal( setTo, false );
			} else {
				drawCal( setTo, true );
			}
			
		});

		$this.find('.left.button').click(function() {
			drawCal( latest.subtract(1, 'month'), false );
		});
		$this.find('.right.button').click(function() {
			drawCal( latest.add(1, 'month'), false );
		})

	});

});
