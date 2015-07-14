<?php


class Label implements Renderable {
	function __construct($label, $sublabel = null) {
		$this->h = new HTMLParentlessContext();
		$this->label = $label;
		$this->customSublabel = $sublabel;
	}
	function render() {
		return $this->h
		->label
			->span->t($this->label)->end
		->end
		->addH(new PossibleSublabel($this->customSublabel, false));
	}
}


class PossibleSublabel implements Renderable {
	function __construct($sublabel, $right = false) {
		$this->h = new HTMLParentlessContext();
		$this->sublabel = $sublabel;
		$this->right = $right;
	}
	function render() {
		if($this->sublabel) {
			return $this->h
			->p->class('sublabel')->t($this->sublabel)->end;
		} else {
			return null;
		}
	}
}

abstract class FormPart implements Renderable {
	public function __construct($field) {
		$this->f = $field;
		$this->h = new HTMLParentlessContext();
	}
}


class BaseHeaderFormPart extends FormPart {
	function render() {
		$inside = $this->h->t($this->f->text)
		->addH($this->f->subhead === null ? null :
			$this->h->div->class('sub header')->t($this->f->subhead)->end
		);
		return $this->f->icon === null ? $inside :
			$this->h
			->i->class($this->f->icon . ' icon')->end
			->div->class('content')
				->addH($inside)
			->end
		;
	}
}


class BaseNoticeFormPart extends FormPart {
	function render() {
		return $this->h
		->addH($this->f->icon === null ? null :
			$this->h
			->i->class($this->f->icon . ' icon')->end
		)
		->div->class('content')
			->addH($this->f->header === null ? null :
				$this->h->div->class('header')
					->t($this->f->header)
				->end
			)
			->p
				->t($this->f->text)
			->end
			->addH(
				$this->f->list === null ? null :
				$this->h->ul->class('list')
					->addH(array_map(
						function($item) {
							return $this->h->li->t($item)->end;
						},
						$this->f->list === null ? [] : $this->f->list
					))
				->end
			)
		->end;
	}
}



class HeaderFormPart extends FormPart {
	function render() {
		$size = ($this->f->size === null) ? 1 : $this->f->size;
		return $this->h
		->{'h' . $size}->class('ui header')
			->addH(
				new BaseHeaderFormPart($this->f)
			)
		->end;
	}
}

class GroupHeaderFormPart extends FormPart {
	function render() {
		$size = ($this->f->size === null) ? 5 : $this->f->size;
		return $this->h
			->{'h' . $size}->class('ui header attached')
				->addH(
					new BaseHeaderFormPart($this->f)
				)
			->end;
	}
}


class GroupNoticeFormPart extends FormPart {
	function render() {
		return
			$this->h
				->div->class('ui message attached ' . ($this->f->icon === null ? '' : ' icon') . ($this->f->type ? ' ' . $this->f->type : ''))
				->addH(
					new BaseNoticeFormPart($this->f)
				)
			->end;
	}
}

class NoticeFormPart extends FormPart {
	function render() {
		return
			$this->h
				->div->class('ui message floating ' . ($this->f->icon === null ? '' : ' icon') . ($this->f->type ? ' ' . $this->f->type : ''))
				->addH(
					new BaseNoticeFormPart($this->f)
				)
				->end;
	}
}



