<?php

class Column {
	function __construct($args) {
		$this->name = $args['name'];
		$this->header = $args['header'];
		$this->width = intval($args['width']);
		$this->sort = isset($args['sort']) ? $args['sort'] : null;
	}
}

class ValueCell {
	function __construct($value, $component) {
		if($value instanceof MongoDate) {
			$value = DateTimeImmutable::createFromFormat('U', $value->sec)->setTimezone(new DateTimeZone('America/New_York'));
		}
		$this->value = $value;
		$this->component = $component;
	}
	function get($h) {


		if($this->component instanceof NamedLabeledComponent) {
			return $this->component->asTableCell($h, $this->value === null ? Result::none(null) : Result::ok($this->value))
				->bindNothing(function($x) use ($h){
					return Result::ok(
						$h
						->td->class('disabled')
							->i->class('ban icon')->end
						->end
					);
				})
				->innerBind(function($x) {
					return $x;
				});
		} else {
			throw new Exception('Other component times not yet supported...');
		}

	}
}

class TableView {
	function __construct($args) {

		$this->server = $args['server'];
		$this->database = $args['database'];
		$this->collection = $args['collection'];
		$this->title = $args['title'];
		$this->cols = $args['cols'];

		$sortBy = [];

		foreach($this->cols as $col) {
			if($col->sort !== null) {
				$sortBy[$col->name] = ($col->sort === 'asc' ? 1 : -1);
			}
		}
		$sortBy['_timestamp'] = -1;
		$sortBy['_id'] = -1; // Ensure determinism so we don't break pagination

		$this->sortBy = $sortBy;

		$this->perPage = isset($args['per-page']) ? $args['per-page'] : null;
	}
	function query($getData) {

		$this->page = (isset($getData['page']) ? $getData['page'] : 1) - 1;


		$client = (new MongoClient($this->server))
			->selectDB($this->database)
			->selectCollection($this->collection);

		$cursor = $client->find()->sort($this->sortBy);

		$this->max = intval(floor($cursor->count() / $this->perPage)); // Need intval for the comparison below to work

		if($this->perPage !== null) {
			$cursor->skip($this->page * $this->perPage);
			$cursor->limit($this->perPage);
		}

		$this->data = iterator_to_array($cursor);
	}
	function setPage($page) {
		$this->form = $page->form;
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
						->table->class('ui celled striped compact table table-view sortable')
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
										->th->class(
											$x->sort === null ? '' :
											($x->sort === 'asc' ? 'sorted ascending' : 'sorted descending')
										)
											->t($x->header)
										->end;
									}, $this->cols))
								->end
							->end
							->add(array_map(function($row) use ($h) {
								return $h
								->tr
									->add(array_map(function($col) use($h, $row) {
											return new ValueCell( isset($row[$col->name]) ? $row[$col->name] : null, $this->form->getByName($col->name) );
									}, $this->cols))
								->end;
							}, $this->data))
						->end
						->hif($this->perPage)
							->div->class('pagination')
								->a->class('ui left floated primary labeled icon button ' . ($this->page === 0 ? 'disabled' : ''))
									->href('?page=' . ($this->page))
									->i->class('left chevron icon')->end
									->t('Previous')
								->end
								->t('Page ' . ($this->page + 1) . ' of ' . ($this->max + 1))
								->a->class('ui right floated primary right labeled icon button ' . (($this->page === $this->max) ? 'disabled' : ''))
									->href('?page=' . ($this->page + 2))
									->i->class('right chevron icon')->end
									->t('Next')
								->end
							->end
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