<?php

# This file contains a number of Renderables used to create various parts of forms.
# Some of them are used on other pages as well.


# Labels (with possible sublabels)
class Label implements Renderable {
	function __construct($field, $sublabel = '') {
		$this->f = $field;
		$this->sublabel = isset($this->f->customSublabel) ? $this->f->customSublabel : $sublabel;
	}
	function render() {
		return h()
		->label
			->span->c($this->f->label)->end
		->end
		->c(new PossibleSublabel($this->sublabel, false));
	}
}

# A sublabel (if one has been provided)
class PossibleSublabel implements Renderable {
	function __construct($sublabel, $right = false) {
		$this->sublabel = $sublabel;
		$this->right = $right;
	}
	function render() {
		if($this->sublabel) {
			return h()
			->p->class('sublabel')->c($this->sublabel)->end;
		} else {
			return null;
		}
	}
}

# A header, excluding stuff specific to whether the header is being placed
# within a group or not.
class BaseHeaderFormPart implements Renderable {
	public function __construct($field) { $this->f = $field; }
	function render() {
		$inside = h()
		->c($this->f->text)
		->c($this->f->subhead === null ? null :
			h()->div->class('sub header')->c($this->f->subhead)->end
		);
		return $this->f->icon === null ? $inside :
			h()
			->i->class($this->f->icon . ' icon')->end
			->div->class('content')
				->c($inside)
			->end
		;
	}
}

# A notice, excluding stuff specific to whether the header is being placed
# within a group or not.
class BaseNoticeFormPart implements Renderable {
	public function __construct($field) { $this->f = $field; }
	function render() {
		return h()
		->c($this->f->icon === null ? null :
			h()
			->i->class($this->f->icon . ' icon')->end
		)
		->div->class('content')
			->c($this->f->header === null ? null :
				h()->div->class('header')
					->c($this->f->header)
				->end
			)
			->p->c($this->f->text)->end
			->c(
				$this->f->list === null ? null :
				h()->ul->class('list')
					->c(array_map(
						function($item) {
							return h()->li->c($item)->end;
						},
						$this->f->list === null ? [] : $this->f->list
					))
				->end
			)
		->end;
	}
}


# A header outside of a group
class HeaderFormPart implements Renderable {
	public function __construct($field) { $this->f = $field; }
	function render() {
		$size = ($this->f->size === null) ? 1 : $this->f->size;
		return h()
		->{'h' . $size}->class('ui header')
			->c(
				new BaseHeaderFormPart($this->f)
			)
		->end;
	}
}

# A header inside of a group
class GroupHeaderFormPart implements Renderable {
	public function __construct($field) { $this->f = $field; }
	function render() {
		$size = ($this->f->size === null) ? 5 : $this->f->size;
		return h()
			->{'h' . $size}->class('ui header attached')
				->c(
					new BaseHeaderFormPart($this->f)
				)
			->end;
	}
}

# A notice outside of a group
class NoticeFormPart implements Renderable {
	public function __construct($field) { $this->f = $field; }
	function render() {

		return h()
		->div
			->class(
				'ui message floating ' .
				($this->f->icon === null ? '' : ' icon') .
				($this->f->ntype ? (' ' . $this->f->ntype) : '')
			)
			->c(
				new BaseNoticeFormPart($this->f)
			)
		->end;
	}
}

# A notice outside of a group
class GroupNoticeFormPart implements Renderable {
	public function __construct($field) { $this->f = $field; }
	function render() {

		return h()
		->div
			->class(
				'ui message attached ' .
				($this->f->icon === null ? '' : ' icon') .
				($this->f->ntype ? (' ' . $this->f->ntype) : '')
			)
			->c(
				new BaseNoticeFormPart($this->f)
			)
		->end;
	}
}


# A generic input field
class InputFormPart implements Renderable {
	# $field - the form field being rendered
	# $type - the type attribute of the input
	# $icon - the icon going inside the input
	# $mask - the input mask
	# $sublabel - the sublabel to use
	function __construct($field, $type, $icon = null, $mask = null, $sublabel = null) {
		$this->f = $field;
		$this->type = $type;
		$this->icon = $icon;
		$this->mask = $mask;
		$this->sublabel = $sublabel;
	}
	function render() {
		return h()
		->div->class('ui field ' . ($this->f->required ? 'required' : ''))
			->c(new Label($this->f, $this->sublabel))
			->div->class($this->icon ? 'ui left icon input' : 'ui input')
				->c($this->icon === null ? null :
					h()
					->i->class('icon ' . $this->icon)->end
				)
				->input
					->type($this->type)
					->name($this->f->name)
					->data('inputmask', $this->mask, $this->mask !== null)
				->end
			->end
		->end;
	}
}

