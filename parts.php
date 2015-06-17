<?php

require('include/HTMLGenerator.php');
require('include/Result.php');
date_default_timezone_set('America/New_York');

abstract class Component extends ArrayObject {
	// abstract function __construct($args);
	abstract function get($h);
	abstract function validate($against);
}


trait TextValidate {
	function validate($str) {
		return (new Ok($str))
			->bind(function($x) {
				if(!is_string($x)) {
					return new Err('Invalid data!');
				}
				return new Ok($x);
			})
			->bind(function($x) {
				if(isset($this->matchHash) && $this->matchHash !== null) {
					if(!password_verify($x, $this->matchHash)) {
						return new Err('Password incorrect!');
					}
				}
				
				return new Ok($x);
			})
			->bind(function($x) {
				if($this->required && trim($x) === '') {
					return new Err('This field is required.');
				}
				return new Ok($x);
			})
			->bind(function($x) {
				if(strlen($x) > $this->maxLength) {
					return new Err('The input is too long. Maximum is ' . $this->maxLength . ' characters.');
				}
				return new Ok($x);
			})
			->bind(function($x) {
				if(strlen($x) < $this->minLength) {
					return new Err('The input is too short. Minimum is ' . $this->minLength . ' characters.');
				}
				return new Ok($x);
			})
			->bind(function($x) {
				if($this->mustMatch !== null && preg_match($this->mustMatch, $x) === 0) {
					return new Err('This input is not valid. It must match the pattern: ' . $this->mustMatch);
				}
				return new Ok($x);
			});
	}
}


trait GroupValidate {
	function validate($against) {

		$total = new Ok([]);


		foreach($this->items as $x) {
			if($x instanceof Group) {
				$result = $x->validate($against);	
				$merger = $result->get();
			} else {
				$result = $x->validate( isset($against[$x->name]) ? $against[$x->name] : null  );
				$merger = [$x->name => $result->get()];
			}

			$total = $total
				->bind(function($x) use ($result) {
					return $result instanceof Err ? new Err([]) : new Ok($x);
				})
				->bind(function($x) use ($merger) {
					return new Ok(array_merge($merger,$x));
				})
				->bind_err(function($x) use ($merger, $result) {
					return $result instanceof Err ? new Err(array_merge($merger,$x)) : new Err($x);
				});

		}
		return $total;
	}
}

class Checkbox extends Component {	
	function __construct($args) {
		$this->label = $args['label'];
		$this->name = $args['name'];
		$this->required = isset($args['required']) ? $args['required'] : false;
	}
	function get($h) {
		return $h
		->div->class('field ' . ($this->required ? 'required' : ''))
			->div->class('ui checkbox')
				->input->type('checkbox')->name($this->name)->end
				->label->t($this->label)->end
			->end
		->end;
	}
	function validate($against) {
		return (new Ok($against))
			->bind(function($x) {
				if($x === 'on') {
					return new Ok(true);
				} else if($x === null) {
					return new Ok(false);
				} else {
					return new Err('Invalid data!');
				}
			})
			->bind(function($x) {
				if($this->required && !$x) {
					return new Err('Please check this box before submitting the form.');
				}
				return new Ok($x);
			});
	}
}

class Textarea extends Component {	
	use TextValidate;
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
		->div->class('field ' . ($this->required ? 'required' : ''))
			->label->t($this->label)->end
			->textarea->name($this->name)->end
		->end;
	}

}


class Dropdown extends Component {
	function __construct($args) {
		$this->label = $args['label'];
		$this->name = $args['name'];
		$this->options = $args['options'];

		$this->required = isset($args['required']) ? $args['required'] : false;
	}
	function get($h) {
		return $h
		->div->class('field')
			->label->t($this->label)->end
			->div->class('ui fluid dropdown selection')
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
		return (new Ok($against))
			->bind(function($x) {
				if($x === '') {
					return new Ok(null);
				} else if(in_array($x, $this->options, TRUE)) {
					return new Ok($x);
				} else {
					return new Err('Invalid data!');
				}
			})
			->bind(function($x) {
				if($this->required && $x === null) {
					return new Err('This field is required.');
				} else {
					return new Ok($x);
				}
			});

	}
}

class Radios extends Component {
	function __construct($args) {
		$this->label = $args['label'];
		$this->name = $args['name'];
		$this->options = $args['options'];

		$this->required = isset($args['required']) ? $args['required'] : false;
	}
	function get($h) {
		return $h
		->div->class('grouped fields validation-root ' . ($this->required ? 'required' : ''))
			->label->t($this->label)->end
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
		return (new Ok($against))
			->bind(function($x) {
				if($x === null) {
					return new Ok(null);
				} else if(in_array($x, $this->options, TRUE)) {
					return new Ok($x);
				} else {
					return new Err('Invalid data!');
				}
			})
			->bind(function($x) {
				if($this->required && $x === null) {
					return new Err('Please choose an option.');
				}
				return new Ok($x);
			});
	}
}


