<?php

require('include/HTMLGenerator.php');
require('include/Validate.php');
date_default_timezone_set('America/New_York');

abstract class Component {
	abstract function get($h);
}

abstract class InputComponent extends Component {
	abstract function validate($against);
}

// Abstract components

abstract class GroupComponent extends InputComponent {
	function validate($against) {
		return $against->innerBind(function($val) {
			return array_reduce($this->items, function($total, $x) use($val) {
				
				if($x instanceof GroupComponent) {
					$result = $x->validate( new OkJust($val) );
					
					$mergeM = $result
						->bind(function($r) {
							return new OkJust(function($total) use ($r) {
								return array_merge($r, $total);
							});	
						})
						->bind_err(function($r) {
							return new Err(function($total) use ($r) {
								return array_merge($r, $total);
							});	
						});

				} else if($x instanceof InputComponent) {
					$result = $x->validate( new OkJust( isset($val[$x->name]) ? $val[$x->name] : null  ) );

					$mergeM = $result
						->bind(function($r) use($x) {
							return new OkJust(function($total) use ($r, $x) {
								return array_merge([$x->name => $r], $total);
							});	
						})
						->bind_err(function($r) use($x) {
							return new Err(function($total) use ($r, $x) {
								return array_merge([$x->name => $r], $total);
							});	
						});

				} else {
					$mergeM = new OkJust(function($z) {
						return $z;
					});
				}


				return $mergeM
					->bind_err(function($merge) use($total) {
						return $total
							->innerBind(function($x) {
								return new Err([]);
							})
							->bind_err(function($x) use ($merge) {
								return new Err($merge($x));
							});
					})
					->innerBind(function($merge) use($total) {
						return $total
							->innerBind(function($x) use ($merge) {
								return new OkJust($merge($x));
							});
					});
					
			}, new OkJust([]));
		});
	}
}



abstract class SpecialInput extends InputComponent {
	function __construct($args) {
		$this->label = $args['label'];
		$this->name = $args['name'];

		$this->maxLength = isset($args['max-length']) ? $args['max-length'] : INF;
		$this->minLength = isset($args['min-length']) ? $args['min-length'] : 0;
		$this->required  = isset($args['required']) ? $args['required'] : false;
		$this->mustMatch = isset($args['must-match']) ? $args['must-match'] : null;
		$this->matchHash = isset($args['match-hash']) ? $args['match-hash'] : null;
	}
	protected function render($h, $type, $icon) {
		return $h
		->div->class('ui field ' . ($this->required ? 'required' : ''))
			->ins(label($h, $this->label))
			->div->class($icon ? 'ui left icon input' : 'ui input')
				->hif($icon)
					->i->class('icon ' . $icon)->end
				->end
				->input->type($type)->name($this->name)->end
			->end
		->end;
	}
}



// Specific components

class Checkbox extends InputComponent {	
	function __construct($args) {
		$this->label = $args['label'];
		$this->name = $args['name'];
		$this->mustCheck = isset($args['must-check']) ? $args['must-check'] : false;
	}
	function get($h) {
		return $h
		->div->class('field ' . ($this->mustCheck ? 'required' : ''))
			->div->class('ui checkbox')
				->input->type('checkbox')->name($this->name)->end
				->ins(label($h, $this->label))
			->end
		->end;
	}
	function validate($against) {
		return $against
			->filterBoolean()
			->mustBeTrue($this->mustCheck);
	}
}

class TimeInput extends InputComponent {	
	function __construct($args) {
		$this->label = $args['label'];
		$this->name = $args['name'];

		$this->required = isset($args['required']) ? $args['required'] : false;
		$this->max = isset($args['max']) ? $args['max'] : null;
		$this->min = isset($args['min']) ? $args['min'] : null;

		$this->step = isset($args['step']) ? $args['step'] : 'any';
	}
	function get($h) {
		return $h
		->div->class('field ' . ($this->required ? ' required' : ''))
			->ins(label($h, $this->label))
			->div->class('ui input time-input')
				->input->type('text')->name($this->name)->data('inputmask', " 'alias': 'h:s t', 'placeholder': 'hh:mm am' ")->end
				->end
			->end
		->end;
	}
	function validate($against) {
		return $against
			->filterTime()
			->requiredMaybe($this->required)
			->minMaxTime($this->min, $this->max)
			->stepTime($this->step);
	}
}

