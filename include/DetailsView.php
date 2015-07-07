<?php

use Sabre\Xml\XmlDeserializable as XmlDeserializable;


class ValueRow implements HTMLComponent {
	function __construct($value, $component) {

		$this->value = $value;
		$this->component = $component;
	}
	function get($h) {


		if($this->component instanceof FieldListItem) {
			return $this->component->asDetailedTableCell(
				$h,
				$this->value === null ? Result::none(null) : Result::ok($this->value)
			)
				->bindNothing(function($x) use ($h){
					return Result::ok(
						$h
						->td->class('disabled')
							->i->class('ban icon')->end
						->end
					);
				})
				->innerBind(function($x) use ($h) {
						return $h
						->tr
							->td->class('right aligned collapsing nowrap')
								->t($this->component->label)
							->end
							->ins($x)
						->end;
				});
		} else {
			throw new Exception('Invalid column!');
		}

	}
}

class ValueTable implements HTMLComponent {
	function __construct($fields, $data, $stamp) {
		$this->fields = $fields;
		$this->data = $data;
		$this->stamp = false;
	}
	function get($h) {
		return $h ->table->class('ui definition table')
			->tbody
				->addH(array_map(function($field) {
					if($field instanceof FieldListItem) {
						return (new ValueRow( isget($this->data[$field->name], null), $field ))
							->get(new HTMLParentlessContext());
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


// Used by details.php and Output.php (For HTML email)
class DetailsView implements HTMLComponent {
	use Configurable;

	function __construct($page) {

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
		$this->server = $mongo->server;
		$this->database = $mongo->database;
		$this->collection = $mongo->collection;

		$this->item = $getData['id'];


		$client = (new MongoClient($this->server))
			->selectDB($this->database)
			->selectCollection($this->collection);


		$data = $client->findOne([
			'_id' => new MongoId($this->item)
		]);

		$data = fixMongoDates($data);

		$this->data = $data;

	}
	function get($h) {

		return
		$h
		->html
			->head
				->meta->charset('utf-8')->end
				->title->t($this->title)->end
				->link->rel("stylesheet")->href("lib/semantic.css")->end
				->link->rel("stylesheet")->href("styles.css")->end
			->end
			->body
				->div->class('ui container wide-page')
					->h1
						->t($this->title)
					->end
					->addC(new ValueTable($this->pageData->getAllFields(), $this->data, true))
				->end
			->end
		->end;
	}
}

