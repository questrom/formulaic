<?php

# Represents the 'views' element in a configuration file.
# Allows views to be queried.
class ViewList implements Configurable {

	private $views;
	function __construct($args, $context) {
		$this->views = $args['children'];
	}
	function getAllViews() {
		return $this->views;
	}
	function getByName($name) {
		foreach ($this->views as $view) {
			if ($view->name === $name) {
				return $view;
			}
		}
		return null;
	}
}

# An interface implemented by every view.
interface View {
	function query($args);
	function setPage($page);
	function makeView($data);
}

# Implemented by all elements that can be parts of a graph view.
interface GraphViewPartFactory {

	# Makes part of a graph view. Should return a Renderable.
	function makeGraphViewPart($data);
}

interface ConfigurableView extends View, Configurable {
	public function getIcon();
}