<?php
class EmailValueRow implements HTMLComponent {
	function __construct($value, $component) {

		$this->value = $value;
		$this->component = $component;
	}
	function get($h) {


		if($this->component instanceof FieldListItem) {
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

class EmailView implements HTMLComponent {
	use Configurable;

	function __construct($page) {

		$this->title = $page->title;
		$this->pageData = $page;

	}
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
								if($field instanceof FieldListItem) {
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