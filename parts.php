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
					return new Err('Invalid input!');
				}
				return new Ok($x);
			});
	}
}


trait GroupValidate {
	function validate($against) {
		return array_reduce($this->items, function($total, $x) use($against) {
			if($x instanceof Group) {
				$result = $x->validate($against);	
				$merger = $result->get();
			} else {
				$result = $x->validate( (isset($x->name) && isset($against[$x->name])) ? $against[$x->name] : null  );
				$merger = isset($x->name) ? [$x->name => $result->get()] : [];
			}
			if($result === null) {
				return $total;
			}
			return $total
				->bind(function($x) use ($result) {
					return $result instanceof Err ? new Err([]) : new Ok($x);
				})
				->bind(function($x) use ($merger) {
					return new Ok(array_merge($merger,$x));
				})
				->bind_err(function($x) use ($merger, $result) {
					return $result instanceof Err ? new Err(array_merge($merger,$x)) : new Err($x);
				});
		}, new Ok([]));
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
					return new Ok(new Nothing());
				} else if(in_array($x, $this->options, TRUE)) {
					return new Ok(new Just($x));
				} else {
					return new Err('Invalid data!');
				}
			})
			->bind(function($x) {
				if($this->required && $x instanceof Nothing) {
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
					return new Ok(new Nothing());
				} else if(in_array($x, $this->options, TRUE)) {
					return new Ok(new Just($x));
				} else {
					return new Err('Invalid data!');
				}
			})
			->bind(function($x) {
				if($this->required && $x instanceof Nothing) {
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
		$this->required  = isset($args['required'])   ? $args['required']   : false;
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
	use TextValidate;
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
		$this->mustHaveDomain = isset($args['must-have-domain']) ? $args['must-have-domain'] : null;
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
			})
			->bind(function($x) {
				if($x !== '' && $this->mustHaveDomain !== null) {
					// The simplest way, according to http://stackoverflow.com/questions/6917198/
					// This seems overly simple, but apparently it works
					$domain = explode('@', $x);
					$domain = array_pop($domain);
					// var_dump($domain);
					if($domain !== $this->mustHaveDomain) {
						return new Err('Domain must equal: ' . $this->mustHaveDomain . '.');
					}
				}
				return new Ok($x);
			});
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
				$addr = filter_var($x, FILTER_VALIDATE_URL);
				if($x === '' || $addr !== false) {
					return new Ok($addr);
				} else {
					return new Err('Invalid URL.');
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
				if(trim($x) === '') {
					return new Ok(new Nothing());
				} else {
					return new Ok(new Just($x));
				}
			})
			->bind(function($x) {
				if($this->required && $x instanceof Nothing) {
					return new Err('This field is required.');
				}
				return new Ok($x);
			})
			->bind(function($x) {
				if($x instanceof Nothing) {
					return new Ok(new Nothing());	
				}

				$num = filter_var($x->get(), FILTER_VALIDATE_INT);

				if($num !== false) {
					return new Ok(new Just($num));
				} else {
					return new Err('Invalid number.');
				}
			})
			->bind(function($x) {
				if($x instanceof Just && ($x->get() < $this->min || $x->get() > $this->max)) {
					return new Err('Number must be between ' . $this->min . ' and ' . $this->max . '.');
				}
				return new Ok($x);
			});
	}
}

class DatePicker extends Component {
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
		return (new Ok($against))
			->bind(function($x) {
				if($x === '') {
					return new Ok(new Nothing());
				}
				
				$date = DateTimeImmutable::createFromFormat('Y-m-d', $x);

				if($date !== false) {
					return new Ok(new Just($date));
				} else {
					return new Err('Invalid date!');
				}
			})
			->bind(function($x) {
				if($this->required && $x instanceof Nothing) {
					return new Err('This field is required.');
				}
				return new Ok($x);
			})
			->bind(function($x) {
				if($x instanceof Just && $this->minDate !== null && $this->minDate > $x->get()) {
					return new Err('Please enter a date starting at ' . $this->minDate->format('Y-m-d'));
				}
				return new Ok($x);
			})
			->bind(function($x) {
				if($x instanceof Just && $this->maxDate !== null && $this->maxDate < $x->get()) {
					return new Err('Please enter a date ending at ' . $this->maxDate->format('Y-m-d'));
				}
				return new Ok($x);
			});

	}
}