class DateTimePicker extends InputComponent {	
	function __construct($args) {
		$this->label = $args['label'];
		$this->name = $args['name'];

		$this->required = isset($args['required']) ? $args['required'] : false;
		$this->max = isset($args['max']) ? DateTimeImmutable::createFromFormat('m/d/Y g:i a', $args['max']) : null;
		$this->min = isset($args['min']) ? DateTimeImmutable::createFromFormat('m/d/Y g:i a', $args['min']) : null;

		$this->step = isset($args['step']) ? $args['step'] : 'any';
	}
	function get($h) {
		return $h
		->div->class('field ' . ($this->required ? ' required' : ''))
			->ins(label($h, $this->label))
			->div->class('ui input')
				->input->type('text')->name($this->name)->data('inputmask', " 'alias': 'proper-datetime' ")->end
				->end
			->end
		->end;
	}
	function validate($against) {
		return $against
			->filterDateTime()
			->requiredMaybe($this->required)
			->minMaxDateTime($this->min, $this->max)
			->stepDateTime($this->step);
	}
}


class Textarea extends InputComponent {	
	function __construct($args) {
		$this->label = $args['label'];
		$this->name = $args['name'];

		$this->maxLength = isset($args['max-length']) ? $args['max-length'] : INF;
		$this->minLength = isset($args['min-length']) ? $args['min-length'] : 0;
		$this->required = isset($args['required']) ? $args['required'] : false;
		$this->mustMatch = isset($args['must-match']) ? $args['must-match'] : null;
	}
	function get($h) {
		return $h
		->ins(fieldBox($h, $this->required))
			->ins(label($h, $this->label))
			->textarea->name($this->name)->end
		->end;
	}
	function validate($against) {
		return $against
			->filterString()
			->matchHash(isset($this->matchHash) ? $this->matchHash : null)
			->minMaxLength($this->minLength, $this->maxLength)
			->matchRegex($this->mustMatch)
			->filterEmptyString()
			->requiredMaybe($this->required);
	}
}

function fieldBox($h, $required) {
	return $h->div->class('field ' . ($required ? ' required' : ''));
}
function label($h, $label) {
	return $h
	->label
		->t($label)
	->end;
}

function dropdownDiv($h) {
	return $h->div->class('ui fluid dropdown selection');
}


class Dropdown extends InputComponent {
	function __construct($args) {
		$this->label = $args['label'];
		$this->name = $args['name'];
		$this->options = $args['options'];

		$this->required = isset($args['required']) ? $args['required'] : false;
	}
	function get($h) {
		return fieldBox($h, $this->required)
			->ins(label($h, $this->label))
			->hif(!$this->required)->t('test')->end
			->ins(dropdownDiv($h))
				->input->name($this->name)->type('hidden')->value('')->end
				->div->class('default text')->t('Please choose an option...')->end
				->i->class('dropdown icon')->end
				->div->class('menu')
					->add(array_map(
						function($v) use($h) {
							return $h
							->div
								->class('item')->data('value', $v)->t($v)
							->end;
						},
						$this->options
					))
				->end
			->end
		->end;
	}
	function validate($against) {
		return $against
			->filterChosenFromOptions($this->options)
			->requiredMaybe($this->required);
	}
}

class Radios extends InputComponent {
	function __construct($args) {

		$args = array_merge($args, [
			'required' => false
		]);

		$this->label = $args['label'];
		$this->name = $args['name'];
		$this->options = $args['options'];

		$this->required = $args['required'];
	}
	function get($h) {
		return $h
		->div->class('grouped fields validation-root ' . ($this->required ? 'required' : ''))
			->ins(label($h, $this->label))
			->add(
				array_map(
					function($v) use($h) {
						return $h
						->div->class('field not-validation-root')
							->div->class('ui radio checkbox')
								->input->name($this->name)->type('radio')->value($v)->end
								->label->t($v)->end
							->end
						->end;
					},
					$this->options
				)
			)
		->end;
	}
	function validate($against) {
		return $against
			->filterChosenFromOptions($this->options)
			->requiredMaybe($this->required);
	}
}


