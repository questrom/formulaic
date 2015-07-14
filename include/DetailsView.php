<?php

class ValueRow implements Renderable {
	function __construct($value, $component) {
		$this->value = $value;
		$this->component = $component;
		$this->h = new HTMLParentlessContext();
	}

	function render() {
		$v = $this->component->makeDetailedTableCell($this->value);
		if($v === null) {
			$v = $this->h
			->td->class('disabled')
				->i->class('ban icon')->end
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

class ValueTable implements Renderable {
	function __construct($fields, $data, $stamp = false) {
		$this->fields = $fields;
		$this->data = $data;
		$this->stamp = $stamp;
		$this->h = new HTMLParentlessContext();
	}
	function render() {
		return $this->h
		->table->class('ui unstackable definition table')
			->tbody
				->addH(array_map(function($field) {
					if($field instanceof TableCellFactory && $field instanceof FormPartFactory) {
						return new ValueRow( isget($this->data[$field->name]), $field );
					} else {
						return null;
					}
				}, $this->fields ))
			->end
			->addH(!$this->stamp ? null :
				$this->h
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
				->end
			)
		->end;
	}
}

class DetailsViewRenderable implements Renderable {
	function __construct($fields, $title, $data) {
		$this->fields = $fields;
		$this->title = $title;
		$this->data = $data;
		$this->h = new HTMLParentlessContext();
	}
	function render() {
		return
		$this->h
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
					->addH( new ValueTable($this->fields, $this->data, true) )
				->end
			->end
		->end;
	}
}


// Used by details.php and Output.php (For HTML email)
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