class GroupHeader extends Component {
	function __construct($str) {
		$this->text = $str;
	}
	function get($h) {
		return $h->t($this->text);
	}
	function validate($a) {
		return null;
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
		$this->size = isset($args['size']) ? $args['size'] : 2;

		/*

			 text: 'Hello world'
			    subhead: 'this is a test'
			    icon: plug
			    size: 1
		*/
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
	function validate($a) {
		return null;
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
	function validate($a) {
		return null;
	}
}

class Notice extends GroupNotice {
	function get($h) {
		return $h->div->class('ui message floating ' . ($this->icon === null ? '' : ' icon') . ($this->type ? ' ' . $this->type : ''))
			->add(parent::get($h))
		->end;
	}
}

function startEndMap(callable $fn, $list) {
	$lastPos = count($list) - 1;
	$result = [];
	foreach ($list as $key => $value) {
		if($key === 0 && $key === $lastPos) {
			$result[] = $fn($value, 'ONLY');
		} else if($key === 0) {
			$result[] = $fn($value, 'FIRST');
		} else if($key === $lastPos) {
			$result[] = $fn($value, 'LAST');
		} else {
			$result[] = $fn($value, 'MIDDLE');
		}
	}
	return $result;
}

class Group extends Component {
	use GroupValidate;
	function __construct($args) {
		$this->items = $args['fields'];
		// $this->header = isset($args['header']) ? $args['header'] : null;
	}
	function get($h) {

		return $h
			->add(
				startEndMap(function($value, $place) use ($h) {
					
					if($place === 'ONLY') {
						$attachment = '';
					} else if($place === 'FIRST') {
						$attachment = 'top attached';
					} else if($place === 'LAST') {
						$attachment = 'bottom attached';
					} else if($place === 'MIDDLE') {
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
						array_push($carry, $item);
						return $carry;
					} else if( is_array(end($carry)) ) {
						array_push($carry[count($carry)-1], $item);
						return $carry;
					} else {
						array_push($carry, [$item]);
						return $carry;
					}
				}, []))
			);
	
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
			->button->type('button')->class('ui labeled icon positive big button centered-button')->data('submit','true')
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

class DebugOutput {
	function __construct($args) {}
	function run($data) {
		var_dump($data);
	}
}

function parse_yaml($file) {
	return yaml_parse_file($file, 0, $ndocs, array(
		'!checkbox' => [ new ReflectionClass('Checkbox'), 'newInstance'],
		'!textbox' => function($v) { return new Textbox($v); },
		'!password' => function($v) { return new Password($v); },
		'!dropdown' => function($v) { return new Dropdown($v); },
		'!radios' => function($v) { return new Radios($v); },
		'!checkboxes' => function($v) { return new Checkboxes($v); },
		'!textarea' => function($v) { return new Textarea($v); },
		'!group' => function($v) { return new Group($v); },
		'!date' => function($v) { return new DatePicker($v); },
		'!phonenumber' => function($v) { return new PhoneNumber($v); },
		'!email' => function($v) { return new EmailAddr($v); },
		'!url' => function($v) { return new UrlInput($v); },
		'!number' => function($v) { return new NumberInp($v); },
		'!debug' => function($v) { return new DebugOutput($v); },
		'!groupheader' => function($v) { return new GroupHeader($v); },
		'!groupnotice' => function($v) { return new GroupNotice($v); },
		'!notice' => function($v) { return new Notice($v); },
		'!header' => function($v) { return new Header($v); }

	));
}