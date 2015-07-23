<?php

# Things shared among TableView, DetailsView, and EmailView.

# Creates the part of a table used to display an individual datum.
class TablePart implements TableViewPartFactory, DetailsViewPartFactory, EmailViewPartFactory {
	function __construct($component) {
		$this->component = $component;
	}
	function makeTableViewPart($value) {
		return new ValueCell($value, $this->component);
	}
	function makeDetailsViewPart($value) {
		return new ValueRow($value, $this->component);
	}
	function makeEmailViewPart($value) {
		return new EmailValueRow($value, $this->component);
	}
}

# A table along with IP/timetsamp info (for DetailsView and EmailView).
class StampedTable implements DetailsViewPartFactory, EmailViewPartFactory {
	function __construct($fields) {
		$this->fields = $fields;
	}
	function makeDetailsViewPart($data) {
		return new ValueTable($this->fields, $data, new IPTimestampInfo($data));
	}
	function makeEmailViewPart($data) {
		return new EmailTable($this->fields, $data, new EmailIPTimestampInfo($data));
	}
}