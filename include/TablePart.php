<?php

# This file provides a number of Renderables used to create parts of tables.
# Such parts are used by TableView, DetailsView, and EmailView.

# The first section of the file deals with table cells; later, the file includes
# other parts of tables (rows, etc.)

# Just contains text
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

# A list of items (from a Checkboxes component, generally)
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

# A link, optionally with "target=_blank"
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

# Passwords aren't saved in the DB, so this basically just shows an error message
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

# In a TableView, just show a download button for a file upload
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

# In a DetailsView or EmailView, show more details about an upload
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

# Put a <pre> around data from a textarea so that newlines are preserved.
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


# Format a boolean value nicely
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

# In an EmailView or DetailsView, show each item in a list.
# Otherwise, only the # of items will be shown (see the code
# for ListComponent.)

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

# More complex parts of tables...

# Display a table row in an email
class EmailValueRow implements Renderable {
	function __construct($value, $component) {
		$this->value = $value;
		$this->component = $component;
	}
	function render() {
		$v = $this->component->makeEmailViewPart($this->value);
		if($v === null) {
			$v = h()
			->td->bgcolor('#ccc')
				->t('(No value)')
			->end;
		}
		return h()
		->tr
			->td->class('right aligned collapsing nowrap')
				->t($this->component->label)
			->end
			->addH($v)
		->end;
	}
}

# Display a table row in Details mode
class ValueRow implements Renderable {
	function __construct($value, $component) {
		$this->value = $value;
		$this->component = $component;
	}

	function render() {
		$v = $this->component->makeDetailsViewPart($this->value);
		if($v === null) {
			$v = h()
			->td->class('disabled')
				->i->class('ban icon')->end
			->end;
		}
		return h()
		->tr
			->td->class('right aligned collapsing nowrap')
				->t($this->component->label)
			->end
			->addH($v)
		->end;
	}
}

# Display IP/timestamp in an email
class EmailIPTimestampInfo implements Renderable {
	function __construct($data) {
		$this->data = $data;
	}
	function render() {
		return h()
		->tfoot
			->tr
				->td->colspan('2')->align('left')
					->strong->t('Timestamp:' . json_decode('"\u2002"'))->end
					->t($this->data['_timestamp']->format('Y/m/d g:i A'))
					->br->end
					->strong->t('IP:' . json_decode('"\u2002"'))->end
					->code->t($this->data['_ip'])->end
				->end
			->end
		->end;
	}
}

# Display IP/timestamp in Details mode
class IPTimestampInfo implements Renderable {
	function __construct($data) {
		$this->data = $data;
	}
	function render() {
		return h()
		->tfoot->class('full-width')
			->tr
				->th->colspan('2')
					->strong->t('Timestamp:' . json_decode('"\u2002"'))->end
					->t(isset($this->data['_timestamp']) ? $this->data['_timestamp']->format('Y/m/d g:i A') : null)
					->p
						->strong->t('IP:' . json_decode('"\u2002"'))->end
						->code->t( isget($this->data['_ip']) )->end
					->end
				->end
			->end
		->end;
	}
}


# A table for use in an email
class EmailTable implements Renderable {
	function __construct($fields, $data, $stamp = false) {
		$this->fields = $fields;
		$this->data = $data;
		$this->stamp = $stamp;
	}
	function render() {
		return h()
		->table->border(1)->width('100%')->style('max-width:800px;')
			->col->width('30%')->end
			->col->width('70%')->end
			->tbody
				->addH(array_map(function($field) {
					if($field instanceof EmailViewPartFactory) {
						return ( new TablePart( $field ) )->makeEmailViewPart(  isget($this->data[$field->name]) );
					} else {
						return null;
					}
				}, $this->fields ))
			->end
			->addH(!$this->stamp ? null : $this->stamp)
		->end;
	}
}

# Display a table in details mode
class ValueTable implements Renderable {
	function __construct($fields, $data, $stamp = false) {
		$this->fields = $fields;
		$this->data = $data;
		$this->stamp = $stamp;
	}
	function render() {
		return h()
		->table->class('ui unstackable definition table')
			->tbody
				->addH(array_map(function($field) {
					if($field instanceof DetailsViewPartFactory) {
						return ( new TablePart( $field ) )->makeDetailsViewPart(  isget($this->data[$field->name]) );
					} else {
						return null;
					}
				}, $this->fields ))
			->end
			->addH(!$this->stamp ? null : $this->stamp)
		->end;
	}
}

# Wrapper for displaying cells in TableView
class ValueCell implements Renderable {
	function __construct($value, $component) {
		$this->value = $value;
		$this->component = $component;
	}

	function render() {
		$v = $this->component->makeTableViewPart($this->value);
		if($v === null) {
			return h()
				->td->class('disabled')
					->i->class('ban icon')->end
				->end;
		} else {
			return $v->render();
		}
	}
}

# An HTML email used by the "email-to" output
class EmailViewRenderable implements Renderable {
	function __construct($title, $pageData, $data) {
		$this->title = $title;
		$this->pageData = $pageData;

		$this->data = $data;
	}
	function render() {
		return
		h()
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
					->addH(
						(new StampedTable($this->pageData->form->getAllFields()))->makeEmailViewPart($this->data)
					)
				->end
			->end
		->end;
	}
}

# A complete details view, displayed when the user presses the "Details" button in a table
class DetailsViewRenderable implements Renderable {
	function __construct($fields, $title, $data) {
		$this->fields = $fields;
		$this->title = $title;
		$this->data = $data;
	}
	function render() {
		return
		h()
		->html
			->head
				->meta->charset('utf-8')->end
				->title->t($this->title)->end
				->link->rel('stylesheet')->href(new AssetUrl('lib/semantic.css'))->end
				->link->rel('stylesheet')->href(new AssetUrl('styles.css'))->end
			->end
			->body
				->addH(new TopHeader())
				->div->class('ui container wide-page')
					->h1
						->t($this->title)
					->end
					->addH(
						(new StampedTable($this->fields))->makeDetailsViewPart($this->data)
					)
				->end
			->end
		->end;
	}
}

# A compelte table view

class TablePage implements Renderable {
	function __construct($f) {
		$this->f = $f;
		$this->byName = $this->f->pageData->form->getAllFields();
	}
	function render() {
		return
		h()
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
									return h()
										->col->style('width: ' . ($x->width * 100) . '%;')
									->end;
								}, $this->f->cols))
								->col->style('width:90px;')->end
							->end
							->thead
								->tr
									->addH(array_map(function($x)  {
										# Show how the table is sorted
										return h()
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
								return h()
								->tr
									->addH(array_map(function($col) use($row) {
										return ( new TablePart( $this->byName[$col->name] ) )->makeTableViewPart(  isget($row[$col->name]) );
									}, $this->f->cols))
									# After the columns, show the Details button
									->td->class('center aligned nowrap unpadded-cell')
										->a->class('ui no-margin compact button')->href('details?form=' . $this->f->formID . '&id=' . $row['_id'])
											->t('Details')
										->end
									->end
								->end;
							}, $this->f->data))
						->end
						->addH(!$this->f->perPage ? null :
							# Pagination menu
							h()
							->div->class('ui text menu')
								->div->class('item')
									->a->class('ui left floated primary labeled icon button '
											. ($this->f->page === 1 ? 'disabled' : ''))
										->href('view?form=' . $this->f->formID . '&view=' . $this->f->name . '&page=' . ($this->f->page - 1))
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
										->href('view?form=' . $this->f->formID . '&view=' . $this->f->name . '&page=' . ($this->f->page + 1))
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