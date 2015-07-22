<?php


# Display information about a particular view
class ViewInfoView implements Renderable {
	function __construct($data, $formData) {
		# $formData holds info about the form as a whole
		# $data holds info about the particular view
		$this->formData = $formData;
		$this->data = $data;
	}
	function render() {
		return h()
			->div->class('item')
				->a->href('view.php?form=' . $this->formData->id . '&view=' . $this->data->name)->class('item')
					->i->class($this->data->type === 'graph' ? 'area chart icon' : 'table icon')->end
					->t($this->data->title)
				->end
			->end;
	}
}

# Display information about a particular form
class FormItemView implements Renderable {
	function __construct($data) {
		$this->data = (object) $data;
	}
	function render() {
		return h()
			->div->class('item')
				->div->class('header')
					->a->href('form.php?form=' . $this->data->id)
						->t($this->data->name)
					->end
					->div->class('ui horizontal right floated label')
						->t($this->data->count)
						->t(json_decode('"\u2004"') . 'submissions')
					->end
				->end
					# Only show the list if the form actually HAS views
					->addH(count($this->data->views) === 0 ? null :
						h()
						->div->class('ui horizontal list low-line-height')
							->div->class('item header')->t('Views: ')->end
								->addH(
									array_map(function($viewInfo) {
										return new ViewInfoView($viewInfo, $this->data);
									}, $this->data->views)
								)
						->end
					)

			->end;
	}
}

# Display the main list of forms
class FormListView implements Renderable {
	function __construct($data) {
		$this->data = $data;
	}
	function render() {
		return h()
		->html
			->head
				->meta->charset('utf-8')->end
				->title->t('Forms')->end
				->link->rel('stylesheet')->href(new AssetUrl('lib/semantic.css'))->end
				->link->rel('stylesheet')->href(new AssetUrl('styles.css'))->end

				# From https://github.com/h5bp/html5-boilerplate/blob/master/src/index.html
				->meta->name('viewport')->content('width=device-width, initial-scale=1')->end

			->end
			->body
				->addH(new BrowserProblemPart(
					h()
					->addH(new TopHeader())
					->div->class('ui text container')
						->h1->class('ui center aligned header')
							->t('All Forms')
						->end
						->div->class('ui large relaxed divided list')
							->addH(
								array_map(function($formInfo) {
									return new FormItemView($formInfo);
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
# The data is assumed to come from Parser::getFormInfo()
class FormList {
	function __construct($data) {
		$this->data = $data;
	}
	function makeFormList() {
		return new FormListView($this->data);
	}
}