<?php


class OrdinaryTableCell implements Renderable {
	function __construct($value) {
		$this->value = $value;
	}
	function render() {
		return h()
		->td
			->t($this->value)
		->end;
	}
}


class ListTableCell implements Renderable {
	function __construct($value) {
		$this->value = $value;
	}
	function render() {
		return h()
		->td
			->ul->class('ui list')
				->addH(array_map(function($x) {
					return h()->li->t($x)->end;
				}, $this->value))
			->end
		->end;
	}
}

class LinkTableCell implements Renderable {
	function __construct($url, $value, $blank = false) {
		$this->url = $url;
		$this->value = $value;
		$this->blank = $blank;
	}
	function render() {
		return h()
		->td
			->a->href($this->url)->target('_blank', $this->blank)
				->t($this->value)
			->end
		->end;
	}
}


class PasswordTableCell implements Renderable {
	function render() {
		return h()
		->td
			->abbr->title('Passwords are not saved in the database')
				->t('N/A')
			->end
		->end;
	}
}


class FileUploadTableCell implements Renderable {
	function __construct($value) {
		$this->value = $value;
	}
	function render() {
		return h()
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
		$this->value = $value;
	}
	function render() {
		$v = $this->value;
		return h()
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

		$this->value = $value;
	}
	function render() {
		return h()
			->td
				->pre->t($this->value)->end
			->end;
	}
}


class CheckboxTableCell implements Renderable {
	function __construct($value) {
		$this->value = $value;

	}
	function render() {
		return h()
		->td->class($this->value ? 'positive' : 'negative')
			->t($this->value ? 'Yes' : 'No')
		->end;
	}

}

class ListEmailTableCell implements Renderable {
	function __construct($v, $value) {
		$this->value = $value;
		$this->v = $v;
	}
	function render() {
		return h()
		->td
			->addH(array_map(function($listitem) {
				return new EmailTable($this->value, $listitem, false);
			}, $this->v))
		->end;
	}
}

class ListDetailedTableCell implements Renderable {
	function __construct($v, $value) {
		$this->value = $value;
		$this->v = $v;
	}
	function render() {
		return h()
		->td
			->addH(array_map(function($listitem) {
				return new ValueTable($this->value, $listitem, false);
			}, $this->v))
		->end;
	}
}
