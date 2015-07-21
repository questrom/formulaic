<?php

use Sabre\Xml\XmlDeserializable;

class ViewList implements XmlDeserializable {
	use Configurable;
	private $views;
	function __construct($args) {
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

interface View {
	function query($args);
	function setPage($page);
	function makeView($data);
}

interface GraphViewPartFactory {
	function makeGraphViewPart($data);
}
