<?php

use Sabre\Xml\XmlDeserializable as XmlDeserializable;


class ValueRow implements HTMLComponent {
	function __construct($value, $component) {

		$this->value = $value;
		$this->component = $component;
	}
	function get($h) {


		if($this->component instanceof Cellable) {
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

class EmailValueRow implements HTMLComponent {
	function __construct($value, $component) {

		$this->value = $value;
		$this->component = $component;
	}
	function get($h) {


		if($this->component instanceof Cellable) {
			return $this->component->asEmailTableCell(
				$h,
				$this->value === null ? Result::none(null) : Result::ok($this->value)
			)
				->bindNothing(function($x) use ($h){
					return Result::ok(
						$h
						->td->bgcolor('#ccc')
							->t('(No value)')
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



		$timestamp = $this->data['_timestamp'];

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
								if($field instanceof Cellable) {
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
			->end
		->end;
	}
}

class EmailView extends DetailsView {
	function get($h) {

		$timestamp = $this->data['_timestamp'];

		return
		$h
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
							->add(array_map(function($field) {
								if($field instanceof Cellable) {
									return new EmailValueRow( isget($this->data[$field->name]), $field );
								} else {
									return null;
								}
							}, $this->pageData->getAllFields() ))
						->end
						->tfoot
							->tr
								->td->colspan('2')->align('left')
									->strong->t('Timestamp:' . json_decode('"\u2002"'))->end
									->t($timestamp->format('Y/m/d g:i A'))
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