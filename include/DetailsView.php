<?php

# The details view, used to display the DB item with a particular ID.
# Just a simple implementation of the View interface.

# Note that there are NO restrictions on what ID can be entered,
# since there is no authentication for views in general.

class DetailsView implements View {
	private $pageData, $mongo;
	function makeView($data) {
		return new DetailsViewRenderable($this->pageData->form->getAllFields(), $this->pageData->title, $data);
	}

	function setPage($page) {
		/** @noinspection PhpUndefinedFieldInspection */
		$this->pageData = $page;
		$this->mongo = $page->getMongo();
	}

	function query($getData) {
		return $this->mongo->getById($getData['id']);
	}
}