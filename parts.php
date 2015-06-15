<?php




class HTMLGenerator {
	function __construct() {
		$this->tagStack = [];
		$this->text = '';
		$this->closed = true;
	}
	function __call($name, $args) {
		$this->text .= ' ' . $name . '="' . htmlspecialchars($args[0]) . '"';
		return $this;
	}
	function __get($name) {
		if($name === "end") {
			$this->close();
			$tag = array_pop($this->tagStack);
			$this->text .= '</' . $tag . '>';
			if(count($this->tagStack) === 0) {
				$ret = $this->text;
				$this->text = '';
				return $ret;
			} else {
				return $this;
			}
		} else {
			$this->close();
			$this->tagStack[] = $name;
			$this->text .= '<' . $name;
			$this->closed = false;
			return $this;
		}
	}
	function add($arr) {
		$this->close();
		if(is_array($arr)) {
			foreach ($arr as $item) {
				$this->add($item);
			}	
		} else {
			$this->text .= ($arr instanceof Component) ? $arr->get(new HTMLGenerator()) : $arr;	
		}
		return $this;
	}
	private function close() {
		if(!$this->closed) {
			$this->text .= '>';
		}
		$this->closed = true;
	}
	function t($text) {
		$this->close();
		$this->text .= htmlspecialchars($text);
		return $this;
	}
	function data($key, $val) {
		$this->text .= ' data-' . $key . '="' . htmlspecialchars($val) . '"';
		return $this;
	}
}



abstract class Component {
	abstract function __construct($args);
	abstract function get($h);
	abstract function validate($against);
}

class Checkbox extends Component {	
	public $label;
	public $name;
	function __construct($args) {
		$this->label = $args['label'];
		$this->name = $args['name'];
	}
	function get($h) {
		return $h->div->class('field')
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
	public $label;
	public $name;
	function __construct($args) {
		$this->label = $args['label'];
		$this->name = $args['name'];
	}
	function get($h) {
		return $h->div->class('field')
			->label->t($this->label)->end
			->textarea->name($this->name)->end
		->end;
	}
	function validate($against) {
		return null;
	}
}


class Dropdown extends Component {
	public $name;
	public $label;
	public $options;
	function __construct($args) {
		$this->label = $args['label'];
		$this->name = $args['name'];
		$this->options = $args['options'];
	}
	function get($h) {
		return $h->div->class('field')
				->label->t($this->label)->end
				->div->class('ui fluid dropdown selection')
					->input->name($this->name)->type('hidden')->value('')->end
					->div->class('default text')->t('Please choose an option...')->end
					->i->class('dropdown icon')->end
					->div->class('menu')
						->add(array_map(
							function($v) { $h = new HTMLGenerator(); return $h->div->class('item')->data('value',$v)->t($v)->end; },
							$this->options
						))
					->end
				->end
			->end;
	}
	function validate($a) {}
}

class Radios extends Component {
	public $name;
	public $label;
	public $options;
	function __construct($args) {
		$this->label = $args['label'];
		$this->name = $args['name'];
		$this->options = $args['options'];
	}
	function get($h) {
		return $h->div->class('grouped fields')
			->label->t($this->label)->end
			->add(
				array_map(
					function($v) {
						$h = new HTMLGenerator();
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
	public $name;
	public $label;
	public $options;
	function __construct($args) {
		$this->label = $args['label'];
		$this->name = $args['name'];
		$this->options = $args['options'];
	}
	function get($h) {
		return $h->div->class('grouped fields')
			->label->t($this->label)->end
			->add(
				array_map(
					function($v) {
						$h = new HTMLGenerator();
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
	public $label;
	public $name;
	public $required;
	public $password;
	function __construct($args) {
		$this->label = $args['label'];
		$this->name = $args['name'];
		$this->required = $args['required'];
		$this->password = isset($args['password']) ? $args['password'] : false;
	}
	function get($h) {
		return $h->div->class('ui field ' . ($this->required ? 'required' : ''))
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

class DateTimePicker extends Component {
	public $label;
	public $name;
	function __construct($args) {
		$this->label = $args['label'];
		$this->name = $args['name'];
	}
	function get($h) {
		return $h->div->class('ui field')
			->label->t($this->label)->end
			->div->class('datetime')
				->input->type('hidden')->name($this->name)->end
				// ->div->class('container')->end
				->div->class('ui top attached buttons')
					->button->type('button')->class('ui icon button')
						->i->class('caret left icon')->end
					->end
					->div->class('ui button')
						->t('July 2015')
					->end
					->button->type('button')->class('ui icon button')
						->i->class('caret right icon')->end
					->end
				->end
				->table->class('ui collapsing compact celled small seven column table')
					->thead
						->tr
							->th

								->button->type('button')->class('ui compact icon button fluid left floated')
									->i->class('caret left icon')->end
								->end
							->end
							->th->colspan(5)
								->h4->class('ui small header')
									->t('July 2015')
								->end
							->end
							->th
								->button->type('button')->class('ui compact icon button fluid right floated')
									->i->class('caret right icon')->end
								->end
							->end
						->end
					->end
					->tbody
						->add(
							array_map(
								function($v) {
									 $h = new HTMLGenerator();
									 return $h->tr->add( 
									 	array_map(
											function($v) {
												 $h = new HTMLGenerator();
												 return $h->td->button->type('button')->class('ui compact fluid attached basic button')->t(22)->end->end;
											}
										, range(0, 6))
									)->end;
								}
							, range(0, 5))
						)
					->end
				->end
			->end
		->end;
	}
	function validate($against) {}
}


class Group extends Component {
	public $items;
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
	public $items;
	function __construct($args) {
		$this->items = $args;
	}
	function get($h) {
		return $h->form->class('ui form')->action('submit.php')->method('POST')
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
	public $form;
	function __construct($yaml) {
		$this->form = new Form($yaml['fields']);
	}
	function get($h) {
		return $h->div->class('ui page grid')
			->div->class('sixteen wide column')
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
		'!datetime' => function($v) { return new DateTimePicker($v); }

	));
}