# A number input
class NumberFormPart implements Renderable {
	function __construct($field) { $this->f = $field; }
	function render() {
		return h()
		->div->class('ui field ' . ($this->f->required ? 'required' : ''))
			->c(new Label($this->f))
			->div->class('ui input')
				->input
					->type('number')
					->name($this->f->name)
					->min($this->f->min, is_finite($this->f->min) )
					->max($this->f->max, is_finite($this->f->max) )
					->step($this->f->integer ? '1' : 'any')
				->end
			->end
		->end;
	}
}

# A dropdown
class DropdownFormPart implements Renderable {
	public function __construct($field) { $this->f = $field; }
	function render() {
		return h()
		->div->class('field ' . ($this->f->required ? ' required' : ''))
			->c(new Label($this->f))
			->div->class('ui fluid dropdown selection')
				->input->name($this->f->name)->type('hidden')->value('')->end
				->div->class('default text')->c('Please choose an option...')->end
				->i->class('dropdown icon')->end
				->div->class('menu')
					->c(array_map(
						function($v) {
							return h()
							->div->class('item')->data('value', $v)
								->c($v)
							->end;
						},
						$this->f->options
					))
				->end
			->end
		->end;
	}
}

# A single radio button
class RadioButton implements Renderable {
	function __construct($name, $value) {
		$this->name = $name;
		$this->value = $value;
	}
	public function render() {
		return h()
		->div->class('field not-validation-root')
			->div->class('ui radio checkbox')
				->input->name($this->name)->type('radio')->value($this->value)->end
				->label->c($this->value)->end
			->end
		->end;
	}
}

# A set of radio buttons
class RadiosFormPart implements Renderable {
	public function __construct($field) { $this->f = $field; }
	public function render() {
		return h()
		->div->class('grouped fields validation-root ' . ($this->f->required ? 'required' : ''))
			->data('radio-group-name', $this->f->name)
			->c(new Label($this->f))
			->c(
				array_map(
					function($v) { return new RadioButton($this->f->name, $v); },
					$this->f->options
				)
			)
		->end;
	}
}

# A textarea
class TextareaFormPart implements Renderable {
	public function __construct($field) { $this->f = $field; }
	function render() {
		return h()
		->div->class('field ' . ($this->f->required ? ' required' : ''))
			->c(new Label($this->f))
			->textarea
				->name($this->f->name)
				->maxlength($this->f->maxLength, is_finite($this->f->maxLength))
			->end
		->end;
	}
}

# A date/time picker
class DateTimePickerFormPart implements Renderable {
	public function __construct($field) { $this->f = $field; }
	function render() {
		$sublabel = '';

		if(isset($this->f->max) && isset($this->f->min)) {
			$sublabel = 'Please provide a date and time between ' . df($this->f->min) . ' and ' . df($this->f->max) . '.';
		} else if (isset($this->f->max)) {
			$sublabel = 'Please provide a date and time no later than ' . df($this->f->max) . '.';
		} else if(isset($this->f->min)) {
			$sublabel = 'Please provide a date and time no earlier than ' . df($this->f->min) . '.';
		}

		return h()
		->div->class('field ' . ($this->f->required ? ' required' : ''))
			->c(new Label($this->f, $sublabel))
			->div->class('ui left icon input')
				->i->class('calendar icon')->end
				->input->type('text')->name($this->f->name)->data('inputmask', " 'alias': 'proper-datetime' ")->end
			->end
		->end;
	}
}

# A time picker
class TimeInputFormPart implements Renderable {
	public function __construct($field) { $this->f = $field; }
	function render() {
		$sublabel = '';

		if(isset($this->f->max) && isset($this->f->min)) {
			$sublabel = 'Please provide a time between ' . ($this->f->min) . ' and ' . ($this->f->max) . '.';
		} else if (isset($this->f->max)) {
			$sublabel = 'Please provide a time no later than ' . ($this->f->max) . '.';
		} else if(isset($this->f->min)) {
			$sublabel = 'Please provide a time no earlier than ' . ($this->f->min) . '.';
		}

		return h()
		->div->class('field ' . ($this->f->required ? ' required' : ''))
			->c(new Label($this->f, $sublabel))
			->div->class('ui left icon input')
				->i->class('clock icon')->end
				->input
					->type('text')
					->name($this->f->name)
					->data('inputmask', " 'alias': 'h:s t', 'placeholder': 'hh:mm am' ")
				->end
			->end
		->end;
	}
}

# A checkbox
class CheckboxFormPart implements Renderable {
	public function __construct($field) { $this->f = $field; }
	function render() {
		return h()
		->div->class('field ' . ($this->f->mustCheck ? 'required' : ''))
			->div->class('ui checkbox')
				->input->type('checkbox')->name($this->f->name)->end
				->c(new Label($this->f))
			->end
		->end;
	}
}