class Checkboxes extends Component {
	function __construct($args) {
		$args = array_merge([
			"max-choices" => INF,
			"min-choices" => 0,
			"required" => false
		], $args);
		parent::__construct($args, ArrayObject::ARRAY_AS_PROPS);
	}
	function get($h) {
		return $h
		->div->class('grouped fields validation-root ' . ($this->required ? 'required' : ''))
			->label->t($this->label)->end
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
		return (new Ok($against))
			->bind(function($x) {
				if($x === null) {
					return new Ok([]);
				} else if(is_array($x) && count(array_diff($x, $this->options)) === 0 ) {
					return new Ok($x);
				} else {
					return new Err('Invalid data!');
				}
			})
			->bind(function($x) {
				if($this->required && count($x) === 0) {
					return new Err('This field is required.');
				}
				return new Ok($x);
			})
			->bind(function($x) {
				if(count($x) < $this['min-choices']) {
					return new Err('Please choose at least ' . $this['min-choices'] . ' options.');
				}
				return new Ok($x);
			})
			->bind(function($x) {
				if(count($x) > $this['max-choices']) {
					return new Err('At most ' . $this['max-choices'] . ' choices are allowed.');
				}
				return new Ok($x);
			});
	}
}

class Textbox extends Component {
	use TextValidate;

	function __construct($args) {
		$this->label = $args['label'];
		$this->name = $args['name'];
		$this->required = isset($args['required']) ? $args['required'] : false;


		$this->maxLength = isset($args['max-length']) ? $args['max-length'] : INF;
		$this->minLength = isset($args['min-length']) ? $args['min-length'] : 0;
		$this->required = isset($args['required']) ? $args['required'] : false;
		$this->mustMatch = isset($args['must-match']) ? $args['must-match'] : null;
		$this->matchHash = isset($args['match-hash']) ? $args['match-hash'] : null;

	}
	function get($h) {
		return $h
		->div->class('ui field ' . ($this->required ? 'required' : ''))
			->label->t($this->label)->end
			->div->class('ui input')
				->input->type('text')->name($this->name)->end
			->end
		->end;
	}
}

abstract class SpecialInput extends Component {
	use TextValidate;
	function __construct($args) {
		$this->label = $args['label'];
		$this->name = $args['name'];

		$this->maxLength = isset($args['max-length']) ? $args['max-length'] : INF;
		$this->minLength = isset($args['min-length']) ? $args['min-length'] : 0;
		$this->required = isset($args['required']) ? $args['required'] : false;
		$this->mustMatch = isset($args['must-match']) ? $args['must-match'] : null;
		$this->matchHash = isset($args['match-hash']) ? $args['match-hash'] : null;
	}
	function render($h, $type, $icon) {
		return $h
		->div->class('ui field ' . ($this->required ? 'required' : ''))
			->label
				->t($this->label)
			->end
			->div->class($icon ? 'ui left icon input' : 'ui input')
				->hif($icon)
					->i->class('icon ' . $icon)->end
				->end
				->input->type($type)->name($this->name)->end
			->end
		->end;
	}
}



