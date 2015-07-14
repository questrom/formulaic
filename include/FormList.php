<?php

class ViewInfoView implements Renderable {
	function __construct($data, $formData) {
		$this->h = new HTMLParentlessContext();
		$this->formData = $formData;
		$this->data = $data;
	}
	function render() {
		return $this->h
				->div->class('item')
				->a->href('view.php?form=' . $this->formData['id'] . '&view=' . $this->data['id'])->class('item')
					->i->class($this->data['type'] === 'graph' ? 'area chart icon' : 'table icon')->end
					->t($this->data['title'])
				->end
			->end;
	}
}

class FormItemView implements Renderable {
	function __construct($data) {
		$this->h = new HTMLParentlessContext();
		$this->data = $data;
	}
	function render() {
		return $this->h
			->div->class('item')
				->div->class('header')
					->a->href('form.php?form=' . $this->data['id'])
						->t($this->data['name'])
					->end
					->div->class('ui label right floated content')
						->t($this->data['count'])
						->t(' submissions')
					->end
				->end
				->div
					->div->class('ui horizontal list')
						->div->class('item header')->t('Views: ')->end

						->addH(
							array_map(function($viewInfo) {
								return new ViewInfoView($viewInfo, $this->data);
							}, $this->data['views'])
						)
					->end
				->end
			->end;
	}
}

class FormListView implements Renderable {
	function __construct($data) {
		$this->h = new HTMLParentlessContext();
		$this->data = $data;
	}
	function render() {
		return $this->h
		->html
			->head
				->meta->charset('utf-8')->end
				->title->t('Forms')->end
				->link->rel('stylesheet')->href(new AssetUrl('lib/semantic.css'))->end
				->link->rel('stylesheet')->href(new AssetUrl('styles.css'))->end

				// From https://github.com/h5bp/html5-boilerplate/blob/master/src/index.html
				->meta->name('viewport')->content('width=device-width, initial-scale=1')->end

			->end
			->body
				->addH(new BrowserProblemPart(
					$this->h
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

class FormList {
	function __construct($data) {
		$this->data = $data;
	}
	function makeFormList() {
		return new FormListView($this->data);
	}
}