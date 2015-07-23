<?php

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


class DetailsView implements View {
	function makeView($data) {
		return new DetailsViewRenderable($this->pageData->form->getAllFields(), $this->pageData->title, $data);
	}

	function setPage($page) {
		$this->pageData = $page;
	}

	function query($getData) {
		$page = $this->pageData;
		$mongo = null;
		foreach($page->outputs->outputs as $output) {
			if($output instanceof MongoOutput) {
				$mongo = $output;
			}
		}
		return $mongo->getById($getData['id']);
	}
}