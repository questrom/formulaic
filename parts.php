<?php

require('include/HTMLGenerator.php');
require('include/Validate.php');
date_default_timezone_set('America/New_York');

abstract class Component {
	function __construct($args) {
		$this->showIf = isset($args['show-if']) ? $args['show-if'] : null;
	}
	abstract function get($h);
}

abstract class InputComponent extends Component {
	function __construct($args) {
		$this->label = $args['label'];
		$this->name = $args['name'];
		parent::__construct($args);
	}
	abstract function validate($against);
}


class FileInfo {
	function __construct($value) {
		$this->value = $value;
	}
}


// Abstract components

abstract class GroupComponent extends Component {
	function validate($against) {
		return $against->innerBind(function($val)  {
			return array_reduce($this->items, function($total, $x) use($val) {
				
				$pval = $val['post'];
				$fval = $val['files'];

				if($x instanceof GroupComponent) {
					if($x->showIf !== null &&
						!(isset($pval[$x->showIf]) ? $pval[$x->showIf] === "on" : false)
					) {
						$result = new NoResult();
					} else {
						$result = $x->validate( new OkJust($val) );
					}
					
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
						})
						->bindNoResult(function() {
							return new OkJust(function($z) {
								return $z;
							});
						});

				} else if($x instanceof InputComponent) {
					if($x->showIf !== null &&
						!(isset($pval[$x->showIf]) ? $pval[$x->showIf] === "on" : false)
					) {
						$result = new NoResult();
					} else if($x instanceof FileUpload) {
						$result = $x->validate( new OkJust( isset($fval[$x->name]) ? $fval[$x->name] : null  ) );
					} else {
						$result = $x->validate( new OkJust( isset($pval[$x->name]) ? $pval[$x->name] : null  ) );
					}

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
						})
						->bindNoResult(function() {
							return new OkJust(function($z) {
								return $z;
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
		parent::__construct($args);

		$this->maxLength = isset($args['max-length']) ? $args['max-length'] : INF;
		$this->minLength = isset($args['min-length']) ? $args['min-length'] : 0;
		$this->required  = isset($args['required']) ? $args['required'] : false;
		$this->mustMatch = isset($args['must-match']) ? $args['must-match'] : null;
		$this->matchHash = isset($args['match-hash']) ? $args['match-hash'] : null;
	}
	protected function render($h, $type, $icon) {
		return $h
		->div->class('ui field ' . ($this->required ? 'required' : ''))->data('show-if',$this->showIf)
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
		parent::__construct($args);
		$this->mustCheck = isset($args['must-check']) ? $args['must-check'] : false;
	}
	function get($h) {
		return $h
		->div->class('field ' . ($this->mustCheck ? 'required' : ''))->data('show-if',$this->showIf)
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
		parent::__construct($args);

		$this->required = isset($args['required']) ? $args['required'] : false;
		$this->max = isset($args['max']) ? $args['max'] : null;
		$this->min = isset($args['min']) ? $args['min'] : null;

		$this->step = isset($args['step']) ? $args['step'] : 'any';
	}
	function get($h) {
		return $h
		->div->class('field ' . ($this->required ? ' required' : ''))->data('show-if',$this->showIf)
			->ins(label($h, $this->label))
			->div->class('ui left icon input')
				->i->class('clock icon')->end
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
		parent::__construct($args);

		$this->required = isset($args['required']) ? $args['required'] : false;
		$this->max = isset($args['max']) ? DateTimeImmutable::createFromFormat('m/d/Y g:i a', $args['max']) : null;
		$this->min = isset($args['min']) ? DateTimeImmutable::createFromFormat('m/d/Y g:i a', $args['min']) : null;

		$this->step = isset($args['step']) ? $args['step'] : 'any';
	}
	function get($h) {
		return $h
		->div->class('field ' . ($this->required ? ' required' : ''))->data('show-if',$this->showIf)
			->ins(label($h, $this->label))
			->div->class('ui left icon input')
				->i->class('calendar icon')->end
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
		parent::__construct($args);

		$this->maxLength = isset($args['max-length']) ? $args['max-length'] : INF;
		$this->minLength = isset($args['min-length']) ? $args['min-length'] : 0;
		$this->required = isset($args['required']) ? $args['required'] : false;
		$this->mustMatch = isset($args['must-match']) ? $args['must-match'] : null;
	}
	function get($h) {
		return $h
		->ins(fieldBox($h, $this->required, $this->showIf))
			->ins(label($h, $this->label))
			->textarea->name($this->name)->end
		->end;
	}
	function validate($against) {
		return $against
			->filterString()
			->minMaxLength($this->minLength, $this->maxLength)
			->matchRegex($this->mustMatch)
			->filterEmptyString()
			->requiredMaybe($this->required);
	}
}

function fieldBox($h, $required, $showIf) {
	return $h->div->class('field ' . ($required ? ' required' : ''))->data('show-if',$showIf);
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
		parent::__construct($args);

		$this->options = $args['options'];
		$this->required = isset($args['required']) ? $args['required'] : false;
	}
	function get($h) {
		return fieldBox($h, $this->required, $this->showIf)
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
		parent::__construct($args);

		$this->options = $args['options'];
		$this->required = isset($args['required']) ? $args['required'] : false;
	}
	function get($h) {
		return $h
		->div->class('grouped fields validation-root ' . ($this->required ? 'required' : ''))->data('show-if',$this->showIf)
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
		parent::__construct($args);
		$this->options = $args['options'];

		$this->required = isset($args['required']) ? $args['required'] : false;
		$this->minChoices = isset($args['min-choices']) ? $args['min-choices'] : 0;
		$this->maxChoices = isset($args['max-choices']) ? $args['max-choices'] : INF;
	}
	function get($h) {
		return $h
		->div->class('grouped fields validation-root ' . ($this->required ? 'required' : ''))->data('show-if',$this->showIf)
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
		parent::__construct($args);
		$this->required  = isset($args['required']) ? $args['required'] : false;


		$this->maxLength = isset($args['max-length']) ? $args['max-length'] : INF;
		$this->minLength = isset($args['min-length']) ? $args['min-length'] : 0;
		$this->required  = isset($args['required'])   ? $args['required']   : false;
		$this->mustMatch = isset($args['must-match']) ? $args['must-match'] : null;

	}
	function get($h) {
		return $h
		->div->class('ui field ' . ($this->required ? 'required' : ''))->data('show-if',$this->showIf)
			->ins(label($h, $this->label))
			->div->class('ui input')
				->input->type('text')->name($this->name)->end
			->end
		->end;
	}
	function validate($against) {
		return $against
			->filterString()
			->minMaxLength($this->minLength, $this->maxLength)
			->matchRegex($this->mustMatch)
			->filterEmptyString()
			->requiredMaybe($this->required);
	}
}


class FileUpload extends InputComponent {
	function __construct($args) {
		parent::__construct($args);
		$this->required  = isset($args['required']) ? $args['required'] : false;
		$this->allowedExtensions = $args['allowed-extensions'];
		$this->maxSize = $args['max-size'];

	}
	function get($h) {
		return $h
		->div->class('ui field ' . ($this->required ? 'required' : ''))->data('show-if',$this->showIf)
			->ins(label($h, $this->label))
			->div->class('ui input')
				->input->type('file')->name($this->name)->end
			->end
		->end;
	}
	function validate($against) {
		return $against
			->innerBind(function($val) {
				if(!is_array($val) || !isset($val['error'])) {
					return new Err('Invalid data.');
				} else if($val['error'] === UPLOAD_ERR_NO_FILE) {
					return new EmptyResult(null);
				} else if($val['error'] === UPLOAD_ERR_OK) {
					return new OkJust($val);
				} else {
					return new Err('Error uploading file.');
				}
			})
			->requiredMaybe($this->required)
			->innerBind(function($file) {
				$ext = substr(strrchr($file['name'], '.'),1);

				if(!in_array($ext, $this->allowedExtensions)) {
					$exts = implode(', ', array_map('htmlspecialchars', $this->allowedExtensions));
					return new Err('Invalid file extension. Supported extensions are: ' . $exts . '.');
				}
				return new OkJust($file);
			})
			->innerBind(function($file) {
				if($file['size'] > $this->maxSize) {
					return new Err('File must be under ' . $this->maxSize . ' bytes in size.');
				}
				return new OkJust($file);
			})
			->innerBind(function($file) {
				return new OkJust(new FileInfo($file));
			});
	}
}


function midpoint($a, $b) {
	return $a + (($b - $a) / 2);
}

class Range extends InputComponent {
	function __construct($args) {

		parent::__construct($args);

		$this->max = isset($args['max']) ? $args['max'] : 1;
		$this->min = isset($args['min']) ? $args['min'] : 0;
		$this->step = isset($args['step']) ? $args['step'] : 'any';
		$this->def = isset($args['default']) ? $args['default'] : midpoint($this->min, $this->max);
	}
	function get($h) {
		return $h
		->div->class('ui field')->data('show-if',$this->showIf)
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
		parent::__construct($args);

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
		parent::__construct($args);

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
		parent::__construct($args);

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
		parent::__construct($args);

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
		parent::__construct($args);

		$this->required = isset($args['required']) ? $args['required'] : false;
		$this->min = isset($args['min']) ? DateTimeImmutable::createFromFormat('Y-m-d', $args['min']) : null;
		$this->max = isset($args['max']) ? DateTimeImmutable::createFromFormat('Y-m-d', $args['max']) : null;
	}
	function get($h) {
		return $h
		->div->class('field ' . ($this->required ? ' required' : ''))->data('show-if',$this->showIf)
			->ins(label($h, $this->label))
			->div->class('ui left icon input')
				->i->class('calendar icon')->end
				->input->type('text')->name($this->name)->data('inputmask', " 'alias': 'mm/dd/yyyy' ")->end
				->end
			->end
		->end;
	}
	function validate($against) {
		return $against
			->filterDate()
			->requiredMaybe($this->required)
			->minMaxDate($this->min, $this->max);
	}
}

class GroupHeader extends Component {
	function __construct($args) {
		if(is_string($args)) {
			$args = ['text' => $args];
		}
		$this->text = $args['text'];
		parent::__construct($args);
	}
	function get($h) {
		return $h
		->h5->class('ui header attached ')->data('show-if', $this->showIf)
			->t($this->text)
		->end;
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
		parent::__construct($args);
	}
	function get($h) { //this->size
		$inside = $h->t($this->text)
				->hif($this->subhead !== null)
					->div->class('sub header')->t($this->subhead)->end
				->end;
		return $h
		->{'h' . $this->size}->class('ui header')->data('show-if', $this->showIf)
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

class BaseNotice extends Component {
	function __construct($args) {
		$this->text = $args['text'];
		$this->header = isset($args['header']) ? $args['header'] : null;
		$this->icon = isset($args['icon']) ? $args['icon'] : null;
		$this->list = isset($args['list']) ? $args['list'] : null;
		$this->type = isset($args['type']) ? $args['type'] : null;
		parent::__construct($args);
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

class GroupNotice extends BaseNotice {
	function get($h) {
		return
		$h
		->div->class('ui message attached ' . ($this->icon === null ? '' : ' icon') . ($this->type ? ' ' . $this->type : ''))->data('show-if', $this->showIf)
			->add(parent::get($h))
		->end;
	}
}

class Notice extends BaseNotice {
	function get($h) {
		return
		$h
		->div->class('ui message floating ' . ($this->icon === null ? '' : ' icon') . ($this->type ? ' ' . $this->type : ''))->data('show-if', $this->showIf)
			->add(parent::get($h))
		->end;
	}
}

class Group extends GroupComponent {
	
	function __construct($args) {
		$this->items = $args['fields'];
		parent::__construct($args);
	}
	function get($h) {

		return $h
		->div->class('group')->data('show-if', $this->showIf)
			->add(array_map(function($value) use ($h) {
					if(is_array($value)) {
						return $h->div->class('ui segment attached ')
							->add($value)
						->end;
					} else {
						return $value;
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
				}, [])))
		->end;
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

class MongoOutput {
	function __construct($args) {
		$this->server = $args['server'];
		$this->database = $args['database'];
		$this->collection = $args['collection'];
	}
	function run($data) {

		$oldData = $data;
		
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

		return $oldData;
	}
}

class S3Output {
	function __construct($args) {
		$this->secret = yaml_parse_file('./config/s3-secret.yml');
		$this->s3 = new S3($this->secret['key-id'], $this->secret['key-secret']);
		$this->bucket = $args['bucket'];
	}
	function run($data) {
		$data = array_map(function($x) {
			if($x instanceof FileInfo) {
				$x = $x->value;
				$ret = $this->s3->putObject(S3::inputFile($x['tmp_name'], false), $this->bucket, 'test.abc', S3::ACL_PUBLIC_READ);
				var_dump($ret);
			} else {
				return $x;
			}
		}, $data);
		return $data;
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
		'!mongo'       => function($v) { return new MongoOutput($v);         },
		'!groupheader' => function($v) { return new GroupHeader($v);         },
		'!groupnotice' => function($v) { return new GroupNotice($v);         },
		'!notice'      => function($v) { return new Notice($v);              },
		'!header'      => function($v) { return new Header($v);              },
		'!datetime'    => function($v) { return new DateTimePicker($v);      },
		'!s3'          => function($v) { return new S3Output($v);            },
		'!file'        => function($v) { return new FileUpload($v);          }
	]);
}