# A show-if
class ShowIfComponentFormPart implements Renderable {
	public function __construct($field) { $this->f = $field; }
	function render() {
		return h()
			->div
				# Expose information about the condition to the client JS code
				->data('show-if-name', $this->f->condition->getName())
				->data('show-if-condition', $this->f->condition->getCondition())
				->c($this->f->item)
			->end;
	}
}

# A range input
class RangeFormPart implements Renderable {
	public function __construct($field) { $this->f = $field; }
	function render() {
		return h()
		->div->class('ui field')
			->c(new Label($this->f))
			->div
				->input
					->type('range')
					->name($this->f->name)
					->max($this->f->max)
					->min($this->f->min)
					->step($this->f->step)
					->value($this->f->def)
				->end
				->span->class('ui left pointing horizontal label range-value')
				->end
			->end
		->end;
	}
}


# A list of form fields
class ListComponentFormPart implements Renderable {
	public function __construct($field) { $this->f = $field; }
	function render() {

		if(is_finite($this->f->maxItems) && $this->f->minItems > 0) {
			$sublabel = 'Please provide between ' . $this->f->minItems . ' and ' . $this->f->maxItems . ' items.';
		} else if (is_finite($this->f->maxItems)) {
			$sublabel = 'Please provide no more than ' . $this->f->maxItems . ' items.';
		} else if($this->f->minItems > 0) {
			$sublabel = 'Please provide at least ' . $this->f->minItems . ' items.';
		} else {
			$sublabel = '';
		}

		return h()
		->div->class('ui field validation-root list-component')->data('count','0')->data('group-name', $this->f->name)
				->data('validation-name', $this->f->name)
			->h5->class('top attached ui message')
				->c($this->f->label)
				->c(new PossibleSublabel($sublabel, true))
			->end
			->div->class('ui bottom attached segment list-items')
				->script->type('text/template')
					->c(
						# Put the contents of the list into a <script> tag, which the client JS code will use
						# Forcibly HTML-encode things so that nested lists are generated properly...
						new DoubleEncode(
							h()
							->div->class('ui vertical segment close-item')
								->div->class('content')
									->c( array_map(function($x) { return $x ? $x->makeFormPart() : null; }, $this->f->items) )
								->end
								->button->type('button')->class('ui compact negative icon button delete-btn')
									# A button for deleting items
									->i->class('trash icon')->end
							   ->end
							->end
						)
					)
				->end
				->div->class('ui center aligned vertical segment')
					# A button for adding new items
					->button->type('button')->class('ui primary labeled icon button add-item')
						->i->class('plus icon')->end
						->c($this->f->addText)
					->end
				->end
			->end

		->end;
	}
}

# A "group" element
class GroupFormPart implements Renderable {
	public function __construct($field) { $this->f = $field; }
	function render() {

		$items = $this->f->items;

		return h()
		->div->class('group')
		->c(array_map(function($value) {
			if(is_array($value)) {
				return h()->div->class('ui segment attached')
					->c(
						# Use makeGroupPart so that things are formatted for display within a group
						array_map(function($x) { return $x ? $x->makeGroupPart() : null; }, $value)
					)
					->end;
			} else {
				return $value->makeGroupPart();
			}
		}, array_reduce($items, function($carry, $item) {
			# Group together all adjacent items except headers and notices, which
			# must be kept separate.
			if($item instanceof Header || $item instanceof Notice) {
				$carry[] = $item;
				return $carry;
			} else if( is_array(end($carry)) ) {
				$carry[count($carry)-1][] = $item;
				return $carry;
			} else {
				$carry[] = [$item];
				return $carry;
			}
		}, [])))
		->end;
	}
}


# A group of checkboxes
class CheckboxesFormPart implements Renderable {
	public function __construct($field) { $this->f = $field; }
	function render() {
		if(is_finite($this->f->maxChoices) && $this->f->minChoices > 0) {
			$sublabel = 'Please choose between ' . $this->f->minChoices . ' and ' . $this->f->maxChoices . ' items from the list.';
		} else if (is_finite($this->f->maxChoices)) {
			$sublabel = 'Please choose no more than ' . $this->f->maxChoices . ' items from the list.';
		} else if($this->f->minChoices > 0) {
			$sublabel = 'Please choose at least ' . $this->f->minChoices . ' items from the list.';
		} else {
			$sublabel = '';
		}
		return h()
			->div
				->class('grouped fields validation-root ' . ($this->f->required ? 'required' : ''))
				->data('validation-name', $this->f->name)

				->c(new Label($this->f, $sublabel))
				->c(
					array_map(
						function($v) {
							return h()->div->class('field not-validation-root')
									->div->class('ui checkbox')
									 ->input->name($this->f->name . '[]')->type('checkbox')->value($v)->end
									  ->label->c($v)->end
									->end
								->end;
						},
						$this->f->options
					)
				)
			->end;
	}
}

