<?php

class ClientData {
	function __construct($post, $files) {
		$this->post = $post;
		$this->files = $files;
	}
}

abstract class Component {
	function __construct($args) { }
	abstract function get($h);
	abstract function getMerger($val);
}

class ShowIfComponent extends Component {
	function __construct($args) {
		$this->item = $args['item'];
		$this->cond = $args['cond'];
	}
	function get($h) {
		return $h
			->div->data('show-if', $this->cond)
				->add($this->item)
			->end;
	}
	function getMerger($val) {
		return $val->bind(function($val) {
			$post_value = $val->post;
			if(!(isset($post_value[$this->cond]) ? $post_value[$this->cond] === "on" : false)) {
				return new OkJust([]);
			} else {
				return $this->item->getMerger( new OkJust( $val  ) );
			}
		});
	}
}

abstract class EmptyComponent extends Component {
	function getMerger($val) {
		return new OkJust([]);
	}
}

abstract class NamedLabeledComponent extends Component {
	function __construct($args) {
		$this->label = $args['label'];
		$this->name = $args['name'];
		parent::__construct($args);
	}
}

abstract class InputComponent extends NamedLabeledComponent {

	abstract protected function validate($against);
	function getMerger($val) {
		$val = $val->innerBind(function($v) {
			return new OkJust(isset($v->post[$this->name]) ? $v->post[$this->name] : null);
		});
		return $this->validate($val)
			->bind(function($r) {
				return new OkJust([$this->name => $r]);
			})
			->bind_err(function($r) {
				return new Err([$this->name => $r]);
			});
	}
}

abstract class FileInputComponent extends NamedLabeledComponent {
	abstract protected function validate($against);
	function getMerger($val) {
		$val = $val->innerBind(function($v) {
			return new OkJust(isset($v->files[$this->name]) ? $v->files[$this->name] : null);
		});
		return $this->validate($val)
			->bind(function($r) {
				return new OkJust([$this->name => $r]);
			})
			->bind_err(function($r) {
				return new Err([$this->name => $r]);
			});
	}
}

class FileInfo {
	function __construct($file, $filename, $mime, $permissions) {
		$this->file = $file;
		$this->filename = $filename;
		$this->mime = $mime;
		$this->permissions = $permissions;
	}
}


// Abstract components

