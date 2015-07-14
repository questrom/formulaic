<?php



class OrdinaryTableCell implements Renderable {
	function __construct($value) {
		$this->h = new HTMLParentlessContext();
		$this->value = $value;
	}
	function render() {
		return $this->h
			->td
				->t($this->value)
			->end;
	}
}


class ListTableCell implements Renderable {
	function __construct($value) {
		$this->h = new HTMLParentlessContext();
		$this->value = $value;
	}
	function render() {
		return $this->h
			->td
				->ul->class('ui list')
					->addH(array_map(function($x) {
						return $this->h->li->t($x)->end;
					}, $this->value))
				->end
			->end;
	}
}

class LinkTableCell implements Renderable {
	function __construct($url, $value, $blank = false) {
		$this->h = new HTMLParentlessContext();
		$this->url = $url;
		$this->value = $value;
		$this->blank = $blank;
	}
	function render() {
		return $this->h
			->td
				->a->href($this->url)->target('_blank', $this->blank)
					->t($this->value)
				->end
			->end;
	}
}


class PasswordTableCell implements Renderable {
	function __construct() {
		$this->h = new HTMLParentlessContext();
	}
	function render() {
		return $this->h
			->td
				->abbr->title('Passwords are not saved in the database')
					->t('N/A')
				->end
			->end;
	}
}


class FileUploadTableCell implements Renderable {
	function __construct($value) {
		$this->h = new HTMLParentlessContext();
		$this->value = $value;
	}
	function render() {
		return $this->h
			->td->class('unpadded-cell')
				->a->href($this->value['url'])->class('ui attached labeled icon button')
					->i->class('download icon')->end
					->t('Download')
				->end
			->end;
	}
}

class FileUploadDetailedTableCell implements Renderable {
	function __construct($value) {
		$this->h = new HTMLParentlessContext();
		$this->value = $value;
	}
	function render() {
		$v = $this->value;
		return $this->h
		->td
			->div->class('ui list')
				->div->class('item') ->strong->t('URL: ')->end->a->href($v['url'])->t($v['url'])->end->end
				->div->class('item') ->strong->t('Original Filename: ')->end->t($v['originalName'])->end
				->div->class('item') ->strong->t('Type: ')->end->t($v['mime'])->end
			->end
		->end;
	}
}

class TextareaTableCell implements Renderable {
	function __construct($value) {
		$this->h = new HTMLParentlessContext();
		$this->value = $value;
	}
	function render() {
		return $this->h
			->td
				->pre->t($this->value)->end
			->end;
	}
}


class CheckboxTableCell implements Renderable {
	function __construct($value) {
		$this->value = $value;
		$this->h = new HTMLParentlessContext();
	}
	function render() {
		return $this->h
		->td->class($this->value ? 'positive' : 'negative')
			->t($this->value ? 'Yes' : 'No')
		->end;
	}

}

class ListEmailTableCell implements Renderable {
	function __construct($value, $fields) {
		$this->h = new HTMLParentlessContext();
		$this->value = $value;
		$this->fields = $fields;
	}
	function render() {
		return $this->h
		->td
			->addH(array_map(function($listitem) {
				return $this->h->table->border(1)
					->addH(array_map(function($field) use ($listitem) {
						if($field instanceof TableCellFactory) {
							return (new EmailValueRow( isget($listitem[$field->name]), $field ));
						} else {
							return null;
						}
					}, $this->fields))
				->end;
			}, $this->value))
		->end;
	}
}

class ListDetailedTableCell implements Renderable {
	function __construct($v, $value) {
		$this->h = new HTMLParentlessContext();
		$this->value = $value;
		$this->v = $v;
	}
	function render() {
		return $this->h
		->td
			->addH(array_map(function($listitem) {
				return new ValueTable($this->value, $listitem, false);
			}, $this->v))
		->end;
	}
}