class Checkboxes extends InputComponent {
	function __construct($args) {
		$this->label = $args['label'];
		$this->name = $args['name'];
		$this->options = $args['options'];

		$this->required = isset($args['required']) ? $args['required'] : false;
		$this->minChoices = isset($args['min-choices']) ? $args['min-choices'] : 0;
		$this->maxChoices = isset($args['max-choices']) ? $args['max-choices'] : INF;
	}
	function get($h) {
		return $h
		->div->class('grouped fields validation-root ' . ($this->required ? 'required' : ''))
			->ins(label($h, $this->label))
			->add(
				array_map(
					function($v) use($h) {
						return $h->div->class('field not-validation-root')
							->div->class('ui checkbox')
								->input->name($this->name . '[]')->type('checkbox')->value($v)->end
								->label->t($v)->end
							->end
						->end;
					},
					$this->options
				)
			)
		->end;
	}
	function validate($against) {
		return $against
			->filterManyChosenFromOptions($this->options)
			->minMaxChoices($this->minChoices, $this->maxChoices)
			->filterNoChoices()
			->requiredMaybe($this->required);
	}
}

class Textbox extends InputComponent {
	function __construct($args) {
		$this->label = $args['label'];
		$this->name = $args['name'];
		$this->required  = isset($args['required']) ? $args['required'] : false;


		$this->maxLength = isset($args['max-length']) ? $args['max-length'] : INF;
		$this->minLength = isset($args['min-length']) ? $args['min-length'] : 0;
		$this->required  = isset($args['required'])   ? $args['required']   : false;
		$this->mustMatch = isset($args['must-match']) ? $args['must-match'] : null;
		$this->matchHash = isset($args['match-hash']) ? $args['match-hash'] : null;

	}
	function get($h) {
		return $h
		->div->class('ui field ' . ($this->required ? 'required' : ''))
			->ins(label($h, $this->label))
			->div->class('ui input')
				->input->type('text')->name($this->name)->end
			->end
		->end;
	}
	function validate($against) {
		return $against
			->filterString()
			->matchHash( isset($this->matchHash) ? $this->matchHash : null )
			->minMaxLength($this->minLength, $this->maxLength)
			->matchRegex($this->mustMatch)
			->filterEmptyString()
			->requiredMaybe($this->required);
	}
}

function midpoint($a, $b) {
	return $a + (($b - $a) / 2);
}

class Range extends InputComponent {
	function __construct($args) {

		$this->label = $args['label'];
		$this->name = $args['name'];

		$this->max = isset($args['max']) ? $args['max'] : 1;
		$this->min = isset($args['min']) ? $args['min'] : 0;
		$this->step = isset($args['step']) ? $args['step'] : 'any';
		$this->def = isset($args['default']) ? $args['default'] : midpoint($this->min, $this->max);
	}
	function get($h) {
		return $h
		->div->class('ui field')
			->ins(label($h, $this->label))
			->div->class('ui input')
				->input
					->type('range')
					->name($this->name)
					->max($this->max)
					->min($this->min)
					->step($this->step)
					->value($this->def)
				->end
			->end
		->end;
	}
	function validate($against) {
		return $against
			->filterString()
			->maybeString() // So we end up with a Maybe<> if not required
			->filterNumber(false)
			->minMaxNumber($this->min, $this->max)
			->stepNumber($this->step);
	}
}



class Password extends SpecialInput {
	function get($h) {
		return $this->render($h, 'password', '');
	}
	function validate($against) {
		return $against
			->filterString()
			->matchHash( isset($this->matchHash) ? $this->matchHash : null )
			->minMaxLength($this->minLength, $this->maxLength)
			->matchRegex($this->mustMatch)
			->filterEmptyString()
			->requiredMaybe($this->required);
	}
}

class PhoneNumber extends SpecialInput {
	function __construct($args) {
		$this->label = $args['label'];
		$this->name = $args['name'];

		$this->required = isset($args['required']) ? $args['required'] : false;
	}
	function get($h) {
		return $this->render($h, 'tel', 'call');
	}
	function validate($against) {
		return $against
			->filterString()
			->filterEmptyString()
			->requiredMaybe($this->required)
			->filterPhone();
	}
}