abstract class GroupComponent extends Component {
	function getMerger($val) {
		return $this->validate($val);
	}
	protected function validate($against) {
		return $against->innerBind(function($val)  {
			return array_reduce($this->items, function($total, $x) use($val) {

				$result = $x->getMerger( new OkJust( $val  ) );

				$mergeM = $result
					->bind(function($r) {
						return new OkJust(function($total) use ($r) {
							return array_merge($r, $total);
						});
					})
					->bind_err(function($r) use($x) {
						return new Err(function($total) use ($r) {
							return array_merge($r, $total);
						});
					});


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

		$this->maxLength = isset($args['max-length']) ? intval($args['max-length']) : INF;
		$this->minLength = isset($args['min-length']) ? intval($args['min-length']) : 0;
		$this->required  = isset($args['required']);
		$this->mustMatch = isset($args['must-match']) ? $args['must-match'] : null;

	}
	protected function makeInput($h, $type, $icon) {
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
		parent::__construct($args);
		$this->mustCheck = isset($args['must-check']);
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
	protected function validate($against) {
		return $against
			->filterBoolean()
			->mustBeTrue($this->mustCheck);
	}
}

class TimeInput extends InputComponent {
	function __construct($args) {
		parent::__construct($args);

		$this->required = isset($args['required']);
		$this->max = isset($args['max']) ? $args['max'] : null;
		$this->min = isset($args['min']) ? $args['min'] : null;

		$this->step = isset($args['step']) ? intval($args['step']) : 'any';
	}
	function get($h) {
		return $h
		->div->class('field ' . ($this->required ? ' required' : ''))
			->ins(label($h, $this->label))
			->div->class('ui left icon input')
				->i->class('clock icon')->end
				->input->type('text')->name($this->name)->data('inputmask', " 'alias': 'h:s t', 'placeholder': 'hh:mm am' ")->end
				->end
			->end
		->end;
	}
	protected function validate($against) {
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

		$this->required = isset($args['required']);
		$this->max = isset($args['max']) ? DateTimeImmutable::createFromFormat('m/d/Y g:i a', $args['max']) : null;
		$this->min = isset($args['min']) ? DateTimeImmutable::createFromFormat('m/d/Y g:i a', $args['min']) : null;

		$this->step = isset($args['step']) ? $args['step'] : 'any';
	}
	function get($h) {
		return $h
		->div->class('field ' . ($this->required ? ' required' : ''))
			->ins(label($h, $this->label))
			->div->class('ui left icon input')
				->i->class('calendar icon')->end
				->input->type('text')->name($this->name)->data('inputmask', " 'alias': 'proper-datetime' ")->end
				->end
			->end
		->end;
	}
	protected function validate($against) {
		return $against
			->filterDateTime()
			->requiredMaybe($this->required)
			->minMaxDateTime($this->min, $this->max)
			->stepDateTime($this->step);
	}
}


class Textarea extends SpecialInput {
	function get($h) {
		return $h
		->ins(fieldBox($h, $this->required))
			->ins(label($h, $this->label))
			->textarea->name($this->name)->end
		->end;
	}
	protected function validate($against) {
		return $against
			->filterString()
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
		parent::__construct($args);

		$this->options = $args['options'];
		$this->required = isset($args['required']);
	}
	function get($h) {
		return fieldBox($h, $this->required)
			->ins(label($h, $this->label))
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
	protected function validate($against) {
		return $against
			->filterChosenFromOptions($this->options)
			->requiredMaybe($this->required);
	}
}

class Radios extends InputComponent {
	function __construct($args) {
		parent::__construct($args);

		$this->options = $args['options'];
		$this->required = isset($args['required']);
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
	protected function validate($against) {
		return $against
			->filterChosenFromOptions($this->options)
			->requiredMaybe($this->required);
	}
}


class Checkboxes extends InputComponent {
	function __construct($args) {
		parent::__construct($args);
		$this->options = $args['options'];

		$this->required = isset($args['required']);
		$this->minChoices = isset($args['min-choices']) ? intval($args['min-choices']) : 0;
		$this->maxChoices = isset($args['max-choices']) ? intval($args['max-choices']) : INF;
	}
	function get($h) {
		return $h
		->div->class('grouped fields validation-root ' . ($this->required ? 'required' : ''))->data('validation-name', $this->name)
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
	protected function validate($against) {
		return $against
			->filterManyChosenFromOptions($this->options)
			->minMaxChoices($this->minChoices, $this->maxChoices)
			->filterNoChoices()
			->requiredMaybe($this->required);
	}
}

class Textbox extends SpecialInput {
	function get($h) {
        return $this->makeInput($h, 'text', null);
	}
	protected function validate($against) {
		return $against
			->filterString()
			->minMaxLength($this->minLength, $this->maxLength)
			->matchRegex($this->mustMatch)
			->filterEmptyString()
			->requiredMaybe($this->required);
	}
}


class FileUpload extends FileInputComponent {
	function __construct($args) {
		parent::__construct($args);
		$this->required  = isset($args['required']);
		$this->allowedExtensions = $args['allowed-extensions'];
		$this->maxSize = intval($args['max-size']);
		$this->permissions = $args['permissions'];

	}
	function get($h) {
		return $h
		->div->class('ui field ' . ($this->required ? 'required' : ''))
			->ins(label($h, $this->label))
			->div->class('ui input')
				->input->type('file')->name($this->name)->end
			->end
		->end;
	}
	protected function validate($against) {
		return $against
			->innerBind(function($val) {
				// See http://php.net/manual/en/features.file-upload.php
				if(!is_array($val) || !isset($val['error']) || is_array($val['error'])) {
					return new Err('Invalid data.');
				} else if($val['error'] === UPLOAD_ERR_INI_SIZE || $val['error'] === UPLOAD_ERR_FORM_SIZE) {
					return new Err('File size exceeds server or form limit.');
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
				if($file['size'] > $this->maxSize) {
					return new Err('File must be under ' . $this->maxSize . ' bytes in size.');
				} else {
					return new OkJust($file);
				}
			})
			->innerBind(function($file) {

				$finfo = new finfo(FILEINFO_MIME_TYPE);
				$mime = $finfo->file($file['tmp_name']);

				$ext = array_search(
					$mime,
					$this->allowedExtensions,
					true
				);

				if($ext === false) {
					return new Err('Invalid file type or wrong MIME type. Allowed extensions are: ' . implode(', ', array_keys($this->allowedExtensions)) . '.');
				}

				if(!is_uploaded_file($file['tmp_name'])) {
					return new Err('Security error.');
				}


				$filename = sha1_file($file['tmp_name']) . '-' . floor(microtime(true)) . '.' . $ext;

				return new OkJust(new FileInfo($file, $filename, $mime, $this->permissions));
			});
	}
}


function midpoint($a, $b) {
	return $a + (($b - $a) / 2);
}

class Range extends InputComponent {
	function __construct($args) {

		parent::__construct($args);

		$this->max = isset($args['max']) ? intval($args['max']) : 1;
		$this->min = isset($args['min']) ? intval($args['min']) : 0;
		$this->step = isset($args['step']) ? $args['step'] : 'any';
		$this->def = isset($args['default']) ? intval($args['default']) : midpoint($this->min, $this->max);
	}
	function get($h) {
		return $h
		->div->class('ui field')
			->ins(label($h, $this->label))
			->div
				->input
					->type('range')
					->name($this->name)
					->max($this->max)
					->min($this->min)
					->step($this->step)
					->value($this->def)
				->end
				->span->class('ui left pointing horizontal label range-value')
				->end
			->end
		->end;
	}
	protected function validate($against) {
		return $against
			->filterString()
			->maybeString() // So we end up with a Maybe<> if not required
			->filterNumber(false)
			->minMaxNumber($this->min, $this->max)
			->stepNumber($this->step);
	}
}


class Password extends SpecialInput {
	function __construct($args) {
		parent::__construct($args);
		$this->matchHash = isset($args['match-hash']) ? $args['match-hash'] : null;
	}
	function get($h) {
		return $this->makeInput($h, 'password', '');
	}
	protected function validate($against) {
		return $against
			->filterString()
			->matchHash( isset($this->matchHash) ? $this->matchHash : null )
			->minMaxLength($this->minLength, $this->maxLength)
			->matchRegex($this->mustMatch)
			->filterEmptyString()
			->requiredMaybe($this->required);
	}
	function getMerger($val) {
		return parent::getMerger($val)
			->bind(function($x) {
				// Avoid storing passwords.
				return new OkJust([]);
			});
	}
}

class PhoneNumber extends SpecialInput {
	function __construct($args) {
		parent::__construct($args);
		$this->required = isset($args['required']);
	}
	function get($h) {
		return $this->makeInput($h, 'tel', 'call');
	}
	protected function validate($against) {
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

		$this->required = isset($args['required']);
		$this->mustHaveDomain = isset($args['must-have-domain']) ? $args['must-have-domain'] : null;
	}
	function get($h) {
		return $this->makeInput($h, 'email', 'mail');
	}
	protected function validate($against) {
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

		$this->required = isset($args['required']);
	}
	function get($h) {
		return $this->makeInput($h, 'url', 'world');
	}
	protected function validate($against) {
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

		$this->required = isset($args['required']);
		$this->min = isset($args['min']) ? intval($args['min']) : -INF;
		$this->max = isset($args['max']) ? intval($args['max']) : INF;
		$this->integer = isset($args['integer']);
	}
	function get($h) {
		return $this->makeInput($h, 'number', '');
	}
	protected function validate($against) {
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

		$this->required = isset($args['required']);
		$this->min = isset($args['min']) ? DateTimeImmutable::createFromFormat('Y-m-d', $args['min']) : null;
		$this->max = isset($args['max']) ? DateTimeImmutable::createFromFormat('Y-m-d', $args['max']) : null;
	}
	function get($h) {
		return $h
		->div->class('field ' . ($this->required ? ' required' : ''))
			->ins(label($h, $this->label))
			->div->class('ui left icon input')
				->i->class('calendar icon')->end
				->input->type('text')->name($this->name)->data('inputmask', " 'alias': 'mm/dd/yyyy' ")->end
				->end
			->end
		->end;
	}
	protected function validate($against) {
		return $against
			->filterDate()
			->requiredMaybe($this->required)
			->minMaxDate($this->min, $this->max);
	}
}

class GroupHeader extends EmptyComponent {
	function __construct($args) {
		$this->text = $args['text'];
		parent::__construct($args);
	}
	function get($h) {
		return $h
		->h5->class('ui header attached ')
			->t($this->text)
		->end;
	}
}

class Header extends EmptyComponent {
	function __construct($args) {
		if(is_string($args)) {
			$args = ['text' => $args];
		}
		$this->text = $args['text'];
		$this->subhead = isset($args['subhead']) ? $args['subhead'] : null;
		$this->icon = isset($args['icon']) ? $args['icon'] : null;
		$this->size = isset($args['size']) ? intval($args['size']) : 1;
		parent::__construct($args);
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

abstract class BaseNotice extends EmptyComponent {
	function __construct($args) {
		$this->__args = $args; // Used by Group later on

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
		->div->class('ui message attached ' . ($this->icon === null ? '' : ' icon') . ($this->type ? ' ' . $this->type : ''))
			->add(parent::get($h))
		->end;
	}
}

class Notice extends BaseNotice {
	function get($h) {
		return
		$h
		->div->class('ui message floating ' . ($this->icon === null ? '' : ' icon') . ($this->type ? ' ' . $this->type : ''))
			->add(parent::get($h))
		->end;
	}
}



class ListComponent extends GroupComponent {
	function __construct($args) {
		$this->items = $args['items'];
		$this->name = $args['name'];
		$this->label = $args['label'];
		$this->addText = isset($args['add-text']) ? $args['add-text'] : 'Add an item';
		parent::__construct($args);
	}
	function get($h) {


		return $h
		->div->class('ui field not-validation-root list-component')->data('count','0')->data('group-name', $this->name)
			->h5->class('top attached ui message')->t($this->label)->end
			->div->data('validation-name', $this->name)->class('validation-root ui bottom attached segment list-items')
				->script->type('text/template')
					->div->class('ui vertical segment close-item')
							->div->class('content')
								->add($this->items)
							->end
							->button->type('button')->class('ui compact negative icon button delete-btn')->i->class('trash icon')->end->end
					->end
				->end
				->div->class('ui center aligned vertical segment')
					->button->type('button')->class('ui primary labeled icon button add-item')
						->i->class('plus icon')->end
						->t($this->addText)
					->end
				->end
			->end
		->end;
	}
	function getMerger($val) {


		$val = $val->innerBind(function($v) {
			return new OkJust(isset($v->post[$this->name]) ? $v->post[$this->name] : null);
		});
		return $val
		->innerBind(function($data) {
			if($data === null) {
				return new OkJust([]);
			} else if(is_array($data)) {
				return new OkJust($data);
			} else {
				return new Err([
					$this->name => 'Invalid data'
				]);;
			}
		})
		->innerBind(function($list) {

			$result = new OkJust([]);
			foreach ($list as $index => $value) {
				$validationResult = parent::getMerger(
					new OkJust(
						new ClientData($value, null)
					)
				);

				$result = $result
					->innerBind(function($soFar) use($validationResult, $index) {
						return $validationResult
							->innerBind(function($fieldResult) use($soFar, $index) {
								$soFar[$index] = $fieldResult;
								return new OkJust($soFar);
							})
							->bind_err(function($fieldError) {
								return new Err([]);
							});
					})
					->bind_err(function($errorSoFar) use($validationResult, $index) {
						return $validationResult
							->bind_err(function($fieldError) use($errorSoFar, $index) {
								foreach($fieldError as $k => $v) {
									$errorSoFar[ $this->name . '[' . $index . '][' . $k . ']'  ] = $v;
								}

								return new Err($errorSoFar);
							})
							->innerBind(function($fieldResult) use($errorSoFar) {
								return new Err($errorSoFar);
							});

					});
			}
			$result = $result
				->innerBind(function($x) {
					return new OkJust([$this->name => array_values($x)]);
				});
			return $result;
		});
	}
}

class Group extends GroupComponent {

	function __construct($args) {
		$this->items = $args['fields'];
		parent::__construct($args);
	}
	function get($h) {

		$items = array_map(function($item) {
			if($item instanceof Header) {
				return new GroupHeader(['text' => $item->text]);
			} else if($item instanceof Notice) {
				return new GroupNotice($item->__args);
			} else {
				return $item;
			}
		}, $this->items);

		return $h
		->div->class('group')
			->add(array_map(function($value) use ($h) {
					if(is_array($value)) {
						return $h->div->class('ui segment attached')
							->add($value)
						->end;
					} else {
						return $value;
					}
				}, array_reduce($items, function($carry, $item) {
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


class FormElem extends GroupComponent {


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

class Page extends Component {
	function __construct($args) {
		$this->form = $args['fields'];
		$this->title = isset($args['title']) ? $args['title'] : 'Form';
		$this->successMessage = isset($args['success-message']) ? $args['success-message'] : 'The form was submitted successfully.';
		$this->debug = isset($args['debug']);
		$this->outputs = $args['outputs'];
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
				->script->src('vendor/components/jquery/jquery.min.js')->end
				->script->src('vendor/robinherbots/jquery.inputmask/dist/jquery.inputmask.bundle.js')->end
				->script->src('vendor/semantic/ui/dist/semantic.js')->end
				->script->src('client.js')->end
			->end
		->end;
	}
	function getMerger($against) {
		return $this->form->getMerger($against)
			->innerBind(function($r) {
				$r['_timestamp'] = new DateTimeImmutable();
				$r['_ip'] = $_SERVER['REMOTE_ADDR'];
				return new OkJust($r);
			});
	}
}