class InputFormPart extends FormPart {
	function __construct($field, $type, $icon = null, $mask = null, $sublabel = null) {
		$this->f = $field;
		$this->h = new HTMLParentlessContext();
		$this->type = $type;
		$this->icon = $icon;
		$this->mask = $mask;
		$this->sublabel = $sublabel;
	}
	function render() {
		return $this->h
		->div->class('ui field ' . ($this->f->required ? 'required' : ''))
			->addH($this->f->getLabel($this->sublabel))
			->div->class($this->icon ? 'ui left icon input' : 'ui input')
				->addH($this->icon === null ? null :
					$this->h
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

class NumberFormPart extends FormPart {
	function __construct($field) {
		$this->f = $field;
		$this->h = new HTMLParentlessContext();
	}
	function render() {
		return $this->h
		->div->class('ui field ' . ($this->f->required ? 'required' : ''))
			->addH($this->f->getLabel())
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

class DropdownFormPart extends FormPart {
	function render() {
		return $this->h->div->class('field ' . ($this->f->required ? ' required' : ''))
			->addH($this->f->getLabel())
			->div->class('ui fluid dropdown selection')
				->input->name($this->f->name)->type('hidden')->value('')->end
				->div->class('default text')->t('Please choose an option...')->end
				->i->class('dropdown icon')->end
				->div->class('menu')
					->addH(array_map(
						function($v) {
							return $this->h
							->div->class('item')->data('value', $v)
								->t($v)
							->end;
						},
						$this->f->options
					))
				->end
			->end
		->end;
	}
}

class RadioButton implements Renderable {
	function __construct($name, $value) {
		$this->name = $name;
		$this->value = $value;
		$this->h = new HTMLParentlessContext();
	}
	public function render() {
		return $this->h
		->div->class('field not-validation-root')
			->div->class('ui radio checkbox')
				->input->name($this->name)->type('radio')->value($this->value)->end
				->label->t($this->value)->end
			->end
		->end;
	}
}


class RadiosFormPart extends FormPart {
	public function render() {
		return $this->h
			->div->class('grouped fields validation-root ' . ($this->f->required ? 'required' : ''))
				->data('radio-group-name', $this->f->name)
			->addH($this->f->getLabel())
			->addH(
				array_map(
					function($v) { return new RadioButton($this->f->name, $v); },
					$this->f->options
				)
			)
			->end;
	}
}


class TextareaFormPart extends FormPart {
	function render() {
		return $this->h
			->div->class('field ' . ($this->f->required ? ' required' : ''))
				->addH($this->f->getLabel())
				->textarea
					->name($this->f->name)
					->maxlength($this->f->maxLength, is_finite($this->f->maxLength))
				->end
			->end;
	}
}


function df($date) {
	return $date->format('g:ia m/d/Y');
}


class DateTimePickerFormPart extends FormPart {
	function render() {
		$sublabel = '';

		if(isset($this->f->max) && isset($this->f->min)) {
			$sublabel = 'Please provide a date and time between ' . df($this->f->min) . ' and ' . df($this->f->max) . '.';
		} else if (isset($this->f->max)) {
			$sublabel = 'Please provide a date and time no later than ' . df($this->f->max) . '.';
		} else if(isset($this->f->min)) {
			$sublabel = 'Please provide a date and time no earlier than ' . df($this->f->min) . '.';
		}

		return $this->h
		->div->class('field ' . ($this->f->required ? ' required' : ''))
			->addH($this->f->getLabel($sublabel))
			->div->class('ui left icon input')
				->i->class('calendar icon')->end
				->input->type('text')->name($this->f->name)->data('inputmask', " 'alias': 'proper-datetime' ")->end
			->end
		->end;
	}
}


class TimeInputFormPart extends FormPart {
	function render() {
		$sublabel = '';

		if(isset($this->f->max) && isset($this->f->min)) {
			$sublabel = 'Please provide a time between ' . ($this->f->min) . ' and ' . ($this->f->max) . '.';
		} else if (isset($this->f->max)) {
			$sublabel = 'Please provide a time no later than ' . ($this->f->max) . '.';
		} else if(isset($this->f->min)) {
			$sublabel = 'Please provide a time no earlier than ' . ($this->f->min) . '.';
		}

		return $this->h
		->div->class('field ' . ($this->f->required ? ' required' : ''))
			->addH($this->f->getLabel($sublabel))
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


class CheckboxFormPart extends FormPart {
	function render() {
		return $this->h
		->div->class('field ' . ($this->f->mustCheck ? 'required' : ''))
			->div->class('ui checkbox')
				->input->type('checkbox')->name($this->f->name)->end
				->addH($this->f->getLabel())
			->end
		->end;
	}
}


class ShowIfComponentFormPart extends FormPart {
	function render() {
		// Provide a way of specifying type of cnodition, then read this in client.js
		return $this->h
			->div
				->data('show-if-name', $this->f->condition->getName())
				->data('show-if-condition', $this->f->condition->getCondition())
				->addH($this->f->item)
			->end;
	}
}


class RangeFormPart extends FormPart {
	function render() {
		return $this->h
		->div->class('ui field')
			->addH($this->f->getLabel())
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



class ListComponentFormPart extends FormPart {
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

		return $this->h
		->div->class('ui field validation-root list-component')->data('count','0')->data('group-name', $this->f->name)
				->data('validation-name', $this->f->name)
			->h5->class('top attached ui message')
				->t($this->f->label)
				->addH(new PossibleSublabel($sublabel, true))
			->end
			->div->class('ui bottom attached segment list-items')
				->script->type('text/template')
					->addH(
						// Forcibly HTML-encode things so that nested lists are generated properly...
						new DoubleEncode(
							$this->h
							->div->class('ui vertical segment close-item')
								->div->class('content')
									->addH( array_map(function($x) { return $x ? $x->makeFormPart() : null; }, $this->f->items) )
								->end
								->button->type('button')->class('ui compact negative icon button delete-btn')
									->i->class('trash icon')->end
							   ->end
							->end
						)
					)
				->end
				->div->class('ui center aligned vertical segment')
					->button->type('button')->class('ui primary labeled icon button add-item')
						->i->class('plus icon')->end
						->t($this->f->addText)
					->end
				->end
			->end

		->end;
	}
}


class GroupFormPart extends FormPart {
	function render() {

		$items = $this->f->items;

		return $this->h
		->div->class('group')
		->addH(array_map(function($value) {
			if(is_array($value)) {
				return (new HTMLParentlessContext())->div->class('ui segment attached')
					->addH(
						array_map(function($x) { return $x ? $x->makeGroupPart() : null; }, $value)
					)
					->end;
			} else {
				return $value->makeGroupPart();
			}
		}, array_reduce($items, function($carry, $item) {
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




class CheckboxesFormPart extends FormPart {
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
		return $this->h
			->div
				->class('grouped fields validation-root ' . ($this->f->required ? 'required' : ''))
				->data('validation-name', $this->f->name)

				->addH($this->f->getLabel($sublabel))
				->addH(
					array_map(
						function($v) {
							return $this->h->div->class('field not-validation-root')
									->div->class('ui checkbox')
									 ->input->name($this->f->name . '[]')->type('checkbox')->value($v)->end
									  ->label->t($v)->end
									->end
								->end;
						},
						$this->f->options
					)
				)
			->end;
	}
}


class CaptchaFormPart extends FormPart {
	function render() {
		$cc = makeCaptcha();
		return $this->h
		->div->class('ui field required')
			->div->class('ui card')
				->div->class('image')
					->img
						->src($cc['data'])
					->end
				->end
				->div->class('content')->data('validation-name', '_captcha')
					->p
						->t('Please prove that you are not a robot by entering the above code into the box below.')
					->end
					->div->class('ui input')
						->input->type('text')->name('_captcha[]')->placeholder('Enter code...')->end
						->input->type('hidden')->name('_captcha[]')->value($cc['id'])->end
					->end
				->end
			->end
		->end;
	}
}


class FormElemFormPart extends FormPart {
	function render() {
		// Use the "novalidate" attribute to disable HTML5 form validation,
		// since we implement our own validation logic.
		return $this->h
		->form->class('ui form')->action('submit.php')->method('POST')->novalidate(true)
			->addH( array_map(function($x) { return ($x && $x instanceof FormPartFactory) ? $x->makeFormPart() : null; }, $this->f->items) )
			->input->type('hidden')->name('__form_name')->value($this->f->id)->end
			->input
				->type('hidden')
				->name('csrf_token')
				->value('__{{CSRF__TOKEN}}__')
			->end
			->div->class('ui floating error message validation-error-message')
				->div->class('header')
					->t('Error validating data')
				->end
				->p
					->t('Unfortunately, the data you provided contains errors. Please see above for more information. ')
					->t('After you have corrected the errors, press the button below to try again.')
				->end
			->end
			->div->class('ui vertically padded center aligned grid')
				->button->type('submit')->class('ui labeled icon positive big button')->data('submit', 'true')
					->i->class('checkmark icon')->end
					->span->t('Submit Form')->end
				->end
			->end
		->end;
	}
}


class TopHeader implements Renderable {
	function __construct() {
		$this->h = new HTMLParentlessContext();
		$this->cfg = Config::get();
	}
	function render() {
		return $this->h
		->div->class('ui top fixed menu')
			->style('background-color: rgb(' . $this->cfg['branding']['color'] . '); box-shadow: 0px 1px 2px 0px rgba(' . $this->cfg['branding']['color'] . ', 0.25);')
			->div->class('item')
				->img->src($this->cfg['branding']['image'])->end
			->end
		->end;
	}
}

class BrowserProblemPart implements Renderable {
	function __construct($inner) {
		$this->inner = $inner;
	}
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

class PageFormPart extends FormPart {
	function render() {
		return $this->h
		->html
			->head
				->meta->charset('utf-8')->end
				->title->t($this->f->title)->end
				->link->rel('stylesheet')->href(new AssetUrl('lib/semantic.css'))->end
				->link->rel('stylesheet')->href(new AssetUrl('styles.css'))->end

				->meta->name('viewport')->content('width=device-width, initial-scale=1')->end // From https://github.com/h5bp/html5-boilerplate/blob/master/src/index.html
				->meta->name('format-detection')->content('telephone=no')->end
			->end
			->body
				->addH(new BrowserProblemPart(
					$this->h
					->addH(new TopHeader())
					->div->class('ui text container')
						->div->class('ui segment')
							->addH($this->f->form->makeFormPart())
						->end
					->end
					->div->class('success-modal ui small modal')
						->div->class('header')
							->t('Submission complete')
						->end
						->div->class('content')
							->p->t($this->f->successMessage)->end
						->end
						->div->class('actions')
							->button->type('button')->class('ui primary button approve')->t('OK')->end
						->end
					->end
					->div->class('failure-modal ui small modal')
						->div->class('red ui header')
							->t('Submission failed')
						->end
						->div->class('content')
							->p->t('The server encountered an error when processing your request. Please try again.')->end
						->end
						->div->class('actions')
							->button->type('button')->class('ui primary button approve')->t('OK')->end
						->end
					->end
					->script->src(new AssetUrl('lib/jquery.js'))->end
					->script->src(new AssetUrl('lib/jquery.inputmask.bundle.js'))->end
					->script->src(new AssetUrl('lib/semantic.js'))->end
					->script->src(new AssetUrl('client.js'))->end
				))
			->end
		->end;
	}
}
