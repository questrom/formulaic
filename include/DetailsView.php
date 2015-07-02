<?php

use Sabre\Xml\XmlDeserializable as XmlDeserializable;


class ValueRow implements HTMLComponent {
	function __construct($value, $component) {
		// var_dump($value);
		if($value instanceof MongoDate) {
			$value = DateTimeImmutable::createFromFormat('U', $value->sec)->setTimezone(new DateTimeZone('America/New_York'));
		}
		$this->value = $value;
		$this->component = $component;
	}
	function get($h) {


		if($this->component instanceof Cellable) {
			return $this->component->asTableCell($h, $this->value === null ? Result::none(null) : Result::ok($this->value), true )
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

class DetailsView implements HTMLComponent, XmlDeserializable {
	use Configurable;

	function __construct($page) {

		$this->title = $page->title;

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


		$this->data = $client->findOne([
			'_id' => new MongoId($this->item)
		]);

	}
	function setPage($page) {
		$this->pageData = $page;

	}
	function get($h) {
		if($this->data['_timestamp'] instanceof MongoDate) {
			$timestamp = DateTimeImmutable::createFromFormat('U', $this->data['_timestamp']->sec)->setTimezone(new DateTimeZone('America/New_York'));
		} else {
			$timestamp = $this->data['_timestamp'];
		}

		return
		$h
		->html
			->head
				->meta->charset('utf-8')->end
				->title->t($this->title)->end
				->link->rel("stylesheet")->href("semantic-ui/semantic.css")->end
				->link->rel("stylesheet")->href("styles.css")->end
			->end
			->body
				->div->class('ui container wide-page')
					->h1
						->t($this->title)
					->end
					->table->class('ui definition table')
						->tbody
							->add(array_map(function($field) {
								if($field instanceof Cellable && ($field instanceof HTMLComponent)) {
									return new ValueRow( isget($this->data[$field->name]), $field );
								} else {
									return null;
								}
							}, $this->pageData->getAllFields() ))
						->end
						->tfoot->class('full-width')
							->tr
								->th->colspan('2')
									->strong->t('Timestamp:' . json_decode('"\u2002"'))->end
									->t($timestamp->format('Y/m/d g:i A'))
									->p
										->strong->t('IP:' . json_decode('"\u2002"'))->end
										->code->t($this->data['_ip'])->end
									->end
								->end
							->end
						->end
					->end
				->end
				->script->src('vendor/components/jquery/jquery.min.js')->end
				->script->src('vendor/robinherbots/jquery.inputmask/dist/jquery.inputmask.bundle.js')->end
				->script->src('semantic-ui/dist/semantic.js')->end
				->script->src('client.js')->end
			->end
		->end;
	}
}