class Password extends SpecialInput {
	function get($h) {
		return $this->render($h, 'password', '');
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
	function validate($str) {
		return (new Ok($str))
			->bind(function($x) {
				if(!is_string($x)) {
					return new Err('Invalid data!');
				}
				return new Ok($x);
			})
			->bind(function($x) {
				if($this->required && trim($x) === '') {
					return new Err('This field is required.');
				}
				return new Ok($x);
			})
			->bind(function($x) {
				$phn = preg_replace('/[^x+0-9]/', '', $x);
				if(strlen($phn) >= 10 || $x === '') {
					return new Ok($phn);
				} else {
					return new Err('Invalid phone number.');
				}
			});
	}
}

class EmailAddr extends SpecialInput {
	function __construct($args) {
		$this->label = $args['label'];
		$this->name = $args['name'];

		$this->required = isset($args['required']) ? $args['required'] : false;
	}
	function get($h) {
		return $this->render($h, 'email', 'mail');
	}
	function validate($str) {
		return (new Ok($str))
			->bind(function($x) {
				if(!is_string($x)) {
					return new Err('Invalid data!');
				}
				return new Ok($x);
			})
			->bind(function($x) {
				if($this->required && trim($x) === '') {
					return new Err('This field is required.');
				}
				return new Ok($x);
			})
			->bind(function($x) {
				$addr = filter_var($x, FILTER_VALIDATE_EMAIL);
				if($x === '' || $addr !== false) {
					return new Ok($addr);
				} else {
					return new Err('Invalid email address.');
				}
			});
	}
}

class NumberInp extends SpecialInput {
	function __construct($args) {
		$this->label = $args['label'];
		$this->name = $args['name'];

		$this->required = isset($args['required']) ? $args['required'] : false;
		$this->min = isset($args['min']) ? $args['min'] : -INF;
		$this->max = isset($args['max']) ? $args['max'] : INF;
	}
	function get($h) {
		return $this->render($h, 'number', '');
	}
	function validate($str) {
		return (new Ok($str))
			->bind(function($x) {
				if(!is_string($x)) {
					return new Err('Invalid data!');
				}
				return new Ok($x);
			})
			->bind(function($x) {
				if($this->required && trim($x) === '') {
					return new Err('This field is required.');
				}
				return new Ok($x);
			})
			->bind(function($x) {
				if($x === '') {
					return new Ok(null);	
				}

				$num = filter_var($x, FILTER_VALIDATE_INT);

				if($num !== false) {
					return new Ok($num);
				} else {
					return new Err('Invalid number.');
				}
			})
			->bind(function($x) {
				if($x !== null && ($x < $this->min || $x > $this->max)) {
					return new Err('Number must be between ' . $this->min . ' and ' . $this->max . '.');
				}
				return new Ok($x);
			});
	}
}

class DateTimePicker extends Component {
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
			->label->t($this->label)->end
			->div->class('ui dropdown datetime basic button')
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
		return (new Ok($against))
			->bind(function($x) {
				if($x === '') {
					return new Ok(null);
				}
				$date = DateTimeImmutable::createFromFormat('Y-m-d', $x);
				if($date !== false) {
					return new Ok($date);
				} else {
					return new Err('Invalid date!');
				}
			})
			->bind(function($x) {
				if($this->required && $x === null) {
					return new Err('This field is required.');
				}
				return new Ok($x);
			})
			->bind(function($x) {
				if($x !== null && $this->minDate !== null && $this->minDate > $x) {
					return new Err('Please enter a date starting at ' . $this->minDate->format('Y-m-d'));
				}
				return new Ok($x);
			})
			->bind(function($x) {
				if($x !== null && $this->maxDate !== null && $this->maxDate < $x) {
					return new Err('Please enter a date ending at ' . $this->maxDate->format('Y-m-d'));
				}
				return new Ok($x);
			});

	}
}



class Group extends Component {
	use GroupValidate;
	function __construct($args) {
		$this->items = $args;
	}
	function get($h) {
		return $h->div->class('ui segment')
			->add($this->items)
		->end;
	}
}


class Form extends Component {
	use GroupValidate;

	function __construct($args) {
		$this->items = $args;
	}
	function get($h) {
		return $h
		->form->class('ui form')->action('submit.php')->method('POST')
			->add($this->items)
			->button->type('button')->class('ui labeled icon primary button')->data('submit','true')
				->i->class('checkmark icon')->end
				->t('Submit Form')
			->end
		->end;
	}

}

class Page extends Component {
	function __construct($yaml) {
		$this->form = new Form($yaml['fields']);
		$this->name = $yaml['name'];
	}
	function get($h) {
		return $h
		->div->class('ui page grid')
			->div->class('sixteen wide column')
				->h1->t($this->name)->end
				->add($this->form)
			->end
		->end;
	}
	function validate($against) {
		return $this->form->validate($against);
	}
}

function parse_yaml($file) {
	return yaml_parse_file($file, 0, $ndocs, array(
		'!checkbox' => function($v) { return new Checkbox($v); },
		'!textbox' => function($v) { return new Textbox($v); },
		'!password' => function($v) { return new Password($v); },
		'!dropdown' => function($v) { return new Dropdown($v); },
		'!radios' => function($v) { return new Radios($v); },
		'!checkboxes' => function($v) { return new Checkboxes($v); },
		'!textarea' => function($v) { return new Textarea($v); },
		'!group' => function($v) { return new Group($v); },
		'!datetime' => function($v) { return new DateTimePicker($v); },
		'!phonenumber' => function($v) { return new PhoneNumber($v); },
		'!email' => function($v) { return new EmailAddr($v); },
		'!number' => function($v) { return new NumberInp($v); }

	));
}