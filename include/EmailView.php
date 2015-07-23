<?php


class EmailView {

	function __construct($page) {
		$this->title = $page->title;
		$this->pageData = $page;
	}

	function makeEmailView() {
		return new EmailViewRenderable($this->title, $this->pageData, $this->data);
	}
}