class EmailAddr extends SpecialInput {
	function __construct($args) {
		$this->label = $args['label'];
		$this->name = $args['name'];

		$this->required = isset($args['required']) ? $args['required'] : false;
		$this->mustHaveDomain = isset($args['must-have-domain']) ? $args['must-have-domain'] : null;
	}
	function get($h) {
		return $this->render($h, 'email', 'mail');
	}
	function validate($against) {
		return $against
			->filterString()
			->filterEmptyString()
			->requiredMaybe($this->required)
			->filterFilterVar(FILTER_VALIDATE_EMAIL, 'Invalid email address.')
			->mustHaveDomain($this->mustHaveDomain);
	}
}
class UrlInput extends SpecialInput {
	function __construct($args) {
		$this->label = $args['label'];
		$this->name = $args['name'];

		$this->required = isset($args['required']) ? $args['required'] : false;
	}
	function get($h) {
		return $this->render($h, 'url', 'world');
	}
	function validate($against) {
		return $against
			->filterString()
			->filterEmptyString()
			->requiredMaybe($this->required)
			->filterFilterVar(FILTER_VALIDATE_URL, 'Invalid URL.');
	}
}
class NumberInp extends SpecialInput {
	function __construct($args) {
		$this->label = $args['label'];
		$this->name = $args['name'];

		$this->required = isset($args['required']) ? $args['required'] : false;
		$this->min = isset($args['min']) ? $args['min'] : -INF;
		$this->max = isset($args['max']) ? $args['max'] : INF;
		$this->integer = isset($args['integer']) ? $args['integer'] : false;
	}
	function get($h) {
		return $this->render($h, 'number', '');
	}
	function validate($against) {
		return $against
			->filterString()
			->maybeString() // So we end up with a Maybe<> if not required
			->requiredMaybe($this->required)
			->filterNumber($this->integer)
			->minMaxNumber($this->min, $this->max);
	}
}

class DatePicker extends InputComponent {
	function __construct($args) {
		$this->label = $args['label'];
		$this->name = $args['name'];

		$this->required = isset($args['required']) ? $args['required'] : false;
		$this->minDate = isset($args['min-date']) ? DateTimeImmutable::createFromFormat('Y-m-d', $args['min-date']) : null;
		$this->maxDate = isset($args['max-date']) ? DateTimeImmutable::createFromFormat('Y-m-d', $args['max-date']) : null;
	}
	function get($h) {
		return $h
		->div->class('ui field')
			->ins(label($h, $this->label))
			->div->class('ui dropdown datepicker basic button')
				->div->class('text')->t('')->end
				->i->class('dropdown icon')->end
				->input->type('hidden')->name($this->name)->end
				->table->class('ui celled small seven column table menu')
					->thead
						->tr
							->th
								->button->type('button')->class('ui compact icon button fluid left floated')
									->i->class('caret left icon')->end
								->end
							->end
							->th->colspan(5)
								->h4->class('ui small center aligned header')->end
							->end
							->th
								->button->type('button')->class('ui compact icon button fluid right floated')
									->i->class('caret right icon')->end
								->end
							->end
						->end
					->end
					->tbody
						->add(array_map(
							function($v) use($h) {
								return $h
								->tr
									->add(array_map(
										function($v) use($h) {
											return $h
											->td
												->button->type('button')->class('ui compact fluid attached basic button')->end
											->end;
										},
										range(0, 6)
									))
								->end;
							},
							range(0, 5)
						))
					->end
				->end
			->end
		->end;
	}
	function validate($against) {
		return $against
			->filterDate()
			->requiredMaybe($this->required)
			->minMaxDate($this->minDate, $this->maxDate);

	}
}

class GroupHeader extends Component {
	function __construct($str) {
		$this->text = $str;
	}
	function get($h) {
		return $h->t($this->text);
	}
}

