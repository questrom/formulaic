<?php


// Misc. Helpers
// =============

class ClientData {
	function __construct($post, $files) {
		$this->post = $post;
		$this->files = $files;
	}
}


class FileInfo {
	function __construct($file, $filename, $mime, $permissions) {
		$this->file = $file;
		$this->filename = $filename;
		$this->mime = $mime;
		$this->permissions = $permissions;
	}
}


// Helper functions
// ================

function fieldBox($h, $required) {
	return $h->div->class('field ' . ($required ? ' required' : ''));
}


function midpoint($a, $b) {
	return $a + (($b - $a) / 2);
}