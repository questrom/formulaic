<?php

# This file includes various interfaces, traits, and abstract classes related to
# components -- that is, classes which are Configurable factories for Renderables.
# For more about Configurable, see Parser.php.

# Implemented by components with associated data (e.g. form fields)
interface Storeable {

	# From the data supplied by the user (usually a ClientData object),
	# obtain the data associated with this form field that should
	# be saved to, say, MongoDB.

	# This both taks and results a "Result" type (see Validate.php).
	public function getSubmissionPart($val);

	# Get all of the Storeable objects contained within this object.
	public function getAllFields();
}

# Implemented by form controls which allow the user to choose from a finite
# set of possible values. This is used by GraphView and not especially important.
interface Enumerative {
	public function getPossibleValues();
}

# A very important interface implemented by everything that can generate HTML.
# So: parts of forms, parts of tables, etc. all implement this same interface.
interface Renderable {
	public function render();
}

# Implemented by Configurables that create Renderables to be displayed within
# forms or within "group" elements. Mainly, this means form fields.
interface FormPartFactory {
	public function makeFormPart();
	public function makeGroupPart();
}

# Implemented by classes that create Renderables to be
# displayed as parts of a table view.
# - $value is the data to be displayed in the table cell.
interface TableViewPartFactory {
	public function makeTableViewPart($value);
}

# Implemented by classes that create Renderables to be
# displayed as parts of the view displayed when the user
# presses the "Details" button in a table.
# - $value is the data to be displayed in the table cell.
interface DetailsViewPartFactory {
	public function makeDetailsViewPart($value);
}

# Implemented by classes that create Renderables to be
# displayed as parts of the view displayed in HTML emails.
# - $value is the data to be displayed in the table cell.
interface EmailViewPartFactory {
	public function makeEmailViewPart($value);
}

# Specifies that a form field looks the same way in groups
# as it does in forms themselves.
trait Groupize {
	public function makeGroupPart() {
		return $this->makeFormPart();
	}
}

# Specifies that a form field looks the same way in details/emails
# as it does in a table view.
trait Tableize {
	function makeDetailsViewPart($v) {
		return $this->makeTableViewPart($v);
	}
	function makeEmailViewPart($v) {
		return $this->makeTableViewPart($v);
	}
}

# A general component with a name and a label, that implements
# a number of the interfaces described above.
abstract class NamedLabeledComponent implements FormPartFactory, Configurable,
	TableViewPartFactory, DetailsViewPartFactory, EmailViewPartFactory, Storeable {

	use Tableize, Groupize;

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

}

# A NamedLabeledComponent that gets its data from $_POST.
abstract class PostInputComponent extends NamedLabeledComponent {
	function getSubmissionPart($val) {
		return $this->validate(
			$val->ifOk(function ($x) {
					return Result::ok($x->post);
				})
				->byName($this->name)
		)->name($this->name);
	}
	protected abstract function validate($val);
}

# A NamedLabeledComponent that gets its data from $_FILES.
abstract class FileInputComponent extends NamedLabeledComponent {
	final function getSubmissionPart($val) {
		return $this->validate(
			$val->ifOk(function ($x) {
					return Result::ok($x->files);
				})
				->byName($this->name)
		)->name($this->name);
	}
	protected abstract function validate($val);
}

# A component that represents a group of other form fields.
abstract class GroupComponent implements FormPartFactory, Storeable, Configurable {
	use Groupize;
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
