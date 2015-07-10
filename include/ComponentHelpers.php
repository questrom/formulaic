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
