<?php

# The details view, used to display the DB item with a particular ID.
# Just a simple implementation of the View interface.

# Note that there are NO restrictions on what ID can be entered,
# since there is no authentication for views in general.

class DetailsView implements View {
	function makeView($data) {
		return new DetailsViewRenderable($this->pageData->form->getAllFields(), $this->pageData->title, $data);
	}

	function setPage($page) {
		$this->pageData = $page;
	}

	function query($getData) {
		$page = $this->pageData;
		$mongo = null;
		foreach($page->outputs->outputs as $output) {
			if($output instanceof MongoOutput) {
				$mongo = $output;
			}
		}
		return $mongo->getById($getData['id']);
	}
}