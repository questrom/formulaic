<?php

require('include/HTMLGenerator.php');

abstract class Component {
	abstract function __construct($args);
	abstract function get($h);
	abstract function validate($against);
}

class Checkbox extends Component {	
	function __construct($args) {
		$this->label = $args['label'];
		$this->name = $args['name'];
	}
	function get($h) {
		return $h
		->div->class('field')
			->div->class('ui checkbox')
				->input->type('checkbox')->name($this->name)->end
				->label->t($this->label)->end
			->end
		->end;
	}
	function validate($against) {
		return null;
	}
}

class Textarea extends Component {	
	function __construct($args) {
		$this->label = $args['label'];
		$this->name = $args['name'];
	}
	function get($h) {
		return $h
		->div->class('field')
			->label->t($this->label)->end
			->textarea->name($this->name)->end
		->end;
	}
	function validate($against) {
		return null;
	}
}


class Dropdown extends Component {
	function __construct($args) {
		$this->label = $args['label'];
		$this->name = $args['name'];
		$this->options = $args['options'];
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
	function validate($a) {}
}

class Radios extends Component {
	function __construct($args) {
		$this->label = $args['label'];
		$this->name = $args['name'];
		$this->options = $args['options'];
	}
	function get($h) {
		return $h
		->div->class('grouped fields')
			->label->t($this->label)->end
			->add(
				array_map(
					function($v) use($h) {
						return $h->div->class('field')
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
	function validate($a) {}
}

class Checkboxes extends Component {
	function __construct($args) {
		$this->label = $args['label'];
		$this->name = $args['name'];
		$this->options = $args['options'];
	}
	function get($h) {
		return $h
		->div->class('grouped fields')
			->label->t($this->label)->end
			->add(
				array_map(
					function($v) use($h) {
						return $h->div->class('field')
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
	function validate($a) {}
}

class Textbox extends Component {
	function __construct($args) {
		$this->label = $args['label'];
		$this->name = $args['name'];
		$this->required = isset($args['required']) ? $args['required'] : false;
		$this->password = isset($args['password']) ? $args['password'] : false;
	}
	function get($h) {
		return $h
		->div->class('ui field ' . ($this->required ? 'required' : ''))
			->label->t($this->label)->end
			->div->class('ui input')
				->input->type($this->password ? 'password' : 'text')->name($this->name)->end
			->end
		->end;
	}
	function validate($against) {
		if($this->required && $against == "") {
			return "Required field cannot be empty";
		} else {
			return null;
		}
	}
}

abstract class SpecialInput extends Component {
	function __construct($args) {
		$this->label = $args['label'];
		$this->name = $args['name'];
	}
	function render($h, $type, $icon) {
		return $h
		->div->class('ui field')
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

class PhoneNumber extends SpecialInput {
	function get($h) {
		return $this->render($h, 'tel', 'call');
	}
	function validate($against) {}
}

class EmailAddr extends SpecialInput {
	function get($h) {
		return $this->render($h, 'email', 'mail');
	}
	function validate($against) {}	
}

class NumberInp extends SpecialInput {
	function get($h) {
		return $this->render($h, 'number', '');
	}
	function validate($against) {}		
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
		return $this->form->validate($against);
	}
}

function parse_yaml($file) {
	return yaml_parse_file($file, 0, $ndocs, array(
		'!checkbox' => function($v) { return new Checkbox($v); },
		'!textbox' => function($v) { return new Textbox($v); },
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