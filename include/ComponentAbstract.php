<?php

use Sabre\Xml\XmlDeserializable as XmlDeserializable;


// Abstract component classes
// ==========================

interface FormPartFactory {
	public function makeFormPart();
	public function makeGroupPart();
}

interface Storeable {
	public function getMerger($val);
	public function getAllFields();
}

interface Enumerative {
	public function getPossibleValues();
}

interface Renderable {
	public function render();
}

interface NormalTableCellFactory {
	public function makeTableCellPart($value);
}

interface DetailsTableCellFactory {
	public function makeDetailedTableCell($value);
	public function makeEmailTableCell($value);
}


trait Groupize {
	public function makeGroupPart() {
		return $this->makeFormPart();
	}
}

trait Tableize {
	function makeDetailedTableCell($v) {
		return $this->makeTableCellPart($v);
	}
	function makeEmailTableCell($v) {
		return $this->makeTableCellPart($v);
	}
}

abstract class NamedLabeledComponent implements FormPartFactory, XmlDeserializable,
    NormalTableCellFactory, DetailsTableCellFactory, Storeable {


	use Configurable, Tableize, Groupize;

	function __construct($args) {
		$this->label = $args['label'];
		$this->name = $args['name'];
		$this->customSublabel = isset($args['sublabel']) ? $args['sublabel'] : null;
	}

	function makeTableCellPart($v) {
		return new OrdinaryTableCell($v);
	}

	final function getAllFields() {
		return [ $this->name => $this ];
	}

	final function getLabel($sublabel = '') {
		return new Label($this->label, isset($this->customSublabel) ? $this->customSublabel : $sublabel);
	}
}

abstract class PostInputComponent extends NamedLabeledComponent {
	function getMerger($val) {
		return $this->validate(
			$val
			->innerBind(function($x) {
				return Result::ok($x->post);
			})
			->byName($this->name)
		)
		->name($this->name);
	}
	protected abstract function validate($val);
}

abstract class FileInputComponent extends NamedLabeledComponent {
	final function getMerger($val) {
		return $this->validate(
			$val
			->innerBind(function($x) {
				return Result::ok($x->files);
			})
			->byName($this->name)
		)
		->name($this->name);
	}
	protected abstract function validate($val);
}

abstract class GroupComponent implements FormPartFactory, Storeable, XmlDeserializable {
	use Configurable, Groupize;
	final function getAllFields() {
		$arr = [];
		foreach($this->items as $item) {
			if($item instanceof Storeable) {
				$arr = array_merge($arr, $item->getAllFields());
			}
		}
		return $arr;
	}
	final function getMerger($val) {
		return $val->groupValidate($this->items);
	}
}
