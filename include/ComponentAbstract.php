<?php

use Sabre\Xml\XmlDeserializable as XmlDeserializable;


// Abstract component classes
// ==========================

interface FormPartFactory {
	public function makeFormPart();
}

interface Validatable {
	public function getMerger($val);
}

interface NameMatcher {
	public function getByName($name);
	public function getAllFields();
}


interface FieldTableItem extends NameMatcher {
	public function asTableCell($h, $value);
}

interface FieldListItem {
	public function asDetailedTableCell($h, $value);
	public function asEmailTableCell($h, $value);
}


interface Enumerative {
	public function getPossibleValues();
}


interface Renderable {
	public function render();
}


abstract class BaseHeader implements FormPartFactory, XmlDeserializable  {
	use Configurable;
	final function __construct($args) {
		$this->__args = $args;

		$this->text = $args['innerText'];
		$this->subhead = isset($args['subhead']) ? $args['subhead'] : null;
		$this->icon = isset($args['icon']) ? $args['icon'] : null;
		$this->size = isset($args['size']) ? intval($args['size']) : null;
	}
}


abstract class BaseNotice implements FormPartFactory, XmlDeserializable {
	use Configurable;
	final function __construct($args) {
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

}

class OrdinaryTableCell implements Renderable {
	function __construct($value) {
		$this->h = new HTMLParentlessContext();
		$this->value = $value;
	}
	function render() {
		return $this->h
			->td
				->t($this->value)
			->end;
	}
}

abstract class NamedLabeledComponent implements FormPartFactory, Validatable, NameMatcher, XmlDeserializable, FieldListItem, FieldTableItem {

	use Configurable;

	function asTableCell($h, $value) {
		return $value->innerBind(function($v) {
			return Result::ok( (new OrdinaryTableCell($v))->render() );
		});
	}
	function asDetailedTableCell($h, $value) {
		return $this->asTableCell($h, $value);
	}
	function asEmailTableCell($h, $value) {
		return $this->asTableCell($h, $value);
	}

	function __construct($args) {
		$this->label = $args['label'];
		$this->name = $args['name'];
		$this->customSublabel = isset($args['sublabel']) ? $args['sublabel'] : null;
	}

	final function getAllFields() { return [ $this ]; }
	final function getLabel($sublabel = '') {
		return new Label($this->label, isset($this->customSublabel) ? $this->customSublabel : $sublabel);
	}
	final function getByName($name) { return ($this->name === $name) ? $this : null; }

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
	final function getMerger($val) {
		return parent::getMerger(
			$val->innerBind(function($x) {
				return Result::ok($x->files);
			})
		);
	}
}

abstract class GroupComponent implements FormPartFactory, Validatable, NameMatcher, XmlDeserializable {
	use Configurable;


	final function getAllFields() {
		$arr = [];
		foreach($this->items as $item) {
			if($item instanceof NameMatcher) {
				$arr = array_merge($arr, $item->getAllFields());
			}
		}
		return $arr;
	}
	final function getByName($name) {
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
	final function getMerger($val) {
		return $val->groupValidate($this->items);
	}
}
