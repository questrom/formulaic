<?php

# These Configurables are used within table views.

# The "col" element. Just holds data from the attributes
# in the configuration file.
class Column implements Configurable {
	function __construct($args) {
		$this->name = $args['name'];
		$this->header = $args['header'];
		$this->width = intval($args['width']);
		$this->sort = isset($args['sort']) ? $args['sort'] : null;
	}
}


# The "table-view" element itself. For mroe info about each method,
# see ConfigurableView in View.php.
class TableView implements ConfigurableView {

	function makeView($data) {
		$this->data = $data['data'];
		$this->max = $data['max'];
		$this->page = $data['pageNum'];
		$this->formID = $data['formID'];

		return new TablePage($this);
	}


	function getIcon() {
		return 'table icon';
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

		# This line ensures that the sort will be deterministic,
		# so page refreshes or moving between pages won't cause the
		# sort order to change.
		$sortBy['_id'] = -1;

		$this->sortBy = $sortBy;

		$this->perPage = isset($args['per-page']) ? $args['per-page'] : null;

	}
	function query($getData) {
		$page = intval(isset($getData['page']) ? $getData['page'] : 1);

		$result = $this->mongo->getTable($page, $this->sortBy, $this->perPage);
		$result['pageNum'] = $page;
		$result['formID'] = $getData['form'];
		return $result;
	}
	function setPage($page) {
		$this->pageData = $page;

		$mongo = null;
		foreach($page->outputs->outputs as $output) {
			if($output instanceof MongoOutput) {
				$mongo = $output;
			}
		}
		$this->mongo = $mongo;
	}
}
