<?php

use Sabre\Xml\XmlDeserializable as XmlDeserializable;


class ValueRow implements Renderable {
	function __construct($value, $component) {

		$this->value = $value;
		$this->component = $component;
		$this->h = new HTMLParentlessContext();
	}

	function render() {
		// var_dump($this->component);
		return $this->component->asDetailedTableCell(
			$this->h,
			$this->value === null ? Result::none(null) : Result::ok($this->value)
		)
			->bindNothing(function($x) {
				return Result::ok(
					$this->h
					->td->class('disabled')
						->i->class('ban icon')->end
					->end
				);
			})
			->innerBind(function($x) {
					return $this->h
					->tr
						->td->class('right aligned collapsing nowrap')
							->t($this->component->label)
						->end
						->ins($x)
					->end;
			});
	}
}

class ValueTable implements Renderable {
	function __construct($fields, $data, $stamp) {
		$this->fields = $fields;
		$this->data = $data;
		$this->stamp = false;
		$this->h = new HTMLParentlessContext();
	}
	function render() {
		return $this->h->table->class('ui definition table')
			->tbody
				->addH(array_map(function($field) {
					if($field instanceof FieldListItem) {
						return new ValueRow( isget($this->data[$field->name]), $field );
					} else {
						return null;
					}
				}, $this->fields ))
			->end
			->hif($this->stamp)
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
			->end
		->end;
	}
}

class DetailsViewRenderable implements Renderable {
	function __construct($field) {
		$this->f = $field;
		$this->h = new HTMLParentlessContext();

	}
	function render() {

		return
		$this->h
		->html
			->head
				->meta->charset('utf-8')->end
				->title->t($this->f->title)->end
				->link->rel('stylesheet')->href('lib/semantic.css')->end
				->link->rel('stylesheet')->href('styles.css')->end
			->end
			->body
				->addH(new TopHeader())
				->div->class('ui container wide-page')
					->h1
						->t($this->f->title)
					->end
					->addH( new ValueTable($this->f->pageData->form->getAllFields(), $this->f->data, true) )
				->end
			->end
		->end;
	}
}


// Used by details.php and Output.php (For HTML email)
class DetailsView implements View {


	function setPage($page) {
		$this->title = $page->title;
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

		$client = (new MongoClient($mongo->server))
			->selectDB($mongo->database)
			->selectCollection($mongo->collection);

		$data = $client->findOne([
			'_id' => new MongoId($getData['id'])
		]);

		$data = fixMongoDates($data);

		$this->data = $data;
	}

	function makeDetailsView() {
		return new DetailsViewRenderable($this);
	}
}

