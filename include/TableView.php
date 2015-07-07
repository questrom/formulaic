<?php
use Sabre\Xml\XmlDeserializable as XmlDeserializable;

class Column implements XmlDeserializable {
	use Configurable;

	function __construct($args) {
		$this->name = $args['name'];
		$this->header = $args['header'];
		$this->width = intval($args['width']);
		$this->sort = isset($args['sort']) ? $args['sort'] : null;
	}
}

class ValueCell implements HTMLComponent {
	function __construct($value, $component) {

		$this->value = $value;
		$this->component = $component;
	}
	function get($h) {


		if($this->component instanceof FieldTableItem) {
			return $this->component->asTableCell($h,
				$this->value === null ? Result::none(null) : Result::ok($this->value),
				false)
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
			throw new Exception('Invalid column!');
		}

	}
}

class TableView implements XmlDeserializable, HTMLComponent {
	use Configurable;

	function __construct($args) {

		$this->title = $args['title'];
		$this->cols = $args['children'];

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
		// $this->perPage = null;
	}
	function query($getData) {

		$this->page = intval(isset($getData['page']) ? $getData['page'] : 1);


		$client = (new MongoClient($this->server))
			->selectDB($this->database)
			->selectCollection($this->collection);

		$cursor = $client->find()->sort($this->sortBy);

		if($this->perPage !== null) {
			$this->max = intval(floor($cursor->count() / $this->perPage)); // Need intval for the comparison below to work
		} else {
			$this->max = 1;
		}

		if($this->perPage !== null) {
			$cursor->skip(($this->page - 1) * $this->perPage);
			$cursor->limit($this->perPage);
		}

		$this->data = fixMongoDates(array_values(iterator_to_array($cursor)));
	}
	function setPage($page) {
		$this->pageData = $page;

		$mongo = null;
		foreach($page->outputs->outputs as $output) {
			if($output instanceof MongoOutput) {
				$mongo = $output;
			}
		}
		$this->server = $mongo->server;
		$this->database = $mongo->database;
		$this->collection = $mongo->collection;
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
						->table->class('unstackable ui celled striped compact table table-view sortable')
							->colgroup
								->add(array_map(function($x) use($h) {
									return $h
										->col->style('width: ' . ($x->width * 100) . '%;')
									->end;
								}, $this->cols))
								->col->style('width:90px;')->end
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
									->th->end
								->end
							->end
							->add(array_map(function($row) use ($h) {
								return $h
								->tr
									->add(array_map(function($col) use($h, $row) {
											return new ValueCell( isset($row[$col->name]) ? $row[$col->name] : null, $this->pageData->getByName($col->name) );
									}, $this->cols))
									->td->class('center aligned nowrap unpadded-cell')
										->a->class('ui no-margin compact button')->href('details.php?id=' . $row['_id'])
											->t('Details')
										->end
									->end
								->end;
							}, $this->data))
						->end
						->hif($this->perPage)
							->div->class('ui text menu')
								->div->class('item')
									->a->class('ui left floated primary labeled icon button ' . ($this->page === 1 ? 'disabled' : ''))
										->href('?page=' . ($this->page - 1))
										->i->class('left chevron icon')->end
										->t('Previous')
									->end
								->end
								->div->class('item pagenumber')
									->t('Page ' . ($this->page) . ' of ' . ($this->max + 1))
								->end
								->div->class('right item')
									->a->class('ui right floated primary right labeled icon button ' . ((($this->page - 1) === $this->max) ? 'disabled' : ''))
										->href('?page=' . ($this->page + 1))
										->i->class('right chevron icon')->end
										->t('Next')
									->end
								->end
							->end

					->end
				->end
			->end
		->end;
	}
}
