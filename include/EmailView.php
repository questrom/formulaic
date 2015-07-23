<?php

# The email view, used to display data within an HTML email.

class EmailView {

	function __construct($page) {
		$this->title = $page->title;
		$this->pageData = $page;
	}

	function makeEmailView() {
		return new EmailViewRenderable($this->title, $this->pageData, $this->data);
	}
}