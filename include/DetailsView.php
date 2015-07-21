<?php

class ValueRow implements Renderable {
	function __construct($value, $component) {
		$this->value = $value;
		$this->component = $component;
	}

	function render() {
		$v = $this->component->makeDetailsViewPart($this->value);
		if($v === null) {
			$v = h()
			->td->class('disabled')
				->i->class('ban icon')->end
			->end;
		}
		return h()
		->tr
			->td->class('right aligned collapsing nowrap')
				->t($this->component->label)
			->end
			->addH($v)
		->end;
	}
}

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

class ValueTable implements Renderable {
	function __construct($fields, $data, $stamp = false) {
		$this->fields = $fields;
		$this->data = $data;
		$this->stamp = $stamp;
	}
	function render() {
		return h()
		->table->class('ui unstackable definition table')
			->tbody
				->addH(array_map(function($field) {
					if($field instanceof DetailsViewPartFactory) {
						return ( new TablePart( $field ) )->makeDetailsViewPart(  isget($this->data[$field->name]) );
					} else {
						return null;
					}
				}, $this->fields ))
			->end
			->addH(!$this->stamp ? null : $this->stamp)
		->end;
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

class IPTimestampInfo implements Renderable {
	function __construct($data) {
		$this->data = $data;
	}
	function render() {
		return h()
		->tfoot->class('full-width')
			->tr
				->th->colspan('2')
					->strong->t('Timestamp:' . json_decode('"\u2002"'))->end
					->t(isset($this->data['_timestamp']) ? $this->data['_timestamp']->format('Y/m/d g:i A') : null)
					->p
						->strong->t('IP:' . json_decode('"\u2002"'))->end
						->code->t( isget($this->data['_ip']) )->end
					->end
				->end
			->end
		->end;
	}
}

class DetailsViewRenderable implements Renderable {
	function __construct($fields, $title, $data) {
		$this->fields = $fields;
		$this->title = $title;
		$this->data = $data;

	}
	function render() {
		return
		h()
		->html
			->head
				->meta->charset('utf-8')->end
				->title->t($this->title)->end
				->link->rel('stylesheet')->href(new AssetUrl('lib/semantic.css'))->end
				->link->rel('stylesheet')->href(new AssetUrl('styles.css'))->end
			->end
			->body
				->addH(new TopHeader())
				->div->class('ui container wide-page')
					->h1
						->t($this->title)
					->end
					->addH(
						(new StampedTable($this->fields))->makeDetailsViewPart($this->data)
					)
				->end
			->end
		->end;
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
		return $mongo->getById($_GET['id']);
	}
}