# A CAPTCHA
class CaptchaFormPart implements Renderable {
	public function __construct($field) { $this->f = $field; }
	function render() {
		return h()
			->div->class('ui field')
				->label->c('Are you a robot?')->end
				->div->class('g-recaptcha')->data('sitekey', Config::get()['recaptcha']['site-key'])->end
			->end;
	}
}

# The <form> element itself
class FormElemFormPart implements Renderable {
	public function __construct($field) { $this->f = $field; }
	function render() {
		// Use the "novalidate" attribute to disable HTML5 form validation,
		// since we implement our own validation logic.
		return h()
		->form->class('ui form')->action('../submit')->method('POST')->novalidate(true)
			->c(array_map(function($x) {
				return ($x && $x instanceof FormPartFactory) ? $x->makeFormPart() : null;
			}, $this->f->items))
			->input->type('hidden')->name('__form_name')->value($this->f->id)->end
			->input
				->type('hidden')
				->name('csrf_token')
				->value('__{{CSRF__TOKEN}}__')
			->end
			->div->class('ui floating error message validation-error-message')
				->div->class('header')
					->c('Error validating data')
				->end
				->p
					->c('Unfortunately, the data you provided contains errors. Please see above for more information. ')
					->c('After you have corrected the errors, press the button below to try again.')
				->end
			->end
			->div->class('ui vertically padded center aligned grid')
				->button->type('submit')->class('ui labeled icon positive big button')->data('submit', 'true')
					->i->class('checkmark icon')->end
					->span->c('Submit Form')->end
				->end
			->end
		->end;
	}
}

# The header at the top of the page
class TopHeader implements Renderable {
	function __construct() { $this->cfg = Config::get(); }
	function render() {
		return h()
		->div->class('ui top fixed menu')
			->style('background-color: rgb(' . $this->cfg['branding']['color'] . '); box-shadow: 0px 1px 2px 0px rgba(' . $this->cfg['branding']['color'] . ', 0.25);')
			->div->class('item')
				->img->src($this->cfg['branding']['image'])->end
			->end
		->end;
	}
}

# Show an error message in old IE versions
class BrowserProblemPart implements Renderable {
	function __construct($inner) { $this->inner = $inner; }
	function render() {
		// Based on html5boilerplate
		return [
			new SafeString(
				'<!--[if lt IE 10]>'
				. '<div style="text-align:center">'
						. '<h1>You are using an unsupported web browser.</h1>'
						. '<p>Please <a href="http://browsehappy.com/">upgrade your browser</a> to use this webpage.</p>'
					. '</div>'
				. '<![endif]--><!--[if gte IE 10]> -->'
			),
			$this->inner,
			new SafeString('<!-- <![endif]-->')
		];
	}
}

# An entire form page
class PageFormPart implements Renderable {
	public function __construct($field) { $this->f = $field; }
	function render() {
		return h()
		->html
			->head
				->meta->charset('utf-8')->end
				->title->c($this->f->title)->end
				->link->rel('stylesheet')->href(new AssetUrl('lib/semantic.css'))->end
				->link->rel('stylesheet')->href(new AssetUrl('styles.css'))->end
				# From https://github.com/h5bp/html5-boilerplate/blob/master/src/index.html
				->meta->name('viewport')->content('width=device-width, initial-scale=1')->end
				->meta->name('format-detection')->content('telephone=no')->end
			->end
			->body
				->c(new BrowserProblemPart(
					h()
					->c(new TopHeader())
					->div->class('ui text container')
						->div->class('ui segment')
							->c($this->f->form->makeFormPart())
						->end
					->end
					->div->class('success-modal ui small modal')
						->div->class('header')
							->c('Submission complete')
						->end
						->div->class('content')
							->p->c($this->f->successMessage)->end
						->end
						->div->class('actions')
							->button->type('button')->class('ui primary button approve')->c('OK')->end
						->end
					->end
					->div->class('failure-modal ui small modal')
						->div->class('red ui header')
							->c('Submission failed')
						->end
						->div->class('content')
							->p->c('The server encountered an error when processing your request. Please try again.')->end
						->end
						->div->class('actions')
							->button->type('button')->class('ui primary button approve')->c('OK')->end
						->end
					->end
					->script->src(new AssetUrl('lib/jquery.js'))->end
					->script->src(new AssetUrl('lib/jquery.inputmask.bundle.js'))->end
					->script->src(new AssetUrl('lib/semantic.js'))->end
					->script->src(new AssetUrl('client.js'))->end
					->script->src('https://www.google.com/recaptcha/api.js')->end
				))
			->end
		->end;
	}
}
