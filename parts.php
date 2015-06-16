<?php

require('include/HTMLGenerator.php');

abstract class Component extends ArrayObject {
	// abstract function __construct($args);
	abstract function get($h);
	abstract function validate($against);
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
		if($this->required && $against !== 'on') {
			return 'Please check this checkbox.';
		} else {
			return null;
		}
	}
}

class Textarea extends Component {	
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
	function validate($str) {
		if($this->required && trim($str) === '') {
			return 'This field is required.';
		}
		if(strlen($str) > $this->maxLength) {
			return 'The input is too long. Maximum is ' . $this->maxLength . ' characters.';
		}
		if(strlen($str) < $this->minLength) {
			return 'The input is too short. Minimum is ' . $this->minLength . ' characters.';	
		}
		if($this->mustMatch !== null && preg_match($this->mustMatch, $str) === 0) {
			return 'This input is not valid. It must match the pattern: ' . $this->mustMatch;
		}
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
							return $h->div->class('item')->data('value',$v)->t($v)->end;
						},
						$this->options
					))
				->end
			->end
		->end;
	}
	function validate($against) {
		if($this->required && $against === '') {
			return 'This field is required.';
		} 
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
		if($this->required && $against === null) {
			return 'Please choose an option.';
		}
	}
}


class Checkboxes extends Component {
	function __construct($args) {
		$args = array_merge($args, [
			"max-choices" => INF,
			"min-choices" => 0,
			"required" => false
		]);

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
		if($against === null) {
			$against = [];
		}
		if($this->required && count($against) === 0) {
			return 'This field is required.';
		}
		if(count($against) < $this['min-choices']) {
			return 'Please choose at least ' . $this['min-choices'] . ' options.'; 
		}
		if(count($against) > $this['max-choices']) {
			return 'At most ' . $this['max-choices'] . ' choices are allowed.'; 
		}
	}
}

class Textbox extends Component {
	function __construct($args) {
		$this->label = $args['label'];
		$this->name = $args['name'];
		$this->required = isset($args['required']) ? $args['required'] : false;


		$this->maxLength = isset($args['max-length']) ? $args['max-length'] : INF;
		$this->minLength = isset($args['min-length']) ? $args['min-length'] : 0;
		$this->required = isset($args['required']) ? $args['required'] : false;
		$this->mustMatch = isset($args['must-match']) ? $args['must-match'] : null;
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
	function validate($str) {
		if($this->required && trim($str) === '') {
			return 'This field is required.';
		}
		if(strlen($str) > $this->maxLength) {
			return 'The input is too long. Maximum is ' . $this->maxLength . ' characters.';
		}
		if(strlen($str) < $this->minLength) {
			return 'The input is too short. Minimum is ' . $this->minLength . ' characters.';	
		}
		if($this->mustMatch !== null && preg_match($this->mustMatch, $str) === 0) {
			return 'This input is not valid. It must match the pattern: ' . $this->mustMatch;
		}
	}
}

abstract class SpecialInput extends Component {
	function __construct($args) {
		$this->label = $args['label'];
		$this->name = $args['name'];

		$this->maxLength = isset($args['max-length']) ? $args['max-length'] : INF;
		$this->minLength = isset($args['min-length']) ? $args['min-length'] : 0;

		$this->required = isset($args['required']) ? $args['required'] : false;
		$this->mustMatch = isset($args['must-match']) ? $args['must-match'] : null;

		// Allowed for all fields but only really makes sense for password ones
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
	function validate($str) {
		if($this->matchHash !== null && !password_verify($str, $this->matchHash)) {
			return 'Password incorrect!';
		}
		if($this->required && trim($str) === '') {
			return 'This field is required.';
		}
		if(strlen($str) > $this->maxLength) {
			return 'The input is too long. Maximum is ' . $this->maxLength . ' characters.';
		}
		if(strlen($str) < $this->minLength) {
			return 'The input is too short. Minimum is ' . $this->minLength . ' characters.';	
		}
		if($this->mustMatch !== null && preg_match($this->mustMatch, $str) === 0) {
			return 'This input is not valid. It must match the pattern: ' . $this->mustMatch;
		}
	}
}



class Password extends SpecialInput {
	function get($h) {
		return $this->render($h, 'password', '');
	}
}

class PhoneNumber extends SpecialInput {
	function get($h) {
		return $this->render($h, 'tel', 'call');
	}
}

class EmailAddr extends SpecialInput {
	function get($h) {
		return $this->render($h, 'email', 'mail');
	}
}

class NumberInp extends SpecialInput {
	function get($h) {
		return $this->render($h, 'number', '');
	}
}

class DateTimePicker extends Component {
	function __construct($args) {
		$this->label = $args['label'];
		$this->name = $args['name'];
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
	function validate($against) {}
}


class Group extends Component {
	function __construct($args) {
		$this->items = $args;
	}
	function get($h) {
		return $h->div->class('ui segment')
			->add($this->items)
		->end;
	}
	function validate($against) {
		$total = [];
		foreach($this->items as $x) {
			if($x instanceof Group) {

				$result = $x->validate( $_POST  );
				$total = array_merge($total, $result);
			} else {
				$result = $x->validate( isset($against[$x->name]) ? $against[$x->name] : null  );
				if($result != null) {
					$total[$x->name] = $result;
				}
			}
		}
		return $total;
	}
}


class Form extends Component {
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
	function validate($against) {
		$total = [];
		foreach($this->items as $x) {
			if($x instanceof Group) {
				$result = $x->validate( $_POST  );
				$total = array_merge($total, $result);
			} else {
				$result = $x->validate( isset($against[$x->name]) ? $against[$x->name] : null  );
				if($result != null) {
					$total[$x->name] = $result;
				}
			}
		}
		return $total;
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
		// semantic-ui doesnt escape things for us
		return array_map('htmlspecialchars', $this->form->validate($against));
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