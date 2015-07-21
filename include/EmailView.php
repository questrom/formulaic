<?php


class EmailValueRow implements Renderable {
	function __construct($value, $component) {

		$this->value = $value;
		$this->component = $component;

	}
	function render() {
		$v = $this->component->makeEmailTableCell($this->value);
		if($v === null) {
			$v = h()
			->td->bgcolor('#ccc')
				->t('(No value)')
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

class EmailIPTimestampInfo implements Renderable {
	function __construct($data) {
		$this->data = $data;
	}
	function render() {
		return h()
		->tfoot
			->tr
				->td->colspan('2')->align('left')
					->strong->t('Timestamp:' . json_decode('"\u2002"'))->end
					->t($this->data['_timestamp']->format('Y/m/d g:i A'))
					->br->end
					->strong->t('IP:' . json_decode('"\u2002"'))->end
					->code->t($this->data['_ip'])->end
				->end
			->end
		->end;
	}
}

class EmailTable implements Renderable {
	function __construct($fields, $data, $stamp = false) {
		$this->fields = $fields;
		$this->data = $data;
		$this->stamp = $stamp;

	}
	function render() {
		return h()
		->table->border(1)->width('100%')->style('max-width:800px;')
			->col->width('30%')->end
			->col->width('70%')->end
			->tbody
				->addH(array_map(function($field) {
					if($field instanceof TableCellFactory && $field instanceof FormPartFactory) {
						return new EmailValueRow( isget($this->data[$field->name]), $field );
					} else {
						return null;
					}
				}, $this->fields ))
			->end
			->addH(!$this->stamp ? null : $this->stamp)
		->end;
	}
}

class EmailViewRenderable implements Renderable {
	function __construct($title, $pageData, $data) {
		$this->title = $title;
		$this->pageData = $pageData;

		$this->data = $data;
	}
	function render() {
		return
		h()
		->html
			->head
				->meta->charset('utf-8')->end
				->title->t($this->title)->end
			->end
			->body
				->div->class('ui container wide-page')
					->h1
						->t($this->title)
					->end
					->addH( new EmailTable($this->pageData->form->getAllFields(), $this->data, new EmailIPTimestampInfo($this->data)) )
				->end
			->end
		->end;
	}
}

class EmailView {

	function __construct($page) {
		$this->title = $page->title;
		$this->pageData = $page;
	}

	function makeEmailView() {
		return new EmailViewRenderable($this->title, $this->pageData, $this->data);
	}
}