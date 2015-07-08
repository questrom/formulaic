<?php

use Sabre\Xml\XmlDeserializable as XmlDeserializable;


// Abstract component classes
// ==========================


interface HTMLComponent {
	public function get($h);
}

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

trait NormalTableCell {
    function asTableCell($h, $value) {
		return $value->innerBind(function($v) use ($h) {
			return Result::ok($h->td->t($v)->end);
		});
	}
	function asDetailedTableCell($h, $value) {
		return $this->asTableCell($h, $value);
	}
	function asEmailTableCell($h, $value) {
		return $this->asTableCell($h, $value);
	}
}

abstract class NamedLabeledComponent implements FormPartFactory, Validatable, NameMatcher, XmlDeserializable, FieldListItem, FieldTableItem {

	use Configurable;
	use NormalTableCell;

	function __construct($args) {
		$this->label = $args['label'];
		$this->name = $args['name'];
	}

	final function getAllFields() { return [ $this ]; }
	final function getLabel() { return new Label($this->label); }
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


    function getAllFields() {
		$arr = [];
		foreach($this->items as $item) {
			if($item instanceof NameMatcher) {
				$arr = array_merge($arr, $item->getAllFields());
			}
		}
		return $arr;
	}
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
	final protected function validate($against) {
		return $against->groupValidate($this->items);
	}
}
