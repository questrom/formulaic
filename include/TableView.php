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


class ValueCell implements Renderable {
	function __construct($value, $component) {
		$this->value = $value;
		$this->component = $component;
		$this->h = new HTMLParentlessContext();
	}
	function render() {
		$v = $this->component->makeTableCellPart($this->value);
		if($v === null) {
			return $this->h
				->td->class('disabled')
					->i->class('ban icon')->end
				->end;
		} else {
			return $v->render();
		}
	}
}

class TablePage implements Renderable {
	function __construct($f) {

		$this->f = $f;
		$this->h = new HTMLParentlessContext();
		// var_dump($f->data);
	}
	function render() {
		return
		$this->h
		->html
			->head
				->meta->charset('utf-8')->end
				->title->t($this->f->title)->end
				->link->rel('stylesheet')->href(new AssetUrl('lib/semantic.css'))->end
				->link->rel('stylesheet')->href(new AssetUrl('styles.css'))->end
			->end
			->body
				->addH(new TopHeader())
				->div->class('ui fluid container table-page')
						->h1
							->t($this->f->title)
						->end
						->table->class('unstackable ui celled striped compact table table-view sortable')
							->colgroup
								->addH(array_map(function($x)  {
									return $this->h
										->col->style('width: ' . ($x->width * 100) . '%;')
									->end;
								}, $this->f->cols))
								->col->style('width:90px;')->end
							->end
							->thead
								->tr
									->addH(array_map(function($x)  {
										return $this->h
										->th->class(
											$x->sort === null ? '' :
											($x->sort === 'asc' ? 'sorted ascending' : 'sorted descending')
										)
											->t($x->header)
										->end;
									}, $this->f->cols))
									->th->end
								->end
							->end
							->addH(array_map(function($row) {
								return $this->h
								->tr
									->addH(array_map(function($col) use($row) {
											return new ValueCell(
												isget($row[$col->name]),
												$this->f->pageData->form->getByName($col->name)
											);
									}, $this->f->cols))
									->td->class('center aligned nowrap unpadded-cell')
										->a->class('ui no-margin compact button')->href('details.php?form=' . $this->f->formID . '&id=' . $row['_id'])
											->t('Details')
										->end
									->end
								->end;
							}, $this->f->data))
						->end
						->addH(!$this->f->perPage ? null :
							$this->h
							->div->class('ui text menu')
								->div->class('item')
									->a->class('ui left floated primary labeled icon button '
											. ($this->f->page === 1 ? 'disabled' : ''))
										->href('view.php?form=' . $this->f->formID . '&view=' . $this->f->name . '&page=' . ($this->f->page - 1))
										->i->class('left chevron icon')->end
										->t('Previous')
									->end
								->end
								->div->class('item pagenumber')
									->t('Page ' . ($this->f->page) . ' of ' . ($this->f->max + 1))
								->end
								->div->class('right item')
									->a->class('ui right floated primary right labeled icon button '
											. ((($this->f->page - 1) === $this->f->max) ? 'disabled' : ''))
										->href('view.php?form=' . $this->f->formID . '&view=' . $this->f->name . '&page=' . ($this->f->page + 1))
										->i->class('right chevron icon')->end
										->t('Next')
									->end
								->end
							->end
						)
				->end
			->end
		->end;
	}
}

class TableView implements XmlDeserializable, View {
	use Configurable;

	function makeView($data) {
		$this->data = $data['data'];
		$this->max = $data['max'];
		$this->page = $data['pageNum'];
		$this->formID = $data['formID'];

		return new TablePage($this);
	}

	function __construct($args) {

		$this->name = $args['name'];
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

		$this->type = 'table';
	}
	function query($getData) {
		$page = intval(isset($getData['page']) ? $getData['page'] : 1);

		$client = (new MongoClient($this->server))
			->selectDB($this->database)
			->selectCollection($this->collection);

		$cursor = $client->find()->sort($this->sortBy);

		if($this->perPage !== null) {
			$max = intval(floor($cursor->count() / $this->perPage)); // Need intval for the comparison below to work
		} else {
			$max = 1;
		}

		if($this->perPage !== null) {
			$cursor->skip(($page - 1) * $this->perPage);
			$cursor->limit($this->perPage);
		}

		return [
			'data' => fixMongoDates(array_values(iterator_to_array($cursor))),
			'max' => $max,
			'pageNum' => $page,
			'formID' => $getData['form']
		];
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
}