class Header extends Component {
	function __construct($args) {
		if(is_string($args)) {
			$args = ['text' => $args];
		}
		$this->text = $args['text'];
		$this->subhead = isset($args['subhead']) ? $args['subhead'] : null;
		$this->icon = isset($args['icon']) ? $args['icon'] : null;
		$this->size = isset($args['size']) ? $args['size'] : 1;
	}
	function get($h) { //this->size
		$inside = $h->t($this->text)
				->hif($this->subhead !== null)
					->div->class('sub header')->t($this->subhead)->end
				->end;
		return $h
		->{'h' . $this->size}->class('ui header')
			->hif($this->icon !== null)
				->i->class($this->icon . ' icon')->end
				->div->class('content')
					->add($inside)
				->end
			->end
			->hif($this->icon === null)
				->add($inside)
			->end
		->end;
	}
}

class GroupNotice extends Component {
	function __construct($args) {
		$this->text = $args['text'];
		$this->header = isset($args['header']) ? $args['header'] : null;
		$this->icon = isset($args['icon']) ? $args['icon'] : null;
		$this->list = isset($args['list']) ? $args['list'] : null;
		$this->type = isset($args['type']) ? $args['type'] : null;
	}
	function get($h) {
		return $h
		->hif($this->icon !== null)
			->i->class($this->icon . ' icon')->end
		->end
		->div->class('content')
			->hif($this->header !== null)
				->div->class('header')
					->t($this->header)
				->end
			->end
			->p
				->t($this->text)
			->end
			->hif($this->list !== null)
			  ->ul->class('list')
			    ->add(array_map(function($item) use($h) {
			    	// var_dump($this->list);
			    	return $h->li->t($item)->end;
			    }, $this->list === null ? [] : $this->list ))
			  ->end
			->end
		->end;
	}
}

class Notice extends GroupNotice {
	function get($h) {
		return $h->div->class('ui message floating ' . ($this->icon === null ? '' : ' icon') . ($this->type ? ' ' . $this->type : ''))
			->add(parent::get($h))
		->end;
	}
}

class Position {
	const Only = 1;
	const First = 2;
	const Last = 3;
	const Middle = 4;
}

function startEndMap(callable $fn, $list) {
	$lastPos = count($list) - 1;
	$result = [];
	foreach ($list as $key => $value) {
		if($key === 0 && $key === $lastPos) {
			$result[] = $fn($value, Position::Only);
		} else if($key === 0) {
			$result[] = $fn($value, Position::First);
		} else if($key === $lastPos) {
			$result[] = $fn($value, Position::Last);
		} else {
			$result[] = $fn($value, Position::Middle);
		}
	}
	return $result;
}

class Group extends GroupComponent {
	
	function __construct($args) {
		$this->items = $args['fields'];
	}
	function get($h) {

		return $h
			->add(
				startEndMap(function($value, $place) use ($h) {
					
					if($place === Position::Only) {
						$attachment = '';
					} else if($place === Position::First) {
						$attachment = 'top attached';
					} else if($place === Position::Last) {
						$attachment = 'bottom attached';
					} else if($place === Position::Middle) {
						$attachment = 'attached';
					}

					if($value instanceof GroupHeader) {
						return $h->h5->class('ui header ' . $attachment)
							->add($value)
						->end;
					} else if(is_array($value)) {
						return $h->div->class('ui  segment ' . $attachment)
							->add($value)
						->end;
					} else if($value instanceof GroupNotice) {
						return $h->div->class('ui message ' . $attachment . ($value->icon === null ? '' : ' icon') . ($value->type ? ' ' . $value->type : ''))
						  ->add($value)
						->end;
					}
				}, array_reduce($this->items, function($carry, $item) {
					if($item instanceof GroupHeader || $item instanceof GroupNotice) {
						$carry[] = $item;
						return $carry;
					} else if( is_array(end($carry)) ) {
						$carry[count($carry)-1][] = $item;
						return $carry;
					} else {
						$carry[] = [$item];
						return $carry;
					}
				}, []))
			);
	
	}
}


class Form extends GroupComponent {


	function __construct($args) {
		$this->items = $args;
	}
	function get($h) {
		return $h
		->form->class('ui form')->action('submit.php')->method('POST')
			->add($this->items)
			->div->class('ui floating error message validation-error-message')
				->div->class('header')
					->t('Error validating data')
				->end
				->p
					->t('Unfortunately, the data you provided contains errors. Please see above for more information. ')
					->t('After you have corrected the errors, press the button below to try again.')
				->end
			->end
			->button->type('button')->class('ui labeled icon positive big button centered-button')->data('submit','true')
				->i->class('checkmark icon')->end
				->span->t('Submit Form')->end
			->end
		->end;
	}

}

