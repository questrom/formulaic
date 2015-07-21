<?php

use Sabre\Xml\XmlDeserializable as XmlDeserializable;


// Abstract component classes
// ==========================

interface FormPartFactory {
	public function makeFormPart();
	public function makeGroupPart();
}

interface Storeable {
	public function getSubmissionPart($val);
	public function getAllFields();
}

interface Enumerative {
	public function getPossibleValues();
}

interface Renderable {
	public function render();
}


interface TableViewPartFactory {
	public function makeTableViewPart($value);
}

interface DetailsViewPartFactory {
	public function makeDetailsViewPart($value);
}

interface EmailViewPartFactory {
	public function makeEmailViewPart($value);
}


trait Groupize {
	public function makeGroupPart() {
		return $this->makeFormPart();
	}
}

trait Tableize {
	function makeDetailsViewPart($v) {
		return $this->makeTableViewPart($v);
	}
	function makeEmailViewPart($v) {
		return $this->makeTableViewPart($v);
	}
}

abstract class NamedLabeledComponent implements FormPartFactory, XmlDeserializable,
	TableViewPartFactory, DetailsViewPartFactory, EmailViewPartFactory, Storeable {

	use Configurable, Tableize, Groupize;

	function __construct($args) {
		$this->label = $args['label'];
		$this->name = $args['name'];
		$this->customSublabel = isset($args['sublabel']) ? $args['sublabel'] : null;
	}

	function makeTableViewPart($v) {
		return new OrdinaryTableCell($v);
	}

	final function getAllFields() {
		return [$this->name => $this];
	}

	final function getLabel($sublabel = '') {
		return new Label($this->label, isset($this->customSublabel) ? $this->customSublabel : $sublabel);
	}
}

abstract class PostInputComponent extends NamedLabeledComponent {
	function getSubmissionPart($val) {
		return $this->validate(
			$val->innerBind(function ($x) {
					return Result::ok($x->post);
				})
				->byName($this->name)
		)->name($this->name);
	}
	protected abstract function validate($val);
}

abstract class FileInputComponent extends NamedLabeledComponent {
	final function getSubmissionPart($val) {
		return $this->validate(
			$val
				->innerBind(function ($x) {
					return Result::ok($x->files);
				})
				->byName($this->name)
		)->name($this->name);
	}
	protected abstract function validate($val);
}

abstract class GroupComponent implements FormPartFactory, Storeable, XmlDeserializable {
	use Configurable, Groupize;
	final function getAllFields() {
		$arr = [];
		foreach ($this->items as $item) {
			if ($item instanceof Storeable) {
				$arr = array_merge($arr, $item->getAllFields());
			}
		}
		return $arr;
	}
	final function getSubmissionPart($val) {
		return $val->groupValidate($this->items);
	}
}
