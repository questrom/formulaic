<?php


class EmailValueRow implements Renderable {
	function __construct($value, $component) {

		$this->value = $value;
		$this->component = $component;
		$this->h = new HTMLParentlessContext();
	}
	function render() {


			$v = $this->component->makeEmailTableCell($this->value);
			if($v === null) {
				$v = $this->h
				->td->bgcolor('#ccc')
					->t('(No value)')
				->end;
			}
			return $this->h
			->tr
				->td->class('right aligned collapsing nowrap')
					->t($this->component->label)
				->end
				->addH($v)
			->end;


	}
}

class EmailViewRenderable implements Renderable {
	function __construct($title, $pageData, $data) {
		$this->title = $title;
		$this->pageData = $pageData;
		$this->h = new HTMLParentlessContext();
		$this->data = $data;
	}
	function render() {
		return
		$this->h
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
					->table->border(1)
						->tbody
							->addH(array_map(function($field) {
								if($field instanceof TableCellFactory && $field instanceof FormPartFactory) {
									return new EmailValueRow( isget($this->data[$field->name], null), $field );
								} else {
									return null;
								}
							}, $this->pageData->form->getAllFields() ))
						->end
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
						->end
					->end
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