class Page extends InputComponent {
	function __construct($yaml) {
		$this->form = new Form($yaml['fields']);
		$this->title = isset($yaml['title']) ? $yaml['title'] : 'Form';
		$this->successMessage = isset($yaml['success-message']) ? $yaml['success-message'] : 'The form was submitted successfully.';
	}
	function get($h) {
		return $h
		->html
			->head
				->meta->charset('utf-8')->end
				->title->t($this->title)->end
				->link->rel("stylesheet")->href("vendor/semantic/ui/dist/semantic.css")->end
				->link->rel("stylesheet")->href("styles.css")->end
			->end
			->body
				->div->class('ui page grid')
					->div->class('sixteen wide column')
						->add($this->form)
					->end
				->end
				->div->class('success-modal ui small modal')
					->div->class('header')
						->t('Submission complete')
					->end
					->div->class('content')
						->p->t($this->successMessage)->end
					->end
					->div->class('actions')
						->button->type('button')->class('ui primary button')->t('OK')->end
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
						->button->type('button')->class('ui primary button')->t('OK')->end
					->end
				->end
				->script->src('http://cdnjs.cloudflare.com/ajax/libs/jquery/2.0.3/jquery.js')->end
				->script->src('vendor/robinherbots/jquery.inputmask/dist/jquery.inputmask.bundle.js')->end
				->script->src('vendor/moment/moment/moment.js')->end
				->script->src('vendor/semantic/ui/dist/semantic.js')->end
				->script->src('client.js')->end
			->end
		->end;
	}
	function validate($against) {
		$res = $this->form->validate($against);
		$res = $res->innerBind(function($r) {
			$r['_timestamp'] = new DateTimeImmutable();
			$r['_ip'] = $_SERVER['REMOTE_ADDR'];	
			return new OkJust($r);
		});
		return $res;
	}
}

// Outputs

class DebugOutput {
	function __construct($args) {}
	function run($data) {
		var_dump($data);
	}
}

class MongoOutput {
	function __construct($args) {
		$this->server = $args['server'];
		$this->database = $args['database'];
		$this->collection = $args['collection'];
	}
	function run($data) {
		
		$data = array_map(function($x) {
			if($x instanceof DateTimeImmutable) {
				return new MongoDate($x->getTimestamp());
			} else {
				return $x;
			}
		}, $data);
		
		$collection = (new MongoClient($this->server))
			->selectDB($this->database)
			->selectCollection($this->collection);
		$collection->insert($data);
	}
}

function parse_yaml($file) {
	return yaml_parse_file($file, 0, $ndocs, [
		'!checkbox'    => [ new ReflectionClass('Checkbox'), 'newInstance'],
		'!textbox'     => function($v) { return new Textbox($v);             },
		'!password'    => function($v) { return new Password($v);            },
		'!dropdown'    => function($v) { return new Dropdown($v);            },
		'!radios'      => function($v) { return new Radios($v);              },
		'!checkboxes'  => function($v) { return new Checkboxes($v);          },
		'!textarea'    => function($v) { return new Textarea($v);            },
		'!range'       => function($v) { return new Range($v);               },
		'!time'        => function($v) { return new TimeInput($v);           },
		'!group'       => function($v) { return new Group($v);               },
		'!date'        => function($v) { return new DatePicker($v);          },
		'!phonenumber' => function($v) { return new PhoneNumber($v);         },
		'!email'       => function($v) { return new EmailAddr($v);           },
		'!url'         => function($v) { return new UrlInput($v);            },
		'!number'      => function($v) { return new NumberInp($v);           },
		'!debug'       => function($v) { return new DebugOutput($v);         },
		'!mongo'       => function($v) { return new MongoOutput($v);         },
		'!groupheader' => function($v) { return new GroupHeader($v);         },
		'!groupnotice' => function($v) { return new GroupNotice($v);         },
		'!notice'      => function($v) { return new Notice($v);              },
		'!header'      => function($v) { return new Header($v);              },
		'!datetime'    => function($v) { return new DateTimePicker($v);      }
	]);
}