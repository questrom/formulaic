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

	function query($page) {
		if($this->perPage !== null) {
			# Use intval so PHP will compare values properly elsewhere in the code.
			$max = intval(floor($this->mongo->count() / $this->perPage));
		} else {
			# If $perPage is not specified, no pagination will occur.
			$max = 1;
		}

		# Retreive all necssary data
		$result = [];
		$result['data'] = $this->mongo->getTable($page, $this->sortBy, ($page - 1) * $this->perPage, $this->perPage);
		$result['pageNum'] = $page;
		$result['formID'] = $this->pageData->id;
		$result['max'] = $max;

		return $result;
	}

	function setPage($page) {
		$this->pageData = $page;
		$this->mongo = $page->getMongo();
	}
}

class CSVView implements View {
	function __construct($tableView) {
		$tableView->perPage = null;
		$this->tableView = $tableView;
	}
	function query($page) {
		return $this->tableView->query($page);
	}
	function setPage($page) {
		return $this->tableView->setPage($page);
	}
	function makeView($data) {
		$this->tableView->makeView($data);
		return new CSVPage($this->tableView);
	}
}