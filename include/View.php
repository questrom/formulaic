<?php

use Sabre\Xml\XmlDeserializable;

class ViewList implements XmlDeserializable {
	use Configurable;
	private $views;
	function __construct($args) {
		$this->views = $args['children'];
	}
	function getByName($name) {
		foreach($this->views as $view) {
			if($view->name === $name) {
				return $view;
			}
		}
		return null;
	}
}
