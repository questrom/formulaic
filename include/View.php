<?php

class Column {
	function __construct($args) {
		$this->name = $args['name'];
		$this->header = $args['header'];
		$this->width = intval($args['width']);
	}
}

class ValueCell {
	function __construct($value) {
		$this->value = $value;
	}
	function get($h) {

		$disabled = is_null($this->value) || ( is_array($this->value) && count($this->value) === 0 );

		return $h
			->td->class(
				($disabled ? 'disabled' : '') .
				(is_bool($this->value) ?
					($this->value ? 'positive' : 'negative')
				: '')
			)
				->hif(is_string($this->value) || is_numeric($this->value))
					->t($this->value)
				->end
				->hif($disabled)
					->i->class('ban icon')->end
				->end
				->hif(is_bool($this->value))
					->t($this->value ? 'Yes' : 'No')
				->end
				->hif(is_array($this->value) && count($this->value) > 0 && is_string($this->value[0]))
					->ul->class('ui list')
						->add(is_array($this->value) ? array_map(function($x) use ($h) {
							return $h->li->t($x)->end;
						}, $this->value) : null)
					->end
				->end
				->hif($this->value instanceof MongoDate)
					->t($this->value instanceof MongoDate ?
						DateTimeImmutable::createFromFormat('U', $this->value->sec)->setTimezone(new DateTimeZone('America/New_York'))->format('n/j/Y g:i A')
						: '')
				->end
			->end;
	}
}

class TableView {
	function __construct($args) {
		$this->server = $args['server'];
		$this->database = $args['database'];
		$this->collection = $args['collection'];
		$this->title = $args['title'];
		$this->cols = $args['cols'];
	}
	function query() {
		$client = (new MongoClient($this->server))
			->selectDB($this->database)
			->selectCollection($this->collection);
		$cursor = $client->find();
		$this->data = iterator_to_array($cursor);
	}
	function get($h) {
		return
		$h
		->html
			->head
				->meta->charset('utf-8')->end
				->title->t($this->title)->end
				->link->rel("stylesheet")->href("vendor/semantic/ui/dist/semantic.css")->end
				->link->rel("stylesheet")->href("styles.css")->end
			->end
			->body
				->div->class('ui page grid wide-page')
					->div->class('sixteen wide column')
						->h1
							->t($this->title)
						->end
						->table->class('ui celled striped compact table table-view')
							->colgroup
								->add(array_map(function($x) use($h) {
									return $h
									->col->style('width: ' . $x->width . '%;')
									->end;
								}, $this->cols))
							->end
							->thead
								->tr
									->add(array_map(function($x) use($h) {
										return $h
										->th
											->t($x->header)
										->end;
									}, $this->cols))
								->end
							->end
							->add(array_map(function($row) use ($h) {
								return $h
								->tr
									->add(array_map(function($col) use($h, $row) {
											return new ValueCell( isset($row[$col->name]) ? $row[$col->name] : null );
									}, $this->cols))
								->end;
							}, $this->data))
						->end
					->end
				->end
				->script->src('vendor/components/jquery/jquery.min.js')->end
				->script->src('vendor/robinherbots/jquery.inputmask/dist/jquery.inputmask.bundle.js')->end
				->script->src('vendor/semantic/ui/dist/semantic.js')->end
				->script->src('client.js')->end
			->end
		->end;
	}
}