<?php

use voku\helper\UTF8;

# Display information about a particular view
class ViewInfoView implements Renderable {
	private $formData, $v;
	function __construct($view, $formData) {
		# $formData holds info about the form as a whole
		# $view holds info about the particular view
		$this->formData = $formData;
		$this->v = $view;
	}
	function render() {
		# See http://php.net/manual/en/function.urlencode.php#111410
		# for the rationale behind escaping the view name as we do.
		# We don't need to escape form ID's since Parser excludes
		# any names that would cause issues in URLs anyway.
		return h()
			->div->class('item')->c(
				h()->a->href('view?form=' . $this->formData->id . '&view=' . urlencode($this->v->name))->class('item')
					->i->class($this->v->getIcon())->end
					->c($this->v->title)
				->end
			);
	}
}

# Display information about a particular form
class FormItemView implements Renderable {
	private $data;
	function __construct($data) {
		$this->data = (object) $data;
	}
	function render() {
		return h()
			->div->class('item')
				->div->class('header')
					->a->href('forms/' . $this->data->id)
						->c($this->data->name)
					->end
					->div->class('ui horizontal right floated label')
						->c($this->data->count)
						->c(UTF8::chr(0x2004) . 'submissions')
					->end
				->end
					# Only show the list if the form actually HAS views
					->c(count($this->data->views) === 0 ? null :
						h()
						->div->class('ui horizontal list low-line-height')
							->div->class('item header')->c('Views: ')->end
								->c(
									array_map(function($view) {
										return new ViewInfoView($view, $this->data);
									}, $this->data->views)
								)
						->end
					)

			->end;
	}
}

# Display a parse error
class ParseErrorView implements Renderable {
	private $data;
	function __construct($data) {
		$this->data = (object) $data;
	}
	function render() {
		return h()
			->div->class('item')
				->div->class('header')
					->a->href('forms/' . $this->data->id)
						->c($this->data->id)
					->end
				->end
				->h4->class('ui red header')
					->c('Error parsing configuration file.')
				->end
			->end;
	}
}

# Display the main list of forms
class FormListView implements Renderable {
	private $data;
	function __construct($data) {
		$this->data = $data;
	}
	function render() {
		return h()
		->html
			->head
				->meta->charset('utf-8')->end
				->title->c('Forms')->end
				->link->rel('stylesheet')->href(new AssetUrl('lib/semantic.css'))->end
				->link->rel('stylesheet')->href(new AssetUrl('styles.css'))->end

				# From https://github.com/h5bp/html5-boilerplate/blob/master/src/index.html
				->meta->name('viewport')->content('width=device-width, initial-scale=1')->end

			->end
			->body
				->c(new BrowserProblemPart(
					h()
					->c(new TopHeader(true))
					->div->class('ui text container')
						->h1->class('ui center aligned header')
							->c('All Forms')
						->end
						->div->class('ui large relaxed divided list segment')->style('padding: 0.5rem 1rem')
							->c(
								array_map(function($formInfo) {
									if(!$formInfo['parse_error']) {
										return new FormItemView($formInfo);
									}
									return new ParseErrorView($formInfo);

								}, $this->data)
							)
						->end
					->end
				))
			->end
		->end;
	}
}

# A factory for a FormListView
# The data is assumed to come from Parser->getFormInfo()
class FormList {
	private $data;
	function __construct($data) {
		$this->data = $data;
	}
	function makeFormList() {
		return new FormListView($this->data);
	}
}