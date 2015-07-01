<?php



// Abstract component classes
// ==========================



interface HTMLComponent {
	public function get($h);
}

interface Validatable {
	public function getMerger($val);
}

interface NameMatcher {
	public function getByName($name);
}

interface Cellable extends NameMatcher {
	public function asTableCell($h, $value);
}

interface Component extends HTMLComponent, Validatable, NameMatcher, Sabre\Xml\XmlDeserializable {}

abstract class EmptyComponent extends ConfigElement implements Component {
	function getMerger($val) {
		return Result::ok([]);
	}
	function getByName($name) {
		return null;
	}
}

abstract class BaseHeader extends EmptyComponent {
	function __construct($args) {
		$this->__args = $args;

		$this->text = $args['innerText'];
		$this->subhead = isset($args['subhead']) ? $args['subhead'] : null;
		$this->icon = isset($args['icon']) ? $args['icon'] : null;
		$this->size = isset($args['size']) ? intval($args['size']) : null;
	}
	function get($h) {
		$inside = $h->t($this->text)
		->hif($this->subhead !== null)
			->div->class('sub header')->t($this->subhead)->end
		->end;
		return $h
			->hif($this->icon !== null)
				->i->class($this->icon . ' icon')->end
				->div->class('content')
					->add($inside)
				->end
			->end
			->hif($this->icon === null)
				->add($inside)
			->end;
	}
}

abstract class BaseNotice extends EmptyComponent {
	function __construct($args) {
		$this->__args = $args; // Used by Group later on

		$this->text = $args['text'];
		$this->header = isset($args['header']) ? $args['header'] : null;
		$this->icon = isset($args['icon']) ? $args['icon'] : null;
		$this->list = isset($args['children']) ? $args['children'] : null;
		if(isset($args['children']) && count($args['children']) === 0) {
			$this->list = null;
		}
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

abstract class NamedLabeledComponent extends ConfigElement implements Component, Cellable {
	function __construct($args) {
		$this->label = $args['label'];
		$this->name = $args['name'];
	}
	protected function getLabel() {
		return new Label($this->label);
	}
	function getByName($name) {
		return ($this->name === $name) ? $this : null;
	}
    abstract protected function validate($against);
    function getMerger($val) {
    	$val = $val->innerBind(function($v) {
			return Result::ok(isset($v[$this->name]) ? $v[$this->name] : null);
		});
		return $this->validate($val)
			->collapse()
			->innerBind(function($r) {
				return Result::ok([$this->name => $r]);
			})
			->ifError(function($r) {
				return Result::error([$this->name => $r]);
			});
    }
    function asTableCell($h, $value) {
		return $value->innerBind(function($v) use ($h) {
			return Result::ok($h
			->td
				->t($v)
			->end);
		});
	}
}

abstract class PostInputComponent extends NamedLabeledComponent {
	function getMerger($val) {
		return parent::getMerger(
			$val->innerBind(function($x) {
				return Result::ok($x->post);
			})
		);
	}
}

abstract class FileInputComponent extends NamedLabeledComponent {
	function getMerger($val) {
		return parent::getMerger(
			$val->innerBind(function($x) {
				return Result::ok($x->files);
			})
		);
	}
}


abstract class GroupComponent extends ConfigElement implements Component {
	function getByName($name) {
		$result = null;
		foreach($this->items as $item) {
			if($item instanceof NameMatcher) {
				$result = $item->getByName($name);
				if($result) {
					return $result;
				}
			}
		}
		return $result;
	}
	function getMerger($val) {
		return $this->validate($val);
	}
	protected function validate($against) {
		return $against->innerBind(function($val) {
			return array_reduce($this->items, function($total, $field) use($val) {
				return $field
					->getMerger(Result::ok($val))
					->collapse()
					->innerBind(function($r) {
						return Result::ok(function($total) use ($r) {
							return array_merge($r, $total);
						});
					})
					->ifError(function($r) {
						return Result::error(function($total) use ($r) {
							return array_merge($r, $total);
						});
					})
					->ifError(function($merge) use($total) {
						return $total
							->innerBind(function($x) {
								return Result::error([]);
							})
							->ifError(function($x) use ($merge) {
								return Result::error($merge($x));
							});
					})
					->innerBind(function($merge) use($total) {
						return $total
							->innerBind(function($x) use ($merge) {
								return Result::ok($merge($x));
							});
					});
			}, Result::ok([]));
		});
	}
}

abstract class SpecialInput extends PostInputComponent {
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
			->add($this->getLabel())
			->div->class($icon ? 'ui left icon input' : 'ui input')
				->hif($icon)
					->i->class('icon ' . $icon)->end
				->end
				->input->type($type)->name($this->name)->end
			->end
		->end;
	}
}
